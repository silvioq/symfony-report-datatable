<?php

namespace Silvioq\ReportBundle\Annotation\DataTable;

use Doctrine\Common\Annotations\Annotation;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class ConditionDataMethod
{
    /** @var string */
    public $value;
}
