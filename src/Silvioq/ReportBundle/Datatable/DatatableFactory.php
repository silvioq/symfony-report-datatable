<?php

namespace  Silvioq\ReportBundle\Datatable;

use Silvioq\ReportBundle\Datatable\Builder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;


class DatatableFactory
{
    /** @var EntityManagerInterface */
    private   $em;

    /** @var RequestStack */
    private   $requestStack;

    public  function  __construct( EntityManagerInterface $em, RequestStack $requestStack )
    {
        $this->em = $em;
        $this->requestStack = $requestStack;
    }

    /**
     * Returns a configured intance of \Silvioq\ReportBundle\Datatable\Builder
     *
     * @return Builder
     */
    public  function  buildDatatable( ):Builder
    {
        $currentRequest = $this->requestStack->getCurrentRequest( );
        return  new  Builder( $this->em, $currentRequest->query->all() + $currentRequest->request->all());
    }

}
