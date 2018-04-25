<?php

/**
 * Created by PhpStorm.
 * User: linyang
 * Date: 2018/4/17
 * Time: 上午10:43
 */
class Monitor
{
    private $dingding = '';
    private $appName = '';

    public function __construct()
    {
        if (!$appName = getenv('APP_NAME')) {
            exit;
        }
        if (!$dingding = getenv('APP_ALERT_DINGDING')) {
            exit;
        }
        $this->dingding = $dingding;
        $this->appName = $appName;
        $this->init();
    }

    private function init()
    {
        while (true) {
            $log = system('cat /usr/local/var/log/www.log.slow');
            if ($log) {
                $this->sendPost($log);
                system('true > /usr/local/var/log/www.log.slow');
            }
            $fpmNum = (int)system('ps axu | grep php-fpm | wc -l');
            if ($fpmNum >= 45) {
                $this->sendPost("php-fpm 进程即将消耗完毕目前数量: {$fpmNum}");
            }
            sleep(15);
        }
    }

    /**
     * 获取本地外网IP
     * @return mixed
     */
    private function serverIp()
    {
        return $_SERVER['SERVER_ADDR'];
    }

    /**
     * 发送请求
     * @param $url
     * @param $content
     * @return mixed
     */
    private function sendPost($content)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_URL, $this->dingding);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json;charset=utf-8;']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'msgtype' => 'text',
            'text' => ['content' => $content],
            'at' => ['isAtAll' => true]
        ]));
        return curl_exec($ch);
    }
}

new Monitor();