@foreach($collection_lists as $collection_list)
<article class="{{ 'item4id'.$collection_list->collection_list_id }}">
    <div class="row thread">
        <div class="thread-info">
            <!-- list title -->
            <span>
                <a class="btn-xs sosad-button-tag" >{{ config('constants.collection_type_info')[$collection_list->type] }}</a>
                <span class=""><strong><a href="{{ route('collections.collection_list_show', $collection_list->collection_list_id) }}">
                    {{ $collection_list->title }}
                </a></strong></span>
                @if(($show_as_collections)&&($collection_list->updated))
                <span class="badge newchapter-badge badge-tag">有更新</span>
                @endif
                <small>
                    @if($collection_list->private)
                    <span class="glyphicon glyphicon-eye-close"></span>
                    @endif
                </small>
            </span>
            <!-- list title end   -->
            <span class="smaller-10">
                {{ $collection_list->brief }}
            </span>
            <span class="smaller-10 grayout brief">
                {!! Helper::trimtext($collection_list->body,60) !!}
            </span>
            @if(($collection_list->type<=2)&&($collection_list->last_thread_title))
                <span><a href="{{ route('thread.show', $collection_list->last_item_id) }}" class="">最新收藏：《{{ $collection_list->last_thread_title }}》</a></span>
            @endif
        </div>
        <div class="thread-meta">
            <span class="">
                <!-- 创建时间/更新时间 -->
                <span class="grayout smaller-10">{{ Carbon\Carbon::parse($collection_list->created_at)->diffForHumans() }}/{{ Carbon\Carbon::parse($collection_list->lastupdated_at)->diffForHumans() }}
                </span>

            </span>
            <span class=" grayout smaller-10 loose">
                <!-- 查看/收藏/含有帖子数目 -->
                <em><span class="glyphicon glyphicon-eye-open"></span>{{ $collection_list->viewed }}/<span class="glyphicon glyphicon glyphicon-heart"></span>{{ $collection_list->collected }}/<span class="glyphicon glyphicon-duplicate"></span>{{ $collection_list->item_number }}</em>
            </span>
            <!-- author  -->
            <span class = "">
                @if ($collection_list->anonymous)
                <span>{{ $collection_list->majia ?? '匿名咸鱼'}}</span>
                @if((Auth::check()&&(Auth::user()->admin)))
                <span class="admin-anonymous"><a href="{{ route('user.show', $collection_list->user_id) }}">{{ $collection_list->name }}</a></span>
                @endif
                @else
                <a href="{{ route('user.show', $collection_list->user_id) }}">{{ $collection_list->name }}</a>
                @endif
            </span>
            <!-- author end -->
        </div>
        <div class="thread-cancel">
            @if($show_as_collections)
            <button class="btn-xs sosad-button-tag hidden cancel-button" type="button" name="button" onClick="cancelCollectionItem({{ $collection_list->collection_list_id }},4,{{ $collected_list->id }})">取消收藏</button>
            @endif
        </div>
    </div>
</article>
@endforeach
