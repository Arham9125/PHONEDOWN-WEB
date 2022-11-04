<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Child;
use App\Models\Guardian;
use App\Models\Relationship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ParentApiController extends BaseController
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

        $user = Guardian::where('email',$request->email);

        if($user->doesntExist())
        {
            return $this->sendError("Error in authentication",['email' => ["Invalid Email Address or Password"]]);
        }

        $user = $user->with(['relation'])->first();

        if($user->status != 1)
        {
            return $this->sendError("Error in authentication",['email' => ["Parent is temporarily deactivated! Please contact admin."]]);
        }

        if(!Hash::check($request->password,$user->password))
        {
            return $this->sendError("Error in authentication",['password' => ["Invalid Email Address or Password"]]);
        }

        $user->api_token = $this->generate_api_token();
        $user->save();

        return $this->sendResponse("User Login Successfully",$user);
    }

    public function register(Request $request)
    {
        $customAttributes = [
            "full_name" => "Full Name",
            "email" => "Email Address",
            "password" => "Password",
            "members" => "Family Members",
            "is_guardian" => "Are You A Guardian",
        ];

        $messages = [
            "unique" => ":attribute already exists",
            "integer" => ":attribute must be a number",
            "required" => "Enter your :attribute",
            "regex" => ":attribute must contain one uppercase, one lowercase, one digit, and one special character",
        ];

        $rules = [
            "full_name" => ["required"],
            "email" => ["required","email","unique:guardians,email"],
            "password" => ["required","regex:/^.*(?=.{3,})(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@$#%]).*$/","min:8"],
            "members" => ["required","integer"],
            "is_guardian" => ["required","in:0,1"],
        ];

        $validate = Validator::make($request->all(),$rules,$messages,$customAttributes);

        if($validate->fails())
        {
            return $this->sendError("Validation Error",$validate->errors());
        }

        $user = new Guardian();
        $user->name = $request->full_name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->members = $request->members;
        $user->is_guardian = $request->is_guardian;
        $user->relationship_id = 1;
        $user->api_token = $this->generate_api_token();
        $user->save();


        $user->guardian_id = $user->id;
        $user->update();

        $user = $user::with(['relation'])->where('id',$user->id)->first();

        return $this->sendResponse("User Registered Successfully",$user);
    }

    public function logout(Request $request)
    {
        $user = Guardian::where('id',$request->auth->id);

        if($user->doesntExist())
        {
            return $this->sendError("Error",['email' => ["Invalid User"]]);
        }

        $user = $user->first();
        $user->api_token = null;
        $user->save();

        return $this->sendResponse("User Logout Successfully");
    }

    public function add_member(Request $request)
    {
        $customAttributes = [
            "full_name" => "Full Name",
            "email" => "Email Address",
            "relationship_id" => "Relationship",
        ];

        $messages = [
            "unique" => ":attribute already exists",
            "required" => "Enter your :attribute",
            "exists" => "Invalid :attribute",
        ];

        $rules = [
            "full_name" => ["required"],
            "email" => ["required","email","unique:children,email"],
            "relationship_id" => ["required","exists:relationships,id"],
        ];

        $validate = Validator::make($request->all(),$rules,$messages,$customAttributes);

        if($validate->fails())
        {
            return $this->sendError("Validation Error",$validate->errors());
        }

        $current_members = Child::where('guardian_id',$request->auth->id)->count() + Guardian::where('sub_guardian',1)->where('guardian_id',$request->auth->id)->count();

        if($current_members >= $request->auth->members)
        {
            return $this->sendError("Registration Error",['members' => ["Member limit exceeded"]]);
        }

        $password = $this->generate_password();

        if($request->relationship_id == 1)
        {
            return $this->sendError("Registration Error",['relationship_id' => ["Invalid Relationship"]]);
        }
        else if(in_array($request->relationship_id,[2,3]))
        {
            $member = new Guardian();
            $member->name = $request->full_name;
            $member->email = $request->email;
            $member->password = Hash::make($password);
            $member->is_guardian = 1;
            $member->sub_guardian = 1;
            $member->guardian_id = $request->auth->id;
            $member->relationship_id = $request->relationship_id;
            $member->status = 0;
            $member->save();

        }
        else{
            $member = new Child();
            $member->name = $request->full_name;
            $member->email = $request->email;
            $member->password = Hash::make($password);
            $member->guardian_id = $request->auth->id;
            $member->relationship_id = $request->relationship_id;
            $member->save();
        }

        $token = Crypt::encryptString($member->id);
        $relationship_token = Crypt::encryptString($member->relationship_id);

        $data = [
            "user" => $member,
            "token" => $token,
            "relationship_token" => $relationship_token,
            "password" => $password,
        ];

        NotificationsController::send('member_invite_request',$data);
        return $this->sendResponse("Member Invite Sent Successfully");
    }

    public function get_all_relationships(Request $request)
    {
        $data = Relationship::where('id','!=',1)
        ->select(['id','relation'])
        ->get();

        return $this->sendResponse("Relation Data",$data);
    }

    public function get_family_members(Request $request)
    {
        $children = Child::where('guardian_id',$request->auth->id)->where('status',1)
        ->with(['relation'])
        ->select(['id','name','email','profile_pic','status','relationship_id',DB::raw("'0' as is_parent")]);

        $members = Guardian::where('id','!=',$request->auth->id)
        ->where('guardian_id',$request->auth->guardian_id)
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
            "is_guardian" => "Are You A Guardian",
            "profile_pic" => "Profile Picture"
        ];

        $messages = [
            "unique" => ":attribute already exists",
            "required" => "Enter your :attribute",
            "mimes" => ":attribute must be a valid image with following extensions: :values",
            "regex" => ":attribute must contain one uppercase, one lowercase, one digit, and one special character",
        ];

        $rules = [
            "full_name" => ["required"],
            "email" => ["required","email",Rule::unique('guardians')->ignore($request->auth->id)],
            "password" => ["required","regex:/^.*(?=.{3,})(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@$#%]).*$/","min:8"],
            "is_guardian" => ["required","in:0,1"],
            "profile_pic" => ["nullable","mimes:jpg,jpeg,png"],
        ];

        $validate = Validator::make($request->all(),$rules,$messages,$customAttributes);

        if($validate->fails())
        {
            return $this->sendError("Validation Error",$validate->errors());
        }

        $user = Guardian::where('id',$request->auth->id);

        if($user->doesntExist())
        {
            return $this->sendError("Error in authentication",['email' => ["Invalid User"]]);
        }

        $user = $user->first();

        $user->name = $request->full_name;
        $user->email = $request->email;
        $user->password = Hash::make('password');
        $user->is_guardian = $request->is_guardian;

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
