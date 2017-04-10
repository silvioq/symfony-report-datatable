<?php

namespace  Silvioq\ReportBundle\Tests;

use PHPUnit\Framework\TestCase;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Silvioq\ReportBundle\Service\DatatableFactory;
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
          $emMock  = $this->getMock('\Doctrine\ORM\EntityManager',
               array('getRepository', 'getClassMetadata', 'persist', 'flush'), array(), '', false);
        
        $factory = new DatatableFactory( $emMock, $this->requestStack );
        $this->assertInstanceOf( Builder::class, $factory->buildDatatable() );
    }
    

}
