<?php

namespace Hmaus\Branda\Tests\Matching\Matcher;

use Hmaus\Branda\Matching\Matcher\HttpMethod;
use Hmaus\SpasParser\ParsedRequest;
use React\Http\Request;

class HttpMethodTest extends \PHPUnit_Framework_TestCase
{
    public function httpMethodTestDataProvider()
    {
        return [
            // in method; parsed method; result
            ['GET', 'PUT', false],
            ['GET', 'GET', true]
        ];
    }

    /**
     * @dataProvider httpMethodTestDataProvider
     * @param $requestMethod
     * @param $parsedRequestMethod
     * @param $result
     */
    public function testHttpMethod($requestMethod, $parsedRequestMethod, $result)
    {
        $matcher = new HttpMethod();

        $request = $this->prophesize(Request::class);
        $request
            ->getMethod()
            ->willReturn($requestMethod);

        $parsedRequest = $this->prophesize(ParsedRequest::class);
        $parsedRequest
            ->getMethod()
            ->willReturn($parsedRequestMethod);

        $this->assertSame(
            $result,
            $matcher->match($request->reveal(), $parsedRequest->reveal())
        );
    }

    public function testCanSayName()
    {
        $matcher = new HttpMethod();
        $this->assertNotEmpty($matcher->getName());
    }

    public function testCanHasId()
    {
        $matcher = new HttpMethod();
        $this->assertNotEmpty($matcher->getId());
    }
}
