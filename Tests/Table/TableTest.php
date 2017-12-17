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

    /**
     * @dataProvider getExpansionData
     */
    public function testExpandedColumn($fields, array $expected)
    {
        $mock = $this->getMockBuilder(stdClass::class)
                ->disableOriginalConstructor()
                ->setMethods(['getArray'])
                ->getMock();

        $allElements = [ 'One', 'Two', 'Three' ];
        $table = new Table(get_class($mock) );
        $table->addExpansible('array', null, $allElements);

        $mock->expects($this->exactly(3))
            ->method('getArray')
            ->willReturn($fields);

        $this->assertEquals(['array.One', 'array.Two', 'array.Three'], $table->getHeader());
        $this->assertSame($expected, $table->getRow($mock));
    }

    public function getExpansionData():array
    {
        $traversable1 = new \ArrayIterator(['One','Three']);
        $traversable2 = new \ArrayIterator(['Three','Four','Two']);
        $traversable3 = new \ArrayIterator();

        return [
            [['One', 'Three'], [true,false,true]],
            [['Three', 'One'], [true,false,true]],
            [[], [false,false,false]],
            [['Two','Four'], [false,true,false]],
            [['Four', 'Two','Five'], [false,true,false]],
            [['One','Three', 'Two'], [true,true,true]],
            [['Two','Three', 'One', 'Four'], [true,true,true]],
            [null, [false,false,false]],
            [$traversable1, [true,false,true]],
            [$traversable2, [false,true,true]],
            [$traversable3, [false,false,false]],
        ];
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
