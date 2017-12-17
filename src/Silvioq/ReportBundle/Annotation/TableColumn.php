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

    /** @var int|float */
    public $order = INF;

    /**
     * Flag for expand ManyToMany associations
     *
     * @var bool
     */
    public $expandMTM;

    /**
     * function name for retrieve all elements for expansion
     *
     * @var string
     */
    public $expandFinder;

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
            'expandMTM' => false,
            'expandFinder' => 'findAll',
        ]);

        $options = $resolver->resolve($array);
        $this->name = $options['name'];
        $this->label = $options['label'];
        $this->getter = $options['getter'];
        $this->order = $options['order'];
        $this->expandMTM = $options['expandMTM'];
        $this->expandFinder = $options['expandFinder'];
    }
}
// vim:sw=4 ts=4 sts=4 et
