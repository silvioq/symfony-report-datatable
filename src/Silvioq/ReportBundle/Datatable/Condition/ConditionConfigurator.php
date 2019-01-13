<?php

namespace   Silvioq\ReportBundle\Datatable\Condition;

use Doctrine\Common\Annotations\Reader;
use Silvioq\ReportBundle\Annotation\Datatable\ConditionDefinition;
use Silvioq\ReportBundle\Annotation\Datatable\ConditionDataMethod;

/**
 * Datatable Configurator for Conditions
 */
class ConditionConfigurator
{
    /**
     * @var array
     */
    private $conditions;

    /**
     * @var string
     */
    private $method = null;

    const VALID_TYPES = ["like", "in", "gte", "gt", "lte", "lt", "eq"];

    /**
     * Adds one configuration
     *
     * @param string $filterName Filter name
     * @param string $columnName Column name (ex. a.column).
     * @param string|callable|ReflectionMethod Type of filter.
     *
     * @return self
     */
    public function add(string $filterName, string $columnName, $type): self
    {
        if (is_string($type)) {
            if (!in_array($type, self::VALID_TYPES)) {
                throw new \InvalidArgumentException('Type is invalid.');
            }

            $this->conditions[$filterName] = [
                'columnName' => $columnName,
                'type' => $type,
                'callback' => null
            ];
        } else if (is_callable($type) || $type instanceof \ReflectionMethod) {
            $this->conditions[$filterName] = [
                'columnName' => $columnName,
                'type' => null,
                'callback' => $type
            ];
        } else {
            throw new \InvalidArgumentException('Type must be string or callable.');
        }

        return $this;
    }

    public function setMethod(string $method):self
    {
        $this->method = $method;

        return $this;
    }

    public function getMethod():?string
    {
        return $this->method;
    }

    /**
     * Returns a configuration
     */
    public function get(string $filterName):array
    {
        if (isset($this->conditions[$filterName])) {
            return $this->conditions[$filterName];
        }

        throw new \OutOfBoundsException(sprintf("Filter %s is not configured.", $filterName));
    }

    static public function loadFromClass(Reader $reader, string $className)
    {
        new ConditionDefinition; # ¿Required?
        new ConditionDataMethod; # ¿Required?

        $definition = new self;
        $methodDetected = false;

        $class = new \ReflectionClass($className);
        foreach ($reader->getClassAnnotations($class) as $def) {
            if ($def instanceof ConditionDefinition) {
                $definition->add($def->filter, $def->column, $def->type);
            } else if ($def instanceof ConditionDataMethod) {
                $definition->setMethod($def->value);
                $methodDetected = true;
            }
        }

        foreach ($class->getMethods() as $method) {
            foreach ($reader->getMethodAnnotations($method) as $def) {
                if ($def instanceof ConditionDefinition) {
                    $definition->add($def->filter, $def->column, $method);
                }
            }
        }

        if (!$methodDetected && $class->hasMethod('getData')) {
            $definition->setMethod('getData');
        }

        return $definition;
    }
}
// vim:sw=4 ts=4 sts=4 et
