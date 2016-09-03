<?php

namespace Hmaus\Branda\Matching\Matcher;

use Hmaus\Branda\Matching\Matcher;
use Hmaus\SpasParser\ParsedRequest;
use React\Http\Request;
use Symfony\Component\HttpFoundation\HeaderBag;

class HttpHeaders implements Matcher
{

    /**
     * Try to match the incoming request to the api description request
     *
     * @param Request $request
     * @param ParsedRequest $parsedRequest
     * @return bool
     */
    public function match(Request $request, ParsedRequest $parsedRequest)
    {
        $requestHeaders = new HeaderBag($request->getHeaders());
        $parsedRequestHeaders = $parsedRequest->getHeaders();

        if ($requestHeaders->count() === 0 && $parsedRequestHeaders->count() === 0) {
            return true;
        }

        if ($parsedRequestHeaders->count() === 0) {
            return true;
        }

        foreach ($parsedRequestHeaders as $key => $value) {
            if (!$requestHeaders->has($key)) {
                return false;
            }

            if ($requestHeaders->get($key) !== array_shift($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return id of the matcher, e.g. 'http_method'
     *
     * @return string
     */
    public function getId()
    {
        return 'http_headers';
    }

    /**
     * Return human readable name of the matcher, e.g. 'HTTP Method Matcher'
     *
     * @return string
     */
    public function getName()
    {
        return 'HTTP Header Matcher';
    }
}