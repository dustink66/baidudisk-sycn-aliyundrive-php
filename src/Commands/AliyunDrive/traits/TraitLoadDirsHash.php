<?php

namespace BaiduDiskSyncAliyunDrive\Commands\AliyunDrive\traits;

use BaiduDiskSyncAliyunDrive\Helper;

trait TraitLoadDirsHash
{
    public          $dirsHash    = [];
    public function loadDirHash()
    {
        $dirHashFile = [
            ROOT_PATH,
            trim($_ENV['STORAGE_PATH'], DIRECTORY_SEPARATOR),
            trim($_ENV['ALIYUN_DRIVE_STORAGE_DIR_HASH'], DIRECTORY_SEPARATOR)
        ];

        $dirHashFile = implode("/", $dirHashFile);
        if (!file_exists($dirHashFile)) {
            return true;
        }

        $handle = fopen($dirHashFile, "r");
        while (!feof($handle)) {
            $line = fgets($handle);
            $line = trim($line);
            if (!$line) {
                continue;
            }
            $lineArr = explode("<==>", $line);

            if (count($lineArr) != 2) {
                echo __FILE__, __LINE__, PHP_EOL;
                echo "<pre>", PHP_EOL;
                print_r($line);
                print_r($lineArr);
                exit;
            }
            $this->dirsHash[$lineArr[0]] = $lineArr[1];
        }
        fclose($handle);

        return true;
    }

}
