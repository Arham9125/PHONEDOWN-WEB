<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Child;
use App\Models\Guardian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ChildrenApiController extends BaseController
{
    public function login(Request $request)
    {
        $customAttributes = [
            "email" => "Email Address",
            "password" => "Password",
        ];

        $messages = [
            "required" => "Enter your :attribute",
            "regex" => ":attribute must contain one uppercase, one lowercase, one digit, and one special character",
        ];

        $rules = [
            "email" => ["required","email"],
            "password" => ["required","regex:/^.*(?=.{3,})(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@$#%]).*$/","min:8"]
        ];

        $validate = Validator::make($request->all(),$rules,$messages,$customAttributes);

        if($validate->fails())
        {
            return $this->sendError("Validation Error",$validate->errors());
        }

        $user = Child::where('email',$request->email);

        if($user->doesntExist())
        {
            return $this->sendError("Error in authentication",['email' => ["Invalid Email Address or Password"]]);
        }

        $user = $user->first();

        if($user->status != 1)
        {
            return $this->sendError("Error in authentication",['email' => ["Child is temporarily deactivated! Please contact parent."]]);
        }

        if(!Hash::check($request->password,$user->password))
        {
            return $this->sendError("Error in authentication",['password' => ["Invalid Email Address or Password"]]);
        }

        $user->api_token = $this->generate_api_token();
        $user->save();

        return $this->sendResponse("User Login Successfully",$user);
    }

    public function get_family_members(Request $request)
    {
        $children = Child::where('guardian_id',$request->auth->guardian_id)
        ->where('id','!=',$request->auth->id)
        ->where('status',1)
        ->with(['relation'])
        ->select(['id','name','email','profile_pic','status','relationship_id',DB::raw("'0' as is_parent")]);

        $members = Guardian::where('guardian_id',$request->auth->guardian_id)
        ->with(['relation'])
        ->select(['id','name','email','profile_pic','status','relationship_id',DB::raw("'1' as is_parent")])
        ->union($children)
        ->get();

        return $this->sendResponse("Family Members",$members);
    }

    public function update_profile(Request $request)
    {
        $customAttributes = [
            "full_name" => "Full Name",
            "email" => "Email Address",
            "password" => "Password",
            "profile_pic" => "Profile Picture",
            "bio" => "Bio",
            "address" => "Address",
        ];

        $messages = [
            "unique" => ":attribute already exists",
            "required" => "Enter your :attribute",
            "mimes" => ":attribute must be a valid image with following extensions: :values",
            "regex" => ":attribute must contain one uppercase, one lowercase, one digit, and one special character",
        ];

        $rules = [
            "full_name" => ["required"],
            "email" => ["required","email",Rule::unique('children')->ignore($request->auth->id)],
            "password" => ["required","regex:/^.*(?=.{3,})(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@$#%]).*$/","min:8"],
            "profile_pic" => ["nullable","mimes:jpg,jpeg,png"],
            "bio" => ["required"],
            "address" => ["required"],
        ];

        $validate = Validator::make($request->all(),$rules,$messages,$customAttributes);

        if($validate->fails())
        {
            return $this->sendError("Validation Error",$validate->errors());
        }

        $user = Child::where('id',$request->auth->id);

        if($user->doesntExist())
        {
            return $this->sendError("Error in authentication",['email' => ["Invalid User"]]);
        }

        $user = $user->first();

        $user->name = $request->full_name;
        $user->email = $request->email;
        $user->password = Hash::make('password');
        $user->bio = $request->bio;
        $user->address = $request->address;

        if($request->hasFile('profile_pic'))
        {
            $file = $request->file('profile_pic');
            $file_directory = "public/profile_pics";
            $file_name = time() . '.' . $file->getClientOriginalExtension();
            $file_path = $file_directory."/".$file_name;

            Storage::putFileAs($file_directory,$file,$file_name);

            if($user->profile_pics != null)
            {
                if(Storage::exists($user->profile_pics))
                {
                    Storage::delete($user->profile_pics);
                }
            }

            $user->profile_pic = $file_path;
        }

        $user->update();

        $user = $user::with(['relation'])->where('id',$user->id)->first();  

        return $this->sendResponse("User Updated Successfully",$user);
    }
}
