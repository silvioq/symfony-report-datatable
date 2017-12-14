<?php

namespace  Silvioq\ReportBundle\Tests\Datatable;

use PHPUnit\Framework\TestCase;
use Silvioq\ReportBundle\Datatable\Builder;
use Doctrine\DBAL\Types\Type as ORMType;
use Silvioq\ReportBundle\Tests\MockBuilder\ClassMetadataInfoMockBuilder;

class  ResultTest  extends  TestCase
{

    /**
     * @covers Builder::add
     * @covers Builder::from
     * @covers Builder::getResult
     */
    public  function  testQueryResult()
    {
        $emMock  = $this->createMock('\Doctrine\ORM\EntityManager',
               array('getRepository', 'getClassMetadata'), array(), '', false);

        (new \Silvioq\ReportBundle\Tests\MockBuilder\ConfigurationMockBuilder($this,$emMock))
            ->configure()->withMetadata();

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
            ->with($this->equalTo('a.field1, a.field2'))
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

        $this->assertEquals( $dt->getArray(), [] );
        
    }
    
    /**
     * @covers getQuery
     */
    public  function  testGetQuery()
    {
        $emMock  = $this->createMock('\Doctrine\ORM\EntityManager',
               array('getRepository', 'getClassMetadata'), array(), '', false);
        (new \Silvioq\ReportBundle\Tests\MockBuilder\ConfigurationMockBuilder($this,$emMock))->configure();
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

        $result = [ 
            [ 'field1' => 'one', 'field2' => 'two' ],
            [ 'field1' => 'three', 'field2' => 'four' ],
        ];

        $expected = [ [ 'one', 'two' ], [ 'three', 'four' ] ];

        $queryMock->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($result))
            ;

        $dt = new Builder( $emMock, 
            [ "search" => [ "value" => "x" ] ] );
        $dt
            ->add( 'field1' )
            ->add( 'field2' )
            ->from( 'Test:Table', 'a' )
            ;

        $metadata = new ClassMetadataInfoMockBuilder($this, $emMock, 'Test:Table' );
        $metadata
            ->addField( 'field1', ORMType::INTEGER )
            ->addField( 'field2' )
            ->build();

        $this->assertEquals( $expected, $dt->getArray() );
        
    }



    /**
     * @covers getQuery
     * @dataProvider  startAndLimit
     */
    public  function  testGetQueryWithStartAndLimit($start, $length)
    {
        $emMock  = $this->createMock('\Doctrine\ORM\EntityManager',
               array('getRepository', 'getClassMetadata', 'persist', 'flush'), array(), '', false);
        (new \Silvioq\ReportBundle\Tests\MockBuilder\ConfigurationMockBuilder($this,$emMock))->configure();
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
        
        if( false !== $length )
            $qbMock->expects($this->once())
                ->method('setMaxResults')
                ->with($this->equalTo($length))
                ->will($this->returnSelf())
                ;
        else
            $qbMock->expects($this->never())
                ->method('setMaxResults')
                ;

        if( false !== $start )
            $qbMock->expects($this->once())
                ->method('setFirstResult')
                ->with($this->equalTo($start))
                ->will($this->returnSelf())
                ;
        else
            $qbMock->expects($this->never())
                ->method('setFirstResult')
                ;

        $qbMock->expects($this->once())
            ->method('getQuery')
            ->will( $this->returnValue($queryMock) )
            ;
        
        $params = [ "search" => [ "value" => '' ] ];
        if( false !== $start ) $params['start'] = $start;
        if( false !== $length ) $params['length'] = $length;
        
        $dt = new Builder( $emMock, $params );

        $result = [ 
            [ 'field1' => 'one', 'field2' => 'two' ],
            [ 'field1' => 'three', 'field2' => 'four' ],
        ];
        $expected = [ [ 'one', 'two' ], [ 'three', 'four' ] ];
        $queryMock->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($result))
            ;

        $dt
            ->add( 'field1' )
            ->add( 'field2' )
            ->from( 'Test:Table', 'a' )
            ;

        $metadata = new ClassMetadataInfoMockBuilder($this, $emMock, 'Test:Table' );
        $metadata
            ->addField( 'field1', ORMType::INTEGER)
            ->addField( 'field2' )
            ->build();

        $this->assertEquals( $expected, $dt->getArray() );
        
    }
    
    public function startAndLimit()
    {
        return [
            [ 10, 20 ],
            [  5, false ],
            [ false, 50 ],
            [ false, false ],
           ] ;
    }



    /**
     * @cover Builder::addHidden
     */
    public  function  testGetResultWithHiddenColumn()
    {
        $emMock  = $this->createMock('\Doctrine\ORM\EntityManager',
               array('getRepository', 'getClassMetadata', 'persist', 'flush'), array(), '', false);
        (new \Silvioq\ReportBundle\Tests\MockBuilder\ConfigurationMockBuilder($this,$emMock))->configure();
        $repoMock = $this->createMock( '\Doctrine\ORM\EntityRepository', ["createQueryBuilder"], array(), '', false );
        $qbMock = $this->createMock( '\Doctrine\ORM\QueryBuilder', ["select", "getQuery"], array(), '', false );
        $queryMock = $this->createMock( '\Doctrine\ORM\AbstractQuery', ['getResult'], array(), '', false );

        $result = [
            [ 'field1' => 1, 'field2' => 1, 'field3' => 2, ],
            [ 'field1' => 2, 'field2' => 2, 'field3' => 4, ],
            [ 'field1' => 3, 'field2' => 3, 'field3' => 5, ]
        ];
        
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
            ->will( $this->returnValue($result))
            ;

        $metadata = new ClassMetadataInfoMockBuilder($this, $emMock, 'Test:Table' );
        $metadata
            ->addField( 'field1' )
            ->addField( 'field2' )
            ->addField( 'field3' )
            ->build();

        $dt = new Builder( $emMock, [ "search" => [ "value" => "x" ] ] );
        $dt
            ->add( 'field1' )
            ->add( 'field2' )
            ->addHidden( 'field3' )
            ->from( 'Test:Table', 'a' )
            ;

        $this->assertEquals( $dt->getArray(), [
                [ 1, 1 ],
                [ 2, 2 ],
                [ 3, 3 ]
            ] );

    }

}
