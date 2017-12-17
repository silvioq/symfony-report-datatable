<?php

namespace  Silvioq\ReportBundle\Tests\Datatable;

use PHPUnit\Framework\TestCase;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Silvioq\ReportBundle\Datatable\DatatableFactory;
use Silvioq\ReportBundle\Datatable\Builder;

class  FactoryTest  extends  TestCase
{
    private $requestStack;

    public  function  setup()
    {
        $request = new Request( 
                [], // GET
                [], // POST
                [], // ?
                [], // COOKIE
                [], // FILES
                []  // SERVER
            );
        $this->requestStack = new RequestStack();
        $this->requestStack->push( $request );
    }




    public  function  testFactory()
    {
        $emMock  = $this->createMock('\Doctrine\ORM\EntityManager',
                array('getRepository', 'getClassMetadata', 'persist', 'flush'), array(), '', false);
        (new \Silvioq\ReportBundle\Tests\MockBuilder\ConfigurationMockBuilder($this,$emMock))->configure();

        $factory = new DatatableFactory( $emMock, $this->requestStack );
        $this->assertInstanceOf( Builder::class, $factory->buildDatatable() );
    }
    

}
