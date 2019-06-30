@extends('layouts.default')
@section('title', '考试试题')
@section('content')
<div class="container-fluid">
    <div class="col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3>废文使用测试题</h3>
                <h4>{{ $user->name }} 您好！欢迎您参与废文使用测试！在这里您将彻底学习如何做条好鱼。每位咸鱼初次答对全部题目时，还会获得升级必备的分值奖励，还等什么呢，快来尝试一下吧！</h4>
                <h6>（请注意，题目不能留空哦！）</h6>
            </div>
            <div class="panel-body">
                @include('shared.errors')
                <form method="POST" action="{{ route('quiz.submittest') }}">
                    {{ csrf_field() }}

                    @foreach ($quizzes as $quiz_key=> $quiz)
                    <div class="h4">
                        <span><strong>第{{ $quiz_key+1 }}题：</strong></span>
                        <input type="text" name="quiz-answer[{{ $quiz->id }}][quiz_id]" class="hidden form-control" value="{{ $quiz->id }} ">
                    </div>
                    <div class="h4">
                        {!! Helper::wrapSpan($quiz->body) !!}
                    </div>
                    @if($quiz->hint)
                    <div class="text-center">
                        <a type="button" data-toggle="collapse" data-target="#quiz-hint{{ $quiz->id }}" style="cursor: pointer;" class="h6">点击查看答题提示</a>
                    </div>
                    <div class="collapse grayout h6" id = "quiz-hint{{ $quiz->id }}">
                        {!! Helper::wrapSpan($quiz->hint) !!}
                    </div>
                    @endif
                    <!-- 各色选项 -->
                    <div class="">
                        @foreach($quiz->random_options as $option_key=>$quiz_option)
                        <!-- 选项本体 -->
                        <div class="">
                            <label><input type="checkbox" name="quiz-answer[{{ $quiz->id }}][{{ $quiz_option->id }}]"><span>选项{{ $option_key+1 }}：</span><span>{!! Helper::wrapSpan($quiz_option->body) !!}</span></label>
                        </div>
                        @endforeach
                    </div>
                    <hr>
                    @endforeach
                    <button type="submit" class="btn btn-danger sosad-button">提交</button>
                </form>
            </div>
            <br>
            <h6 class="grayout">(答题功能刚制作完成，题库还在填充中，目前只提供基础答题，更多题目和其他分值奖励，请等待新系统出现)</h6>
        </div>
    </div>
</div>
@stop