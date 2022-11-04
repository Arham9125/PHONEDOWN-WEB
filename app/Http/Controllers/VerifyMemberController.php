<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\Guardian;
use App\Models\Relationship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class VerifyMemberController extends Controller
{
    public function verify_member($token,$relation,Request $request)
    {
        $member_id = Crypt::decryptString($token);
        $relationship_id = Crypt::decryptString($relation);

        if(Relationship::where('id',$relationship_id)->exists())
        {
            if($relationship_id == 1)
            {
                return "Error Invalid Request";
            }
            else if(in_array($relationship_id,[2,3]))
            {
                $member = Guardian::where('id',$member_id);
            }
            else{
                $member = Child::where('id',$member_id);
            }

            if($member->exists())
            {
                $member = $member->first();

                if($member->status == 1)
                {
                    return "Email Already Verified";
                }
                else{
                    $member->status = 1;
                    $member->save();

                    return "Email Verified Successfully";
                }
            }
            else{
                return "Error Invalid Request";
            }
        }
        else{
            return "Error Invalid Request";
        }

    }
}
