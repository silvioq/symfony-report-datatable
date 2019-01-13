<?php

namespace Silvioq\ReportBundle\Annotation\DataTable;

use Doctrine\Common\Annotations\Annotation;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class ConditionDefinition
{
    /**
     * @Required
     * @var string
     */
    public $column;

    /**
     * @var string
     */
    public $filter;

    /**
     * @var string
     * @Enum({"like", "in", "gte", "gt", "lte", "lt", "eq", "callback"})
     */
    public $type = "like";
}
