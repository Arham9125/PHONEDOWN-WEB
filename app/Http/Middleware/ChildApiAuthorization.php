<?php

namespace App\Http\Middleware;

use App\Models\Child;
use Closure;
use Illuminate\Http\Request;

class ChildApiAuthorization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if(!$request->hasHeader('Authorization'))
        {
            return response()->json([
                'success' => false,
                'message' => "Authentication Failed",
                'error' => "Authorization token is required",
            ]);

        }

        $token = $request->header('Authorization');
        $user = Child::where('api_token',$token)->where('status',1);
        if($user->doesntExist())
        {
            return response()->json([
                'success' => false,
                'message' => "Authentication Failed",
                'error' => "User doesn't exist",
            ]);
        }

        $user = $user
        ->first();

        $request->merge(['auth'=>$user]);
        return $next($request);
    }
}
