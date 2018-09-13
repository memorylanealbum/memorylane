<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Mail\ActivationLink;
use App\Mail\ResetPassword;
use App\Http\Requests\UserRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function __construct()
    {
        $this -> user_validation = new UserRequest();
    }
    public function register(Request $request)
    {
        try
        {
            $data = $request -> all();
            $validation = $this -> user_validation -> register($data);
            if($validation -> fails())
                return cleanErrors($validation -> errors());
            $data['token']    = $this -> guidv4();
            $data['password'] = Hash::make($data['password']);
            User::create($data);
            //Mail::to("ansjabr@mailinator.com")->send(new ActivationLink($data));
            return success([$data['token']]);
        }
        catch(\Exception $e)
        {
            return failure(["error" => $e -> getMessage()]);
        }
    }
    private function guidv4()
    {
        if (function_exists('com_create_guid') === true)
            return trim(com_create_guid(), '{}');
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }
    public function login(Request $request)
    {
        $data = $request -> all();
        $validation = $this -> user_validation -> login($data);
        if($validation -> fails())
            return cleanErrors($validation -> errors());
        $user = User::table()
                    ->byUserName($data["username"]);
        if(!$user -> exists())
            return failure(["error" => "Username or password did not work"]);
        $user = $user -> first();
        $hash = $user -> password;
        if(!$this -> isPasswordValid($data['password'], $hash))
            return failure(["error" => "Username or password did not work"]);
        return success($user -> first(["name", "email", "username", "token"]) -> toArray());
    }
    private function isPasswordValid($password, $hash)
    {
        if(Hash::check($password, $hash))
            return true;
        return false;
    }
    public function activate($activation_link)
    {
        $user = User::table()
                    ->byActivationLink($activation_link);
        if($user -> exists())
        {
            $user -> update(['status' => 1]);
            $status = 1;
        }
        else
            $status = 0;
        return view('emails.after_activation')->withStatu($status);
    }
    public function resetPassword(Request $request)
    {
        $data = $request -> all();
        $validation = $this -> user_validation -> resetPassword($data);
        if($validation -> fails())
            return cleanErrors($validation -> errors());
        $password = $this -> randomPassword();
        $hashed_password = Hash::make($password);
        User::table()
            ->byEmail($data['email'])
            ->update(['password' => $hashed_password]);
        Mail::to($data['email'])->send(new ResetPassword($password));
        return success();
    }
    private function randomPassword()
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array();
        $alphaLength = strlen($alphabet) - 1;
        for($i = 0; $i < 8; $i++)
        {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass);
    }
    public function changePassword(Request $request)
    {
        $data = $request -> all();
        $validation = $this -> user_validation -> changePassword($data);
        if($validation -> fails())
            return cleanErrors($validation -> errors());
        $user = User::table()
                    ->byUserName($data["username"])
                    ->first(['password']);
        $hash = $user -> password;
        if(!$this -> isPasswordValid($data['password'], $hash))
            return failure(["error" => "The password you entered is not correct."]);
        $hashed_password = Hash::make($data['new_password']);
        $user = User::table()
                    ->byUserName()
                    ->update(['password' => $hashed_password]);
        return success();
    }
    public function validateToken($token)
    {
        if(empty($token))
            return false;
        $user = User::table()
                    ->byToken($token);
        if(!$user -> exists())
            return false;
        return $user->first();
    }
}
