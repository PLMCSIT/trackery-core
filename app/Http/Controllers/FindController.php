<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;


class FindController extends Controller
{
    public function index()
    {
        $train_count = DB::table('TrainInfo')->count();
        if($train_count!=0){
            return view('find.find');    
        }
        else{
            return view('find.train');
        }
    }

    public function upload(Request $request)
    {
        $file = $request->file('image');
        $ext = array('jpg','JPEG','bmp','png', 'JPG');
        $valid = false;
        foreach($ext as $extension){
            if($file->getClientOriginalExtension() == $extension){
                $valid = true;
            }
        }
        if(!$valid)
        {
            return view('find.find')->with('valid', 'false');
        }
        else{
            # $storage = base_path('eigencore/test/recognize');
            $storage = base_path('public/img/faces/find');
            $image_name = $this->GUIDRANDOM();
            $file_name = $image_name.".".$file->getClientOriginalExtension();
            $file->move($storage,$file_name);

            $python_script = base_path('eigen-core/src/main.py demo');
            $image_to_recog = $storage.'/'.$file_name;
            $exec_query = '/usr/bin/python '.$python_script.' '.$image_name.' '.$image_to_recog;
            exec($exec_query , $output);
            $path_like = $output[2].'%';
            $result = DB::table('TrainInfo')->where('file', 'like', $path_like)->take(1)->get();
            // var_dump($exec_query);
            // var_dump($output);
            // # static path for display
            $storage_path_1 = 'img/faces/find/';
            $storage_path_2 = 'img/faces/found/';
            $imagepath = $storage_path_1.$file_name;
            $matchpath = $storage_path_2.$result[0]->file;
            $runtime = $output[1];
            return view('find.train')
                ->with('uploaded', 'true')
                ->with('result', $result)
                ->with('matchpath', $matchpath)
                ->with('imagepath', $imagepath)
                ->with('runtime', $runtime);
        }
    }

    private function GUIDRANDOM ($trim = true)
    {
        // Windows
        if (function_exists('com_create_guid') === true) {
            if ($trim === true)
                return trim(com_create_guid(), '{}');
            else
                return com_create_guid();
        }

        // OSX/Linux
        if (function_exists('openssl_random_pseudo_bytes') === true) {
            $data = openssl_random_pseudo_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }

        // Fallback (PHP 4.2+)
        mt_srand((double)microtime() * 10000);
        $charid = strtolower(md5(uniqid(rand(), true)));
        $hyphen = chr(45);                  // "-"
        $lbrace = $trim ? "" : chr(123);    // "{"
        $rbrace = $trim ? "" : chr(125);    // "}"
        $guidv4 = $lbrace.
                substr($charid,  0,  8).$hyphen.
                substr($charid,  8,  4).$hyphen.
                substr($charid, 12,  4).$hyphen.
                substr($charid, 16,  4).$hyphen.
                substr($charid, 20, 12).
                $rbrace;
        return $guidv4;
    }

}
