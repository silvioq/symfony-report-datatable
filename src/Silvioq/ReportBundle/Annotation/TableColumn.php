<?php

namespace Silvioq\ReportBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @Annotation
 */
class TableColumn
{
    public $name;
    public $label;
    public $getter;

    /** @var int */
    public $order = INF;

    /**
     * @var int
     */
    public $key;

    public function __construct(array $array =[])
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults( [
            'name' => null,
            'label' => null,
            'getter' => null,
            'order' => INF,
        ]);

        $options = $resolver->resolve($array);
        $this->name = $options['name'];
        $this->label = $options['label'];
        $this->getter = $options['getter'];
        $this->order = $options['order'];
    }
}
// vim:sw=4 ts=4 sts=4 et
