<?php

namespace  Silvioq\ReportBundle\Tests;

use PHPUnit\Framework\TestCase;
use Doctrine\ORM\QueryBuilder;
use Silvioq\ReportBundle\Datatable\Builder;

class  WheresTest  extends  TestCase
{
    /**
     * @covers Builder::where
     * @covers Builder::whereOnFiltered
     * @covers Builder::addWheresToCB
     * @dataProvider whereFunctions
     */
    function testAndWhereOnQueryBuilder($functionName)
    {     
        $emMock  = $this->createMock('\Doctrine\ORM\EntityManager',
               array('getRepository', 'getClassMetadata', 'persist', 'flush'), array(), '', false);
        $repoMock = $this->createMock( '\Doctrine\ORM\EntityRepository', ["createQueryBuilder"], array(), '', false );
        $qbMock = $this->createMock( '\Doctrine\ORM\QueryBuilder', ["select", "getQuery", "andWhere"], array(), '', false );
        $queryMock = $this->createMock( '\Doctrine\ORM\AbstractQuery', ['getResult'], array(), '', false );

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
            ->will( $this->returnValue($queryMock) )
            ;
        
        $qbMock->expects($this->once())
            ->method('andWhere')
            ->with($this->equalTo( 'a.field1 = 1') )
            ;
            
        $queryMock->expects($this->once())
            ->method('getResult')
            ->will( $this->returnValue([]))
            ;
            
        $dt = new Builder( $emMock, 
            [ "search" => [ "value" => "x" ] ] );
        $dt
            ->add( 'field1' )
            ->add( 'field2' )
            ->from( 'Test:Table', 'a' )
            ->$functionName( 'a.field1 = 1')
            ;

        $this->assertEquals( $dt->getArray(), [] );
    }



    /**
     * @covers Builder::where
     * @covers Builder::whereOnFiltered
     * @covers Builder::addWheresToCB
     * @dataProvider whereFunctions
     */
    function testAndWhereWithCallable($functionName)
    {     
        $emMock  = $this->createMock('\Doctrine\ORM\EntityManager',
               array('getRepository', 'getClassMetadata', 'persist', 'flush'), array(), '', false);
        $repoMock = $this->createMock( '\Doctrine\ORM\EntityRepository', ["createQueryBuilder"], array(), '', false );
        $qbMock = $this->createMock( QueryBuilder::class, ["select", "getQuery", "andWhere"], array(), '', false );
        $queryMock = $this->createMock( '\Doctrine\ORM\AbstractQuery', ['getResult'], array(), '', false );
        
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
            ->will( $this->returnValue($queryMock) )
            ;
        
        $qbMock->expects($this->once())
            ->method('andWhere')
            ;
            
        $queryMock->expects($this->once())
            ->method('getResult')
            ->will( $this->returnValue([]))
            ;
            
        $dt = new Builder( $emMock, 
            [ "search" => [ "value" => "x" ] ] );
            
            
        $called = 0;
        $_self = $this;
        
        $dt
            ->add( 'field1' )
            ->add( 'field2' )
            ->from( 'Test:Table', 'a' )
            ->$functionName( function( $qb) use( &$called, $_self ){
                $called ++;
                $_self->assertInstanceOf( QueryBuilder::class, $qb );
            } )
            ;

        $this->assertEquals( $dt->getArray(), [] );
        $this->assertEquals( 1, $called );
    }
    

    /**
     * @covers Builder::where
     * @covers Builder::whereOnFiltered
     */
    function testAndWhereCalledFromCount()
    {     
        $emMock  = $this->createMock('\Doctrine\ORM\EntityManager',
               array('getRepository', 'getClassMetadata', 'persist', 'flush'), array(), '', false);
        $repoMock = $this->createMock( '\Doctrine\ORM\EntityRepository', ["createQueryBuilder"], array(), '', false );
        $qbMock = $this->createMock( '\Doctrine\ORM\QueryBuilder', ["select", "getQuery", "andWhere", 'setMaxResults'], array(), '', false );
        $queryMock = $this->createMock( '\Doctrine\ORM\AbstractQuery', ['getResult'], array(), '', false );

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
            ->method('setMaxResults')
            ->with($this->equalTo( '1') )
            ->will($this->returnSelf())
            ;
            
        $qbMock->expects($this->once())
            ->method('getQuery')
            ->will( $this->returnValue($queryMock) )
            ;

        $qbMock->expects($this->once())
            ->method('andWhere')
            ->with($this->equalTo( 'a.field1 = 1') )
            ;

        $queryMock->expects($this->once())
            ->method('getResult')
            ->will( $this->returnValue([[0,3]]))
            ;
            
        $dt = new Builder( $emMock, 
            [ "search" => [ "value" => "x" ] ] );
        $dt
            ->add( 'field1' )
            ->add( 'field2' )
            ->from( 'Test:Table', 'a' )
            ->where( 'a.field1 = 1')
            ->whereOnFiltered( 'a.field2 = 2' )
            ;

        $this->assertEquals( 3, $dt->getCount() );
    }

    

    /**
     * @covers Builder::where
     * @covers Builder::whereOnFiltered
     */
    function testAndWhereCalledFromFilteredCount()
    {     
        $emMock  = $this->createMock('\Doctrine\ORM\EntityManager',
               array('getRepository', 'getClassMetadata', 'persist', 'flush'), array(), '', false);
        $repoMock = $this->createMock( '\Doctrine\ORM\EntityRepository', ["createQueryBuilder"], array(), '', false );
        $qbMock = $this->createMock( '\Doctrine\ORM\QueryBuilder', ["select", "getQuery", "andWhere", 'setMaxResults'], array(), '', false );
        $queryMock = $this->createMock( '\Doctrine\ORM\AbstractQuery', ['getResult'], array(), '', false );

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

        $qbMock->expects($this->exactly(0))
            ->method('setMaxResults')
            ->with($this->equalTo( '1') )
            ->will($this->returnSelf())
            ;
            
        $qbMock->expects($this->once())
            ->method('getQuery')
            ->will( $this->returnValue($queryMock) )
            ;

        $qbMock->expects($this->at(1))
            ->method('andWhere')
            ->with($this->equalTo( 'a.field2 = 2') )
            ;
        $qbMock->expects($this->at(2))
            ->method('andWhere')
            ->with($this->equalTo( 'a.field1 = 1') )
            ;

        $queryMock->expects($this->once())
            ->method('getResult')
            ->will( $this->returnValue([[0,3]]))
            ;
            
        $dt = new Builder( $emMock, 
            [ "search" => [ "value" => "x" ] ] );
        $dt
            ->add( 'field1' )
            ->add( 'field2' )
            ->from( 'Test:Table', 'a' )
            ->where( 'a.field1 = 1')
            ->whereOnFiltered( 'a.field2 = 2' )
            ;

        $this->assertEquals( 3, $dt->getFilteredCount() );
    }




    public function whereFunctions()
    {
        return[ ["where"], ["whereOnFiltered"] ];
    }



}
