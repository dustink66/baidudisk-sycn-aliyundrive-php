<?php

namespace BaiduDiskSyncAliyunDrive\Commands\AliyunDrive;


use BaiduDiskSyncAliyunDrive\AliyunDrive;
use BaiduDiskSyncAliyunDrive\Commands\AliyunDrive\traits\TraitLoadDirsHash;
use BaiduDiskSyncAliyunDrive\Helper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateFolders extends AliyunDriveBaseCommand
{
    public          $dirsHash    = [];
    protected static $defaultName = 'aliyundrive:create-folders';

    protected $dirHashFile = '';

    use TraitLoadDirsHash;

    protected function configure(): void
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dirHashFile       = [
            ROOT_PATH,
            trim($_ENV['STORAGE_PATH'], DIRECTORY_SEPARATOR),
            trim($_ENV['ALIYUN_DRIVE_STORAGE_DIR_HASH'], DIRECTORY_SEPARATOR)
        ];
        $this->dirHashFile = implode("/", $dirHashFile);

        $this->loadDirHash();

        $this->loadAccessToken();
        //$aliyun = new AliyunDrive();

        $baiduDirs = $this->readYieldFile();
        foreach ($baiduDirs as $line) {
            $line = json_decode($line, true);
            if (!isset($line['path'])) {
                continue;
            }

            $dirs = explode("/", trim($line['path'], "/"));

            $fullDir      = [];
            $parentFileId = $_ENV['ALIYUN_DRIVE_DEFAULT_PARENT_ID'];
            foreach ($dirs as $_dir) {
                $fullDir[] = $_dir;

                $fullDirStr = implode("/", $fullDir);

                if (isset($this->dirsHash[$fullDirStr])) {
                    $parentFileId = $this->dirsHash[$fullDirStr];
                    //echo "===> ", $this->dirsHash[$fullDirStr], ": 文件夹已经存在。", PHP_EOL;
                    continue;
                }

                list($parentFileId, $resp) = $this->createFolder($_dir, $fullDir, $parentFileId);
                if (!$parentFileId) {
                    echo $fullDirStr, ": 未创建成功", PHP_EOL;

                    echo __FILE__, __LINE__, PHP_EOL;
                    echo "<pre>", PHP_EOL;
                    print_r($resp);
                    exit;
                }

                $output->writeln("创建成功：" . $fullDirStr . "===> " . $parentFileId);
                $this->dirsHash[$fullDirStr] = $parentFileId;
                file_put_contents($this->dirHashFile, $fullDirStr . "<==>" . $parentFileId . PHP_EOL, FILE_APPEND);
            }

        }

        echo "Create folders: over.", PHP_EOL;

        return Command::SUCCESS;
    }

    private function createFolder($folderName, $fullPath, $parentFileId = '')
    {
        $url = "https://api.aliyundrive.com/adrive/v2/file/createWithFolders";

        $accessToken = $this->getAccessToken();
        $curl        = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            "Accept: application/json",
            "Authorization: Bearer {$accessToken}",
            "Content-Type: application/json",
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $data = <<<DATA
{
"drive_id": "{$_ENV['ALIYUN_DRIVE_ID']}",
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

        return isset($resp['file_id']) ? [$resp['file_id'], $resp] : [false, $resp];
    }

    private function readYieldFile()
    {
        $storageFile = [
            ROOT_PATH,
            trim($_ENV['STORAGE_PATH'], DIRECTORY_SEPARATOR),
            trim($_ENV['BAIDU_DISK_STORAGE_DIR'], DIRECTORY_SEPARATOR)
        ];

        $storageFile = implode(DIRECTORY_SEPARATOR, $storageFile);

        $handle = fopen($storageFile, 'r');
        while (!feof($handle)) {
            yield fgets($handle);
        }
        fclose($handle);
    }

}
