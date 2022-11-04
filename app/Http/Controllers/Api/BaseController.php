<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class BaseController extends Controller
{
    public function sendResponse(String $message,$data = [])
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ]);
    }


    public function sendError(String $message,$error = [])
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => $error,
        ]);
    }

    public function generate_api_token()
    {
        return Hash::make(Str::random(20));
    }

    public function generate_password($upper = 2, $lower = 3, $numeric = 2, $other = 1) {

        $password = Array();
        $characters = ["!","@","$","#","%"];

        //Create contents of the password
        for ($i = 0; $i < $upper; $i++) {
            $password[] = chr(rand(65, 90));
        }
        for ($i = 0; $i < $lower; $i++) {
            $password[] = chr(rand(97, 122));
        }
        for ($i = 0; $i < $numeric; $i++) {
            $password[] = chr(rand(48, 57));
        }
        for ($i = 0; $i < $other; $i++) {
            $password[] = $characters[array_rand($characters,1)];
        }

        //using shuffle() to shuffle the order
        shuffle($password);
        return implode("",$password);
    }
}
