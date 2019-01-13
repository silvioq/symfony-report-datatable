<?php

namespace  Silvioq\ReportBundle\Tests\Datatable\Condition;

use Silvioq\ReportBundle\Datatable\Condition\ConditionConfigurator;

use PHPUnit\Framework\TestCase;

class ConditionConfiguratorTest extends TestCase
{
    public function testAddConditionConfiguration()
    {
        $cond = new ConditionConfigurator();
        $cond->add("field", "a.column", "eq");
        $this->assertTrue(is_array($cond->get("field")));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidType()
    {
        $cond = new ConditionConfigurator();
        $cond->add("field", "a.column", "invalid type");
    }

    /**
     * @expectedException \OutOfBoundsException
     */
    public function testInvalidGet()
    {
        $cond = new ConditionConfigurator();
        $cond->add("field", "a.column", "eq");
        $cond->get("no field");
    }

    public function testAnnotation()
    {
        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        $cond = ConditionConfigurator::loadFromClass($reader, Mock\MockClass::class);

        $column = $cond->get("column");
        $this->assertSame('a.column', $column['columnName']);
        $this->assertSame('like', $column['type']);
        $this->assertNull($column['callback']);

        $column = $cond->get("column2");
        $this->assertSame('a.column2', $column['columnName']);
        $this->assertSame('eq', $column['type']);
        $this->assertNull($column['callback']);

        $column = $cond->get("column3");
        $this->assertSame('a.column3', $column['columnName']);
        $this->assertNull( $column['type']);
        $this->assertNotNull($column['callback']);
        $this->assertInstanceOf(\ReflectionMethod::class, $column['callback']);

        $this->assertSame('getArrayData', $cond->getMethod());

        $cond = ConditionConfigurator::loadFromClass($reader, Mock\MockDefaultClass::class);
        $this->assertSame('getData', $cond->getMethod());
    }
}
