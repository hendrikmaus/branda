<?php

namespace Hmaus\Branda\Matching;

use Hmaus\SpasParser\ParsedRequest;
use React\Http\Request;

class MatchingService
{
    /**
     * @var Matcher[]
     */
    private $matchers;

    /**
     * @param Request $request
     * @param ParsedRequest $parsedRequest
     * @return bool
     */
    public function match(Request $request, ParsedRequest $parsedRequest)
    {
        foreach ($this->matchers as $matcher) {
            if (!$matcher->match($request, $parsedRequest)) {
                return false;
            }
        }

        return true;
    }

    public function addMatcher(Matcher $matcher)
    {
        $this->matchers[] = $matcher;
    }
}