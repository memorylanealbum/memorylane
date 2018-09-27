<?php

namespace App\Http\Middleware;

use Closure;
use App\Http\Controllers\UserController;

class AuthenticateToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = new UserController;
        $data = $request ->all();
        if(empty($data['_token']) || !$user_details=$user -> validateTOken($data['_token']))
        {
            return failure(["error" => "Authorization failed."], 401);
        }
        $user_details = $user_details -> toArray();
        $user_details['user_id'] = $user_details['id'];
        $user_details['subscription_db'] = $user_details['subscription'];
        unset($user_details['subscription']);
        unset($user_details['id']);
        unset($user_details['password']);
        unset($user_details['image']);
        if(!empty($data['email'])) $user_details['request_email'] = $data['email'];
        if(!empty($data['name'])) $user_details['request_name'] = $data['name'];
        $request -> request -> add($user_details);
        return $next($request);
    }
}
