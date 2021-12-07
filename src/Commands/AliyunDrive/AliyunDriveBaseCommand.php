<?php

namespace BaiduDiskSyncAliyunDrive\Commands\AliyunDrive;


use BaiduDiskSyncAliyunDrive\AliyunDrive;
use BaiduDiskSyncAliyunDrive\Helper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AliyunDriveBaseCommand extends Command
{
    public function loadAccessToken()
    {
        Helper::loadAliyunDriveToken();

        if (!Helper::getAliyunDriveAccessToken()) {
            $refreshToken = Helper::getAliyunDriveRefreshToken();

            AliyunDrive::refreshToken($refreshToken);

            Helper::loadAliyunDriveToken();
        }

        if (!Helper::getAliyunDriveAccessToken()) {
            echo "Access Token 失效，无法从refresh token重新获取。", PHP_EOL;
            exit;
        }
    }

    public function getAccessToken()
    {
        for ($i = 0; $i < 3; $i++) {
            if (isset($_ENV['ALIYUN_ACCESS_TOKEN']) && $_ENV['ALIYUN_ACCESS_TOKEN'] && $_ENV['ALIYUN_EXPIRE_TIME'] > time()) {
                return $_ENV['ALIYUN_ACCESS_TOKEN'];
            }
            $this->loadAccessToken();
        }

        return false;
    }

    public function getDriveId(){
        for ($i = 0; $i < 3; $i++) {
            if (isset($_ENV['ALIYUN_DRIVE_ID']) && $_ENV['ALIYUN_DRIVE_ID'] ) {
                return $_ENV['ALIYUN_DRIVE_ID'];
            }
            $this->loadAccessToken();
        }

        return false;
    }


}
