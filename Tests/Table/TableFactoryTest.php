<?php

namespace  Silvioq\ReportBundle\Tests\Table;

use PHPUnit\Framework\TestCase;
use Silvioq\ReportBundle\Table\TableFactory;
use Silvioq\ReportBundle\Table\DefinitionLoaderInterface;
use Silvioq\ReportBundle\Table\Table;

class TableFactoryTest extends TestCase
{
    public function testCreation()
    {
        $loaderMock = $this
            ->getMockBuilder(DefinitionLoaderInterface::class)
            ->getMock();

        $loaderMock->expects($this->once())
            ->method('addColumns')
            ->willReturn(DefinitionLoaderInterface::COMPLETE);

        $factory = (new TableFactory())->addLoader($loaderMock,0);

        $this->assertInstanceOf(Table::class, $factory->build(stdClass::class));
    }

    public function testPriority()
    {
        $loaderMock = $this
            ->getMockBuilder(DefinitionLoaderInterface::class)
            ->getMock();

        $notCalledMock = $this
            ->getMockBuilder(DefinitionLoaderInterface::class)
            ->getMock();

        $loaderMock->expects($this->once())
            ->method('addColumns')
            ->willReturn(DefinitionLoaderInterface::COMPLETE);

        $notCalledMock->expects($this->never())
            ->method('addColumns')
            ->willReturn(DefinitionLoaderInterface::COMPLETE);

        $factory = (new TableFactory())
            ->addLoader($notCalledMock,0)
            ->addLoader($loaderMock,10);
        $this->assertInstanceOf(Table::class, $factory->build(stdClass::class));
    }

    public function testReturnControl()
    {
        $loaderMock = $this
            ->getMockBuilder(DefinitionLoaderInterface::class)
            ->getMock();

        $secondMock = $this
            ->getMockBuilder(DefinitionLoaderInterface::class)
            ->getMock();

        $loaderMock->expects($this->once())
            ->method('addColumns')
            ->willReturn(DefinitionLoaderInterface::PARTIAL);

        $secondMock->expects($this->once())
            ->method('addColumns')
            ->willReturn(DefinitionLoaderInterface::COMPLETE);

        $factory = (new TableFactory())
            ->addLoader($secondMock,0)
            ->addLoader($loaderMock,10);
        $this->assertInstanceOf(Table::class, $factory->build(stdClass::class));
    }
}
// vim:sw=4 ts=4 sts=4 et
