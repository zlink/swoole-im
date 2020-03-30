<?php


namespace App\Helpers;


class WebSocketEvents
{
    const WORKER_START = 'WorkerStart';
    const HANDSHAKE = 'HandShake';
    const OPEN = 'Open';
    const MESSAGE = 'Message';
    const CLOSE = 'Close';

    public static function events()
    {
        return [self::WORKER_START, self::HANDSHAKE, self::OPEN, self::MESSAGE, self::CLOSE];
    }
}