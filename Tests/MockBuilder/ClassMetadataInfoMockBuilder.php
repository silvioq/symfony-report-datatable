<?php

namespace Silvioq\ReportBundle\Tests\MockBuilder;

use PHPUnit\Framework\TestCase;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\DBAL\Types\Type as ORMType;
use Doctrine\ORM\EntityManagerInterface;

class ClassMetadataInfoMockBuilder
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
    private $repoName;

    /**
     * @var array
     */
    private $fields = [];

    /**
     * @var array
     */
    private $assoc = [];

    public function __construct( TestCase $test, EntityManagerInterface $emMock, $repoName )
    {
        $this->test = $test;
        $this->emMock = $emMock;
        $this->repoName = $repoName;
    }
    
    public function addField( $fieldName, $type = ORMType::INTEGER )
    {
        $this->fields[] = [ 'name' => $fieldName, 'type' => $type ];
        return $this;
    }

    public function addManyToOne( $fieldName )
    {
        $this->assoc[$fieldName] = [ 'type' => \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE ];
    }

    /**
     * @return ClassMetadataInfo
     */
    public function build($expectTypes=true)
    {
        /* @var ClassMetadataInfo */
        $mock = $this->test
                ->getMockBuilder(ClassMetadataInfo::class)
                ->disableOriginalConstructor()
                ->getMock();

        $fields = [];
        $with = [];
        $names = [];
        foreach( $this->fields as $f )
        {
            array_push( $with, [ $this->test->equalTo($f['name'] ) ] );
            array_push( $names, $f['name'] );
            $fields[$f['name']] = $f['type'];
        }

        $mock->expects($this->test->once())
            ->method('getFieldNames')
            ->will($this->test->returnValue($names))
            ;

        $mock->expects($this->test->any())
            ->method('getAssociationMappings')
            ->will($this->test->returnValue($this->assoc))
            ;

        $this->emMock->expects($this->test->atLeastOnce())
            ->method('getClassMetadata')
            ->with($this->test->equalTo($this->repoName))
            ->will($this->test->returnValue($mock))
            ;

        if( $expectTypes )
        {
            $method = $mock->expects($this->test->exactly(count($with)))
                ->method('getTypeOfField')
                ;

            call_user_func_array([$method,'withConsecutive'],$with)
                ->will($this->test->returnCallback(function($arg) use($fields){
                        if( !isset( $fields[$arg] ) )
                            throw new \LogicException( 'Error on consecutive calls' );

                        return $fields[$arg];
                    } ) )
                ;
        }

        return $mock;
    }
}
