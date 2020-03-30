<?php

return [
    'server' => [
        'host' => env('SERVER_HOST', '0.0.0.0'),
        'port' => env('SERVER_PORT', 8001),
        'ssl_cert_file' => env('SERVER_SSL_CERT_FILE'),
        'ssl_key_file' => env('SERVER_SSL_KEY_FILE'),
        'daemonize' => env('SERVER_DAEMONIZE', false),
        'worker_num' => env('SERVER_WORKER_NUM', 8),
        'max_conn' => env('SERVER_MAX_CONN', 10000),
        // 'heartbeat_check_interval' => 5,        //5s进行一次心跳检测
        // 'heartbeat_idle_time' => 10,            //10s没有心跳，切断
        'log_file' => './data/' . date('Y-m-d') . '-error.log',        //设置日志文件地址
        'heartbeat_idle_time' => 180 //TCP连接的最大闲置时间，单位s , 如果某fd最后一次发包距离现在的时间超过heartbeat_idle_time会把这个连接关闭。
    ],
    'redis' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env('REDIS_PORT', 6379),
        'auth' => env('REDIS_AUTH', '')
    ],
    'mongo' => [
        'host' => env('MONGODB_HOST', '127.0.0.1'),
        'port' => env('MONGODB_PORT', 27017),
        'username' => env('MONGODB_USERNAME', 'root'),
        'password' => env('MONGODB_PASSWORD', '123456'),
    ],
];
