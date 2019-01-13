<?php

namespace   Silvioq\ReportBundle\Datatable\Condition;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\QueryBuilder;
use Silvioq\ReportBundle\Datatable\Builder as DatatableBuilder;


/**
 * Datatable Configurator builder
 */
class ConditionBuilder
{
    /**
     * @var Reader
     */
    private $reader;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    public function configureCondition($element, DatatableBuilder $dataTable, string $className = null)
    {
        if (null === $className) {
            $className = get_class($element);
        }
        $configurator = ConditionConfigurator::loadFromClass($this->reader, $className);
        if (is_array($element)) {
            $data = $element;
        } else {
            $method = $configurator->getMethod();
            if (null === $method && method_exists($element, "getData")){
                $method = "getData";
            }
            if (null === $method) {
                throw new \LogicException(sprintf('Not defined method for retrieve data in %s.', get_class($element)));
            }

            $data = $element->$method();

            if (!is_array($data)) {
                throw new \LogicException(sprintf('Method %s::%s must return array.', get_class($element), $method));
            }
        }

        $dataTable->condition(function (QueryBuilder $queryBuilder) use($configurator, $data, $element) {
            foreach ($data as $field => $value) {
                if (null === $value || (is_string($value) && '' === trim($value)) || (is_array($value) && 0 == count($value))) {
                    continue;
                }

                $def = $configurator->get($field);
                $column = $this->sanitizeColumnName($queryBuilder, $def['columnName']);

                switch ($def['type']) {
                    case 'like':
                        $this->addConditionLike($queryBuilder, $column, $value);
                        break;
                    case 'eq':
                        $this->addConditionEq($queryBuilder, $column, $value);
                        break;
                    default:
                        if ($def['callback'] instanceof \ReflectionMethod) {
                            $def['callback']->invoke($element, $queryBuilder);
                        } else if (is_callable($def['callback'])) {
                            $def['callback']($queryBuilder);
                        } else if (is_string($type)){
                            throw new \RuntimeException(sprintf('Type %s not implemented yet.', $type));
                        } else {
                            throw new \RuntimeException('Invalid or not implmented type');
                        }
                }
            }
        });
    }

    /** @var int */
    private $paramNumber = 0;
    private function paramName():string
    {
        return 'condition_builder' .(++$this->paramNumber);
    }

    private function addConditionLike(QueryBuilder $queryBuilder, $column, $value)
    {
        if (is_array($value)) {
            $orx = $queryBuilder->expr()->orX();
            foreach ($value as $v) {
                $param = $this->paramName();
                $expr = $queryBuilder->expr()->like($column, ':' . $param);
                $orx->add($expr);
                $queryBuilder->setParameter($param, '%'.trim($v).'%');
            }
            $expr = $orx;
        } else {
            $param = $this->paramName();
            $expr = $queryBuilder->expr()->like($column, ':' . $param);
            $queryBuilder->setParameter($param, '%'.trim($value).'%');
        }
        $queryBuilder->andWhere($expr);
    }

    private function addConditionEq(QueryBuilder $queryBuilder, $column, $value)
    {
        $param = $this->paramName();
        if (is_array($value)) {
            $expr = $queryBuilder->expr()->in($column, ':'.$param);
        } else {
            $expr = $queryBuilder->expr()->eq($column, ':'.$param);
        }
        $queryBuilder->setParameter($param, $value);
        $queryBuilder->andWhere($expr);
    }

    private function sanitizeColumnName(QueryBuilder $queryBuilder, string $column):string
    {
        if (preg_match('@[a-zA-Z]+\.[a-zA-Z].*@', $column)) {
            return $column;
        }

        return $queryBuilder->getRootAlias() . '.' . $column;
    }
}
