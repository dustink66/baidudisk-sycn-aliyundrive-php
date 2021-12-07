<?php

namespace BaiduDiskSyncAliyunDrive\Commands\AliyunDrive;


use BaiduDiskSyncAliyunDrive\AliyunDrive;
use BaiduDiskSyncAliyunDrive\Commands\AliyunDrive\traits\TraitLoadDirsHash;
use BaiduDiskSyncAliyunDrive\Helper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FilesUpload extends AliyunDriveBaseCommand
{
    public           $dirsHash    = [];
    protected static $defaultName = 'aliyundrive:files-upload';

    /**
     * 已经上传成功的列表。
     */
    protected $uploadFilesSuccessHash = [];

    protected $dirHashFile = '';

    use TraitLoadDirsHash;

    protected function configure(): void
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $files = $this->readYieldFile();

        $this->loadDirHash();

        $this->loadUploadSuccessHash();

        foreach ($files as $line) {
            $line = json_decode($line, true);

            echo "====>", $line['path'], PHP_EOL;
            if ( $line['size']/1024/1024 > 200){
                //TODO 超过200Mb文件暂时不处理。
                echo "超过200Mb暂时不处理", PHP_EOL;

                continue;
            }

            if (isset($this->uploadFilesSuccessHash[$line['path']])){
                continue;
            }

            $dlink        = $this->getFileMeta($line['fs_id']);
            $fileLinkInfo = $dlink[0];

            $curStorageFile = [
                ROOT_PATH,
                trim($_ENV['STORAGE_PATH'], DIRECTORY_SEPARATOR),
                trim($_ENV['ALIYUN_SOURCE_TMP_FILE_NAME'], DIRECTORY_SEPARATOR)
            ];
            $curStorageFile = implode("/", $curStorageFile);

            $dlLink = $fileLinkInfo['dlink'] . "&access_token=" . $this->getBaiduAccessToken();
            echo "==>", $curStorageFile, "({$dlLink})", PHP_EOL;

            system("curl -L -X GET '{$dlLink}'  -H 'User-Agent: pan.baidu.com'  --output $curStorageFile");

            echo "{$fileLinkInfo['path']}, 下载完成，等待上传...", PHP_EOL;
            $accessToken     = $this->getAccessToken();
            $driveId         = $this->getDriveId();
            $uploadLogic     = new AliyunDriveUpload($accessToken, $driveId);
            $curParentFileId = $this->getParentFileId($fileLinkInfo['path']);
            echo "curParentFileId: $curParentFileId", PHP_EOL;
            $uploadLogic->handle($curStorageFile, $fileLinkInfo['filename'], $curParentFileId, $line['path']);
        }

        return Command::SUCCESS;
    }

    private function getParentFileId($filePath)
    {
        $pathInfo = explode("/", trim($filePath, "/"));

        array_pop($pathInfo);
        $curPath = implode("/", $pathInfo);

        return isset($this->dirsHash[$curPath]) ? $this->dirsHash[$curPath] : $_ENV['ALIYUN_DRIVE_DEFAULT_PARENT_ID'];
    }

    private function getFileMeta($fileId)
    {
        $fileId = is_array($fileId) ? $fileId : [$fileId];
        $url    = "https://pan.baidu.com/rest/2.0/xpan/multimedia?method=filemetas&access_token={ACCESS_TOKEN}&fsids={FILES_ID}&dlink=1";
        $url    = strtr($url, [
            '{ACCESS_TOKEN}' => $this->getBaiduAccessToken(),
            '{FILES_ID}'     => urlencode("[" . implode(",", $fileId) . "]")
        ]);

        $result = Helper::curlGet($url);
        $result = json_decode($result, true);
        if (isset($result['errmsg']) && $result['errmsg'] == 'succ') {
            return $result['list'];
        }
        return false;
    }

    private function downloadFile($fileLink, $size)
    {

        $bufferSize = 10;
        $resource   = fopen($fileLink, "r");
        while (!feof($resource) && $bufferSize < $size) {
            yield fread($resource, 80920);
            $bufferSize += 80920;
        }

        fclose($resource);
    }

    private function readYieldFile()
    {
        $storageFile = [
            ROOT_PATH,
            trim($_ENV['STORAGE_PATH'], DIRECTORY_SEPARATOR),
            trim($_ENV['BAIDU_DISK_STORAGE_FILE'], DIRECTORY_SEPARATOR)
        ];

        $storageFile = implode(DIRECTORY_SEPARATOR, $storageFile);

        $handle = fopen($storageFile, 'r');
        while (!feof($handle)) {
            yield fgets($handle);
        }
        fclose($handle);
    }

    private function getBaiduAccessToken()
    {
        Helper::loadBaiduDiskToken();

        if (isset($_ENV['BAIDU_ACCESS_TOKEN']) && $_ENV['BAIDU_ACCESS_TOKEN'] && $_ENV['BAIDU_EXPIRES_IN'] > time()) {
            return $_ENV['BAIDU_ACCESS_TOKEN'];
        }

        echo "baidu token 失效。", PHP_EOL;
        exit;

        return false;
    }


    private function loadUploadSuccessHash()
    {
        $file = [
            ROOT_PATH,
            trim($_ENV['STORAGE_PATH'], DIRECTORY_SEPARATOR),
            trim($_ENV['ALIYUN_UPLOAD_SUCCESS'], DIRECTORY_SEPARATOR)
        ];
        $file = implode("/", $file);

        $handle = fopen($file, 'r');
        while (!feof($handle)) {
            $line = fgets($handle);
            $line = trim($line);
            $arr = explode("\t", $line);

            if (!$arr[0]){
                continue;
            }
            $this->uploadFilesSuccessHash[$arr[0]] = 1;
        }
        fclose($handle);
        return $this;
    }
}
