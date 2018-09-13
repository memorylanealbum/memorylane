<?php

namespace App\Http\Controllers;

use App\Models\Images;
use Illuminate\Http\Request;
use App\Http\Controllers\ImageUploadController;

class ImageController extends Controller
{
    public function __construct()
    {
        
    }
    public function upload(Request $request)
    {
        $data = $request -> all();
        if(!$this-> canUpload($data['user_id']))
            return failure(['error' => 'You have already uploaded an image today.']);
        $upload_controller =  new ImageUploadController($request);
        if($upload_controller -> fails())
        {
            return $upload_controller -> error();
        }
        $file_name = $upload_controller ->name();
        $this -> insertIntoImages($data['user_id'], $file_name);
        return success();
    }
    private function canUpload($user_id)
    {
        $image  = Images::table()
                        ->byUserId($user_id)
                        ->today();
        if($image -> exists())
            return false;
        return true;
    }
    private function insertIntoImages($user_id,$file_name)
    {
        Images::create(['user_id' => $user_id, 'image' => $file_name]);
    }
}
