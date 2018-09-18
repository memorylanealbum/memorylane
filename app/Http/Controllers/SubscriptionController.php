<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\SubscriptionRequest;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        $this -> subscription_validation = new SubscriptionRequest();
    }
    public function subscribe(Request $request)
    {
        $data = $request -> all();
        $validation = $this -> subscription_validation -> subscribe($data);
        if($validation -> fails())
            return cleanErrors($validation -> errors());
        if(!empty($data['subscription_db']))
            return failure(["error" => "You are already subscribed to ". $data['subscription'] . " subscription."]);
            dd(User::find($data['user_id']));
        User::find($data['user_id'])->update(['subscription' => $data['subscription']]);
        return success();
    }
}
