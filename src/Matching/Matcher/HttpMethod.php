<?php

namespace Hmaus\Branda\Matching\Matcher;

use Hmaus\Branda\Matching\Matcher;
use Hmaus\Spas\Parser\ParsedRequest;
use React\Http\Request;

class HttpMethod implements Matcher
{
    /**
     * Return `true` if both HTTP methods match
     *
     * @param Request $request
     * @param ParsedRequest $parsedRequest
     * @return bool
     */
    public function match(Request $request, ParsedRequest $parsedRequest)
    {
        return $request->getMethod() === $parsedRequest->getMethod();
    }

    /**
     * Return id of the matcher, e.g. 'http_method'
     *
     * @return string
     */
    public function getId() : string
    {
        return 'http_method';
    }

    /**
     * Return human readable name of the matcher, e.g. 'HTTP Method Matcher'
     *
     * @return string
     */
    public function getName() : string
    {
        return 'HTTP Method Matcher';
    }
}
