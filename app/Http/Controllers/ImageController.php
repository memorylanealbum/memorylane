<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Images;
use Illuminate\Http\Request;
use App\Http\Requests\ImageRequest;
use App\Http\Controllers\ImageUploadController;

class ImageController extends Controller
{
    public function __construct()
    {
        $this -> image_validation = new ImageRequest();
    }
    public function upload(Request $request)
    {
        $data = $request -> all();
        if(!$this-> canUpload($data['user_id']))
            return failure(['error' => 'You have already uploaded an image today.']);
        $upload_controller =  new ImageUploadController();
        $upload_controller -> upload($request);
        if($upload_controller -> fails())
        {
            return $upload_controller -> error();
        }
        $file_name = $upload_controller ->getFileName();
        $compression = 0; $thumb_150 = ""; $thumb_320 = "";
        if(!$upload_controller -> tinyPngFails())
        {
            $thumb_150  = $upload_controller ->getThumb150();
            $thumb_320  = $upload_controller ->getThumb320();
            $compression = 1;
        }
        $title = !empty($data['title']) ? $data['title'] : '';
        $this -> insertIntoImages($data['user_id'], $file_name, $thumb_150, $thumb_320, $compression, $title);
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
    private function insertIntoImages($user_id,$file_name, $thumb_150, $thumb_320, $compression, $title)
    {
        Images::create([
                        'user_id'     => $user_id, 
                        'thumb_150'   => $thumb_150, 
                        'thumb_320'   => $thumb_320, 
                        'compression' => $compression, 
                        'image'       => $file_name,
                        'title'       => $title
                        ]);
    }
    public function uploadProfilePic($request, $user_id)
    {
        if(!$request -> hasFile('image'))
            return;
        $data = $request -> all();
        $upload_controller =  new ImageUploadController();
        $upload_controller -> shouldTinyPng(false);
        $upload_controller ->setPath('profile_uploads');
        $upload_controller -> upload($request);
        if($upload_controller -> fails())
        {
            return $upload_controller -> error();
        }
        $file_name = $upload_controller ->getFileName();
        User::find($user_id)->update(['image' => $file_name]);
    }
    public function get(Request $request)
    {
        $data = $request -> all();
        $validation = $this -> image_validation -> get($data);
        if($validation -> fails())
            return cleanErrors($validation -> errors());
        $date = new Carbon($data['date']);
        $month = $date->format('F');
        $year = $date->format('Y');
        $first_of_month = new Carbon("first day of $month $year");
        $last_of_month = new Carbon("last day of $month $year");
        $images = Images::table()
                        ->selectRaw('
                                        image,
                                        CASE WHEN (thumb_150 is null or thumb_150 = "") THEN image ELSE thumb_150 END as thumb_150,
                                        CASE WHEN (thumb_320 is null or thumb_320 = "") THEN image ELSE thumb_320 END as thumb_320,
                                        DATE_FORMAT(created_at, "%d") as day'
                                    )
                        ->byUserId($data['user_id'])
                        ->betweenDates($first_of_month, $last_of_month)
                        ->get();
        return success($this -> createWholeMOnth($images, $first_of_month, $last_of_month));
    }
    private function createWholeMOnth($images, $start_date, $end_date)
    {
        $response = [];
        for($i = 0; $i < 31; $i++)
        {
            if($i == 0)
                $plus_on_day = $start_date;
            else
                $plus_on_day = $start_date -> addDay(1);
            if($plus_on_day <= $end_date)
            {
                $day = $plus_on_day->format('d');
                $does_exist = $images -> where('day', $day);
                if($does_exist -> count())
                    $response[$day] = $does_exist -> pluck('image', 'thumb_150', 'thumb_320') -> toArray(); 
                else
                    $response[$day] = "";
            }
        }
        return $response;
    }
}
