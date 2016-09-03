<?php

namespace Hmaus\Branda\Matching\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @codeCoverageIgnore
 */
class MatcherCompilerPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $matcherServiceId = 'hmaus.branda.matching.matching_service';

        if (!$container->has($matcherServiceId)) {
            return;
        }

        $definition = $container->findDefinition($matcherServiceId);
        $taggedServices = $container->findTaggedServiceIds('hmaus.branda.tag.matcher');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addMatcher', [new Reference($id)]);
        }
    }
}