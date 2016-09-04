<?php

namespace Hmaus\Branda\Tests\ApiDescription;

use Hmaus\Branda\ApiDescription\ApiBlueprint;
use Hmaus\DrafterPhp\Drafter;
use Hmaus\Spas\Parser\Apib\ApibParsedRequestsProvider;
use Hmaus\SpasParser\ParsedRequest;

class ApiBlueprintTest extends \PHPUnit_Framework_TestCase
{
    public function testReturnsParsedRequests()
    {
        $drafter = new Drafter(__DIR__.'/../../vendor/bin/drafter');
        $parser = new ApibParsedRequestsProvider();
        $reader = new ApiBlueprint($drafter, $parser);

        $description = __DIR__.'/../fixtures/Real World API.md';
        $parsedRequests = $reader->parse($description);

        foreach ($parsedRequests as $parsedRequest) {
            $this->assertInstanceOf(ParsedRequest::class, $parsedRequest);
        }
    }
}
