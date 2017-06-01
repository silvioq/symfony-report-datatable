<?php

namespace  Silvioq\ReportBundle\Service;

use Doctrine\Common\Annotations\Reader;
use Silvioq\ReportBundle\Service\Annotation\TableColumn;
use Silvioq\ReportBundle\Table\Table;


class TableFactory
{
    
    /**
     * @var Reader
     */
    private $reader;
    
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }
    
    /**
     * @return Table
     */    
    public function build($entityClass)
    {
        $class = new \ReflectionClass($entityClass);
        // TODO: Check. Needed for autoload
        new TableColumn();

        $columns = [];

        foreach( $class->getMethods() as $method )
        {
            $annotation = $this->reader->getMethodAnnotation($method, TableColumn::class);
            if( null === $annotation ) continue;

            if( null === $annotation->name )
            {
                $annotation->name = preg_replace( '/^get/', '', $method->getName() );
                $annotation->name = strtolower( substr( $annotation->name, 0, 1 ) ) . substr( $annotation->name, 1 );
            }
            
            if( null === $annotation->getter )
                $annotation->getter = $method->getName();
            
            array_push( $columns, $annotation );
        }

        foreach( $class->getProperties() as $property )
        {
            $annotation = $this->reader->getPropertyAnnotation($property, TableColumn::class);
            if( null === $annotation ) continue;
            
            if( null === $annotation->name )
                $annotation->name = $property->getName();

            array_push( $columns, $annotation );
        }

        usort( $columns, function($a,$b){
            if( $a->order < $b->order ) return -1;
            if( $a->order > $b->order ) return 1;
            return 0;
        });

        $table = new Table($entityClass);

        foreach( $columns as $col )
        {
            $table->add( $col->name, $col->label, $col->getter );
        }
        
        return $table;
    }
}
