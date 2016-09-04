<?php

namespace Hmaus\Branda\Server;

use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Http\Server as HttpServer;
use React\Socket\Server as SocketServer;
use React\Socket\ServerInterface as SocketServerInterface;

class ReactProvider
{
    public function getLoop()
    {
        return Factory::create();
    }

    public function getSocketServer(LoopInterface $loop)
    {
        return new SocketServer($loop);
    }

    public function getHttpServer(SocketServerInterface $socketServer)
    {
        return new HttpServer($socketServer);
    }
}