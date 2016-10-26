<?php

namespace Hmaus\Branda\Matching;

use Hmaus\Spas\Parser\ParsedRequest;
use React\Http\Request;

interface Matcher
{
    /**
     * Try to match the incoming request to the api description request
     *
     * @param Request $request
     * @param ParsedRequest $parsedRequest
     * @return bool
     */
    public function match(Request $request, ParsedRequest $parsedRequest);

    /**
     * Return id of the matcher, e.g. 'http_method'
     *
     * @return string
     */
    public function getId();

    /**
     * Return human readable name of the matcher, e.g. 'HTTP Method Matcher'
     *
     * @return string
     */
    public function getName();
}
