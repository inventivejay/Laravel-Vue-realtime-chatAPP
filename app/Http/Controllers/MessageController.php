<?php

namespace App\Http\Controllers;

use App\Message;
use Illuminate\Http\Request;
use App\User;
use App\Events\MessageSend;

class MessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function user_list(){
        $users = User::latest()->where('id','!=',auth()->user()->id)->get();
        $unreadIds = Message::select(\DB::raw('`from` as sender_id , count(`from`) as messages_count'))
            ->where('to', auth()->id())
            ->where('read', false)
            ->where('type', 1)
            ->groupBy('from')
            ->get();
        $users = $users->map(function ($user) use ($unreadIds) {
            $userUnread = $unreadIds->where('sender_id', $user->id)->first();
            $user->unread = $userUnread ? $userUnread->messages_count : 0;
            return $user;
        });

        if(\Request::ajax()){
            return response()->json($users,200);
        }
        return abort(404);
    }
    public function current_user()
    {
        $user = User::latest()->where('id','=',auth()->user()->id)->get();

        if(\Request::ajax()){
            return response()->json($user,200);
        }
        return abort(404);
    }
    public function user_message($id=null){
        if(!\Request::ajax()){
           return abort(404);
        }
        $user = User::findOrFail($id);
       $messages = $this-> message_by_user_id($id);
        return response()->json([
            'messages'=>$messages,
            'user'=>$user
        ]);
    }

    public function update_unread($id)
    {
        $messages = Message::where('from', $id)
                    ->update(['read' => '1']);

    if(\Request::ajax()){
        return response()->json($messages,200);
    }
    return abort(404);
    }

    public function send_message(Request $request){
        if(!$request->ajax()){
            abort(404);
        }
       $messages = Message::create([
           'message'=>$request->message,
           'from'=>auth()->user()->id,
           'to'=>$request->user_id,
           'type'=>0
       ]);
       $messages = Message::create([
        'message'=>$request->message,
        'from'=>auth()->user()->id,
        'to'=>$request->user_id,
        'type'=>1
    ]);
       broadcast(new MessageSend($messages));
       return response()->json($messages,201);


    }
    public function delete_single_message($id=null){
        if(!\Request::ajax()){
          return  abort(404);
        }
        Message::findOrFail($id)->delete();
        return response()->json('deleted',200);
    }
    public function delete_all_message($id=null){
      $messages =  $this->message_by_user_id($id);
        foreach ($messages as $value) {
          Message::findOrFail($value->id)->delete();
        }
        return response()->json('all deleted',200);
    }
    public function message_by_user_id($id){
        $messages = Message::where(function($q) use($id){
            $q->where('from',auth()->user()->id);
            $q->where('to',$id);
            $q->where('type',0);
        })->orWhere(function($q) use ($id){
            $q->where('from',$id);
            $q->where('to',auth()->user()->id);
            $q->where('type',1);
        })->with('user')->get();
        return $messages;
    }
}
