<?php

namespace  Silvioq\ReportBundle\Tests\Datatable\Condition;

use Silvioq\ReportBundle\Datatable\Condition\ConditionBuilder;
use Silvioq\ReportBundle\Datatable\Builder as DatatableBuilder;
use Doctrine\ORM\QueryBuilder;

use PHPUnit\Framework\TestCase;

class ConditionBuilderTest extends TestCase
{
    public function testBuilder()
    {
        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        $data = new Mock\MockClass(['column' => 'one']);
        $mockDt = $this->getMockBuilder(DatatableBuilder::class)
                ->disableOriginalConstructor()
                ->getMock();
        $mockQb = $this->getMockBuilder(QueryBuilder::class)
                ->disableOriginalConstructor()
                ->getMock();

        $mockExpr = $this->getMockBuilder(\Doctrine\ORM\Query\Expr::class)
                ->disableOriginalConstructor()
                ->getMock();

        $function = null;
        $mockDt->expects($this->once())
            ->method('condition')
            ->with($this->callback(function($data) use (&$function) {
                $function = $data;
                return is_callable($function);
            }));
            ;

        $mockQb->expects($this->exactly(1))
            ->method('expr')
            ->with()
            ->willReturn($mockExpr);

        $mockExpr->expects($this->once())
            ->method('like')
            ->with('a.column', ':condition_builder1')
            ;

        $builder = new ConditionBuilder($reader);
        $builder->runCondition($data, $mockDt);

        $function($mockQb);
    }

    public function testBuilderRootAlias()
    {
        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        $data = new Mock\MockClass(['column4' => 'one']);
        $mockDt = $this->getMockBuilder(DatatableBuilder::class)
                ->disableOriginalConstructor()
                ->getMock();
        $mockQb = $this->getMockBuilder(QueryBuilder::class)
                ->disableOriginalConstructor()
                ->getMock();

        $mockExpr = $this->getMockBuilder(\Doctrine\ORM\Query\Expr::class)
                ->disableOriginalConstructor()
                ->getMock();

        $function = null;
        $mockDt->expects($this->once())
            ->method('condition')
            ->with($this->callback(function($data) use (&$function) {
                $function = $data;
                return is_callable($function);
            }));
            ;

        $mockQb->expects($this->once())
            ->method('expr')
            ->with()
            ->willReturn($mockExpr);

        $mockQb->expects($this->once())
            ->method('getRootAlias')
            ->with()
            ->willReturn('x');

        $mockExpr->expects($this->once())
            ->method('eq')
            ->with('x.column4', ':condition_builder1')
            ->will($this->returnSelf())
            ;
        $mockQb->expects($this->once())
            ->method('setParameter')
            ->with('condition_builder1', 'one')
            ->will($this->returnSelf())
            ;

        $builder = new ConditionBuilder($reader);
        $builder->runCondition($data, $mockDt);

        $function($mockQb);
    }
}
