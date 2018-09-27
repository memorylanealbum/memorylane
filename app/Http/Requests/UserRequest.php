<?php

namespace App\Http\Requests;

use Validator;

class UserRequest
{
    public function register($data)
    {
        return Validator::make($data, [
            "name"      => "required|string",
            "username"  => "required|string|unique:users,username",
            "email"     => "email|required|unique:users,email",
            "password"  => "required|min:6|max:16|confirmed",
        ]);
    }
    public function updateProfile($data)
    {
        $user_id = $data['user_id'];
        return Validator::make($data, [
            "request_name"      => "required|string",
            "request_email"     => "email|required|unique:users,email,$user_id"
        ]);
    }
    public function login($data)
    {
        return Validator::make($data, [
            "username"  => "required",
            "password"  => "required"
        ]);
    }
    public function resetPassword($data)
    {
        return Validator::make($data, [
            "email"     => "email|required|exists:users,email",
        ]);
    }
    public function changePassword($data)
    {
        return Validator::make($data, [
            "password"      => "required",
            "new_password"  => "required|min:6|max:16|confirmed",
        ]);
    }
    /*
    |--------------------------------------------------------------------------
    | Admin Panel Requests
    |--------------------------------------------------------------------------
    |
    | Following requests are for admin panel
    |
    */
}
