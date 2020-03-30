<?php /** @noinspection PhpUndefinedFieldInspection */


namespace App\Components\Services;


use App\Components\Repositories\CommentRepository;
use App\Helpers\ResponseTrait;
use App\Helpers\WebSocketEvents;
use Illuminate\Container\Container;
use MongoDB\Client;
use Swoole\Redis;
use Swoole\WebSocket\Server;

class WebSocketServer extends Server
{
    use ResponseTrait;

    protected $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
        $this->init();
    }

	// init a websocket server
    protected function init()
    {
        $config = app('config');

        parent::__construct(
            $config->get('server.host'),
            $config->get('server.port'),
            SWOOLE_PROCESS,
            SWOOLE_SOCK_TCP
        );
    }

    protected function dispatchEvents()
    {
        $this->on(WebSocketEvents::WORKER_START, [$this, 'onWorkerStart']);
        $this->on(WebSocketEvents::OPEN, [$this, 'onOpen']);
        $this->on(WebSocketEvents::MESSAGE, [$this, 'onMessage']);
        $this->on(WebSocketEvents::CLOSE, [$this, 'onClose']);
    }

    /**
     * mount service container to every worker.
     * @param Server $server
     * @param $workerId
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function onWorkerStart(Server $server, $workerId)
    {
        // 挂载同步mongo客户端
        $this->comment = new CommentRepository($this->app->make(Client::class));
    }

    public function onHandShake()
    {
        // todo
    }

    public function onOpen($server, $request)
    {
        $uid = $request->get['uid'] ?? false;
        $token = $request->get['token'] ?? false;

		logger()->info('Receive Open Event:', [$request]);

		// user auth
        if (!$uid || !$token) {
            $this->failed($request->fd, ['error' => 'uid and token is required.']);
            $this->disconnect($request->fd, 1000, '');
            return;
        }

        // bind message event handler
        // $this->messageHandler = ltrim($request->server['path_info'], '/');

        $key = sprintf('token:%s', $uid);
        app('redis')->get($key, function (Redis $redis, $result) use ($request, $token) {
            if ($result && $result == $token) {

                $this->success($result->fd, [
                    'type' => 'connect',
                    'answer' => date('Y-m-d H:i:s'),
                    'respEvent' => 20
                ]);
            } else {
                $this->failed($request->fd, ['error' => 'Unauthorized.']);
                $this->disconnect($request->fd, 1000, '');
            }
        });
    }

    /**
     * @param $server
     * @param $frame
     * @throws \Exception
     */
    public function onMessage($server, $frame)
    {
        try {
			logger()->info('Receive Message Event:', $frame);
			$request = $this->decode($frame->data, true);
			$handler = $request['handler'] ?? 'Default'; // set default handler
            $handler = '\App\Handlers\\' . trim($handler) . 'Handler';
            if (!class_exists($handler)) {
                throw new \Exception('Undefined class: ' . $handler);
            }

            // dispatch event to handler by handler name
            (new $handler($server, $frame, $request))->handle();
        } catch (\Exception $exception) {

            logger()->info($exception->getMessage());
        }


    }

    public function onClose($server, $fd)
    {
        $key = 'fd:' . $fd;
        app('redis')->get($key, function (Redis $redis, $result) use ($key) {
            if (!empty($result)) {
                $redis->del($result, function () use ($result) {
                    logger('Redis delete key: ' . $result);
                });
                $redis->del($key, function () use ($key) {
                    logger('Redis delete key: ' . $key);
                });
            }
        });
        logger()->info('Closed Event: connection -> ' . $key);
    }

    public function start()
    {
        $this->dispatchEvents();

		// todo:: dispay server info
        parent::start();
    }

}
