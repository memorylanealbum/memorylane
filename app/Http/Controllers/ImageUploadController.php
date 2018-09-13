<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\ImageUploadRequest;

class ImageUploadController extends Controller
{
    private $file_name;
    public function __construct($request)
    {
        $this -> validation = new ImageUploadRequest();
        $this -> errors = [];
        $this -> fails  = false;;
        $this -> upload($request);
    }
    private function upload($request)
    {
        $data = $request -> all();
        $validation = $this -> validation -> upload($data);
        if($validation -> fails())
        {
            $this -> fails = true;
            $this -> error =  cleanErrors($validation -> errors());
            return;
        }
        $content = $request -> file('image');
        $path = '/january';
        $file_name = Storage::disk('public_uploads')->put($path, $content);
        if(!$file_name) {
            return $this -> error = ["error" => "Image could not be uploaded."];
        }
        $this -> file_name = $file_name;
    }
    public function error()
    {
        return $this -> error;
    }
    public function fails()
    {
        return $this -> fails;
    }
    public function name()
    {
        return $this -> file_name;
    }
}
