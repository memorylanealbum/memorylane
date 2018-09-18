<?php

namespace App\Http\Requests;

use Validator;

class SubscriptionRequest
{
    public function subscribe($data)
    {
        return Validator::make($data, [
            "subscription"      => "required|in:weekly,monthly",
        ]);
    }
}
