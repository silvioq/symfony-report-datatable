<?php

namespace Silvioq\ReportBundle\Util;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author silvioq
 *
 * Transform in scalar anything
 */
class Scalarize
{
    /**
     * @var string
     */
    private $arraySeparator;

    /**
     * @var string
     */
    private $dateFormat;

    /**
     * @param array config  Config settings
     *
     * Default settings
     * - array_separator. Value ","
     * - date_format. Value 'Y-m-d'
     */
    public function __construct( array $config = array() )
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults( [
            'array_separator' => ',',
            'date_format' => 'Y-m-d',
        ]);

        /** @var array */
        $options = $resolver->resolve( $config );

        $this->arraySeparator = (string)$options['array_separator'];
        $this->dateFormat = (string)$options['date_format'];
    }

    /**
     * @param mixed $data
     * @return string|integer|boolean
     */
    public function scalarize($data)
    {
        if( null === $data ) return '';

        if( is_string( $data ) || is_bool( $data ) || is_numeric( $data ) )
            return $data;

        if( $data instanceof \DateTime ) return $data->format($this->dateFormat);

        if( is_object( $data ) && method_exists( $data, '__toString' ) ) return (string)$data;

        if( $data instanceof \Traversable ) 
        {
            $ret = [];
            foreach($data as $x )           
            {
                $ret[] = static::toScalar($x); 
            }
            return join( $this->arraySeparator, $ret );
        }

        // still an object
        if( is_object( $data ) ) return sprintf( 'object(%s)', get_class( $data ) );

        if( is_array( $data ) ) return join( $this->arraySeparator, $data );

        return $data;
    }

    /**
     * @param $data
     * @return string|integer|boolean
     */
    static public function toScalar($data)
    {
        return (new self())->scalarize($data);
    }

}
// vim:sw=4 ts=4 sts=4 et
