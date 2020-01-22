<?php
namespace App\Sosadfun\Traits;

use DB;
use Cache;
use ConstantObjects;

trait ThreadObjectTraits{
    use FindThreadTrait;

    public function threadProfile($id)
    {
        return Cache::remember('threadProfile.'.$id, 10, function () use($id){
            $thread = $this->findThread($id);
            if(!$thread){return;}
            $thread->load('last_post', 'last_component');
            if($thread->channel()->type==="list"&&$thread->last_component_id>0&&$thread->last_component){
                $thread->last_component->load('simpleInfo.reviewee');
            }
            if($thread->channel()->type==="homework"){
                $thread->load('homework_registration.homework');
            }
            if($thread->channel()->id===2){
                $tongren = \App\Models\Tongren::find($thread->id);
                $thread->setAttribute('tongren', $tongren);
            }
            if(in_array($thread->channel()->type,['book','list','box'])){
                $thread->setAttribute('component_index', $thread->component_index());
            }
            $thread->setAttribute('random_review', $thread->random_editor_recommendation());
            $thread->setAttribute('recent_rewards', $thread->latest_rewards());
            return $thread;
        });
    }

    public function threadProfilePosts($id)
    {
        return Cache::remember('threadProfilePosts.'.$id, 60, function () use($id){
            return \App\Models\Post::with('author.title','last_reply')
            ->withType('post')
            ->where('thread_id','=',$id)
            ->where('fold_state','=',0)
            ->ordered('most_upvoted')
            ->take(5)
            ->get();
        });
    }

    public function clearThread($id)
    {
        Cache::forget('thread.'.$id);
        Cache::forget('threadProfile.'.$id);
    }

    public function decide_thread_show_config($request)
    {
        $show_profile = true;
        
        $page = (int)(is_numeric($request->page)? $request->page:'1');
        if($page>1||$request->withType||$request->userOnly||$request->withFolded||$request->withReplyTo||$request->ordered||$request->withComponent){
            $show_profile = false;
        }

        return [
            'show_profile' => $show_profile,
        ];
    }

}