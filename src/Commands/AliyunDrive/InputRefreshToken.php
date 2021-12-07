<?php

namespace BaiduDiskSyncAliyunDrive\Commands\AliyunDrive;


use BaiduDiskSyncAliyunDrive\AliyunDrive;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InputRefreshToken extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'aliyundrive:input-refresh-token';

    protected function configure(): void
    {
        $this->addArgument('refresh_token', InputArgument::REQUIRED, 'aliyun-drive refresh token');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $refreshToken =  $input->getArgument("refresh_token");

        AliyunDrive::refreshToken($refreshToken);

        return Command::SUCCESS;
    }

}
