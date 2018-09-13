<?php

namespace App\Http\Requests;

use Validator;

class ImageUploadRequest
{
    public function upload($data)
    {
        return Validator::make($data, [
            "image"      => "required|image",
        ]);
    }
}
