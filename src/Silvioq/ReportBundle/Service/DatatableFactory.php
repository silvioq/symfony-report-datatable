<?php

namespace  Silvioq\ReportBundle\Service;

use  Silvioq\ReportBundle\Datatable\Builder;

class DatatableFactory
{

    private   $doctrine;
    private   $request_stack;

    public  function  __construct( $doctrine, $request_stack )
    {
        $this->doctrine = $doctrine;
        $this->request_stack = $request_stack;
    }

    public  function  buildDatatable( )
    {
        $currentRequest = $this->request_stack->getCurrentRequest( );

        return  new  Builder( $this->doctrine->getManager(), $currentRequest->query->all() + $currentRequest->request->all());
    }

}
