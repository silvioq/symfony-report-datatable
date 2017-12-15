<?php

namespace   Silvioq\ReportBundle\Datatable;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\DBAL\Types\Type as ORMType;

class WhereBuilder
{
    const NOT_SEARCHABLE_COLUMN_TYPES = [
        'BOOLEAN',
        'DATEINTERVAL',
        'BINARY',
        'BLOB',
        'OBJECT',
        'TARRAY',
    ];

    const DATETIME_COLUMN_TYPES = [
        'DATE',
        'DATE_IMMUTABLE',
        'DATETIME',
        'DATETIME_IMMUTABLE',
        'DATETIMETZ',
        'DATETIMETZ_IMMUTABLE',
    ];

    const TIME_COLUMN_TYPES = [
        'TIME',
        'TIME_IMMUTABLE',
    ];

    const JSON_COLUMN_TYPES = [
        'JSON',
        'JSON_ARRAY',
    ];

    const NUMERIC_COLUMN_TYPES = [
        'INTEGER',
        'SMALLINT',
        'BIGINT',
        'DECIMAL',
        'FLOAT',
    ];

    const STRING_COLUMN_TYPES = [
        'STRING',
        'TEXT',
        'SIMPLE_ARRAY',
        'GUID',
    ];

    const STRING = 1;
    const NUMERIC = 2;
    const DATE = 3;
    const TIME = 4;
    const NOTSEARCH = 5;
    const JSON = 6;

    /**
     * @var int
     */
    private $parameterCount = 0;

    /**
     * @var array
     */
    private $parameterList  = array();

    /**
     * @var array
     */
    private $columnTypes;

    /**
     * @var QueryBuilder|null
     */
    private $qb;

    /**
     * @var bool
     */
    private $dateFormatFunc = false;

    /**
     * @var bool
     */
    private $isPostgres = false;

    /**
     * @var array|null
     */
    private $ORMColumnTypes = null;

    public function  __construct(EntityManagerInterface $em)
    {
        /** @var bool */
        $this->dateFormatFunc = class_exists( 'DoctrineExtensions\Query\Postgresql\DateFormat' );

        /** @var string */
        $driverName = $em->getConnection()->getDriver()->getName();
        $this->isPostgres = 'pdo_pgsql' === $driverName;

        if( $this->dateFormatFunc && $em->getConfiguration()->getCustomDatetimeFunction( 'DATE_FORMAT' ) === null )
        {
            switch ($driverName)
            {
                case 'pdo_pgsql':
                    $em->getConfiguration()->addCustomDatetimeFunction( 'DATE_FORMAT', 
                            "DoctrineExtensions\\Query\\Postgresql\\DateFormat" );
                    break;

                case 'pdo_mysql':
                    $em->getConfiguration()->addCustomDatetimeFunction( 'DATE_FORMAT', 
                            "DoctrineExtensions\\Query\\Mysql\\DateFormat" );
                    break;

                case 'pdo_oracle':
                case 'oci8':
                    $em->getConfiguration()->addCustomDatetimeFunction( 'DATE_FORMAT', 
                            "DoctrineExtensions\\Query\\Postgresql\\DateFormat" );
                    break;

                default:
                    $this->dateFormatFunc = false;
            }
        }
    }

    public function setColumnTypes(array $cts):self
    {
        $this->columnTypes = $cts;

        return $this;
    }

    private function getColumnType($columnName):string
    {
        if( strpos( $columnName, '.' ) === false )
            throw new \LogicException(sprintf('Column %s has not alias', $columnName));

        if( isset( $this->columnTypes[$columnName] ) )
            return $this->columnTypes[$columnName];

        throw new \LogicException( sprintf( 'Column %s does not exists', $columnName ) );
    }

    /**
     * @param QueryBuilder $qb
     */
    public function setQueryBuilder(QueryBuilder $qb):self
    {
        $this->qb = $qb;
        $this->parameterCount = 0;
        $this->parameterList = [];

        return $this;
    }

    public function  createParameter($str):string
    {
        if( null === $this->qb )
            throw new \LogicException( 'Must call setQueryBuilder before' );

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
        if( !$this->qb->getParameter( $param ) ) $this->qb->setParameter( $param, $str );
        return  ':' . $param;
    }

    /**
     * Returns a filter expression
     *
     * @param string $columnName
     * @param string $searchStr
     *
     * @return string
     *
     * @throws LogicException
     */
    public function  getWhereFor( string $columnName, string  $searchStr):string
    {
        if( null === $this->qb )
            throw new \LogicException( 'Must call setQueryBuilder before' );

        $ct = $this->getColumnType( $columnName );

        if( $this->isColumnType($ct, self::STRING) ) {
            $param = $this->createParameter( "%" . strtolower( $searchStr ). "%");
            return  $this->qb->expr()->like( sprintf('LOWER(%s)',$columnName), $param );
        }
        elseif( $this->isColumnType($ct, self::NUMERIC))
        {
            if( is_numeric( $searchStr ) )
                return $this->qb->expr()->eq( $columnName, $searchStr );
            else
                return '';
        } else if( $this->isColumnType($ct, self::JSON) ) {
            if( $this->isPostgres )
                return  ''; // TODO write proper condition
            else
            {
                $param = $this->createParameter( "%" . strtolower( $searchStr ). "%");
                return  $this->qb->expr()->like( sprintf('LOWER(%s)',$columnName), $param );
            }
        }

        /** @var bool */
        $isDate = $this->isColumnType($ct, self::DATE);
        if( $this->dateFormatFunc && $isDate )
        {
            $param = $this->createParameter( "%" . strtolower( $searchStr ). "%" );
            $fecha = $this->createParameter( 'YYYY-MM-DD' );
            return  $this->qb->expr()->like( sprintf( 'DATE_FORMAT(%s,%s)', $columnName, $fecha ) , $param);
        }
        elseif( $isDate )
        {
            $param = $this->createParameter( "%" . strtolower( $searchStr ). "%");
            return  $this->qb->expr()->like( $columnName, $param );
        }

        /** @var bool */
        $isTime = $this->isColumnType($ct, self::TIME);
        if( $this->dateFormatFunc && $isTime ) {
            $param = $this->createParameter( "%" . strtolower( $searchStr ). "%" );
            $fecha = $this->createParameter( 'HH:MI:SS' );
            return  $this->qb->expr()->like( sprintf( 'DATE_FORMAT(%s,%s)', $columnName, $fecha ) , $param);
        }
        elseif( $isTime )
        {
            $param = $this->createParameter( "%" . strtolower( $searchStr ). "%" );
            return  $this->qb->expr()->like( $columnName, $param );
        }

        if ($this->isColumnType($ct, self::NOTSEARCH))
            return '';

        throw new \LogicException( sprintf( "Can't generate where expression for column %s, search string %s",
                    $columnName, $searchStr ) );
    }

    /**
     * Get where handling SQL like expression. If not expression
     * detected, return getWhereFor function as fallback
     *
     * @param string $columnName
     * @param string $searchStr
     *
     * @return string
     *
     * @throws LogicException
     */
    public function getExpresiveWhere($columnName, $searchStr)
    {
        if( $searchStr === 'is null'){
            $filter = $this->qb->expr()->isNull( $columnName );
        } else if( $searchStr === 'is not null' ){
            $filter = $this->qb->expr()->isNotNull( $columnName );
        } else if( preg_match( '/^not\s+in\s*\((.*)\)$/', $searchStr, $matches ) ){
            $filter = $this->qb->expr()->notIn( $columnName, $matches[1] );
        } else if( preg_match( '/^in\s*\((.*)\)$/', $searchStr, $matches ) ){
            $lista = preg_split( "/,/", $matches[1] );
            $param = $this->createParameter($lista);
            $filter = $this->qb->expr()->In( $columnName, $param );
        } else if( $searchStr === 'true' || $searchStr === 'false' ){
            $filter = $this->qb->expr()->eq( $columnName, $searchStr );
        } else if( preg_match( '/^between\s+(.*)and(.*)$/', $searchStr, $matches ) ) {
            $ct = $this->getColumnType( $columnName );
            if( $this->dateFormatFunc && ( $ct == ORMType::DATE ||  $ct == ORMType::DATETIME ) )
                $filter = $this->qb->expr()->between( sprintf("DATE_FORMAT(%s,'YYYY-MM-DD')",$columnName),
                    $this->createParameter(trim($matches[1]) ),
                    $this->createParameter(trim($matches[2]) ) );
            else
                $filter = $this->qb->expr()->between( $columnName,
                    $this->createParameter(trim($matches[1])),
                    $this->createParameter(trim($matches[2])));
        } else{
            $filter = $this->getWhereFor( $columnName, $searchStr );
        }
        return $filter;
    }

    private function isColumnType(string $columnORMType, int $columnSearchType):bool
    {
        $this->buildORMTypes();
        if( !isset( $this->ORMColumnTypes[$columnORMType] ) )
            return false;

        return $columnSearchType === $this->ORMColumnTypes[$columnORMType];
    }

    private function buildORMTypes()
    {
        if (null !== $this->ORMColumnTypes )
            return;

        $this->ORMColumnTypes = [];

        foreach( array_map(function($type){
                return constant('Doctrine\DBAL\Types\Type::' . $type);
            }, array_filter( self::STRING_COLUMN_TYPES, function($type) {
                return defined( 'Doctrine\DBAL\Types\Type::' . $type);
            } ) ) as $type ) {
            $this->ORMColumnTypes[$type] = self::STRING;
        }

        foreach( array_map(function($type){
                return constant('Doctrine\DBAL\Types\Type::' . $type);
            }, array_filter( self::NUMERIC_COLUMN_TYPES, function($type) {
                return defined( 'Doctrine\DBAL\Types\Type::' . $type);
            } ) ) as $type ) {
            $this->ORMColumnTypes[$type] = self::NUMERIC;
        }

        foreach( array_map(function($type){
                return constant('Doctrine\DBAL\Types\Type::' . $type);
            }, array_filter( self::DATETIME_COLUMN_TYPES, function($type) {
                return defined( 'Doctrine\DBAL\Types\Type::' . $type);
            } ) ) as $type ) {
            $this->ORMColumnTypes[$type] = self::DATE;
        }

        foreach( array_map(function($type){
                return constant('Doctrine\DBAL\Types\Type::' . $type);
            }, array_filter( self::NOT_SEARCHABLE_COLUMN_TYPES, function($type) {
                return defined( 'Doctrine\DBAL\Types\Type::' . $type);
            } ) ) as $type ) {
            $this->ORMColumnTypes[$type] = self::NOTSEARCH;
        }

        foreach( array_map(function($type){
                return constant('Doctrine\DBAL\Types\Type::' . $type);
            }, array_filter( self::TIME_COLUMN_TYPES, function($type) {
                return defined( 'Doctrine\DBAL\Types\Type::' . $type);
            } ) ) as $type ) {
            $this->ORMColumnTypes[$type] = self::TIME;
        }

        foreach( array_map(function($type){
                return constant('Doctrine\DBAL\Types\Type::' . $type);
            }, array_filter( self::JSON_COLUMN_TYPES, function($type) {
                return defined( 'Doctrine\DBAL\Types\Type::' . $type);
            } ) ) as $type ) {
            $this->ORMColumnTypes[$type] = self::JSON;
        }
    }
}
// vim:sw=4 ts=4 sts=4 et
