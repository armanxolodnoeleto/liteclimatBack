<?php
namespace App\Services;

class PhotoUploadService {
    protected $folder;

    public function __construct($projectId){
        if ($projectId == config('projects.lk')) {
            $this->folder = 'lk/';
        }else {
            $this->folder = 'xl/';
        }
    }

    public function uploadPhoto($file, $dir){
        $return = [];
        $dir .= $this->folder;
        $return['dir'] = $dir;
        $x = explode('.', $file->getClientOriginalName());
        $format = end($x);
        $return['format'] = strtolower($format);
        $return['original_name'] = $file->getClientOriginalName();
        $return['name'] = md5(time().$file->getClientOriginalName());
        $file_name = $return['name'].'.'.$return['format'];

        if(!file_exists(public_path($dir))){
            mkdir(public_path($dir), 0777);
        }
        $file->move($dir, $file_name);
        $return['full_name'] = $file_name;
        return $return;
    }
}
