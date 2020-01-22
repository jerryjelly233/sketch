<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessage;
use App\Models\User;
use App\Models\Message;
use App\Models\PublicNotice;
use App\Http\Resources\MessageResource;
use App\Http\Resources\PaginateResource;
use App\Http\Resources\PublicNoticeResource;

class MessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function store(StoreMessage $form)
    {
        $message = $form->userSend();
        if(!$message){abort(495);}
        $message->load('message_body','poster','receiver');
        return response()->success([
            'message' => new MessageResource($message),
        ]);
    }

    public function groupmessage(StoreMessage $form)
    {
        $messages = $form->adminSend();
        if(!$messages){abort(495);}
        $messages->load('message_body','poster','receiver')->except('seen');
        return response()->success([
            'messages' => MessageResource::collection($messages),
        ]);
    }

    public function publicnotice(StoreMessage $form)
    {
        $public_notice = $form->generatePublicNotice();
        if(!$public_notice){abort(495);}
        $public_notice->load('author');
        return response()->success([
            'public_notice' => new PublicNoticeResource($public_notice),
        ]);
    }

    public function index($id, Request $request)
    {
        $user = User::find($id);
        if(!$user){abort(404);}
        
        if(auth('api')->id()!=$user->id&&!auth('api')->user()->isAdmin()){abort(403);}
        //访问的信箱需为登录用户的信箱或登录用户为管理员

        $chatWith = $request->chatWith ?? 0;
        $query = Message::with('poster.title', 'receiver.title', 'message_body');

        switch ($request->withStyle) {
            case 'sendbox': $query = $query->withPoster($user->id);
            break;
            case 'dialogue': $query = $query->withDialogue($user->id, $chatWith);
            break;
            default: $query = $query->withReceiver($user->id)->withRead($request->read);
            break;
        }
        $messages = $query->ordered($request->ordered)
        ->paginate(config('constants.messages_per_page'));
        if((request()->withStyle==='sendbox'
            || request()->withStyle==='dialogue')
            && (!auth('api')->user()->isAdmin())){
            $messages->except('seen');
        }
        return response()->success([
            'style' => $request->withStyle,
            'messages' => MessageResource::collection($messages),
            'paginate' => new PaginateResource($messages),
        ]);

    }
}