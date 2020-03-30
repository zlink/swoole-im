<?php


namespace App\Helpers;


trait ResponseTrait
{
    protected $statusCode = FoundationResponse::STATUS_OK;

    /**
     * @param string $statusCode
     * @return ResponseTrait
     */
    public function setStatusCode(string $statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatusCode(): string
    {
        return $this->statusCode;
    }

    protected function status($status, array $data, $code = null)
    {
        if ($code) {
            $this->setStatusCode($code);
        }

        $status = [
            'status' => $status,
            'code' => $this->getStatusCode(),
            'timestamp' => time(),
        ];

        $data = array_merge($status, $data);

        return $this->respond($data);
    }

    protected function respond($data)
    {
        logger()->info("Push response:", $data);
        if ($this->isEstablished($this->fd)) {
            $this->push($this->fd, $this->encode($data));
        }
        return false;
    }

    public function failed($fd, $data, $code = FoundationResponse::STATUS_ERROR, $status = 'Error')
    {
        $this->fd = $fd;

        $this->status($status, compact('data'), $code);
    }

    public function success($fd, $data, $status = 'Success')
    {
        $this->fd = $fd;

        return $this->status($status, compact('data'));
    }

    /**
     * parse message body
     * @param $data
     * @param bool $flag
     * @return mixed
     */
    protected function decode($data, $flag = false)
    {
        return json_decode($data, $flag);
    }

    /**
     * encode response body
     * @param $data
     * @return false|string
     */
    protected function encode($data)
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}