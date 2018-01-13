<?php

namespace  Silvioq\ReportBundle\Table\DefinitionLoader;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManagerInterface;
use Silvioq\ReportBundle\Annotation\TableColumn;
use Silvioq\ReportBundle\Table\Table;
use Silvioq\ReportBundle\Table\DefinitionLoaderInterface;


class DoctrineDefinitionLoader implements DefinitionLoaderInterface
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var Reader
     */
    protected $reader;

    public function __construct(EntityManagerInterface $em, Reader $reader)
    {
        $this->em = $em;
        $this->reader = $reader;
    }

    /**
     * @inheritdoc
     */
    public function addColumns(Table $table):int
    {
        $entityClass = $table->getEntityClass();
        $columns = $this->generateColumns($entityClass);

        if( count( $columns ) == 0 )
            return DefinitionLoaderInterface::PARTIAL;

        usort( $columns, function($a,$b){
            if( $a->order < $b->order ) return -1;
            if( $a->order > $b->order ) return 1;
            if( $a->key < $b->key ) return -1;
            if( $a->key > $b->key ) return 1;
            return 0;
        });

        $metadata = null;

        foreach( $columns as $col ) {
            if( $col->expandMTM ) {
                $metadata = $this->em->getClassMetadata($entityClass);
                /** @var array */
                $fieldMapping = $metadata->getAssociationMapping($col->name );
                if (\Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_MANY !== $fieldMapping['type'])
                    throw new \RuntimeException(sprintf('Column %s must be MANY_TO_MANY association', $col->name ));

                $table->addExpansible($col->name, $col->getter,
                    $this->em->getRepository($fieldMapping['targetEntity'])->{$col->expandFinder}(),
                    $col->label ?? ''
                );
            } else {
                $table->add( $col->name, $col->label, $col->getter );
            }
        }

        return DefinitionLoaderInterface::COMPLETE;
    }

    protected function generateColumns($entityClass):array
    {
        /** @var array */
        $columns = $this->columnsFromAnnotation($entityClass);
        if( count( $columns ) == 0 ) {
            $columns = $this->columnsFromMetadata($entityClass);
        }

        return $columns;
    }

    /**
     * @param string $entityClass
     * @return array
     */
    private function columnsFromAnnotation(string $entityClass):array
    {
        $class = new \ReflectionClass($entityClass);
        // TODO: Check. Needed for autoload
        new TableColumn();

        $columns = [];
        $count = 0;

        foreach( $class->getProperties() as $property )
        {
            $annotation = $this->reader->getPropertyAnnotation($property, TableColumn::class);
            if( null === $annotation ) continue;
            
            if( null === $annotation->name )
                $annotation->name = $property->getName();

            $annotation->key = ++$count;
            array_push( $columns, $annotation );
        }

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

            $annotation->key = ++$count;
            array_push( $columns, $annotation );
        }

        return $columns;
    }

    private function columnsFromMetadata($entityClass)
    {
        $metadata = $this->em->getClassMetadata($entityClass);
        if( null === $metadata )
            return [];

        $columns = [];
        $count = 0;
        $fields = $metadata->getFieldNames();
        foreach( $fields as $field )
        {
            $col = new TableColumn();
            $col->name = $field;
            $col->key = $count++;
            array_push( $columns, $col );
        }

        foreach( $metadata->getAssociationMappings() as $field => $mapping )
        {
            switch($mapping['type']){
                case \Doctrine\ORM\Mapping\ClassMetadataInfo::ONE_TO_ONE:
                case \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE:
                    $col = new TableColumn();
                    $col->name = $field;
                    $col->key = $count++;
                    array_push( $columns, $col );
            }
                    
        }

        return $columns;
    }
}
// vim:sw=4 ts=4 sts=4 et
