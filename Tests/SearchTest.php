<?php

namespace  Silvioq\ReportBundle\Tests;

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
        $comp1 = $e->like('LOWER(a.field1)', ':ppp1');
        $comp2 = $e->like('LOWER(a.field2)', ':ppp1');
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

        $this->assertEquals( $dt->getResult(), [] );
        
    }



    public  function  testSearchOnIntegerColumns()
    {
        $emMock  = $this->createMock('\Doctrine\ORM\EntityManager',
               array('getRepository', 'getClassMetadata'), array(), '', false);
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
        $comp1 = $e->eq('a.field1', 3);
        $comp2 = $e->eq('a.field2', 3);
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
              "search" => [ "value" => "3" ],
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

        $this->assertEquals( $dt->getResult(), [] );
        
    }


}
