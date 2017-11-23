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

    /** @var int */
    public $order = 0;

    /**
     * TODO: Ignore in annotations
     *
     * @var int
     */
    public $key;
}
// vim:sw=4 ts=4 sts=4 et
