<?php

namespace  Silvioq\ReportBundle\Service;

use Silvioq\ReportBundle\Datatable\Builder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;


class DatatableFactory
{

    private   $em;
    private   $request_stack;

    public  function  __construct( EntityManagerInterface $em, RequestStack $request_stack )
    {
        $this->em = $em;
        $this->request_stack = $request_stack;
    }

    public  function  buildDatatable( )
    {
        $currentRequest = $this->request_stack->getCurrentRequest( );

        return  new  Builder( $this->em, $currentRequest->query->all() + $currentRequest->request->all());
    }

}
