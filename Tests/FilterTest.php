<?php

namespace  Silvioq\ReportBundle\Tests;

use PHPUnit\Framework\TestCase;
use Silvioq\ReportBundle\Datatable\Builder;
use Silvioq\ReportBundle\Datatable\BuilderException;
use Doctrine\DBAL\Types\Type as ORMType;
use Doctrine\ORM\EntityManager;

class  FilterTest  extends  TestCase
{
    /**
     * @cover Builder::filter
     */
    public  function  testResultFiltering()
    {
        $emMock  = $this->createMock('\Doctrine\ORM\EntityManager',
               array('getRepository', 'getClassMetadata', 'persist', 'flush'), array(), '', false);
        $repoMock = $this->createMock( '\Doctrine\ORM\EntityRepository', ["createQueryBuilder"], array(), '', false );
        $qbMock = $this->createMock( '\Doctrine\ORM\QueryBuilder', ["select", "getQuery"], array(), '', false );
        $queryMock = $this->createMock( '\Doctrine\ORM\AbstractQuery', ['getResult'], array(), '', false );
        $metadataMock = $this->createMock( '\Doctrine\ORM\Mapping\ClassMetadataInfo', ['getFieldNames','getTypeOfField'], array(), '', false );
        
        $result = [
            [ 'field1' => 1, 'field2' => 1],
            [ 'field1' => 2, 'field2' => 2],
            [ 'field1' => 3, 'field2' => 3]
        ];
        
        $emMock->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo('Test:Table'))
            ->will($this->returnValue($repoMock))
            ;
            
        $emMock->expects($this->once())
            ->method('getClassMetadata')
            ->with($this->equalTo('Test:Table'))
            ->will($this->returnValue($metadataMock))
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

        $metadataMock->expects($this->at(1))
            ->method('getTypeOfField')
            ->with( $this->equalTo('field1'))
            ->will( $this->returnValue(ORMType::INTEGER))
            ;

        $metadataMock->expects($this->at(2))
            ->method('getTypeOfField')
            ->with( $this->equalTo('field2'))
            ->will( $this->returnValue(ORMType::INTEGER))
            ;

        $metadataMock->expects($this->once())
            ->method('getFieldNames')
            ->will( $this->returnValue(['field1','field2']))
            ;

            
        $dt = new Builder( $emMock, 
            [ "search" => [ "value" => "x" ] ] );
        $dt
            ->add( 'field1' )
            ->add( 'field2' )
            ->from( 'Test:Table', 'a' )
            ->filter( 'field2', 
                function($val){ return $val["field2"] * 2; } )
            ;

        $this->assertEquals( $dt->getArray(), [
                [ 1, 1 * 2],
                [ 2, 2 * 2],
                [ 3, 3 * 2]
            ] );

    }
    
    /**
     * @covers Builder::filter
     */
    public function testThrowsWhenNotExistsColumn()
    {
        $emMock = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

         $dt = new Builder( $emMock, [ "search" => [ "value" => "x" ] ] );
         $dt
            ->add( 'field1' )
            ->add( 'field2' )
            ->from( 'Test:Table', 'a' );

         $this->expectException( BuilderException::class );
         $dt->filter( 'field3', 'get_class' );
    }

    /**
     * @covers Builder::filter
     */
    public function testThrowsWhenFilterIsAlreadyDefined()
    {
        $emMock = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

         $dt = new Builder( $emMock, [ "search" => [ "value" => "x" ] ] );
         $dt
            ->add( 'field1' )
            ->add( 'field2' )
            ->from( 'Test:Table', 'a' );

         $dt->filter( 'field2', 'get_class' );
         $this->expectException( BuilderException::class );
         $dt->filter( 'field2', 'get_class' );
    }

    /**
     * @covers Builder::filter
     */
    public function testThrowsWhenFilterFunctionIsInvalid()
    {
        $emMock = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

         $dt = new Builder( $emMock, [ "search" => [ "value" => "x" ] ] );
         $dt
            ->add( 'field1' )
            ->add( 'field2' )
            ->from( 'Test:Table', 'a' );

         $this->expectException( \InvalidArgumentException::class );
         $dt->filter( 'field2', 'none' );
    }
}
