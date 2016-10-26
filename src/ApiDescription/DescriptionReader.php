<?php

namespace Hmaus\Branda\ApiDescription;

use Hmaus\Spas\Parser\ParsedRequest;

interface DescriptionReader
{
    /**
     * @param mixed $description Raw api description
     * @return ParsedRequest[]
     */
    public function parse($description);
}
