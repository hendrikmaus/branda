<?php

namespace Hmaus\Branda\Tests\Matching\Matcher;

use Hmaus\Branda\Matching\Matcher\HttpHeaders;
use Hmaus\Spas\Parser\ParsedRequest;
use React\Http\Request;
use Symfony\Component\HttpFoundation\HeaderBag;

class HttpHeadersTest extends \PHPUnit_Framework_TestCase
{
    public function httpHeadersTestDataProvider()
    {
        return [
            [
                [],     // request header
                [],     // parsed request header
                true,   // result
            ],
            [
                ['foo' => 'bar'],
                [],
                true,
            ],
            [
                ['foo' => 'bar'],
                ['alice' => 'bob'],
                false,
            ],
            [
                ['foo' => 'bar'],
                ['foo' => 'foo'],
                false,
            ],
            [
                ['foo' => 'bar'],
                ['foo' => 'bar'],
                true,
            ],
            [
                ['foo' => 'bar', 'bob' => 'alice'],
                ['foo' => 'bar', 'bob' => 'alice'],
                true,
            ],
            [
                ['foo' => 'bar', 'bob' => 'alice'],
                ['foo' => 'bar'],
                true,
            ],
            [
                ['foo' => 'bar'],
                ['foo' => 'bar', 'bob' => 'alice'],
                false,
            ],
        ];
    }

    /**
     * @dataProvider httpHeadersTestDataProvider
     * @param $requestHeaders
     * @param $parsedRequestHeaders
     * @param $result
     */
    public function testHeaders($requestHeaders, $parsedRequestHeaders, $result)
    {
        $matcher = new HttpHeaders();

        $request = $this->prophesize(Request::class);
        $request
            ->getHeaders()
            ->willReturn($requestHeaders);

        $parsedRequest = $this->prophesize(ParsedRequest::class);
        $parsedRequest
            ->getHeaders()
            ->willReturn(new HeaderBag($parsedRequestHeaders));

        $this->assertSame(
            $result,
            $matcher->match(
                $request->reveal(),
                $parsedRequest->reveal()
            )
        );
    }

    public function testCanSayName()
    {
        $matcher = new HttpHeaders();
        $this->assertNotEmpty($matcher->getName());
    }

    public function testCanHasId()
    {
        $matcher = new HttpHeaders();
        $this->assertNotEmpty($matcher->getId());
    }
}
