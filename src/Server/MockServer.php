<?php

namespace Hmaus\Branda\Server;

use Hmaus\Branda\Matching\MatchingService;
use Hmaus\Spas\Parser\ParsedRequest;
use Hmaus\Spas\Parser\SpasRequest;
use Hmaus\Spas\Parser\SpasResponse;
use React\Http\Request;
use React\Http\Response;
use Symfony\Component\Console\Style\SymfonyStyle;

class MockServer
{
    /**
     * @var MatchingService
     */
    private $matchingService;
    /**
     * @var ReactProvider
     */
    private $reactProvider;
    /**
     * @var SymfonyStyle
     */
    private $io;

    public function __construct(SymfonyStyle $io, MatchingService $matchingService, ReactProvider $reactProvider)
    {
        $this->io = $io;
        $this->matchingService = $matchingService;
        $this->reactProvider = $reactProvider;
    }

    public function serve(
        string $address,
        int $port,
        array $parsedRequests
    ) {
        $this->logRoutes($parsedRequests);

        $loop = $this->reactProvider->getLoop();
        $socket = $this->reactProvider->getSocketServer($loop);
        $http = $this->reactProvider->getHttpServer($socket);

        $http->on('request', function($request, $response) use ($parsedRequests) {
            $this->onRequest(
                $request,
                $response,
                $parsedRequests
            );
        });

        $this->io->success(sprintf('Mock server running on http://%s:%d', $address, $port));
        $this->io->comment('Quit the server with CONTROL-C.');

        $socket->listen($port, $address);
        $loop->run();
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $parsedRequests
     * @codeCoverageIgnore
     */
    private function onRequest(Request $request, Response $response, array $parsedRequests)
    {
        $this->logRequest($request);

        /** @var ParsedRequest $match */
        $match = $this->match($request, $parsedRequests);

        $response->writeHead(
            $match->getResponse()->getStatusCode(),
            $match->getResponse()->getHeaders()->all()
        );

        $response->end(
            $match->getResponse()->getBody() ?? ''
        );

        $this->logMatch($match);
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
     * @param Request $request
     * @param ParsedRequest[] $parsedRequests
     * @return ParsedRequest
     */
    public function match(Request $request, array $parsedRequests)
    {
        foreach ($parsedRequests as $parsedRequest) {
            $match = $this->matchingService->match($request, $parsedRequest);

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
    public function mismatch(Request $httpRequest)
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
     * @param ParsedRequest[] $parsedRequests
     * @codeCoverageIgnore
     */
    private function logRoutes(array $parsedRequests)
    {
        foreach ($parsedRequests as $request) {
            $this->io->write('[add route] ');
            $this->io->write(sprintf('<info>%s</info> ', $request->getMethod()));
            $this->io->write(sprintf('<comment>%s</comment> ', $request->getHref()));
            $this->io->write(sprintf('<fg=blue>%s</>', $this->getLastNamePart($request->getName())));
            $this->io->newLine();
        }
        $this->io->newLine();
    }

    /**
     * @param ParsedRequest $match
     * @codeCoverageIgnore
     */
    private function logMatch(ParsedRequest $match)
    {
        /*
         * todo this will also look like it matched a mismatch
         *      [request] PUT /?hh=
         *      [matched] PUT  No match found
         */
        $this->io->write('<info>[matched]</info> ');
        $this->io->write(sprintf('<info>%s</info> ', $match->getMethod()));
        $this->io->write(sprintf('<comment>%s</comment> ', $match->getHref()));
        $this->io->write(sprintf('<fg=blue>%s</>', $this->getLastNamePart($match->getName())));
        $this->io->newLine(2);
    }

    /**
     * @param Request $request
     * @codeCoverageIgnore
     */
    private function logRequest(Request $request)
    {
        $queryString = '';
        foreach ($request->getQuery() as $key => $value) {
            $queryString .= sprintf('&%s=%s', $key, $value);
        }

        $this->io->write('[request] ');
        $this->io->write(sprintf('<info>%s</info> ', $request->getMethod()));
        if (!$queryString) {
            $this->io->write(sprintf('<comment>%s</comment> ', $request->getPath()));
        } else {
            $this->io->write(sprintf('<comment>%s?%s</comment> ', $request->getPath(), substr($queryString, 1)));
        }
        $this->io->newLine();
    }
}
