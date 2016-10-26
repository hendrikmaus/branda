<?php

namespace Hmaus\Branda\ApiDescription;

use Hmaus\DrafterPhp\DrafterInterface;
use Hmaus\Spas\Parser\ParsedRequest;
use Hmaus\Spas\Parser\Parser;

class ApiBlueprint implements DescriptionReader
{
    /**
     * @var DrafterInterface
     */
    private $drafter;

    /**
     * @var Parser
     */
    private $requestProvider;

    public function __construct(DrafterInterface $drafter, Parser $requestProvider)
    {
        $this->drafter = $drafter;
        $this->requestProvider = $requestProvider;
    }

    /**
     * @param string $description Absolute path to api description
     * @return ParsedRequest[]
     */
    public function parse($description)
    {
        $rawParseResult = $this->drafter
            ->input($description)
            ->format('json')
            ->type('refract')
            ->run();

        return $this->requestProvider->parse(
            json_decode($rawParseResult, true)
        );
    }
}
