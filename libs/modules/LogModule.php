<?php

class LogModule
{
    static function SetMysqErrorlLog($log_msg)
    {
        $log_filename = getDocumentRoot() . "mysql-log";
        if (!file_exists($log_filename)) {
            mkdir($log_filename, 0777, true);
        }
        $log_file_data = $log_filename . '/log_' . date('Y-m-d') . '.log';
        // if you don't add `FILE_APPEND`, the file will be erased each time you add a log
        file_put_contents($log_file_data, $log_msg . "\n", FILE_APPEND);
    }

    static function SetMysqlLog($log_msg)
    {
        $log_filename = getDocumentRoot() . "mysql-log/execution";
        if (!file_exists($log_filename)) {
            mkdir($log_filename, 0777, true);
        }
        $log_file_data = $log_filename . '/log_' . date('Y-m-d') . '.log';
        // if you don't add `FILE_APPEND`, the file will be erased each time you add a log
        file_put_contents($log_file_data, $log_msg . "\n", FILE_APPEND);

    }

    static function SetEventLog($log_msg)
    {
        $log_filename = getDocumentRoot() . "event-log";
        if (!file_exists($log_filename)) {
            mkdir($log_filename, 0777, true);
        }
        $log_file_data = $log_filename . '/log_' . date('Y-m-d') . '.log';
        // if you don't add `FILE_APPEND`, the file will be erased each time you add a log
        file_put_contents($log_file_data, $log_msg . "\n", FILE_APPEND);
    }
}