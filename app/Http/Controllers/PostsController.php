<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StorePost;

use Illuminate\Support\Facades\DB;
use App\Models\Thread;
use App\Models\Post;
use App\Events\NewPost;
use Carbon\Carbon;
use Auth;
use App\Models\Chapter;
use App\Models\Activity;
use App\Models\Collection;
use App\Helpers\ThreadObjects;


class PostsController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth')->except('show');
    }

    public function store(StorePost $form, Thread $thread)
    {
        if ((Auth::user()->isAdmin())||((!$thread->locked)&&(($thread->public)||($thread->user_id==Auth::id())))){
            $post = $form->generatePost($thread);
            $post->checklongcomment();
            event(new NewPost($post));
            if($post->parent&&$post->parent->type==="chapter"&&$post->parent->reply_count){
                $post->user->reward("first_post");
                return back()->with('success', '您得到了新章节率先回帖的特殊奖励');
            }else{
                $post->user->reward("regular_post");
            }
            return back()->with('success', '您已成功回帖');
        }else{
            return back()->with('danger', '抱歉，本主题锁定或设为隐私，不能回帖');
        }
    }
    public function edit(Post $post)
    {
        $thread=$post->thread;
        $channel=$thread->channel();
        if((Auth::user()->isAdmin())||((Auth::id() === $post->user_id)&&(!$thread->locked)&&($thread->channel()->allow_edit))){
            return view('posts.post_edit', compact('post'));
        }else{
            return redirect()->route('error', ['error_code' => '403']);
        }
    }

    public function update(StorePost $form, Post $post)
    {
        $thread=$post->thread;
        if ((Auth::user()->isAdmin())||((Auth::id() == $post->user_id)&&(!$thread->locked)&&($thread->channel()->allow_edit))){
            $form->updatePost($post);
            return redirect()->route('thread.showpost', $post->id)->with('success', '您已成功修改帖子');
        }else{
            return redirect()->route('error', ['error_code' => '403']);
        }
    }
    public function show($id)
    {
        $post = ThreadObjects::postProfile($id);
        if(!$post){
            abort(404);
        }
        $thread = ThreadObjects::thread($post->thread_id);
        return view('posts.show',compact('post','thread'));
    }

    public function destroy($id){
        $post = Post::findOrFail($id);
        $thread=$post->thread;
        if((!$thread->locked)&&(Auth::id()==$post->user_id)){
            if(($post->maintext)&&($post->chapter_id !=0)){
                $chapter = $post->chapter;
                if($chapter->post_id == $post->id){
                    $chapter->delete();
                }
            }
            $post->delete();
            return redirect()->route('home')->with("success","已经删帖");
        }else{
            return redirect()->route('error', ['error_code' => '403']);
        }
    }
}
