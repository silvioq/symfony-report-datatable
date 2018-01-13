<?php

namespace  Silvioq\ReportBundle\Tests;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Silvioq\ReportBundle\DependencyInjection\Compiler\AddTableLoaderPass;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Clase para chequear la inicialización del servicio en symfony
 * @see https://github.com/matthiasnoback/SymfonyDependencyInjectionTest#symfonydependencyinjectiontest
 */
class  CompilerPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AddTableLoaderPass());
    }

    public  function  testCompilerPass( )
    {
        $collectingService = new Definition();
        $this->setDefinition('silvioq.report.table', $collectingService);

        $collectedService = new Definition();
        $collectedService->addTag('silvioq.table.loader', [ 'priority' => 10 ]);
        $this->setDefinition('collected', $collectedService);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'silvioq.report.table',
            'addLoader',
            [ new Reference('collected'), 10 ]
        );
    }

}
