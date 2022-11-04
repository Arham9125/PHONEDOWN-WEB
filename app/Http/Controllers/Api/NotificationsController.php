<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\SendEmail;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NotificationsController extends Controller
{
    public static function send(String $slug,$data = [])
    {
        $notification = Notification::where('slug',$slug)->where('status',1);
        if($notification->exists())
        {
            $notification = $notification->first();
            if($notification->type == 1)
            {
                $subject = $notification->subject;
            }
            $body = $notification->body;

            if($slug == "member_invite_request")
            {
                $user = $data['user'];
                $link = route("verify_member",[$data['token'],$data['relationship_token']]);

                if(Str::contains($body, "[link]"))
                {
                    $body = Str::replace("[link]",$link,$body);
                }

                if(Str::contains($body, "[name]"))
                {
                    $body = Str::replace("[name]",$user->name,$body);
                }

                if(Str::contains($body, "[email]"))
                {
                    $body = Str::replace("[email]",$user->email,$body);
                }

                if(Str::contains($body, "[password]"))
                {
                    $body = Str::replace("[password]",$data['password'],$body);
                }

                Mail::to($user->email)->send(new SendEmail($subject,$body));
            }
        }
    }
}
