<?php

namespace Silvioq\ReportBundle\Util;

/**
 * @author silvioq
 *
 * Transform in scalar anything
 */
class Scalarize
{

    /**
     * @param $data
     * @return string|integer|boolean
     */
    static public function toScalar($data)
    {
        if( null === $data ) return '';

        if( is_string( $data ) || is_bool( $data ) || is_numeric( $data ) )
            return $data;

        if( $data instanceof \DateTime ) return $data->format('Y-m-d');

        if( is_object( $data ) && method_exists( $data, '__toString' ) ) return (string)$data;

        if( $data instanceof \Traversable ) 
        {
            $ret = [];
            foreach($data as $x )           
            {
                $ret[] = static::toScalar($x); 
            }
            return join( ',', $ret );       
        }
        
        // still an object
        if( is_object( $data ) ) return sprintf( 'object(%s)', get_class( $data ) );

        if( is_array( $data ) ) return join( ',', $data );

        return $data;
    }

}
// vim:sw=4 ts=4 sts=4 et
