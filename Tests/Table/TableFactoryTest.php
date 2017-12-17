<?php

namespace  Silvioq\ReportBundle\Tests\Table;

use PHPUnit\Framework\TestCase;
use Silvioq\ReportBundle\Table\TableFactory;
use Silvioq\ReportBundle\Table\Table;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Silvioq\ReportBundle\Tests\MockBuilder\ClassMetadataInfoMockBuilder;
use Doctrine\DBAL\Types\Type as ORMType;

class TableFactoryTest extends TestCase
{

    public function setUp()
    {
        // Force autoload for Annotation reader
        new \Doctrine\ORM\Mapping\Column();
    }

    public function testEntity()
    {
        $emMock = $this
            ->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityMock = $this
            ->getMockBuilder(stdClass::class)
            ->getMock();

        $factory = new TableFactory($emMock, new AnnotationReader());

        $metadata = new ClassMetadataInfoMockBuilder($this, $emMock, get_class($entityMock) );
        $metadata
            ->addField( 'field1', ORMType::INTEGER )
            ->addField( 'field2' )
            ->build(false);

        $table = $factory->build(get_class($entityMock));
        $this->assertInstanceOf(Table::class, $table);
    }

    /**
     * @depends testEntity
     */
    public function testEntityWithOptions()
    {
        $emMock = $this
            ->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityMock = $this
            ->getMockBuilder(stdClass::class)
            ->getMock();

        $factory = new TableFactory($emMock, new AnnotationReader());

        $metadata = new ClassMetadataInfoMockBuilder($this, $emMock, get_class($entityMock) );
        $metadata
            ->addField( 'field1', ORMType::INTEGER )
            ->addField( 'field2' )
            ->build(false);

        $table = $factory->build(get_class($entityMock), ['array_separator' => '-' ]);
        $this->assertInstanceOf(Table::class, $table);
    }

    /**
     * @depends testEntity
     */
    public function testReader()
    {
        $emMock = $this
            ->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $factory = new TableFactory($emMock, new AnnotationReader());
        $table = $factory->build(Entity\Entity::class);
        $this->assertInstanceOf( Table::class, $table );

        $this->assertEquals( [ "Age", "This name" ], $table->getHeader() );

        $entity = new Entity\Entity();
        $entity->setName('Maradona');
        $this->assertEquals( [ 42, 'Maradona'], $table->getRow($entity) );
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @expectedExceptionMessage The option "key" does not exist. Defined options are: "getter", "label", "name", "order".
     * @depends testEntity
     */
    public function testReaderWithInvalidAnnotation()
    {
        $emMock = $this
            ->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $factory = new TableFactory($emMock, new AnnotationReader());
        $table = $factory->build(Entity\EntityWithInvalidAnnotation::class);
        $this->assertInstanceOf( Table::class, $table );
    }

}
