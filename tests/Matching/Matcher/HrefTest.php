<?php

namespace Hmaus\Branda\Tests\Matching\Matcher;

use Hmaus\Branda\Matching\Matcher\Href;
use Hmaus\SpasParser\ParsedRequest;
use React\Http\Request;

class HrefTest extends \PHPUnit_Framework_TestCase
{
    public function hrefTestDataProvider()
    {
        return [
            // request path; request query, parsed path template; result
            ['/foo', [], '/bar', false],
            ['/foo/bar', [], '/bar', false],
            ['/foo', [], '/foo', true],
            ['/foo', [], '/foo', true],
            ['/foo/bar', [], '/foo/bar', true],
            ['/foo/34', [], '/foo/{id}', true],
            ['/foo/34/bar', [], '/foo/{id}/bar', true],
            ['/foo/34/bar', [], '/foo/{id}/bob', false],
            ['/foo/bar', [], '/foo/{id}/bob', false],
            ['/foo', ['bar' => '12'], '/foo{?bar}', true],
            ['/foo', ['bar' => '12'], '/foo{?alice}', false],
            ['/foo', ['bar' => '12', 'alice' => 'bob'], '/foo{?bar,alice}', true],
            ['/foo', ['bar' => '12'], '/foo{?bar,alice}', false],
        ];
    }

    /**
     * @dataProvider hrefTestDataProvider
     * @param $requestPath
     * @param $requestQuery
     * @param $parsedRequestPath
     * @param $result
     */
    public function testMatcher($requestPath, $requestQuery, $parsedRequestPath, $result)
    {
        $matcher = new Href();

        $request = $this->prophesize(Request::class);
        $request
            ->getPath()
            ->willReturn($requestPath);

        $request
            ->getQuery()
            ->willReturn($requestQuery);

        $parsedRequest = $this->prophesize(ParsedRequest::class);
        $parsedRequest
            ->getHref()
            ->willReturn($parsedRequestPath);

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
        $matcher = new Href();
        $this->assertNotEmpty($matcher->getName());
    }

    public function testCanHasId()
    {
        $matcher = new Href();
        $this->assertNotEmpty($matcher->getId());
    }
}
