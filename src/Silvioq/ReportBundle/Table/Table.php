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
     * Add field.
     *
     * @return self
     */
    public function add( $name, $label = null, $getter = null ):self
    {
        if( isset( $this->columns[$name] ) )
            throw new \InvalidArgumentException( sprintf( 'Column %s already exists', $name ) );

        $this->columns[$name] = new Column($name, $label, $getter);
        return $this;
    }

    /**
     * Add expansible
     *
     * @param string $name  Generic name of column
     * @param string $getter  Getter of entity collection
     * @param array|\Traversable $targetCollection  Collection of elements
     *
     * @return self
     */
    public function addExpansible(string $name, $getter, $targetCollection)
    {
        if( null === $getter )
            $getter = 'get' . ucfirst( $name );
        else if( !is_string( $getter ) && !is_callable( $getter ) )
            throw new \InvalidArgumentException( 'getter must be string or callable' );

        if( !is_array($targetCollection) && !$targetCollection instanceof \Traversable )
            throw new \InvalidArgumentException('$targetCollection argument must be iterable');

        foreach ($targetCollection as $targetEntity) {
            $subColumnName = $this->scalarizer->scalarize($targetEntity);
            $columnName = $name.'.'.$subColumnName;

            $callback = function($entity) use($targetEntity,$getter){
                if( is_callable( $getter ) ) {
                    $entityList = $getter($entity);
                } else
                    $entityList = $entity->$getter();

                if( !is_array($entityList) && !$entityList instanceof \Traversable )
                    return false;

                foreach( $entityList as $selectedEntity ) {
                    if( $targetEntity === $selectedEntity ) {
                        return true;
                    }
                }
                return false;
            };

            $this->add( $columnName, $columnName, $callback );
        }
        return $this;
    }

    /**
     * @return self
     *
     * @throws \OutOfBoundsException
     */
    public function removeField( $name ):self
    {
        if( !isset( $this->columns[$name] ) ) {
            throw new \OutOfBoundsException( sprintf( 'Column %s does not exists', $name ) );
        }

        unset( $this->columns[$name] );

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
        }, array_values($this->columns) );
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
                if( is_callable( $getter ) ) {
                    return $getter($entity);
                } else
                    return $entity->$getter();
            }, array_values($this->columns) );
    }
}
// vim:sw=4 ts=4 sts=4 et
