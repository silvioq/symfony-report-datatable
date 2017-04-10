<?php

namespace  Silvioq\ReportBundle\Tests;

use PHPUnit\Framework\TestCase;

use Silvioq\ReportBundle\Datatable\Builder;

class  ResultTest  extends  TestCase
{


    public  function  testQueryResult()
    {
        $emMock  = $this->createMock('\Doctrine\ORM\EntityManager',
               array('getRepository', 'getClassMetadata', 'persist', 'flush'), array(), '', false);
        $repoMock = $this->createMock( '\Doctrine\ORM\EntityRepository', ["createQueryBuilder"], array(), '', false );
        $qbMock = $this->createMock( '\Doctrine\ORM\QueryBuilder', ["select", "getQuery"], array(), '', false );
        
        $emMock->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo('Test:Table'))
            ->will($this->returnValue($repoMock))
            ;

        $repoMock->expects($this->once())
            ->method("createQueryBuilder")
            ->with($this->equalTo('a'))
            ->will($this->returnValue( $qbMock ) )
            ;
            
        $qbMock->expects($this->once())
            ->method('select')
            ->will($this->returnSelf() )
            ;
            
        $qbMock->expects($this->once())
            ->method('getQuery')
            ->will( $this->returnValue(true) )
            ;
            
        $dt = new Builder( $emMock, 
            [ "search" => [ "value" => "x" ] ] );
        $dt
            ->add( 'field1' )
            ->add( 'field2' )
            ->from( 'Test:Table', 'a' )
            ;

        $this->assertTrue( $dt->getQuery() );
        
    }
    

}
