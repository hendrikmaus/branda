<?php

namespace Hmaus\Branda\Tests\Server;

use Hmaus\Branda\Server\ReactProvider;
use React\EventLoop\LoopInterface;
use React\Socket\Server as SocketServer;
use React\Http\Server as HttpServer;

class ReactProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testCanProvideReactComponents()
    {
        $provider = new ReactProvider();

        $loop = $provider->getLoop();
        $this->assertInstanceOf(LoopInterface::class, $loop);

        $socket = $provider->getSocketServer($loop);
        $this->assertInstanceOf(SocketServer::class, $socket);

        $http = $provider->getHttpServer($socket);
        $this->assertInstanceOf(HttpServer::class, $http);
    }
}
