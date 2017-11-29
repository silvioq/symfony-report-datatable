<?php

namespace  Silvioq\ReportBundle\Tests\Table;

use PHPUnit\Framework\TestCase;
use Silvioq\ReportBundle\Table\Table;

class TableTest extends TestCase
{

    public function testValidColumn()
    {
    
        $mock = $this->getMockBuilder(stdClass::class)
                ->disableOriginalConstructor()
                ->setMethods(['getName','getLastName','getAge'])
                ->getMock();
                
        $mock->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('my name'))
            ;

        $mock->expects($this->once())
            ->method('getLastName')
            ->will($this->returnValue('my last name'))
            ;

        $mock->expects($this->once())
            ->method('getAge')
            ->will($this->returnValue(42))
            ;

        $table = new Table(get_class( $mock) );
        $table->add( 'name' )->add('lastName')->add('age', null, function($e){ return $e->getAge() / 2; } );
        
        $this->assertEquals( ['Name', 'Last name', "Age"], $table->getHeader() );
        $this->assertEquals( ['my name', 'my last name', 21], $table->getRow($mock) );
        
    }
    
    
    public function testNotBuildedTable()
    {
        $table = new Table(self::class);
        $this->expectException(\LogicException::class);
        $table->getHeader();
    }

    /**
     * @depends testValidColumn
     */
    public function testRemoveColumn()
    {
        $table = new Table( \stdClass::class );
        $table->add('name' )->add('lastName')->add('age')->removeField('age');

        $this->assertEquals( ['Name', 'Last name'], $table->getHeader() );
    }


    /**
     * @depends testValidColumn
     * @expectedException \InvalidArgumentException
     */
    public function testDuplicatedColumn()
    {
        $table = new Table( \stdClass::class );
        $table->add('name' )->add('lastName')->add('age')->add('name');
    }

    public function testNotBuildedTableOnRows()
    {
        $table = new Table(self::class);
        $this->expectException(\LogicException::class);
        $table->getRow($table);
    }
   
}
