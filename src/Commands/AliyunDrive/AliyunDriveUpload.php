<?php


namespace BaiduDiskSyncAliyunDrive\Commands\AliyunDrive;

class AliyunDriveUpload
{
    private $token, $driveId;

    public function __construct($token, $driveId)
    {
        $this->token   = $token;
        $this->driveId = $driveId;
    }

    function updateFileFile($fileName, $parentFileId, $size)
    {
        $partStr = [];
        for ($i = 0; $i < 1; $i++) {
            $partStr[] = [
                'part_number' => $i + 1
            ];
        }
        $url = "https://api.aliyundrive.com/adrive/v2/file/create_with_proof";

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

        $data = [
            'drive_id'          => $this->driveId,
            'part_info_list'    => $partStr,
            'parent_file_id'    => $parentFileId,
            'name'              => $fileName,
            'type'              => 'file',
            "check_name_mode"   => "auto_rename",
            'size'              => $size,
            "content_hash_name" => "none",
            "proof_version"     => "v1"
        ];

        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        //for debug only!
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $resp = curl_exec($curl);
        curl_close($curl);


        return json_decode($resp, true);
    }

    function updateFile($url, $sourceFile, $timeout = 30)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Referer:https://www.aliyundrive.com/',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.66 Safari/537.36'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        // curl_setopt($ch, CURLOPT_NOBODY, true);// 是否不需要响应的正文,为了节省带宽及时间,在只需要响应头的情况下可以不要正文
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_INFILE, $sourceFile);

        $response = curl_exec($ch);

        curl_close($ch);

        return $response;
    }

    function uploadFileComplete($uploadId, $fileId, $parentFileId)
    {
        $url = "https://api.aliyundrive.com/adrive/v2/file/complete";

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

        $data = [
            'drive_id'  => $this->driveId,
            'file_id'   => $fileId,
            "upload_id" => $uploadId
        ];

        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        //for debug only!
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $resp = curl_exec($curl);
        curl_close($curl);
        $resp = json_decode($resp, true);

        echo "==> Upload Completed", PHP_EOL;
        return $resp;
    }

    public function handle($sourceFile, $fileName, $parentFileId, $baiduFilePath)
    {
        $fileSize = filesize($sourceFile);

        $updateFileFilesResult = $this->updateFileFile($fileName, $parentFileId, $fileSize);

        $curUploadFileId = $updateFileFilesResult['upload_id'];
        $curFileId       = $updateFileFilesResult['file_id'];

        $fileSha1 = sha1_file($sourceFile);

        $fd = fopen($sourceFile, "rb");
        $i  = 0;

        $this->updateFile($updateFileFilesResult['part_info_list'][$i]['upload_url'], $fd);
        echo " UpdateFile . ....", __FILE__, __LINE__, PHP_EOL;
        $completedResult = $this->uploadFileComplete($curUploadFileId, $curFileId, $parentFileId);

        if ($completedResult['content_hash'] == strtoupper($fileSha1)) {
            echo "success", PHP_EOL;

            $logFile = [
                ROOT_PATH,
                trim($_ENV['STORAGE_PATH'], DIRECTORY_SEPARATOR),
                trim($_ENV['ALIYUN_UPLOAD_SUCCESS'], DIRECTORY_SEPARATOR)
            ];
        }
        else {
            echo "fail", PHP_EOL;
            echo "fileSha1: ", $fileSha1, PHP_EOL;
            echo $completedResult['content_hash'], PHP_EOL;
            $logFile = [
                ROOT_PATH,
                trim($_ENV['STORAGE_PATH'], DIRECTORY_SEPARATOR),
                trim($_ENV['ALIYUN_UPLOAD_FAIL'], DIRECTORY_SEPARATOR)
            ];
        }

        echo "aliyundrive new file: ", $completedResult['file_id'] ?? '', PHP_EOL;

        $logFile = implode("/", $logFile);
        $log     = $baiduFilePath . "\t" . ($completedResult['file_id'] ?? '') . PHP_EOL;
        file_put_contents($logFile, $log, FILE_APPEND);

        echo PHP_EOL, PHP_EOL;
    }


}
