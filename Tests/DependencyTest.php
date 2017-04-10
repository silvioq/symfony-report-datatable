<?php

namespace  Silvioq\ReportBundle\Tests;

use Silvioq\ReportBundle\DependencyInjection\SilvioqReportExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

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

    public  function  testDependencies( )
    {
        $this->load();
        $this->assertContainerBuilderHasService( 'silvioq.report.datatable' );
    }

}
