<?php

namespace Hmaus\Branda\Server;

use Hmaus\Branda\Matching\MatchingService;
use Hmaus\SpasParser\ParsedRequest;
use Hmaus\SpasParser\SpasRequest;
use Hmaus\SpasParser\SpasResponse;
use React\EventLoop\Factory;
use React\Http\Request;
use React\Http\Response;
use React\Socket\Server;
use Symfony\Component\Console\Style\SymfonyStyle;

class MockServer
{
    public function serve(
        string $address,
        int $port,
        SymfonyStyle $io,
        array $parsedRequests,
        MatchingService $matcher
    ) {
        $this->logRoutes($io, $parsedRequests);

        /**
         * @param Request $request
         * @param Response $response
         */
        $app = function ($request, $response) use ($parsedRequests, $io, $matcher) {
            $this->route($request, $response, $io, $this, $matcher, $parsedRequests);
        };

        $loop = Factory::create();
        $socket = new Server($loop);
        $http = new \React\Http\Server($socket, $loop);

        $http->on('request', $app);

        $io->success(sprintf('Mock server running on http://%s:%d', $address, $port));
        $io->comment('Quit the server with CONTROL-C.');

        $socket->listen($port, $address);
        $loop->run();
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param SymfonyStyle $io
     * @param MockServer $self
     * @param MatchingService $matcher
     * @param ParsedRequest[] $parsedRequests
     */
    private function route(
        Request $request,
        Response $response,
        SymfonyStyle $io,
        MockServer $self,
        MatchingService $matcher,
        array $parsedRequests
    ) {
        $queryString = '';
        foreach ($request->getQuery() as $key => $value) {
            $queryString .= sprintf('&%s=%s', $key, $value);
        }

        $io->write('[request] ');
        $io->write(sprintf('<info>%s</info> ', $request->getMethod()));
        if (!$queryString) {
            $io->write(sprintf('<comment>%s</comment> ', $request->getPath()));
        } else {
            $io->write(sprintf('<comment>%s?%s</comment> ', $request->getPath(), substr($queryString, 1)));
        }
        $io->newLine();

        /** @var ParsedRequest $match */
        $match = $self->match($matcher, $request, $parsedRequests);

        $response->writeHead(
            $match->getResponse()->getStatusCode(),
            $match->getResponse()->getHeaders()->all()
        );

        $response->end(
            $match->getResponse()->getBody() ?? ''
        );

        /*
         * todo this will also look like it matched a mismatch
         *      [request] PUT /?hh=
         *      [matched] PUT  No match found
         */
        $io->write('<info>[matched]</info> ');
        $io->write(sprintf('<info>%s</info> ', $match->getMethod()));
        $io->write(sprintf('<comment>%s</comment> ', $match->getHref()));
        $io->write(sprintf('<fg=blue>%s</>', $self->getLastNamePart($match->getName())));
        $io->newLine(2);
    }


    /**
     * @param string $name
     * @param string $delimiter
     * @return string
     */
    private function getLastNamePart(string $name, $delimiter = ' > ')
    {
        if (!$name) {
            return '';
        }

        $parts = explode($delimiter, $name);

        return array_pop($parts);
    }

    /**
     * @param MatchingService $matcher
     * @param Request $request
     * @param ParsedRequest[] $parsedRequests
     * @return ParsedRequest
     */
    private function match(MatchingService $matcher, Request $request, array $parsedRequests)
    {
        foreach ($parsedRequests as $parsedRequest) {
            $match = $matcher->match($request, $parsedRequest);

            if (!$match) {
                continue;
            }

            return $parsedRequest;
        }

        return $this->mismatch($request);
    }

    /**
     * @param Request $httpRequest
     * @return ParsedRequest
     */
    private function mismatch(Request $httpRequest)
    {
        $response = new SpasResponse();
        $response->setStatusCode(404);
        $response->setBody("404 - No matching resource in API description\n");
        $response->getHeaders()->set('Content-Type', 'text/plain');

        $request = new SpasRequest();
        $request->setResponse($response);
        $request->setMethod($httpRequest->getMethod());
        $request->setName('No match found');

        return $request;
    }

    /**
     * @param SymfonyStyle $io
     * @param array $parsedRequests
     */
    private function logRoutes(SymfonyStyle $io, array $parsedRequests)
    {
        foreach ($parsedRequests as $request) {
            $io->write('[add route] ');
            $io->write(sprintf('<info>%s</info> ', $request->getMethod()));
            $io->write(sprintf('<comment>%s</comment> ', $request->getHref()));
            $io->write(sprintf('<fg=blue>%s</>', $this->getLastNamePart($request->getName())));
            $io->newLine();
        }
        $io->newLine();
    }
}