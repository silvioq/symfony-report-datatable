<?php

namespace  Silvioq\ReportBundle\Tests;

use Silvioq\ReportBundle\DependencyInjection\SilvioqReportExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\DependencyInjection\Reference;
use Silvioq\ReportBundle\Table\TableFactory;
use Silvioq\ReportBundle\Table\DefinitionLoader\DoctrineDefinitionLoader;
use Silvioq\ReportBundle\Datatable\DatatableFactory;
use Silvioq\ReportBundle\Datatable\Builder;

/**
 * Clase para chequear la inicialización del servicio en symfony
 * @see https://github.com/matthiasnoback/SymfonyDependencyInjectionTest#symfonydependencyinjectiontest
 */
class  DependencyTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions()
    {
        return [
            new SilvioqReportExtension(),
        ];
    }

    public  function  testDependencies()
    {
        $this->load();
        $this->assertContainerBuilderHasService( 'silvioq.report.datatable', DatatableFactory::class );
        $this->assertContainerBuilderHasService( 'silvioq.report.dt', Builder::class );
        $this->assertContainerBuilderHasService( 'silvioq.report.table', TableFactory::class );
        $this->assertContainerBuilderHasService( 'silvioq.report.table.doctrineloader', DoctrineDefinitionLoader::class);
        $this->assertContainerBuilderHasAlias('datatable', "silvioq.report.dt");
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'silvioq.report.table.doctrineloader',
            0, new Reference('doctrine.orm.entity_manager'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'silvioq.report.table.doctrineloader',
            1, new Reference('annotation_reader'));

        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            'silvioq.report.table.doctrineloader',
            'silvioq.table.loader',
            [ 'priority' => 0 ]
        );
    }
}
