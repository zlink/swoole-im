<?php

try {
	// load .env file config
    Dotenv\Dotenv::create(ROOT_PATH)->load();
} catch (\Dotenv\Exception\ValidationException $exception) {
    //
}

$app = new App\Components\Services\WebSocketServer(
    new App\Application(ROOT_PATH)
);

return $app;
