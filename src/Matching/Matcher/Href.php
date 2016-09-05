<?php

namespace Hmaus\Branda\Matching\Matcher;

use Hmaus\Branda\Matching\Matcher;
use Hmaus\SpasParser\ParsedRequest;
use React\Http\Request;
use Rize\UriTemplate\UriTemplate;

class Href implements Matcher
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
        $fromPath = $this->mergePathAndQuery($request);
        $toPath = $parsedRequest->getHref();

        if ($fromPath === $toPath) {
            return true;
        }

        $uriTemplate = new UriTemplate();

        $params = @$uriTemplate->extract(
            $toPath,
            $fromPath
        );

        if (count($params) === 0) {
            return false;
        }

        foreach ($params as $paramValue) {
            if ($paramValue === null || $paramValue === '') {
                return false;
            }
        }

        if (!$this->hasQuery($toPath)) {
            if ($uriTemplate->expand($toPath, $params) !== $fromPath) {
                return false;
            }
        }

        return true;
    }

    private function hasQuery($uriTemplate)
    {
        return strpos($uriTemplate, '?') !== false;
    }

    private function mergePathAndQuery(Request $request)
    {
        $path = $request->getPath();
        $query = $request->getQuery();

        if (!$query) {
            return $path;
        }

        $queryString = '';
        foreach ($query as $key => $value) {
            $queryString .= sprintf(
                '&%s=%s',
                $key,
                $value
            );
        }

        $merged = sprintf(
            '%s?%s',
            $path,
            substr($queryString, 1)
        );

        return $merged;
    }

    /**
     * Return id of the matcher, e.g. 'http_method'
     *
     * @return string
     */
    public function getId()
    {
        return 'href';
    }

    /**
     * Return human readable name of the matcher, e.g. 'HTTP Method Matcher'
     *
     * @return string
     */
    public function getName()
    {
        return 'Href Matcher';
    }
}