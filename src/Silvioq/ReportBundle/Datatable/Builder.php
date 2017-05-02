<?php
namespace   Silvioq\ReportBundle\Datatable;
use   Silvioq\ReportBundle\Datatable\BuilderException;
use   Doctrine\ORM\Query\Expr;
use   Doctrine\DBAL\Types\Type as ORMType;
use   Doctrine\ORM\EntityManagerInterface;


class  Builder {

    private  $alias;
    private  $repo;
    private  $joins;

    /**
     * Global wheres for query
     * @var array
     */
    private  $wheres;

    /**
     * For filtered count wheres for query
     * @var array
     */
    private  $wheresOnFilter;

    /**
     * @var array
     */
    private  $get;

    /**
     * Lista de columnas
     * @var array
     */
    private  $cols;
    private  $colsH;

    /**
     * @var EntityManagerInterface
     */
    private  $_em;

    /**
     * @var array
     */
    private  $filter;
    private  $result;
    private  $columnTypes;

    private  $query;
    private  $count;
    private  $filteredCount;

    function   __construct( EntityManagerInterface $em, array $get){
        $this->alias = 'a';
        $this->joins = array();
        $this->cols  = array();
        $this->colsH = array();
        $this->filter= array();
        $this->wheres= array();
        $this->wheresOnFilter = array();
        $this->get   = $get;
        $this->_em   = $em;

        $this->query = null;
        $this->count = null;
        $this->columnTypes = null;
        $this->filteredCount = null;
        $this->dateFormatFunc = class_exists( "DoctrineExtensions\Query\Postgresql\DateFormat" );

        if( $this->dateFormatFunc && $em->getConfiguration()->getCustomDatetimeFunction( 'DATE_FORMAT' ) === null )
        {
            switch($em->getConnection()->getDriver()->getName() )
            {
                case 'pdo_pgsql':
                    $em->getConfiguration()->addCustomDatetimeFunction( 'DATE_FORMAT', 
                            \DoctrineExtensions\Query\Postgresql\DateFormat::class );
                    break;

                case 'pdo_mysql':
                    $em->getConfiguration()->addCustomDatetimeFunction( 'DATE_FORMAT', 
                            \DoctrineExtensions\Query\Mysql\DateFormat::class );
                    break;

                case 'pdo_oracle':
                    $em->getConfiguration()->addCustomDatetimeFunction( 'DATE_FORMAT', 
                            \DoctrineExtensions\Query\Postgresql\DateFormat::class );
                    break;

                default:
                    $this->dateFormatFunc = false;
            }
        }
    }


    public  function   from( $repo, $alias ){
        $this->resetQuery();
        $this->repo = $repo;
        $this->alias = $alias;
        return  $this;
    }

    public  function   add( $col ){
        if( is_array( $col ) ){
            foreach( $col as $c ){
                $this->cols[] = $c;
            }
        } else
            $this->cols[] = $col;
        $this->resetQuery();
        return  $this;
    }

    public  function   where( $condition )
    {
        $this->wheres[] = $condition;
        return $this;
    }

    public  function  whereOnFiltered( $condition )
    {
        $this->wheresOnFilter[] = $condition;
        return $this;
    }

    public  function   addHidden( $col ){
        $this->add( $col );
        $this->colsH[] = self::normalizeColName( $col );
        return  $this;
    }

    public  function  join($field , $alias ){
        if( isset( $this->joins[$alias] ) )
            throw  new  BuilderException( $alias . ' ya definido' );
        $this->resetQuery();
        $this->joins[$alias] = $field;
        return  $this;
    }

    /**
     * Filters an output field with a function
     */
    public  function  filter($colName, $function ){
        $colName = self::normalizeColName($colName);
        if( isset( $this->joins[$colName] ) )
            throw  new  BuilderException( 'Filtro en ' . $colName . ' ya definido' );
        $this->resetQuery();
        $this->filter[$colName] = $function;
        return  $this;
    }

    public  function   getRepo(){ return $this->repo; }
    public  function   getAlias(){ return  $this->alias; }
    public  function   getJoins(){ return  $this->joins; }
    public  function   getColumns(){ return $this->cols; }

    private function   resetQuery(){ $this->query = null; $this->result = null; $this->columnTypes = null; return $this; }

    public  function   getDraw()
    {
        if( !$this->get ) return null;
        if( !isset( $this->get['draw'] ) ) return null;
        return $this->get['draw'];
    }

    private  function   dataTableQuery($forCount = false){
        $alias = $this->getAlias();
        $table = $this->getRepo();
        $get   = $this->get;
        $cols  = $this->getColumns();
        if( !$table ) throw  new  BuilderException( 'Repositorio no definido' );
        $joins = $this->getJoins();
        /*
         * Set to default
         */
        $aColumns = array();
        $oColumns = array();
        $mColumns = array();
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
   
        $cb = $this->_em
                ->getRepository($table)
                ->createQueryBuilder($alias)
                ->select($select)
                ;
        foreach( $joins as $a => $j ){
            $cb->leftJoin( $j, $a);
        }
    
        if ( !$forCount && isset( $get['start'] ) && $get['length'] != '-1' ){
            $cb->setFirstResult( (int)$get['start'] )
               ->setMaxResults( (int)$get['length'] );
        }
     
        /*
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
                    $filter = $this->getWhereFor( $oColumns[$i], $search, $cb );
                    if( $filter ) array_push( $aLike, $filter );
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
                $filter = null;
                if( $val === 'is null'){
                    $filter = $cb->expr()->isNull( $oColumns[$i] );
                } else if( $val === 'is not null' ){
                    $filter = $cb->expr()->isNotNull( $oColumns[$i] );
                } else if( preg_match( '/^not\s+in\s*\((.*)\)$/', $val, $matches ) ){
                    $filter = $cb->expr()->notIn( $oColumns[$i], $matches[1] );
                } else if( preg_match( '/^in\s*\((.*)\)$/', $val, $matches ) ){
                    $lista = preg_split( "/,/", $matches[1] );
                    $param = $this->createParameter( $lista, $cb );
                    $filter = $cb->expr()->In( $oColumns[$i], $param );
                } else if( $val === 'true' || $val === 'false' ){
                    $filter = $cb->expr()->eq( $oColumns[$i], $val );
                } else if( preg_match( '/^between\s+(.*)and(.*)$/', $val, $matches ) ) {
                    $ct = $this->getColumnType( $oColumns[$i] );
                    if( $this->dateFormatFunc && ( $ct == ORMType::DATE ||  $ct == ORMType::DATETIME ) )
                        $filter = $cb->expr()->between( sprintf("DATE_FORMAT(%s,%s)",$oColumns[$i],
                            $this->createParameter('YYYY-MM-DD', $cb ) ), 
                            $this->createParameter(trim($matches[1]), $cb ),
                            $this->createParameter(trim($matches[2]), $cb ) );
                    else
                        $filter = $cb->expr()->between( $oColumns[$i], $matches[1], $matches[2] );
                } else{
                    $filter = $this->getWhereFor( $oColumns[$i], $val, $cb );
                }
                if( $filter ) $cb->andWhere( $filter );
            }
        }

        /**
         * Agrego for filtering wheres
         */
        $this->addWheresToCB( $cb, $this->wheresOnFilter );

        /**
         * Agrego where.
         */
        $this->addWheresToCB( $cb, $this->wheres );
       
        /*
         * SQL queries
         * Get data to display
         */
        $query = $cb->getQuery();
        return $query;
    }

    /**
     * @return int
     */
    public  function  getFilteredCount(){
        if( $this->filteredCount === null ){
            $query = $this->dataTableQuery(true);
            $aResultTotal = $query->getResult();
            $this->filteredCount = intval($aResultTotal[0][1]);
        }
        return  $this->filteredCount;
    }

    public  function  getQuery(){
        if( $this->query === null ){
            $this->query = $this->dataTableQuery();
        }
        return  $this->query;
    }

    private function  getColumnTypes( )
    {
        if( $this->columnTypes !== null ) return $this->columnTypes;
        $md = $this->_em->getClassMetadata( $this->getRepo() );
        $alias = $this->getAlias();
        $ret = array();
        $fieldNames = $md->getFieldNames();
        foreach( $fieldNames as $field )
        {
            $ret[$alias . "." . $field] = $md->getTypeOfField($field);
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

    private function  getColumnType( $column )
    {
        if( strpos( $column, '.' ) === false ) return $this->getColumnType( $this->getAlias() . '.' . $column );
        $ct = $this->getColumnTypes();
        if( isset( $ct[$column] ) ) return $ct[$column]; else return "unknonw";
    }


    private  $parameterCount = 0;
    private  $parameterList  = array();
    private function  createParameter( $str, $cb )
    {
        $hash = md5( is_array($str) ? join(',', $str) : $str );
        if( isset( $this->parameterList[$hash]) )
        {
            $param = $this->parameterList[$hash];
        }
        else
        {
            $this->parameterCount ++;
            $param = 'ppp' . $this->parameterCount;
            $this->parameterList[$hash] = $param;
        }
        if( !$cb->getParameter( $param ) ) $cb->setParameter( $param, $str );
        return  ':' . $param;
    }

    /**
     * Retorna una expresión de filtro.
     */
    private function  getWhereFor( $columnName, $searchStr, $cb )
    {
        $ct = $this->getColumnType( $columnName );
        if( $ct === ORMType::STRING || $ct === ORMType::TEXT )
        {
            $param = $this->createParameter( "%" . strtolower( $searchStr ). "%", $cb );
            return  $cb->expr()->like( sprintf('LOWER(%s)',$columnName), $param );
        }
        elseif( in_array( $ct, array( ORMType::INTEGER, ORMType::SMALLINT, ORMType::BIGINT ) )  && is_numeric( $searchStr ) )
        {
            return  $cb->expr()->eq( $columnName, $searchStr );
        }
        elseif( $this->dateFormatFunc && ( $ct == ORMType::DATE ||  $ct == ORMType::DATETIME ) )
        {
            $param = $this->createParameter( "%" . strtolower( $searchStr ). "%", $cb );
            $fecha = $this->createParameter( 'YYYY-MM-DD', $cb );
            return  $cb->expr()->like( sprintf( 'DATE_FORMAT(%s,%s)', $columnName, $fecha ) , $param);
        }
        elseif( $ct == ORMType::DATE ||  $ct == ORMType::DATETIME )
        {
            $param = $this->createParameter( "%" . strtolower( $searchStr ). "%", $cb );
            return  $cb->expr()->like( $columnName, $param );
        }
        return null;
    }


    public  function  getResult(){
        if( $this->result === null ){
            $this->result = $this->getQuery()->getResult();
        }
        return $this->result;
    }

    public  function  getAll(){
        $cols   = $this->getColumns();
        $result = array();
        foreach( $this->getResult() as $row ){
            $xrow = array();
            for( $i = 0; $i < count( $cols ); $i ++ ){
                $colName = self::normalizeColName( $cols[$i] );
                if( !$colName ) continue;
                if( in_array( $colName, $this->colsH ) ) continue;
                if( isset( $this->filter[$colName] ) ){
                    $data = $this->filter[$colName]( $row );
                    $xrow[$colName] = $data;
                } else if( $row[$colName] && $this->getColumnType( $colName ) ===  ORMType::TIME )
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
     * @return array
     */
    public  function  getArray(){
        $ret = [];
        foreach( $this->getAll() as $v ){
            $ret[] = array_values( $v );
        }
        return $ret;
    }


    static  function   normalizeColName($colName){
        return  str_replace( '.', '_', $colName );
    }



    /**
     * @return int
     */
    public function getCount(){
        if( $this->count === null ){
            $alias = $this->getAlias();
            $table = $this->getRepo();
            if( !$table ) throw  new  BuilderException( 'Repositorio no definido' );
            $cb = $this->_em
                ->getRepository( $table )
                ->createQueryBuilder($alias)
                ->select( 'COUNT(' . $alias . ' )' )
                ->setMaxResults(1);
            $this->addWheresToCB( $cb, $this->wheres );

            // dado que al agregar "wheres" al QueryBuilder puede haber
            // referecias a joins externos, agrego los joins que hubiera
            if( count( $this->wheres ) > 0 )
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

    private  function  addWheresToCB( $cb, $wheres )
    {
        foreach( $wheres as $customWhere )
        {
            if( is_callable( $customWhere ) )
            {
                $data = $customWhere( $cb );
                $cb->andWhere( $data );
            }
            else
            {
                $cb->andWhere( $customWhere );
            }
        }
    }
}

?>
