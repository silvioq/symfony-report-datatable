<?php

namespace  Silvioq\ReportBundle\Tests\Table;

use PHPUnit\Framework\TestCase;
use Silvioq\ReportBundle\Table\Column;

class ColumnTest extends TestCase
{

    public function testValidColumn()
    {
        $col = new Column("name");
        $this->assertEquals("name", $col->getName() );
        $this->assertEquals("Name", $col->getLabel() );
        $this->assertEquals("getName", $col->getGetter() );

        $col = new Column("lastName");
        $this->assertEquals("lastName", $col->getName() );
        $this->assertEquals("Last name", $col->getLabel() );
        $this->assertEquals("getLastName", $col->getGetter() );

        $col = new Column("lastName", "last name", function(){ return 1; } );
    }
    
    /**
     * @dataProvider getInvalidTriplete
     */
    public function testInvalidColumn($name,$label,$getter)
    {
        $this->expectException(\InvalidArgumentException::class);
        $col = new Column($name,$label,$getter );
    }
    
    
    public function getInvalidTriplete()
    {
        return [
            [ false, null, null ],
            [ null, null, null ],
            [ "name", "name", 1234 ],
            [ "name", 1234, "name" ],
         ];
    }

}
