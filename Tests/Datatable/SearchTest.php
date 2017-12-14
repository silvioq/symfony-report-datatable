<?php

namespace  Silvioq\ReportBundle\Tests\Datatable;

use PHPUnit\Framework\TestCase;
use Silvioq\ReportBundle\Datatable\Builder;
use Doctrine\ORM\Query\Expr;
use Doctrine\DBAL\Types\Type as ORMType;

class  SearchTest  extends  TestCase
{

    public  function  testSearchOnStringColumns()
    {
        $emMock  = $this->createMock('\Doctrine\ORM\EntityManager',
               array('getRepository', 'getClassMetadata'), array(), '', false);

        (new \Silvioq\ReportBundle\Tests\MockBuilder\ConfigurationMockBuilder($this,$emMock,'pdo_mysql'))->configure();

        $repoMock = $this->createMock( '\Doctrine\ORM\EntityRepository', ["createQueryBuilder"], array(), '', false );
        $qbMock = $this->createMock( '\Doctrine\ORM\QueryBuilder', ["select", "getQuery", "andWhere",'expr'], array(), '', false );
        $queryMock = $this->createMock( '\Doctrine\ORM\AbstractQuery', ['getResult'], array(), '', false );
        $metadataMock = $this->createMock( '\Doctrine\ORM\Mapping\ClassMetadataInfo', ['getFieldNames','getTypeOfField'], array(), '', false );

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

        $metadataMock->expects($this->at(1))
            ->method('getTypeOfField')
            ->with( $this->equalTo('field1'))
            ->will( $this->returnValue(ORMType::STRING))
            ;

        $metadataMock->expects($this->at(2))
            ->method('getTypeOfField')
            ->with( $this->equalTo('field2'))
            ->will( $this->returnValue(ORMType::STRING))
            ;

        $metadataMock->expects($this->at(3))
            ->method('getTypeOfField')
            ->with( $this->equalTo('field3'))
            ->will( $this->returnValue(ORMType::JSON_ARRAY))
            ;

        $metadataMock->expects($this->once())
            ->method('getFieldNames')
            ->will( $this->returnValue(['field1','field2','field3']))
            ;

        $repoMock->expects($this->once())
            ->method("createQueryBuilder")
            ->with($this->equalTo('a'))
            ->will($this->returnValue( $qbMock ) )
            ;

        $qbMock->expects($this->once())
            ->method('select')
            ->with($this->equalTo('a.field1, a.field2, a.field3'))
            ->will($this->returnSelf() )
            ;

        $e = new Expr();
        $comp1 = $e->like('LOWER(a.field1)', ':ppp1');
        $comp2 = $e->like('LOWER(a.field2)', ':ppp1');
        $comp3 = $e->like('LOWER(a.field3)', ':ppp1');
        $qbMock->expects($this->once())
            ->method('andWhere')
            ->with($this->equalTo(new Expr\Orx([$comp1, $comp2, $comp3] )))
            ->will($this->returnSelf() )
            ;
            
        $qbMock->expects($this->exactly(3))
            ->method('setParameter')
            ->with($this->equalTo('ppp1'),$this->equalTo('%x%'),$this->anything())
            ->will($this->returnSelf())
            ;
        
        $qbMock->expects($this->once())
            ->method('getQuery')
            ->will( $this->returnValue($queryMock) )
            ;
            
        $qbMock
            ->method('expr')
            ->will($this->returnValue(new Expr()))
            ;
            
        $queryMock->expects($this->once())
            ->method('getResult')
            ->will( $this->returnValue([]))
            ;

        $dt = new Builder( $emMock, 
            [ 
              "search" => [ "value" => "x" ],
              "columns" => [
                   [ 'searchable' => true ],
                   [ 'searchable' => true ],
                   [ 'searchable' => true ],
                ],
            ] );

        $dt
            ->add( 'field1' )
            ->add( 'field2' )
            ->add( 'field3' )
            ->from( 'Test:Table', 'a' )
            ;

        $this->assertEquals( $dt->getArray(), [] );
        
    }

    /**
     * @dataProvider getORMTypes
     */
    public  function  testSearchStringOnNumericColumns($numericType, $dataValue = 3)
    {
        $emMock  = $this->createMock('\Doctrine\ORM\EntityManager',
               array('getRepository', 'getClassMetadata'), array(), '', false);
        (new \Silvioq\ReportBundle\Tests\MockBuilder\ConfigurationMockBuilder($this,$emMock))->configure();
        $repoMock = $this->createMock( '\Doctrine\ORM\EntityRepository', ["createQueryBuilder"], array(), '', false );
        $qbMock = $this->createMock( '\Doctrine\ORM\QueryBuilder', ["select", "getQuery", "andWhere",'expr'], array(), '', false );
        $queryMock = $this->createMock( '\Doctrine\ORM\AbstractQuery', ['getResult'], array(), '', false );
        $metadataMock = $this->createMock( '\Doctrine\ORM\Mapping\ClassMetadataInfo', ['getFieldNames','getTypeOfField'], array(), '', false );

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

        $metadataMock->expects($this->at(1))
            ->method('getTypeOfField')
            ->with( $this->equalTo('field1'))
            ->will( $this->returnValue($numericType))
            ;

        $metadataMock->expects($this->at(2))
            ->method('getTypeOfField')
            ->with( $this->equalTo('field2'))
            ->will( $this->returnValue(ORMType::STRING))
            ;

        $metadataMock->expects($this->once())
            ->method('getFieldNames')
            ->will( $this->returnValue(['field1','field2']))
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

        $e = new Expr();
        $comp2 = $e->like('LOWER(a.field2)', ':ppp1');
        $qbMock->expects($this->once())
            ->method('andWhere')
            ->with($this->equalTo(new Expr\Orx([$comp2] )))
            ->will($this->returnSelf() )
            ;

        $qbMock->expects($this->exactly(1))
            ->method('setParameter')
            ->with($this->equalTo('ppp1'),$this->equalTo('%hello%'),$this->anything())
            ->will($this->returnSelf())
            ;

        $qbMock->expects($this->once())
            ->method('getQuery')
            ->will( $this->returnValue($queryMock) )
            ;

        $qbMock
            ->method('expr')
            ->will($this->returnValue(new Expr()))
            ;
            
        $queryMock->expects($this->once())
            ->method('getResult')
            ->will( $this->returnValue([]))
            ;
            
        $dt = new Builder( $emMock, 
            [ 
              "search" => [ "value" => "hello" ],
              "columns" => [
                   [ 'searchable' => true ],
                   [ 'searchable' => true ],
                ],
            ] );
        $dt
            ->add( 'field1' )
            ->add( 'field2' )
            ->from( 'Test:Table', 'a' )
            ;

        $this->assertEquals( $dt->getArray(), [] );
    }


    /**
     * @dataProvider getORMTypes
     */
    public  function  testSearchOnIntegerColumns($numericType, $dataValue = 3)
    {
        $emMock  = $this->createMock('\Doctrine\ORM\EntityManager',
               array('getRepository', 'getClassMetadata'), array(), '', false);
        (new \Silvioq\ReportBundle\Tests\MockBuilder\ConfigurationMockBuilder($this,$emMock))->configure();
        $repoMock = $this->createMock( '\Doctrine\ORM\EntityRepository', ["createQueryBuilder"], array(), '', false );
        $qbMock = $this->createMock( '\Doctrine\ORM\QueryBuilder', ["select", "getQuery", "andWhere",'expr'], array(), '', false );
        $queryMock = $this->createMock( '\Doctrine\ORM\AbstractQuery', ['getResult'], array(), '', false );
        $metadataMock = $this->createMock( '\Doctrine\ORM\Mapping\ClassMetadataInfo', ['getFieldNames','getTypeOfField'], array(), '', false );

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

        $metadataMock->expects($this->at(1))
            ->method('getTypeOfField')
            ->with( $this->equalTo('field1'))
            ->will( $this->returnValue($numericType))
            ;

        $metadataMock->expects($this->at(2))
            ->method('getTypeOfField')
            ->with( $this->equalTo('field2'))
            ->will( $this->returnValue($numericType))
            ;

        $metadataMock->expects($this->once())
            ->method('getFieldNames')
            ->will( $this->returnValue(['field1','field2']))
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

        $e = new Expr();
        $comp1 = $e->eq('a.field1', $dataValue);
        $comp2 = $e->eq('a.field2', $dataValue);
        $qbMock->expects($this->once())
            ->method('andWhere')
            ->with($this->equalTo(new Expr\Orx([$comp1, $comp2] )))
            ->will($this->returnSelf() )
            ;
            
        $qbMock->expects($this->once())
            ->method('getQuery')
            ->will( $this->returnValue($queryMock) )
            ;

        $qbMock
            ->method('expr')
            ->will($this->returnValue(new Expr()))
            ;

        $queryMock->expects($this->once())
            ->method('getResult')
            ->will( $this->returnValue([]))
            ;
            
        $dt = new Builder( $emMock, 
            [ 
              "search" => [ "value" => (string)$dataValue ],
              "columns" => [
                   [ 'searchable' => true ],
                   [ 'searchable' => true ],
                ],
            ] );
        $dt
            ->add( 'field1' )
            ->add( 'field2' )
            ->from( 'Test:Table', 'a' )
            ;

        $this->assertEquals( $dt->getArray(), [] );
    }

    public function getORMTypes():array
    {
        return [
            [ ORMType::INTEGER, 3 ],
            [ ORMType::DECIMAL, 3.16 ],
            [ ORMType::SMALLINT, -4 ],
            [ ORMType::BIGINT, 2 ** 33 ],
            [ ORMType::FLOAT, 3.141592 ],
        ];
    }

    public  function  testSearchNull()
    {
        $emMock  = $this->createMock('\Doctrine\ORM\EntityManager',
               array('getRepository', 'getClassMetadata'), array(), '', false);
        (new \Silvioq\ReportBundle\Tests\MockBuilder\ConfigurationMockBuilder($this,$emMock))->configure();
        $repoMock = $this->createMock( '\Doctrine\ORM\EntityRepository', ["createQueryBuilder"], array(), '', false );
        $qbMock = $this->createMock( '\Doctrine\ORM\QueryBuilder', ["select", "getQuery", "andWhere",'expr'], array(), '', false );
        $queryMock = $this->createMock( '\Doctrine\ORM\AbstractQuery', ['getResult'], array(), '', false );
        $metadataMock = $this->createMock( '\Doctrine\ORM\Mapping\ClassMetadataInfo', ['getFieldNames','getTypeOfField'], array(), '', false );

        $emMock->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo('Test:Table'))
            ->will($this->returnValue($repoMock))
            ;

        $metadataMock->expects($this->never())
            ->method('getFieldNames')
            ->will( $this->returnValue(['field1','field2']))
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

        $e = new Expr();
        $comp1 = $e->isNull('a.field1');
        $qbMock->expects($this->once())
            ->method('andWhere')
            ->with($this->equalTo($comp1))
            ->will($this->returnSelf() )
            ;

        $qbMock->expects($this->once())
            ->method('getQuery')
            ->will( $this->returnValue($queryMock) )
            ;

        $qbMock
            ->method('expr')
            ->will($this->returnValue(new Expr()))
            ;

        $queryMock->expects($this->once())
            ->method('getResult')
            ->will( $this->returnValue([]))
            ;

        $dt = new Builder( $emMock,
            [
              "search" => [ "value" => "" ],
              "columns" => [
                   [ 'searchable' => true, 'search' => [ 'value' => 'is null' ] ],
                   [ 'searchable' => true ],
                ],
            ] );

        $dt
            ->add( 'field1' )
            ->add( 'field2' )
            ->from( 'Test:Table', 'a' )
            ;

        $this->assertEquals( $dt->getArray(), [] );

    }


    public  function  testSearchNotNull()
    {
        $emMock  = $this->createMock('\Doctrine\ORM\EntityManager',
               array('getRepository', 'getClassMetadata'), array(), '', false);
        (new \Silvioq\ReportBundle\Tests\MockBuilder\ConfigurationMockBuilder($this,$emMock))->configure();
        $repoMock = $this->createMock( '\Doctrine\ORM\EntityRepository', ["createQueryBuilder"], array(), '', false );
        $qbMock = $this->createMock( '\Doctrine\ORM\QueryBuilder', ["select", "getQuery", "andWhere",'expr'], array(), '', false );
        $queryMock = $this->createMock( '\Doctrine\ORM\AbstractQuery', ['getResult'], array(), '', false );
        $metadataMock = $this->createMock( '\Doctrine\ORM\Mapping\ClassMetadataInfo', ['getFieldNames','getTypeOfField'], array(), '', false );

        $emMock->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo('Test:Table'))
            ->will($this->returnValue($repoMock))
            ;

        $metadataMock->expects($this->never())
            ->method('getFieldNames')
            ->will( $this->returnValue(['field1','field2']))
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

        $e = new Expr();
        $comp1 = $e->isNotNull('a.field2');
        $qbMock->expects($this->once())
            ->method('andWhere')
            ->with($this->equalTo($comp1))
            ->will($this->returnSelf() )
            ;

        $qbMock->expects($this->once())
            ->method('getQuery')
            ->will( $this->returnValue($queryMock) )
            ;

        $qbMock
            ->method('expr')
            ->will($this->returnValue(new Expr()))
            ;

        $queryMock->expects($this->once())
            ->method('getResult')
            ->will( $this->returnValue([]))
            ;

        $dt = new Builder( $emMock,
            [
              "search" => [ "value" => "" ],
              "columns" => [
                   [ 'searchable' => true ],
                   [ 'searchable' => true, 'search' => [ 'value' => 'is not null' ] ],
                ],
            ] );

        $dt
            ->add( 'field1' )
            ->add( 'field2' )
            ->from( 'Test:Table', 'a' )
            ;

        $this->assertEquals( $dt->getArray(), [] );

    }


    public  function  testSearchBetweenDate()
    {
        $emMock  = $this->createMock('\Doctrine\ORM\EntityManager',
               array('getRepository', 'getClassMetadata'), array(), '', false);
        (new \Silvioq\ReportBundle\Tests\MockBuilder\ConfigurationMockBuilder($this,$emMock))->configure();
        $repoMock = $this->createMock( '\Doctrine\ORM\EntityRepository', ["createQueryBuilder"], array(), '', false );
        $qbMock = $this->createMock( '\Doctrine\ORM\QueryBuilder', ["select", "getQuery", "andWhere",'expr'], array(), '', false );
        $queryMock = $this->createMock( '\Doctrine\ORM\AbstractQuery', ['getResult'], array(), '', false );
        $metadataMock = $this->createMock( '\Doctrine\ORM\Mapping\ClassMetadataInfo', ['getFieldNames','getTypeOfField'], array(), '', false );

        $emMock->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo('Test:Table'))
            ->will($this->returnValue($repoMock))
            ;

        $metadataMock->expects($this->once())
            ->method('getFieldNames')
            ->will( $this->returnValue(['field1','field2']))
            ;

        $emMock->expects($this->once())
            ->method('getClassMetadata')
            ->with($this->equalTo('Test:Table'))
            ->will($this->returnValue($metadataMock))
            ;

        $metadataMock->expects($this->at(1))
            ->method('getTypeOfField')
            ->with( $this->equalTo('field1'))
            ->will( $this->returnValue(ORMType::DATETIME))
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

        $qbMock->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive( ['ppp1', '1900-01-01'], ['ppp2','2012-01-01'] )
            ->will($this->returnSelf())
            ;

        $e = new Expr();
        if( \Silvioq\ReportBundle\Tests\MockBuilder\ConfigurationMockBuilder::doctrineExtensionsEnabled() ) {
            $comp1 = $e->between("DATE_FORMAT(a.field1,'YYYY-MM-DD')", ':ppp1', ':ppp2');
        } else {
            $comp1 = $e->between('a.field1', ':ppp1', ':ppp2');
        }
        $qbMock->expects($this->once())
            ->method('andWhere')
            ->with($this->equalTo($comp1))
            ->will($this->returnSelf() )
            ;

        $qbMock->expects($this->once())
            ->method('getQuery')
            ->will( $this->returnValue($queryMock) )
            ;

        $qbMock
            ->method('expr')
            ->will($this->returnValue(new Expr()))
            ;

        $queryMock->expects($this->once())
            ->method('getResult')
            ->will( $this->returnValue([]))
            ;

        $dt = new Builder( $emMock,
            [
              "search" => [ "value" => "" ],
              "columns" => [
                   [ 'searchable' => true, 'search' => [ 'value' => 'between 1900-01-01 and 2012-01-01' ] ],
                   [ 'searchable' => true ],
                ],
            ] );

        $dt
            ->add( 'field1' )
            ->add( 'field2' )
            ->from( 'Test:Table', 'a' )
            ;

        $this->assertEquals( $dt->getArray(), [] );

    }

    public  function  testSearchBetweenStrings()
    {
        $emMock  = $this->createMock('\Doctrine\ORM\EntityManager',
               array('getRepository', 'getClassMetadata'), array(), '', false);
        (new \Silvioq\ReportBundle\Tests\MockBuilder\ConfigurationMockBuilder($this,$emMock))->configure();
        $repoMock = $this->createMock( '\Doctrine\ORM\EntityRepository', ["createQueryBuilder"], array(), '', false );
        $qbMock = $this->createMock( '\Doctrine\ORM\QueryBuilder', ["select", "getQuery", "andWhere",'expr'], array(), '', false );
        $queryMock = $this->createMock( '\Doctrine\ORM\AbstractQuery', ['getResult'], array(), '', false );
        $metadataMock = $this->createMock( '\Doctrine\ORM\Mapping\ClassMetadataInfo', ['getFieldNames','getTypeOfField'], array(), '', false );

        $emMock->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo('Test:Table'))
            ->will($this->returnValue($repoMock))
            ;

        $metadataMock->expects($this->once())
            ->method('getFieldNames')
            ->will( $this->returnValue(['field1','field2']))
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
            ->with($this->equalTo('a.field1, a.field2'))
            ->will($this->returnSelf() )
            ;

        $qbMock->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive( ['ppp1', 'A'], ['ppp2','Z'] )
            ->will($this->returnSelf())
            ;

        $e = new Expr();
        $comp1 = $e->between('a.field1', ':ppp1', ':ppp2');
        $qbMock->expects($this->once())
            ->method('andWhere')
            ->with($this->equalTo($comp1))
            ->will($this->returnSelf() )
            ;

        $qbMock->expects($this->once())
            ->method('getQuery')
            ->will( $this->returnValue($queryMock) )
            ;

        $qbMock
            ->method('expr')
            ->will($this->returnValue(new Expr()))
            ;

        $queryMock->expects($this->once())
            ->method('getResult')
            ->will( $this->returnValue([]))
            ;

        $dt = new Builder( $emMock,
            [
              "search" => [ "value" => "" ],
              "columns" => [
                   [ 'searchable' => true, 'search' => [ 'value' => 'between A and Z' ] ],
                   [ 'searchable' => true ],
                ],
            ] );

        $dt
            ->add( 'field1' )
            ->add( 'field2' )
            ->from( 'Test:Table', 'a' )
            ;

        $this->assertEquals( $dt->getArray(), [] );

    }


    public  function  testSearchOnBooleanColumns()
    {
        $emMock  = $this->createMock('\Doctrine\ORM\EntityManager',
               array('getRepository', 'getClassMetadata'), array(), '', false);

        (new \Silvioq\ReportBundle\Tests\MockBuilder\ConfigurationMockBuilder($this,$emMock))->configure();

        $repoMock = $this->createMock( '\Doctrine\ORM\EntityRepository', ["createQueryBuilder"], array(), '', false );
        $qbMock = $this->createMock( '\Doctrine\ORM\QueryBuilder', ["select", "getQuery", "andWhere",'expr'], array(), '', false );
        $queryMock = $this->createMock( '\Doctrine\ORM\AbstractQuery', ['getResult'], array(), '', false );
        $metadataMock = $this->createMock( '\Doctrine\ORM\Mapping\ClassMetadataInfo', ['getFieldNames','getTypeOfField'], array(), '', false );

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

        $metadataMock->expects($this->at(1))
            ->method('getTypeOfField')
            ->with( $this->equalTo('field1'))
            ->will( $this->returnValue(ORMType::BOOLEAN))
            ;

        $metadataMock->expects($this->at(2))
            ->method('getTypeOfField')
            ->with( $this->equalTo('field2'))
            ->will( $this->returnValue(ORMType::STRING))
            ;

        $metadataMock->expects($this->once())
            ->method('getFieldNames')
            ->will( $this->returnValue(['field1','field2']))
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

        $e = new Expr();
        $comp2 = $e->like('LOWER(a.field2)', ':ppp2');
        $qbMock->expects($this->once())
            ->method('andWhere')
            ->with($this->equalTo('LOWER(a.field2) LIKE :ppp1'))
            ->will($this->returnSelf() )
            ;

        $qbMock->expects($this->exactly(1))
            ->method('setParameter')
            ->with($this->equalTo('ppp1'),$this->equalTo('%x%'),$this->anything())
            ->will($this->returnSelf())
            ;

        $qbMock->expects($this->once())
            ->method('getQuery')
            ->will( $this->returnValue($queryMock) )
            ;

        $qbMock
            ->method('expr')
            ->will($this->returnValue(new Expr()))
            ;

        $queryMock->expects($this->once())
            ->method('getResult')
            ->will( $this->returnValue([]))
            ;

        $dt = new Builder( $emMock, 
            [ 
              "search" => [ "value" => "x" ],
              "columns" => [
                   [ 'searchable' => true ],
                   [ 'searchable' => true ],
                ],
            ] );

        $dt
            ->add( 'field1' )
            ->add( 'field2' )
            ->from( 'Test:Table', 'a' )
            ;

        $this->assertEquals( $dt->getArray(), [] );

    }

    /**
     * @covers Builder::getWhereFor()
     * @dataProvider getAllORMTypes
     */
    public function testAllORMTypes($ormType, $searchStr, $driver, $expectedSearch = null )
    {
        $emMock  = $this->createMock('\Doctrine\ORM\EntityManager',
               array('getRepository', 'getClassMetadata'), array(), '', false);

        (new \Silvioq\ReportBundle\Tests\MockBuilder\ConfigurationMockBuilder($this,$emMock, $driver))->configure();

        $repoMock = $this->createMock( '\Doctrine\ORM\EntityRepository', ["createQueryBuilder"], array(), '', false );
        $qbMock = $this->createMock( '\Doctrine\ORM\QueryBuilder', ["select", "getQuery", "andWhere",'expr'], array(), '', false );
        $queryMock = $this->createMock( '\Doctrine\ORM\AbstractQuery', ['getResult'], array(), '', false );
        $metadataMock = $this->createMock( '\Doctrine\ORM\Mapping\ClassMetadataInfo', ['getFieldNames','getTypeOfField'], array(), '', false );

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

        $metadataMock->expects($this->at(1))
            ->method('getTypeOfField')
            ->with( $this->equalTo('field1'))
            ->will( $this->returnValue($ormType))
            ;

        $metadataMock->expects($this->once())
            ->method('getFieldNames')
            ->will( $this->returnValue(['field1']))
            ;

        $repoMock->expects($this->once())
            ->method("createQueryBuilder")
            ->with($this->equalTo('a'))
            ->will($this->returnValue( $qbMock ) )
            ;

        $qbMock->expects($this->once())
            ->method('select')
            ->with($this->equalTo('a.field1'))
            ->will($this->returnSelf() )
            ;

        if (is_string($expectedSearch)) {
            $qbMock->expects($this->once())
                ->method('andWhere')
                ->with($this->equalTo(new Expr\Orx($expectedSearch)))
                ->will($this->returnSelf())
                ;

            $qbMock->expects($this->once())
                ->method('setParameter')
                ->will($this->returnSelf())
                ;
        } else if (false === $expectedSearch) {
            $qbMock->expects($this->never())
                ->method('andWhere');
            $qbMock->expects($this->never())
                ->method('setParameter');
        }

        $qbMock->expects($this->once())
            ->method('getQuery')
            ->will( $this->returnValue($queryMock) )
            ;

        $qbMock
            ->method('expr')
            ->will($this->returnValue(new Expr()))
            ;

        $queryMock->expects($this->once())
            ->method('getResult')
            ->will( $this->returnValue([]))
            ;

        $dt = new Builder( $emMock,
            [
              "search" => [ "value" => $searchStr ],
              "columns" => [
                   [ 'searchable' => true ],
                ],
            ] );

        $dt
            ->add( 'field1' )
            ->from( 'Test:Table', 'a' )
            ;

        $this->assertEquals( $dt->getArray(), [] );
    }

    public function getAllORMTypes():array
    {
        $ret = [];
        $reflectionClass = new \ReflectionClass(ORMType::class);
        foreach( [ 'pdo_mysql', 'pdo_pgsql', 'oci8', 'pdo_oracle' ] as $driver ) {
            foreach( $reflectionClass->getConstants() as $constant => $value ) {
                array_push($ret, [ $value, 'stringVal', $driver ] );
                array_push($ret, [ $value, '33', $driver ] );
                array_push($ret, [ $value, '33.2', $driver ] );
                array_push($ret, [ $value, '2017-01-01', $driver ] );
            }
        }

        array_push($ret, [ ORMType::JSON_ARRAY, 'stringVal', 'pdo_mysql', "LOWER(a.field1) LIKE :ppp1"] );
        array_push($ret, [ ORMType::JSON_ARRAY, 'stringVal', 'pdo_pgsql', false] );

        return $ret;
    }
}
// vim:sw=4 ts=4 sts=4 et
