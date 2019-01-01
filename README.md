# Doctrine - Symfony - Datatable Integration

* Simple class for [Datatables](https://datatables.net) integration.

## Symfony 4

```php
# config/bundles.php
...
    Silvioq\ReportBundle\SilvioqReportBundle::class => ['all' => true],
...
```

## Datatable usage

TODO

## Table usage

Table is a simple class for generating large reports (or CSV) from your entities

Simple usage

```php
<?php
namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use AppBundle\Entity\Entity;

class SimpleController extends Controller
{
    public function downloadAction()
    {
        $table =  $this->get('silvioq.report.table')->build(Entity::class);
                $em = $this->getDoctrine()->getManager();
        $response = new StreamedResponse();
        $iterator = $em->getRepository(Entity::class)
                ->createQueryBuilder('a')
                ->getQuery()->iterate();

        $trans = $this->get('translator');
        $header = array_map(function($header)use($trans){
                return $trans->trans($header);
            }, $table->getHeader() );

        $response->setCallback(function() use($iterator,$table, $header){
            $f = fopen( "php://output", "w" );
            try
            {
                fputcsv( $f, $header );
                foreach( $iterator as $row )
                {
                    $entity = $row[0];
                    fputcsv( $f, $table->getRow($entity) );
                }
            }
            catch(\Exception $e )
            {
                echo $e->getMessage();
                return;
            }
            fclose( $f );
        });
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'entity.csv' );
        $response->headers->set('Content-Type','text/csv');

        return $response;
    }
}
```


[![Build Status](https://travis-ci.org/silvioq/symfony-report-datatable.svg?branch=master)](https://travis-ci.org/silvioq/symfony-report-datatable)
