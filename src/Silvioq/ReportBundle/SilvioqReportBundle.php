<?php

namespace Silvioq\ReportBundle;

use Silvioq\ReportBundle\DependencyInjection\Compiler\AddTableLoaderPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SilvioqReportBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new AddTableLoaderPass());
    }
}
