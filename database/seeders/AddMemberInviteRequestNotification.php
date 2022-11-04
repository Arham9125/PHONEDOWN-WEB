<?php

namespace Database\Seeders;

use App\Models\Notification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddMemberInviteRequestNotification extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Notification::where('slug','member_invite_request')->delete();

        Notification::insert([
            array('slug'=>'member_invite_request','subject'=>'Invite Request','body'=>'Dear [name],<br><br>You are requested to join PhoneDown App by verifying your email with following <a href="[link]">link</a>.<br><br> You can login your account using following credentials: <br><br><b>Email:</b> [email] <br><b>Password:</b> [password]','description'=>'Notification for member invite request','type'=>1,'variables'=>'[name],[link],[email],[password]','status'=>1),
        ]);
    }
}
