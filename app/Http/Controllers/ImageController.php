<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Images;
use Illuminate\Http\Request;
use App\Http\Requests\ImageRequest;
use App\Http\Controllers\UserController;
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
    public function update(Request $request)
    {
        $data = $request -> all();
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
        $this -> expirePreviousImage($data['user_id']);
        $this -> insertIntoImages($data['user_id'], $file_name, $thumb_150, $thumb_320, $compression, $title);
        return success();
    }
    private function expirePreviousImage($user_id)
    {
        $image = Images::table()
                      ->byUserId($user_id)
                      ->active()
                      ->today();
        if($image -> count())
            $image -> update(['status' => 0]);
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
    private function makeDates($date, $subscription_type)
    {
        if($subscription_type == 'weekly')
        {
            $date  = new Carbon($date);
            $start = $date -> startOfWeek();
            $date  = new Carbon($date);
            $end   = $date -> endOfWeek();
        }
        else
        {
            $date  = new Carbon($date);
            $start = $date -> startOfMonth();
            $date  = new Carbon($date);
            $end   = $date -> endOfMonth();
        }
        return ["start" => $start, "end" => $end];
    }
    public function getForAdmin($user_id, $date, Request $request)
    {
        $request -> request -> add(["user_id" => $user_id, "date" => $date]);
        return $this -> get($request);
    }
    public function monthly(Request $request)
    {
        return $this -> get($request, "monthly");
    }
    public function get(Request $request, $period = null)
    {
        $user_controller = new UserController();
        $data = $request -> all();
        $validation = $this -> image_validation -> get($data);
        if($validation -> fails())
            return cleanErrors($validation -> errors());
        $subscription_type = $user_controller -> subscriptionType($data['user_id']);
        if(empty($subscription_type) || !empty($period)) $subscription_type = "monthly";
        $range = $this -> makeDates($data['date'], $subscription_type);
        $start = $range['start'];
        $end = $range['end'];
        $images = Images::table()
                        ->selectRaw('
                                        image,
                                        CASE WHEN (thumb_150 is null or thumb_150 = "") THEN image ELSE thumb_150 END as thumb_150,
                                        CASE WHEN (thumb_320 is null or thumb_320 = "") THEN image ELSE thumb_320 END as thumb_320,
                                        DATE_FORMAT(created_at, "%d") as day'
                                    )
                        ->byUserId($data['user_id'])
                        ->betweenDates($start, $end)
                        ->active()
                        ->get();
        return success($this -> createFromRange($images, $start, $end));
    }
    private function createFromRange($images, $start_date, $end_date)
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
                    $response[$day] = $does_exist -> first() -> toArray(); 
                else
                    $response[$day] = "";
            }
        }
        return $response;
    }
}
