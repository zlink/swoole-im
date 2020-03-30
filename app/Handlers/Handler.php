<?php


namespace App\Handlers;


abstract class Handler
{
    protected $server;

    protected $frame;

    protected $data;

    protected $fd;

    public function __construct($server, $frame, $data)
    {
        $this->server = $server;
        $this->frame = $frame;
        $this->fd = $frame->fd;
        $this->data = $data;
    }

	public function success($data)
	{
		return $this->server->success($this->fd, $data);
	}

	public function failed($data)
	{
		return $this->server->failed($this->fd, $data);
	}

}

