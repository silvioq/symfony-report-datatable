<?php

namespace  Silvioq\ReportBundle\Tests\Util;

use PHPUnit\Framework\TestCase;
use Silvioq\ReportBundle\Util\Scalarize;

class ScalarizeTest extends TestCase
{

    public function testScalars()
    {
        $this->assertEquals( 1, Scalarize::toScalar( 1 ) );
        $this->assertEquals( '', Scalarize::toScalar( null ) );
        $this->assertEquals( 'hello w', Scalarize::toScalar( "hello w" ) );
        $this->assertTrue( Scalarize::toScalar( true ) );
    }

    public function testDateTime()
    {

        $date = new \DateTime();
        $date->setDate( 1945,10,17 );
        $date->setTime( 0,0, 0 );
        $this->assertEquals( '1945-10-17', Scalarize::toScalar( $date ) );
    }

    public function testArray()
    {
        $this->assertEquals( '1,2,name', Scalarize::toScalar( [ 1, 2, 'name' ] ) );
        $arr = [ 'a' => 1, 'b' => 2, 'name' => 'name' ];
        $this->assertEquals( '1,2,name', Scalarize::toScalar( $arr ) );
    }

    public function testObject()
    {
        $mock = $this->getMockBuilder(stdClass::class)
                ->disableOriginalConstructor()
                ->setMethods(['__toString'])
                ->getMock();

        $mock->expects($this->once())
            ->method('__toString')
            ->will($this->returnValue('hello'));

        $this->assertEquals( 'hello', Scalarize::toScalar( $mock ) );
    }

    public function testIteration()
    {
        $i = $this->iterateme();
        $this->assertEquals( '1,2,hello', Scalarize::toScalar($i ) );
    }


    private function iterateme()
    {
        yield 1;
        yield 2;
        
        $mock = $this->getMockBuilder(stdClass::class)
                ->disableOriginalConstructor()
                ->setMethods(['__toString'])
                ->getMock();

        $mock->expects($this->once())
            ->method('__toString')
            ->will($this->returnValue('hello'));

        yield $mock;
    }

}
// vim:sw=4 ts=4 sts=4 et
