<?php

namespace  Silvioq\ReportBundle\Tests;

use PHPUnit\Framework\TestCase;

use Silvioq\ReportBundle\Datatable\Builder;

class  ResultTest  extends  TestCase
{

    /**
     * @covers Builder::add
     * @covers Builder::from
     */
    public  function  testQueryResult()
    {
        $emMock  = $this->createMock('\Doctrine\ORM\EntityManager',
               array('getRepository', 'getClassMetadata'), array(), '', false);
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
            ;

        $this->assertEquals( $dt->getResult(), [] );
        
    }
    
    /**
     * @covers getQuery
     */
    public  function  testGetQuery()
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
            
        $dt = new Builder( $emMock, 
            [ "search" => [ "value" => "x" ] ] );
        $dt
            ->add( 'field1' )
            ->add( 'field2' )
            ->from( 'Test:Table', 'a' )
            ;

        $this->assertInstanceOf( \Doctrine\ORM\AbstractQuery::class, $dt->getQuery() );
        
    }



    /**
     * @covers getQuery
     */
    public  function  testGetQueryWithLimit()
    {
        $emMock  = $this->createMock('\Doctrine\ORM\EntityManager',
               array('getRepository', 'getClassMetadata', 'persist', 'flush'), array(), '', false);
        $repoMock = $this->createMock( '\Doctrine\ORM\EntityRepository', ["createQueryBuilder"], array(), '', false );
        $qbMock = $this->createMock( '\Doctrine\ORM\QueryBuilder', ["select", "getQuery", "andWhere", 'setMaxResults', 'setFirstResult'], array(), '', false );
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
            ->with($this->equalTo(20))
            ->will($this->returnSelf())
            ;

        $qbMock->expects($this->once())
            ->method('setFirstResult')
            ->with($this->equalTo(10))
            ->will($this->returnSelf())
            ;

        $qbMock->expects($this->once())
            ->method('getQuery')
            ->will( $this->returnValue($queryMock) )
            ;
        
        $dt = new Builder( $emMock, 
            [ "search" => [ "value" => '' ], "start" => 10, "length" => 20 ] );
        $dt
            ->add( 'field1' )
            ->add( 'field2' )
            ->from( 'Test:Table', 'a' )
            ;

        $this->assertInstanceOf( \Doctrine\ORM\AbstractQuery::class, $dt->getQuery() );
        
    }

}
