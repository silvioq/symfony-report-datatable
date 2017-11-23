<?php

namespace   Silvioq\ReportBundle\Table;

use Silvioq\ReportBundle\Util\Scalarize;

/**
 * @author silvioq
 */
class Table
{
    /** @var array */
    private $columns;
    
    /** @var string */
    private $entityClass;

    /** @var Scalarize */
    private $scalarizer;

    public function __construct($entityClass, array $scalarizerOption = array())
    {
        $this->entityClass = $entityClass;
        $this->columns = [];
        $this->scalarizer = new Scalarize($scalarizerOption);
    }

    /**
     * @return self
     */
    public function add( $name, $label = null, $getter = null ):self
    {
        array_push( $this->columns, new Column($name,$label, $getter ) );
        return $this;
    }

    /**
     * @return array
     */
    public function getHeader():array
    {
        if( !count($this->columns) )
            throw new \LogicException("Generator not initialized");
              
        return array_map( function(Column $col){
            return $col->getLabel();
        }, $this->columns );
    }

    /**
     * Return table row with scalar values
     * @return array
     */
    public function getRow($entity):array
    {
        $data = $this->getRawData($entity);
        foreach( $data as &$row ) $row = $this->scalarizer->scalarize($row);
        return $data;
    }

    /**
     * Return table row
     * @return array
     */
    public function getRawData($entity):array
    {
        if( !count($this->columns) )
            throw new \LogicException("Generator not initialized");

        if( !($entity instanceof $this->entityClass ) )
            throw new \InvalidArgumentException(sprintf( "Argument 1 must be an instance of %s", $this->entityClass ) );

        return array_map( function(Column $col)use($entity){
                $getter = $col->getGetter();
                if( is_callable( $getter ) )
                {
                    return $getter($entity);
                }
                else return $entity->$getter();
            }, $this->columns );
    }

}
