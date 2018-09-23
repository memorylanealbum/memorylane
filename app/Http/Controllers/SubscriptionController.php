<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\UserController;
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
        User::where('id', $data['user_id'])->update(['subscription' => $data['subscription']]);
        return success();
    }
    public function getStartingDate($user_id)
    {
        $user_controller   = new UserController();
        $registration_date = User::table()
                                 ->byId($user_id)
                                 ->selectRaw('DATE_FORMAT(created_at, "%d") as day,
                                             DATE_FORMAT(created_at, "%m") as month,
                                             DATE_FORMAT(created_at, "%Y") as year')
                                ->first();
        return success($registration_date);
    }
}
