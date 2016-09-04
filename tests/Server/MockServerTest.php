<?php

namespace Hmaus\Branda\Tests\Server;

use Hmaus\Branda\Matching\MatchingService;
use Hmaus\Branda\Server\MockServer;
use Hmaus\Branda\Server\ReactProvider;
use Prophecy\Argument;
use React\EventLoop\LoopInterface;
use React\Http\Server as HttpServer;
use React\Socket\Server as SocketServer;
use Symfony\Component\Console\Style\SymfonyStyle;

class MockServerTest extends \PHPUnit_Framework_TestCase
{
    public function testHappyCase()
    {
        $server = new MockServer();

        $address = '127.0.0.1';
        $port = 8000;
        $io = $this->prophesize(SymfonyStyle::class);
        $parsedRequests = [];
        $matcher = $this->prophesize(MatchingService::class);

        $reactLoop = $this->prophesize(LoopInterface::class);
        $reactSocketServer = $this->prophesize(SocketServer::class);
        $reactHttpServer = $this->prophesize(HttpServer::class);
        $reactProvider = $this->prophesize(ReactProvider::class);

        $reactProvider
            ->getLoop()
            ->willReturn(
                $reactLoop->reveal()
            );

        $reactProvider
            ->getSocketServer(Argument::any())
            ->willReturn(
                $reactSocketServer->reveal()
            );

        $reactProvider
            ->getHttpServer(Argument::any())
            ->willReturn(
                $reactHttpServer->reveal()
            );

        $server->serve(
            $address,
            $port,
            $io->reveal(),
            $parsedRequests,
            $matcher->reveal(),
            $reactProvider->reveal()
        );
    }
}
