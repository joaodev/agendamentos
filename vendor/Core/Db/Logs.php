<?php

namespace Core\Db;

use Exception;

class Logs extends InitDb
{
    public function toLog(string $msg): bool|string
    {
        try {
            $model = new Model();
            $dataPost = [
                'log_user_id' => (!empty($_SESSION['COD']) ? $_SESSION['COD'] : ''),
                'log_action' => $msg,
                'log_date' => date('Y-m-d H:i:s'),
                'log_ip' => self::getUserIp(),
                'log_user_agent' => $_SERVER["HTTP_USER_AGENT"],
                'log_status' => http_response_code()
            ];

            $crud = new Crud();
            $crud->setTable('logs');

            return $crud->create($dataPost);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    private static function getUserIp()
    {
        return $_SERVER['HTTP_CLIENT_IP']
            ?? $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['HTTP_X_FORWARDED']
            ?? $_SERVER['HTTP_FORWARDED_FOR']
            ?? $_SERVER['HTTP_FORWARDED']
            ?? $_SERVER['REMOTE_ADDR']
            ?? 'UNKNOWN';
    }
}