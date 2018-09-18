<?php

namespace App\Http\Requests;

use Validator;

class ImageRequest
{
    public function get($data)
    {
        return Validator::make($data, [
            "date"      => "required|date_format:Y-m-d",
        ]);
    }
}
