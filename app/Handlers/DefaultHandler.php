<?php


namespace App\Handlers;


use App\Exceptions\RequestException;
use Exception;
use MongoDB\Driver\Exception\ServerException;
use Swoole\Redis;

/**
 *
 *  webim 事件处理
 */
class StarHandler extends Handler implements IHandler
{

    public function handle()
    {
        try {
			// check required params.
            if (!is_array($this->data) || empty($this->data) || !isset($this->data['event'])) {
                throw new RequestException('Bad Request.');
            }

            logger()->info('Receive Message: ', $this->data);

            if (!method_exists($this, $this->data['event'])) {
                throw new RequestException(sprintf('Unhandled event: %s', $this->data['event']));
            }

			// handle event by event name
            call_user_func([$this, $this->data['event']], $this->data);

        } catch (RequestException $exception) {
            $this->server->failed($this->fd, ['error' => $exception->getMessage()]);
        } catch (ServerException $exception) {
            logger()->error($exception);
        }
    }

    public function bind($data)
    {
        if (!isset($data['role']) || empty($data['role'])) {
            throw new RequestException('Role params is required . ');
        }

        $id = $data['id'] ?? 0;
        $role = $data['role'] ?? '';
        $fds = 'fd:' . $this->fd;

        $user = sprintf('user:%s-%s', $role, $id);

		// 使用了连接池，会自动释放
        app('redis')->set($user, $fds, function (Redis $redis, $result) use ($user) {
			logger()->info('Bind event:', [$user, $fd, $result]);
            $response = [
                'event' => 'bind',
                'user' => $user,
                'fd' => 'fd:' . $this->fd,
                'respEvent' => 20,
                'seqId' => $data['seqId'] ?? 0,
                'id' => $data['id'] ?? 0,
                'status' => true,
            ];

            $this->success($response);
        });

        // 另存一份,用户反查
        app('redis')->set($fds, $user, function () {
        });
    }

    public function lyCount($data)
    {
        if (!isset($data['to']) || !isset($data['from'])) {
            throw new \Exception('To or From params is Required . ');
        }

        $key = sprintf('off:from-%s-to-%s', $data['from'], $data['to']);
        app('redis')->llen($key, function (Redis $redis, $result) {
            $response = [
                'type' => $data['type'] ?? '',
                'respEvent' => 10,
                'seqId' => $data['seqId'] ?? '',
                'messageCount' => $result,
            ];
            $this->success($response);
        });
    }

    public function lyKey($data)
    {
        if (!isset($data['to'])) {
            throw new \Exception('To params is Required . ');
        }

        $key = 'off:from-*-to-' . $data['to'];
        app('redis')->keys($key, function (Redis $redis, $result) {
            $uids = [];
            if ($result && is_array($result)) {
                foreach ($result as $item) {
                    preg_match('/off:from-[u|e]-(\d+)-to-/', $item, $matches);
                    if (!empty($matches)) {
                        $uids[] = $matches[1];
                    }
                }
            }

            $response = [
                'type' => 'lyKey',
                'uids' => $uids,
                'respEvent' => 10,
                'to' => $data['to'] ?? '',
                'seqId' => $data['seqId'] ?? '',
            ];
            $this->success($response);
        });
    }

    public function ly($data)
    {
        if (!isset($data['to']) || !isset($data['from'])) {
            throw new \Exception('To or From params is Required . ');
        }

        $key = 'off:from- ' . $data['from'] . '-to-' . $data['to'];
        app('redis')->lrange($key, 0, -1, function (Redis $redis, $result) use ($data, $key) {
            if ($result && is_array($result)) {
                foreach ($result as $index => $item) {
                    $this->success(['comment' => json_decode($item)]);
                }
                app('redis')->del($key, function (Redis $redis, $ctx) use ($key, $result) {
                    logger()->info('comment:', [$key, $result]);
                });
                // 记录留言到mongodb
                $this->server->comment->set(['from' => $data['from'], 'to' => $data['to'], 'content' => $result]);
            } else {
                $this->success(['comment' => []]);
            }
        });
    }

    public function zx($data)
    {
        $key = 'user:*';
        app('redis')->keys($key, function (Redis $redis, $result) {
            $this->success([
                'type' => 'zx',
                'online' => $result,
                'answer' => date('Y - m - d H:i:s'),
                'respEvent' => 20,
            ]);
        });
    }

    public function dh($data)
    {
        if (!isset($data['to']) || empty($data['to'])) {
            throw new Exception("To params is required.");
        }

        $user = 'user:' . $data['to'];
        app('redis')->get($user, function (Redis $redis, $result) use ($data) {
            $channel = explode(':', $result);
            $fd = $channel[1] ?? 0;

            $response = [
                'type' => 'dh',
                'ask' => $data['message'] ?? '',
                'respEvent' => 20,
                'seqId' => $data['seqId'] ?? '',
                'from' => $data['from'] ?? '',
                'to' => $data['to'] ?? '',
                'messageType' => $data['messageType'] ?? '',
            ];

            if (!$fd) {
                // user offline, write to comment
                if (!empty($data['from']) && !empty($data['to'])) {
                    $key = 'off:from-' . $data['from'] . '-to-' . $data['to'];
                    app('redis')->rpush($key, json_encode($response, JSON_UNESCAPED_UNICODE),
                        function (Redis $redis, $result) {
                        });
                }
            } else {
                // log message comment
                $this->server->comment->set(['from' => $data['from'], 'to' => $data['to'], 'content' => $data['message']]);

                $this->success($response);
            }
        });
    }
}

