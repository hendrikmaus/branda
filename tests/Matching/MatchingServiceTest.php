<?php

namespace Hmaus\Branda\Tests\Matching;

use Hmaus\Branda\Matching\Matcher\HttpMethod;
use Hmaus\Branda\Matching\MatchingService;
use Hmaus\Spas\Parser\SpasRequest;
use Prophecy\Argument;
use React\Http\Request;

class MatchingServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testMatcherReturnsMismatch()
    {
        $matcher = $this->prophesize(HttpMethod::class);
        $matcher
            ->match(Argument::cetera())
            ->willReturn(false);

        $matcherService = new MatchingService();
        $matcherService->addMatcher($matcher->reveal());

        $request = $this->prophesize(Request::class);
        $parsedRequest = $this->prophesize(SpasRequest::class);

        $match = $matcherService->match(
            $request->reveal(),
            $parsedRequest->reveal()
        );

        $this->assertFalse($match);
    }

    public function testMatcherReturnsMatch()
    {
        $matcher = $this->prophesize(HttpMethod::class);
        $matcher
            ->match(Argument::cetera())
            ->willReturn(true);

        $matcherService = new MatchingService();
        $matcherService->addMatcher($matcher->reveal());

        $request = $this->prophesize(Request::class);
        $parsedRequest = $this->prophesize(SpasRequest::class);

        $match = $matcherService->match(
            $request->reveal(),
            $parsedRequest->reveal()
        );

        $this->assertTrue($match);
    }
}
