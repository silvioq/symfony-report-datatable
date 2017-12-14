<?php
namespace Silvioq\ReportBundle\Tests\MockBuilder;

use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;

class ConfigurationMockBuilder
{
    /**
     * @var TestCase
     */
    private $test;

    /**
     * @var EntityManagerInterface
     */
    private $emMock;

    /**
     * @var string
     */
    private $driver;

    public function __construct( TestCase $test, EntityManagerInterface $emMock, $driver = 'pdo_pgsql' )
    {
        $this->test = $test;
        $this->emMock = $emMock;
        $this->driver = $driver;
    }

    public static function doctrineExtensionsEnabled():bool
    {
        return class_exists( "DoctrineExtensions\\Query\\Postgresql\\DateFormat" );
    }

    public function configure():self
    {
        /* @var Configuration */
        $confMock = $this->test
                ->getMockBuilder(Configuration::class)
                ->disableOriginalConstructor()
                ->getMock();

        /* @var Connection */
        $connMock = $this->test
                ->getMockBuilder(Connection::class)
                ->disableOriginalConstructor()
                ->getMock();

        /** @var Driver */
        $dMock = $this->test
                ->getMockBuilder(Driver::class)
                ->disableOriginalConstructor()
                ->getMock();

        if( self::doctrineExtensionsEnabled() ) {
            $this->emMock->expects($this->test->exactly(2))
                ->method('getConfiguration')
                ->will($this->test->returnValue($confMock));
        }

        $this->emMock->expects($this->test->once())
            ->method('getConnection')
            ->will($this->test->returnValue($connMock));

        $connMock->expects($this->test->once())
            ->method('getDriver')
            ->with()
            ->willReturn($dMock);

        $dMock->expects($this->test->once())
            ->method('getName')
            ->willReturn($this->driver);

        return $this;

    }

    public function withMetadata():self
    {
        /** @var \Doctrine\ORM\Mapping\ClassMetadataInfo */
        $metadataMock = $this->test
              ->getMockBuilder('\Doctrine\ORM\Mapping\ClassMetadataInfo')
              ->disableOriginalConstructor()
              ->getMock();

        $metadataMock->expects($this->test->any())
            ->method('getFieldNames')
            ->willReturn([]);

        $this->emMock->expects($this->test->any())
            ->method('getClassMetadata')
            ->will($this->test->returnValue($metadataMock))
            ;

        return $this;
    }
}
// vim:sw=4 ts=4 sts=4 et
