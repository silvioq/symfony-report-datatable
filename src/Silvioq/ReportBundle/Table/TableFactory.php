<?php

namespace  Silvioq\ReportBundle\Table;

use Silvioq\ReportBundle\Annotation\TableColumn;
use Silvioq\ReportBundle\Table\Table;


class TableFactory
{
    /**
     * @var DefinitionLoaderInterface[]
     */
    private $loaders = [];

    public function addLoader(DefinitionLoaderInterface $definition, int $priority):self
    {
        $this->loaders[] = [
            'loader' => $definition,
            'priority' => $priority,
        ];

        return $this;
    }

    /**
     * @return Table
     */    
    public function build($entityClass, array $scalarizerOptions = []):Table
    {
        if (count($this->loaders) === 0) {
            throw new \LogicException('TableFactory not configured');
        }

        usort($this->loaders, function($a,$b) {
            return $b['priority'] - $a['priority'];
        });

        $table = new Table($entityClass, $scalarizerOptions);

        foreach ($this->loaders as $loader) {
            /** @var DefinitionLoaderInterface $loader */
            $ret = $loader['loader']->addColumns($table);
            if (DefinitionLoaderInterface::COMPLETE === $ret)
                break;
        }

        return $table;
    }

}
// vim:sw=4 ts=4 sts=4 et
