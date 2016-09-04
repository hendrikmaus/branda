<?php

namespace Hmaus\Branda\ApiDescription;

use Hmaus\SpasParser\ParsedRequest;

interface DescriptionReader
{
    /**
     * @param mixed $description Raw api description
     * @return ParsedRequest[]
     */
    public function parse($description);
}