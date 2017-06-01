<?php

namespace  Silvioq\ReportBundle\Tests\Table;

use PHPUnit\Framework\TestCase;
use Silvioq\ReportBundle\Service\TableFactory;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;

class TableFactoryTest extends TestCase
{
    public function testReader()
    {
        // Force autoload for Annotation reader
        new \Doctrine\ORM\Mapping\Column();
    
        $factory = new TableFactory(new AnnotationReader());
        $table = $factory->build(Entity\Entity::class);
        $this->assertInstanceOf( \Silvioq\ReportBundle\Table\Table::class, $table );
        
        $this->assertEquals( [ "This name", "Age" ], $table->getHeader() );
        
        $entity = new Entity\Entity();
        $entity->setName('Maradona');
        $this->assertEquals( [ 'Maradona', 42 ], $table->getRow($entity) );
    }
}
