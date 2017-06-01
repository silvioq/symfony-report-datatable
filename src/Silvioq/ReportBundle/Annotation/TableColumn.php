<?php

namespace Silvioq\ReportBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
class TableColumn
{
    public $name;
    public $label;
    public $getter;
    public $order = 0;
}
