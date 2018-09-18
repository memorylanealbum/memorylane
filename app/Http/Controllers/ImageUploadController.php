<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\ImageUploadRequest;

class ImageUploadController extends Controller
{
    private $file_name;
    private $thumb_150;
    private $thumb_320;
    private $tinypng_fails = false;
    private $path;
    private $should_tinypng = true;
    public function __construct()
    {
        $optimize = \Tinify\setKey("JJDYg4lKtbOMmfVq05LlCVO7TXHRB2T6");
        $this -> validation = new ImageUploadRequest();
        $this -> errors = [];
        $this -> fails  = false;
        $this -> path = 'uploads/'.Carbon::now() -> format('Y/m/d');
    }
    public function upload($request)
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
        $path = $this -> path;
        $file_name = Storage::disk('public_uploads')->put($path, $content);
        if(!$file_name) {
            return $this -> error = ["error" => "Image could not be uploaded."];
        }
        $this -> file_name = $file_name;
        if(!$this -> should_tinypng)
            return;
        try
        {
            $source = \Tinify\fromFile($file_name);
            $source->toFile($file_name);
            $this -> makeThumbnail($file_name, 150);
            $this -> makeThumbnail($file_name, 320);
        }
        catch(\Tinify\AccountException $e) {
            $this -> tinypng_fails = true;
        } catch(\Tinify\ClientException $e) {
            $this -> tinypng_fails = true;
        } catch(\Tinify\ServerException $e) {
            $this -> tinypng_fails = true;
        } catch(\Tinify\ConnectionException $e) {
            $this -> tinypng_fails = true;
        } catch(\Exception $e) {
            $this -> tinypng_fails = true;
        } catch(Exception $e) {
            $this -> tinypng_fails = true;
        }
    }
    private function makeThumbnail($path, $dimenstions)
    {
        try{
            $compressed = \Tinify\fromFile($path);
            $resized = $compressed->resize(array(
                "method" => "cover",
                "width" => $dimenstions,
                "height" => $dimenstions
            )); 
            $path_parts = pathinfo($path);
            $dirname    = $path_parts['dirname'];
            $extention  =  $path_parts['extension'];
            $file_name  =  $path_parts['filename'];
            $thumb = $dirname.'/'.$file_name.'_'.$dimenstions.'_'.$dimenstions.'.'.$extention;
            $resized->toFile($thumb);
            $this -> {'thumb_'.$dimenstions} = $thumb;
            }catch(\Tinify\AccountException $e) {
                throw new Exception('Thumbnailing failed');
            } catch(\Tinify\ClientException $e) {
                throw new Exception('Thumbnailing failed');
            } catch(\Tinify\ServerException $e) {
                throw new Exception('Thumbnailing failed');
            } catch(\Tinify\ConnectionException $e) {
                throw new Exception('Thumbnailing failed');
            } catch(\Exception $e) {
                throw new Exception('Thumbnailing failed');
            } catch(Exception $e) {
                throw new Exception('Thumbnailing failed');
            }
    }
    public function error()
    {
        return $this -> error;
    }
    public function fails()
    {
        return $this -> fails;
    }
    public function getFileName()
    {
        return $this -> file_name;
    }
    public function getThumb150()
    {
        return $this -> thumb_150;
    }
    public function getThumb320()
    {
        return $this -> thumb_320;
    }
    public function tinyPngFails()
    {
        return $this -> tinypng_fails;
    }
    public function setPath($path)
    {
        $this -> path = $path;
    }
    public function shouldTinyPng($flag)
    {
        $this -> should_tinypng = $flag;
    }
}
