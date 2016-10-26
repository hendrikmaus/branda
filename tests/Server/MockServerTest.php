<?php

namespace Hmaus\Branda\Tests\Server;

use Hmaus\Branda\Matching\MatchingService;
use Hmaus\Branda\Server\MockServer;
use Hmaus\Branda\Server\ReactProvider;
use Hmaus\Spas\Parser\ParsedRequest;
use Hmaus\Spas\Parser\SpasRequest;
use Hmaus\Spas\Parser\SpasResponse;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use React\EventLoop\LoopInterface;
use React\Http\Request;
use React\Http\Server as HttpServer;
use React\Socket\Server as SocketServer;
use Symfony\Component\Console\Style\SymfonyStyle;

class MockServerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MockServer
     */
    private $server;

    /**
     * @var SymfonyStyle|ObjectProphecy
     */
    private $io;

    /**
     * @var MatchingService|ObjectProphecy
     */
    private $matcher;

    /**
     * @var LoopInterface|ObjectProphecy
     */
    private $reactLoop;

    /**
     * @var SocketServer|ObjectProphecy
     */
    private $reactSocketServer;

    /**
     * @var HttpServer|ObjectProphecy
     */
    private $reactHttpServer;

    /**
     * @var ReactProvider|ObjectProphecy
     */
    private $reactProvider;

    /**
     * @var string
     */
    private $address;

    /**
     * @var int
     */
    private $port;

    protected function setUp()
    {
        $this->io = $this->prophesize(SymfonyStyle::class);
        $this->matcher = $this->prophesize(MatchingService::class);
        $this->reactLoop = $this->prophesize(LoopInterface::class);
        $this->reactSocketServer = $this->prophesize(SocketServer::class);
        $this->reactHttpServer = $this->prophesize(HttpServer::class);
        $this->reactProvider = $this->prophesize(ReactProvider::class);
        $this->address = '127.0.0.1';
        $this->port = 8000;

        $this->reactProvider
            ->getLoop()
            ->willReturn(
                $this->reactLoop->reveal()
            );

        $this->reactProvider
            ->getSocketServer(Argument::any())
            ->willReturn(
                $this->reactSocketServer->reveal()
            );

        $this->reactProvider
            ->getHttpServer(Argument::any())
            ->willReturn(
                $this->reactHttpServer->reveal()
            );

        $this->server = new MockServer(
            $this->io->reveal(),
            $this->matcher->reveal(),
            $this->reactProvider->reveal()
        );
    }

    private function setUpIo()
    {
        $this->io
            ->comment(Argument::any())
            ->shouldBeCalled();

        $this->io
            ->success(Argument::any())
            ->shouldBeCalled();

        $this->io
            ->newLine()
            ->shouldBeCalled();

        $this->io
            ->write(Argument::any())
            ->shouldBeCalled();
    }

    public function testParsedRequestNameCanBeEmpty()
    {
        $this->setUpIo();
        $this->io
            ->write(Argument::exact('<fg=blue></>'))
            ->shouldBeCalledTimes(1);

        $this->serve([$this->getParsedRequest()]);
    }

    public function testParsedRequestNameCanBeOneSegmentOnly()
    {
        $parsedRequest = $this->getParsedRequest();
        $parsedRequest->setName('Hello World');

        $this->setUpIo();
        $this->io
            ->write(Argument::exact('<fg=blue>Hello World</>'))
            ->shouldBeCalledTimes(1);

        $this->serve([$parsedRequest]);
    }

    public function testParsedRequestNamesLastSegmentWillBePrinted()
    {
        $parsedRequest = $this->getParsedRequest();
        $parsedRequest->setName('Hello > World');

        $this->setUpIo();
        $this->io
            ->write(Argument::exact('<fg=blue>World</>'))
            ->shouldBeCalledTimes(1);

        $this->serve([$parsedRequest]);
    }

    public function testMatchingCanMismatch()
    {
        $request = $this->prophesize(Request::class);
        $mismatch = $this->server->match($request->reveal(), []);

        $this->assertInstanceOf(ParsedRequest::class, $mismatch);
        $this->assertContains('404', $mismatch->getResponse()->getBody());
    }

    public function testMatchingCanContinueMatchingUntilMatch()
    {
        $this
            ->matcher
            ->match(Argument::cetera())
            ->willReturn(false, true)
            ->shouldBeCalledTimes(2)
        ;

        $request = $this->prophesize(Request::class);
        $match = $this->server->match($request->reveal(), [$this->getParsedRequest(), $this->getParsedRequest()]);

        $this->assertInstanceOf(ParsedRequest::class, $match);
    }

    private function getParsedRequest()
    {
        $parsedResponse = new SpasResponse();
        $parsedRequest = new SpasRequest();
        $parsedRequest->setResponse($parsedResponse);

        return $parsedRequest;
    }

    /**
     * @param $parsedRequests
     */
    private function serve($parsedRequests)
    {
        $this->server->serve(
            $this->address,
            $this->port,
            $parsedRequests
        );
    }
}
