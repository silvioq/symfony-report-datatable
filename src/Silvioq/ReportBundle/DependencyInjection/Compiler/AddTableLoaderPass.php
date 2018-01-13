<?php

namespace Silvioq\ReportBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AddTableLoaderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('silvioq.report.table')) {
            return;
        }

        $definition = $container->findDefinition('silvioq.report.table');
 
        $taggedServices = $container->findTaggedServiceIds('silvioq.table.loader');
        foreach( $taggedServices as $id => $tags ) {
            foreach( $tags as $tag ) {
                $definition->addMethodCall('addLoader', [ new Reference($id), $tag['priority'] ] );
            }
        }
    }
}
