<?php

namespace   Silvioq\ReportBundle\Datatable;

use Silvioq\ReportBundle\Datatable\BuilderException;
use Doctrine\ORM\Query\Expr;
use Doctrine\DBAL\Types\Type as ORMType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Datatable builder arrays class
 *
 * TODO: Translate to English all doc
 */
class  Builder {

    private  $alias;
    private  $repo;

    /** @var array */
    private  $joins;

    /**
     * Global conditions for query
     * @var array
     */
    private  $globalConditions;

    /**
     * For search conditions for query
     * @var array
     */
    private  $searchConditions;

    /**
     * @var array
     */
    private  $get;

    /**
     * Column list
     *
     * @var array
     */
    private  $cols;

    /**
     * Hidden column list
     *
     * @var array
     */
    private  $colsH;

    /**
     * @var EntityManagerInterface
     */
    private  $_em;

    /**
     * @var array|null
     */
    private $filter;

    /** @var array|null */
    private $result;

    /**
     * @var array|null
     */
    private $columnTypes = null;

    /**
     * Generated query
     *
     * @var \Doctrine\ORM\Query|null
     */
    private  $query;

    /** @var int|null */
    private  $count;

    /** @var int|null */
    private  $filteredCount;

    /** @var WhereBuilder */
    private $whereBuilder;

    function   __construct( EntityManagerInterface $em, array $get){
        $this->alias = 'a';
        $this->joins = array();
        $this->cols  = array();
        $this->colsH = array();
        $this->filter= array();
        $this->globalConditions = array();
        $this->searchConditions = array();
        $this->get   = $get;
        $this->_em   = $em;

        $this->query = null;
        $this->count = null;
        $this->columnTypes = null;
        $this->filteredCount = null;

        $this->whereBuilder = new WhereBuilder($this->_em);
    }

    /**
     * Set FROM table for query
     *
     * @param string $repo Repo name
     * @param string $alias
     * @return self
     */
    public  function   from( $repo, $alias ):self{
        $this->resetQuery();
        $this->repo = $repo;
        $this->alias = $alias;
        return  $this;
    }

    /**
     * Adds column to query
     * @param  string $col  Column name. It can be preceded by alias 
     * @throws BuilderException if $col alredy added
     * @return self
     */
    public  function   add( $col ){
        if( is_array( $col ) ){
            foreach( $col as $c ) $this->add( $c );
            return $this;
        }

        if( in_array( $col, $this->cols ) )
            throw new BuilderException( sprintf( "Column %s is already added", $col ) );

        $this->cols[] = $col;
        $this->resetQuery();
        return  $this;
    }

    /**
     * Add a condition to global conditions. Affects all return set elements, like count(),
     * filteredCount() and getAll() functions.
     *
     * @param string|callable $condition  Condition to add to query builder. If it's callable,
     *                                    $condition is called with QueryBuilder parameter
     */
    public  function   where( $condition )
    {
        if( !is_callable( $condition ) && !is_string( $condition ) )
            throw new \InvalidArgumentException( 'Condition must be callable or simple string' );

        $this->globalConditions[] = $condition;
        return $this;
    }

    /**
     * Add a condition to filtered wheres. Affects filteredCount() and dataset returned on
     * getAll()
     *
     * @param string|callable $condition  Condition to add to query builder. If it's callable,
     *                                    $condition is called with QueryBuilder parameter
     */
    public  function  condition( $condition )
    {
        if( !is_callable( $condition ) && !is_string( $condition ) )
            throw new \InvalidArgumentException( 'Condition must be callable or simple string' );

        $this->searchConditions[] = $condition;
        return $this;
    }
    
    /**
     * Adds hidden column to query. The query will include the hiddens columns,
     * but this is not returned after execution
     *
     * @param string $col
     * @return self
     */
    public  function   addHidden( $col ):self{
        $this->add( $col );
        $this->colsH[] = self::normalizeColName( $col );
        return  $this;
    }

    /**
     * Add joineable table
     *
     * @param string $field   Field for joined table
     * @param string $alias   Alias for table
     * @return self
     */
    public  function  join($field , $alias ):self{
        if( isset( $this->joins[$alias] ) )
            throw  new  BuilderException( sprintf( '%s already defined', $alias ) );
        $this->resetQuery();
        $this->joins[$alias] = $field;
        return  $this;
    }

    /**
     * Filters an output field with a function
     * @param string   $colName   Column name to apply filter
     * @param callable $function  Callable  Function for filtering. The function receives
     *                            the entire row in raw format and must return
     *                            the value
     * @throws \InvalidArgumentException
     * @throws BuilderException
     * @return self
     */
    public  function  filter($colName, $function ):self{
        if( !is_callable( $function ) )
            throw new \InvalidArgumentException( 'Argument #2 must be callable' );

        if( !in_array( $colName, $this->cols ) )
            throw  new  BuilderException( sprintf( 'Column %s does not exists', $colName ) );

        $colName = self::normalizeColName($colName);
        if( isset( $this->filter[$colName] ) )
            throw  new  BuilderException( sprintf( 'Filter in column %s already defined', $colName ) );

        $this->resetQuery();
        $this->filter[$colName] = $function;
        return  $this;
    }

    /**
     * Get main repo
     *
     * @return string
     */
    public  function   getRepo():string
    {
        if( null === $this->repo ) {
            throw new BuilderException( 'Main repository not defined' );
        }

        return $this->repo;
    }

    /**
     * Get alias for repository
     *
     * @return string
     */
    public  function   getAlias():string{
        return  $this->alias;
    }

    /**
     * Get all join declarations
     *
     * @return array
     */
    public  function   getJoins():array{
        return  $this->joins;
    }

    public  function   getColumns(){ return $this->cols; }

    /**
     * Reset cached query and results
     */
    private function   resetQuery(){
        $this->query = null;
        $this->result = null;
        $this->columnTypes = null;
        $this->count = null;
        $this->filteredCount = null;
        return $this;
    }

    /**
     * Useful for Datatable. Draw is the number of ejecution and must be
     * returned in Json response
     *
     * @return  integer|null
     */
    public  function   getDraw()
    {
        if( !$this->get ) return null;
        if( !isset( $this->get['draw'] ) ) return null;
        return $this->get['draw'];
    }

    /**
     * TODO: This function must be implemented in another class. They will receive alias, table, columns, joins and query (get).
     * @return \Doctrine\ORM\AbstractQuery
     */
    private  function   dataTableQuery($forCount = false):\Doctrine\ORM\AbstractQuery
    {
        $alias = $this->getAlias();
        $get   = $this->get;
        $cols  = $this->getColumns();
        $joins = $this->getJoins();

        /*
         * Set to default
         */
        $aColumns = array();
        $oColumns = array();
        
        foreach($cols as $value){
          if( strpos( $value, '.' ) > 0 ){
              $oColumns[] = $value;
              $aColumns[] = $value . ' as ' . self::normalizeColName($value);
          } else {
              $aColumns[] = $alias .'.'. $value;
              $oColumns[] = $alias .'.'. $value;
          }
        }
        if( $forCount ){
            $select = "count($alias)";
        } else {
            $select = str_replace(" , ", " ", implode(", ", $aColumns));
        }

        /** @var QueryBuilder */
        $cb = $this->getRepository()
                ->createQueryBuilder($alias)
                ->select($select)
                ;

        $this->whereBuilder->setQueryBuilder($cb)
            ->setColumnTypes($this->getColumnTypes())
            ;

        foreach( $joins as $a => $j ){
            $cb->leftJoin( $j, $a);
        }

        /**
         * Si no estamos contando, entonces establecemos
         * el rango de registros
         */
        if( !$forCount && isset( $get['start'] ) )
            $cb->setFirstResult( (int)$get['start'] );

        if( !$forCount && isset( $get['length'] ) && ((int)$get['length'] ) > 0 )
             $cb->setMaxResults( (int)$get['length'] );

     
        /**
         * Ordering
         */
        if ( !$forCount && isset( $get['order'] ) ){
            foreach( $get['order'] as $order ){
               $cb->orderBy($oColumns[ (int)$order['column'] ], $order['dir']);
            }
        }

        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        if ( isset($get['search']) && $get['search']['value'] != '' ){
            $search = $get['search']['value'];
            $aLike = array();
            for ( $i=0 ; $i<count($oColumns) ; $i++ ){
                if ( isset($get['columns'][$i])
                  && isset($get['columns'][$i]['searchable'] )
                  && false !== $get['columns'][$i]['searchable'] ){
                    $filter = $this->whereBuilder->getWhereFor($oColumns[$i], $search);
                    if( '' !== $filter ) array_push( $aLike, $filter );
                }
            }
            if(count($aLike) > 0) $cb->andWhere(new Expr\Orx($aLike));
            else unset($aLike);
        }

        /**
         * Filtrando por columnas en particular
         * Recorro todos los sSearch recibidos y veo si alguno tiene algo.
         * De tenerlo, lo agrego como "and"
         */
        for ( $i=0 ; $i<count($oColumns) ; $i++ ){
            if( !isset( $get['columns'][$i] ) ) continue;
            $column = $get['columns'][$i];
            if( isset( $column['search'] ) && isset( $column['search']['value'] ) && $column['search']['value'] )
            {
                $val = $column['search']['value'];

                /** @var string */
                $filter = $this->whereBuilder->getExpresiveWhere($oColumns[$i], $val);

                if( '' !== $filter ) $cb->andWhere( $filter );
            }
        }

        /**
         * Adds search conditions
         */
        $this->addWheresToCB( $cb, $this->searchConditions );

        /**
         * Adds global conditions
         */
        $this->addWheresToCB( $cb, $this->globalConditions );
       
        /*
         * SQL queries
         * Get data to display
         */
        $query = $cb->getQuery();
        return $query;
    }

    /**
     * @var \Doctrine\ORM\EntityRepository
     */
    private function getRepository():\Doctrine\ORM\EntityRepository
    {
        return $this->_em
            ->getRepository($this->getRepo());
    }

    /**
     * Returns ORM Query
     * @return \Doctrine\ORM\AbstractQuery
     */
    private function getQuery():\Doctrine\ORM\AbstractQuery
    {
        if( null === $this->query ) {
            $this->query = $this->dataTableQuery();
        }
        return  $this->query;
    }

    private function getColumnTypes( ):array
    {
        if( $this->columnTypes !== null )
            return $this->columnTypes;

        $md = $this->_em->getClassMetadata( $this->getRepo() );
        $alias = $this->getAlias();
        $ret = array();
        $fieldNames = $md->getFieldNames();
        foreach( $fieldNames as $field )
        {
            $ret[$field] = $ret[$alias . "." . $field] = $md->getTypeOfField($field);
        }

        foreach( $this->getJoins() as $a => $table )
        {
            $table = preg_replace( '/^[^\.]+./', '', $table );
            $map =  $md->getAssociationMapping( $table );
            $mdr =  $this->_em->getClassMetadata( $map['targetEntity'] );
            foreach( $mdr->getFieldNames() as $field )
            {
                $ret[$a. '.' . $field] = $mdr->getTypeOfField($field);
            }
        }
        return  $this->columnTypes = $ret;
    }

    /**
     * Get result of query.
     *
     * @return \Generator
     */
    public  function  getAll()
    {
        $cols   = $this->getColumns();
        $result = array();
        foreach( $this->getResult() as $row ) {
            $xrow = array();
            for( $i = 0; $i < count( $cols ); $i ++ ) {
                $colName = self::normalizeColName( $cols[$i] );
                if( !$colName ) continue;

                /* Not returned columns */
                if( in_array( $colName, $this->colsH ) ) continue;

                if( isset( $this->filter[$colName] ) ){
                    $data = $this->filter[$colName]( $row );
                    $xrow[$colName] = $data;
                } else if( $row[$colName] instanceof \DateTime && $this->getColumnTypes()[$colName] ===  ORMType::TIME )
                {
                    $xrow[$colName] = $row[$colName]->format( 'H:i' );
                } else if( $row[$colName] instanceof \DateTime ){
                    $xrow[$colName] = $row[$colName]->format( 'Y-m-d' );
                } else if( is_object( $row[$colName] ) ){
                    $xrow[$colName] = $row[$colName]->__toString();
                } else {
                    $xrow[$colName] = $row[$colName];
                }
            }
            yield $xrow;
        }
    }

    /**
     * Devuelve todas las líneas en formato array, sin nombres de campo (NO_ASSOC)
     *
     * @return array
     */
    public  function  getArray():array
    {
        $ret = [];
        foreach( $this->getAll() as $v ){
            $ret[] = array_values( $v );
        }
        return $ret;
    }


    static private function normalizeColName($colName):string
    {
        return  str_replace( '.', '_', $colName );
    }



    /**
     * Get all fields from table (counting only global conditions)
     *
     * @return int
     */
    public function getCount():int
    {
        if( $this->count === null ){
            $alias = $this->getAlias();
            $cb = $this->getRepository()
                ->createQueryBuilder($alias)
                ->select( 'COUNT(' . $alias . ' )' )
                ->setMaxResults(1);
            $this->addWheresToCB( $cb, $this->globalConditions );

            // dado que al agregar "globalConditions" al QueryBuilder puede haber
            // referecias a joins externos, agrego los joins que hubiera
            if( count( $this->globalConditions ) > 0 )
            {
                foreach( $this->getJoins() as $a => $j ){
                    $cb->leftJoin( $j, $a);
                }
            }

            $aResultTotal = $cb->getQuery()->getResult();
            $this->count = intval($aResultTotal[0][1]);
        }
        return  $this->count;
    }

    /**
     * Get record counts with filtering, applying filters and global conditions
     *
     * @return int
     */
    public  function  getFilteredCount():int
    {
        if( null === $this->filteredCount ){
            $query = $this->dataTableQuery(true);
            $aResultTotal = $query->getResult();
            $this->filteredCount = intval($aResultTotal[0][1]);
        }
        return  $this->filteredCount;
    }

    /**
     * Adds global conditions to QueryBuilder
     */
    private  function  addWheresToCB( QueryBuilder $cb, array $conditions )
    {
        foreach( $conditions as $customWhere )
        {
            if( is_callable( $customWhere ) )
            {
                $data = $customWhere( $cb );
                if( null !== $data )
                    $cb->andWhere( $data );
            }
            else
            {
                $cb->andWhere( $customWhere );
            }
        }
    }

    /**
     * Get result of query.
     *
     * @return array
     */
    private  function  getResult():array
    {
        if( $this->result === null ){
            $this->result = $this->getQuery()->getResult();
        }
        return $this->result;
    }
}
// vim:sw=4 ts=4 sts=4 et
