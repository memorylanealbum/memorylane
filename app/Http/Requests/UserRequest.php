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
            "username"      => "required|string|exists:users,username",
            "password"      => "required",
            "new_password"  => "required|min:6|max:16|confirmed",
        ]);
    }
}
