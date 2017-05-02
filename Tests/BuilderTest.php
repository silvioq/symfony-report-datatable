<?php

namespace  Silvioq\ReportBundle\Tests;

use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManager;
use Silvioq\ReportBundle\Datatable\Builder;
use Silvioq\ReportBundle\Datatable\BuilderException;

class  BuilderTest  extends  TestCase
{

    /**
     * @covers Builder::getAlias
     * @covers Builder::getRepo
     * @covers Builder::getJoin
     * @covers Builder::getColumns
     */
    public  function  testGetAlias()
    {
        $emMock  = $this->createMock('\Doctrine\ORM\EntityManager',
               array('getRepository', 'getClassMetadata', 'persist', 'flush'), array(), '', false);
        
            
        $dt = new Builder( $emMock, 
            [ "search" => [ "value" => "x" ] ] );
        $dt
            ->add( 'field1' )
            ->add( 'field2' )
            ->from( 'Test:Table', 'a' )
            ->join( 'a.joinme', 'j' );
            ;

        $this->assertEquals( 'a', $dt->getAlias() );
        $this->assertEquals( 'Test:Table', $dt->getRepo() );
        $this->assertEquals( [ 'j' => 'a.joinme' ], $dt->getJoins() );
        $this->assertEquals( [ 'field1', 'field2' ], $dt->getColumns() );
    }
    
    /**
     * @covers Builder::getDraw
     */
    public function testGetDrawFromDatatableJavascriptCall()
    {
        $emMock  = $this->createMock('\Doctrine\ORM\EntityManager',
               array('getRepository', 'getClassMetadata', 'persist', 'flush'), array(), '', false);

        $dt = new Builder( $emMock, [ "draw" => 'me' ] );
        $this->assertEquals( 'me', $dt->getDraw() );
    }

    /**
     * @covers Builder::join
     */
    public function testTrhowOnAlreadyJoined()
    {
        $emMock = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dt = new Builder( $emMock, [ "search" => [ "value" => "x" ] ] );
        $dt
            ->add( 'field1' )
            ->add( 'field2' )
            ->join( 'field3', 'd' )
            ->from( 'Test:Table', 'a' );

        $dt->join( 'field4', 'c' );
        $this->expectException( BuilderException::class );
        $dt->join( 'field4', 'c' );

    }

}
