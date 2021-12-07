<?php

namespace BaiduDiskSyncAliyunDrive\Commands\AliyunDrive\traits;

use BaiduDiskSyncAliyunDrive\Helper;

trait TraitCreateFolder
{

    public function createFolder($parentFileId, $folderName)
    {
        $url = "https://api.aliyundrive.com/adrive/v2/file/createWithFolders";

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            "Accept: application/json",
            "Authorization: Bearer {$this->token}",
            "Content-Type: application/json",
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $data = <<<DATA
{
"drive_id": "{$this->driveId}",
"parent_file_id": "{$parentFileId}",
"name": "{$folderName}",
"check_name_mode": "refuse",
"type": "folder"
}
DATA;

        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        //for debug only!
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $resp = curl_exec($curl);
        curl_close($curl);

        $resp = json_decode($resp, true);

        return isset($resp['file_id']) ? $resp['file_id'] : false;
    }

}
