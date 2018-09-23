<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Mail\ActivationLink;
use App\Mail\ResetPassword;
use App\Http\Requests\UserRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\ImageController;

class UserController extends Controller
{
    public function __construct()
    {
        $this -> user_validation = new UserRequest();
    }
    public function register(Request $request)
    {
        $image_controller = new ImageController();
        try
        {
            $data = $request -> all();
            $validation = $this -> user_validation -> register($data);
            if($validation -> fails())
                return cleanErrors($validation -> errors());
            $data['token']    = $this -> guidv4();
            $data['password'] = Hash::make($data['password']);
            $user_id = User::create($data) -> id;
            $image_controller -> uploadProfilePic($request, $user_id);
            //Mail::to("ansjabr@mailinator.com")->send(new ActivationLink($data));
            return success(['_token' => $data['token']]);
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
        if(!$user -> count())
            return failure(["error" => "Username or password did not work"]);
        $user = $user -> first(["name", "email", "username", "token as _token", "image", "subscription", "password"]);
        $hash = $user -> password;
        $user = $user -> toArray();
        unset($user['password']);
        if(!$this -> isPasswordValid($data['password'], $hash))
            return failure(["error" => "Username or password did not work"]);
        $user['_token'] = $this -> updateToken($user['username']);
        return success($user);
    }
    private function updateToken($username)
    {
        $token = $this -> guidv4();
        User::table()
            ->byUserName($username)
            ->update(['token' => $token]);
        return $token;
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
        if($user -> count())
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
                    ->byUserName($data['username'])
                    ->update(['password' => $hashed_password]);
        return success();
    }
    public function validateToken($token)
    {
        if(empty($token))
            return false;
        $user = User::table()
                    ->byToken($token);
        if(!$user -> count())
            return false;
        return $user->first();
    }
    /*
    |--------------------------------------------------------------------------
    | Admin Panel Methods
    |--------------------------------------------------------------------------
    |
    | Following methods are for admin panel
    |
    */
    public function index($subscription)
    {
        if($subscription == "not")
        {
            $user = User::table()
                        ->notSubscribed();
        }
        else
        {
            $user = User::table()
                        ->ofType($subscription);
        }
        $user = $user -> selectRaw('
                                    u.id, u.name , u.email,
                                    CASE WHEN u.image is null or u.image = "" THEN "img/blank-profile.png" ELSE u.image END as image
                                    '
                                  ) -> get();
        return success($user);
    }
    public function subscriptionType($user_id)
    {
        return User::table()
                   ->byId($user_id)
                   ->first(['subscription'])
                   ->subscription;
    }
}
