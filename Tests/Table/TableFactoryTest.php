<?php

namespace  Silvioq\ReportBundle\Tests\Table;

use PHPUnit\Framework\TestCase;
use Silvioq\ReportBundle\Service\TableFactory;
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

    public function testReader()
    {
        $emMock = $this
            ->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $factory = new TableFactory($emMock, new AnnotationReader());
        $table = $factory->build(Entity\Entity::class);
        $this->assertInstanceOf( \Silvioq\ReportBundle\Table\Table::class, $table );

        $this->assertEquals( [ "This name", "Age" ], $table->getHeader() );

        $entity = new Entity\Entity();
        $entity->setName('Maradona');
        $this->assertEquals( [ 'Maradona', 42 ], $table->getRow($entity) );
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @expectedExceptionMessage The option "key" does not exist. Defined options are: "getter", "label", "name", "order".
     */
    public function testReaderWithInvalidAnnotation()
    {
        $emMock = $this
            ->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $factory = new TableFactory($emMock, new AnnotationReader());
        $table = $factory->build(Entity\EntityWithInvalidAnnotation::class);
        $this->assertInstanceOf( \Silvioq\ReportBundle\Table\Table::class, $table );
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
    }

}
