<?php

namespace   Silvioq\ReportBundle\Table;

/**
 * @author silvioq
 */
class Column
{
    private $name;
    private $getter;
    private $label;
    
    public function __construct( $name, $label = null, $getter = null )
    {
        if( !is_string( $name ) )
            throw new \InvalidArgumentException( 'Name must be string' );
        
        if( null === $label )
            $label = static::humanize( $name );
        else if( !is_string( $label ) )
            throw new \InvalidArgumentException( 'Label must be string' );
            
        if( null === $getter )
            $getter = 'get' . ucfirst( $name );
        else if( !is_string( $getter ) && !is_callable( $getter ) )
            throw new \InvalidArgumentException( 'getter must be string or callable' );

        $this->name = $name;
        $this->label = $label;
        $this->getter = $getter;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getLabel()
    {
        return $this->label;
    }
    
    public function getGetter()
    {
        return $this->getter;
    }
    
    /**
     * @see https://github.com/symfony/symfony/blob/master/src/Symfony/Component/Form/FormRenderer.php#L306
     */
    static public function humanize($text)
    {
        return ucfirst(trim(strtolower(preg_replace(array('/([A-Z])/', '/[_\s]+/'), array('_$1', ' '), $text))));
    }
}
// vim:sw=4 ts=4 sts=4 et
