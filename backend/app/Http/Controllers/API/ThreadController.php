<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Thread;
use App\Models\Post;
use App\Http\Requests\StoreThread;
use App\Http\Requests\UpdateThread;
use App\Http\Resources\ThreadBriefResource;
use App\Http\Resources\ThreadInfoResource;
use App\Http\Resources\ThreadProfileResource;
use App\Http\Resources\PostIndexResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\PaginateResource;
use App\Sosadfun\Traits\ThreadQueryTraits;
use App\Sosadfun\Traits\ThreadObjectTraits;
use App\Sosadfun\Traits\PostObjectTraits;
use App\Sosadfun\Traits\RecordRedirectTrait;
use App\Sosadfun\Traits\DelayRecordHistoryTraits;
use Cache;
use Carbon;
use ConstantObjects;

class ThreadController extends Controller
{
    use ThreadQueryTraits;
    use ThreadObjectTraits;
    use PostObjectTraits;
    use RecordRedirectTrait;
    use DelayRecordHistoryTraits;

    public function __construct()
    {
        $this->middleware('auth:api')->except(['index', 'show','channel_index']);
        $this->middleware('filter_thread')->only('show');
    }
    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function index(Request $request)
    {
        $request_data = $this->sanitize_thread_request_data($request);

        if($request_data&&!auth('api')->check()){abort(401);}

        $query_id = $this->process_thread_query_id($request_data);

        $threads = $this->find_threads_with_query($query_id, $request_data);

        return response()->success([
            'threads' => ThreadInfoResource::collection($threads),
            'paginate' => new PaginateResource($threads),
            'request_data' => $request_data,
        ]);
    }

    public function channel_index($channel, Request $request)
    {
        if(!auth('api')->check()&&$request->page){abort(401);}

        $channel = collect(config('channel'))->keyby('id')->get($channel);

        if($channel->id===config('constants.list_channel_id')&&$request->channel_mode==='review'){
            $request_data = $this->sanitize_review_posts_request_data($request);
            $query_id = $this->process_review_posts_query_id($request_data);
            $posts = $this->find_review_posts_with_query($query_id, $request_data);
            return response()->success([
                'posts' => PostIndexResource::collection($posts),
                'paginate' => new PaginateResource($posts),
                'request_data' => $request_data,
            ]);
        }

        $primary_tags = ConstantObjects::extra_primary_tags_in_channel($channel->id);

        $queryid = 'channel-index'
        .'-ch'.$channel->id
        .'-withBianyuan'.$request->withBianyuan
        .'-withTag'.$request->withTag
        .'-ordered'.$request->ordered
        .(is_numeric($request->page)? 'P'.$request->page:'P1');

        $time = 30;
        if(!$request->withTag&&!$request->ordered&&!$request->page){$time=2;}

        $threads = Cache::remember($queryid, $time, function () use($request, $channel) {
            return $threads = Thread::with('author', 'tags', 'last_post')
            ->isPublic()
            ->inChannel($channel->id)
            ->withBianyuan($request->withBianyuan)
            ->withTag($request->withTag)
            ->ordered($request->ordered)
            ->paginate(config('preference.threads_per_page'))
            ->appends($request->only('withBianyuan', 'ordered', 'withTag','page'));
        });

        $simplethreads = $this->find_top_threads_in_channel($channel->id);

        return response()->success([
            'channel' => $channel,
            'threads' => ThreadInfoResource::collection($threads),
            'primary_tags' => $primary_tags,
            'request_data' => $request->only('withBianyuan', 'ordered', 'withTag','page'),
            'simplethreads' => ThreadBriefResource::collection($simplethreads),
            'paginate' => new PaginateResource($threads),
        ]);

    }
    /**
    * Store a newly created resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @return \Illuminate\Http\Response
    */
    public function store(StoreThread $form)//
    {
        $channel = $form->channel();
        if(empty($channel)||((!$channel->is_public)&&(!auth('api')->user()->canSeeChannel($channel->id)))){abort(403);}

        //针对创建清单进行一个数值的限制
        if($channel->type==='list'){
            $list_count = Thread::where('user_id', auth('api')->id())->withType('list')->count();
            if($list_count > auth('api')->user()->user_level){abort(403);}
        }
        if($channel->type==='box'){
            $box_count = Thread::where('user_id', auth('api')->id())->withType('box')->count();
            if($box_count >=1){abort(403);}//暂时每个人只能建立一个问题箱
        }
        $thread = $form->generateThread();
        return response()->success(new ThreadProfileResource($thread));
    }

    /**
    * Display the specified resource.
    *
    * @param  int  $thread
    * @return \Illuminate\Http\Response
    */
    public function show($id, Request $request)
    {
        $show_config = $this->decide_thread_show_config($request);
        if($show_config['show_profile']){
            $thread = $this->threadProfile($id);
        }else{
            $thread = $this->findThread($id);
        }
        $thread->delay_count('view_count', 1);
        if(auth('api')->check()){
            $this->delay_record_thread_view_history(auth('api')->id(), $thread->id, Carbon::now());
        }

        $request_data = $this->sanitize_thread_post_request_data($request);

        if($request_data&&!auth('api')->check()){abort(401);}

        $query_id = $this->process_thread_post_query_id($request_data);

        $posts = $this->find_thread_posts_with_query($thread, $query_id, $request_data);

        $withReplyTo = '';
        if($request->withReplyTo>0){
            $withReplyTo = $this->findPost($request->withReplyTo);
            if($withReplyTo&&$withReplyTo->thread_id!=$thread->id){
                $withReplyTo = '';
            }
        }
        $inComponent = '';
        if($request->inComponent>0){
            $inComponent = $this->findPost($request->inComponent);
            if($inComponent&&$inComponent->thread_id!=$thread->id){
                $inComponent = '';
            }
        }

        return response()->success([
            'thread' => new ThreadProfileResource($thread),
            'withReplyTo' => $withReplyTo,
            'inComponent' => $inComponent,
            'posts' => PostResource::collection($posts),
            'request_data' => $request_data,
            'paginate' => new PaginateResource($posts),
        ]);

    }

    public function show_profile($id, Request $request)
    {
        if($request->review_redirect){
            $this->recordRedirectReviewCount($request->review_redirect);
        }
        $thread = $this->threadProfile($id);
        $posts = $this->threadProfilePosts($id);
        $thread->delay_count('view_count', 1);
        if(auth('api')->check()){
            $this->delay_record_thread_view_history(auth('api')->id(), $thread->id, Carbon::now());
        }
        return response()->success([
            'thread' => new ThreadProfileResource($thread),
            'posts' => PostResource::collection($posts),
        ]);
    }

    /**
    * Update the specified resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function update($id, StoreThread $form) //TODO
    {
        $thread = Thread::on('mysql::write')->find($id);
        $thread = $form->updateThread($thread);
        return response()->success(new ThreadProfileResource($thread));
    }


    /**
    * Remove the specified resource from storage.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function destroy($id)
    {
        if(!auth('api')->user()->isAdmin()&&(auth('api')->id()!=$thread->user_id||!$thread->channel()->allow_deletion||$thread->is_locked)){abort(403);}

        $thread->apply_to_delete();

        $this->clearThread($id);
        $thread = $this->threadProfile($id);

        return response()->success([
            'thread' => new ThreadProfileResource($thread),
        ]);
    }

    public function update_tag($id, Request $request)
    {
        $thread = Thread::on('mysql::write')->find($id);
        $user = CacheUser::Auser();
        if(!$thread||$thread->user_id!=$user->id||($thread->is_locked&&!$user->isAdmin())){abort(403);}

        $thread->drop_none_tongren_tags();//去掉所有本次能选的tag的范畴内的tag
        $thread->tags()->syncWithoutDetaching($thread->tags_validate($request->tags));//并入新tag. tags应该输入一个array of tags，前端进行一定的过滤

        $this->clearThread($id);
        $thread = $this->threadProfile($id);

        return response()->success([
            'thread' => new ThreadProfileResource($thread),
        ]);
    }
}