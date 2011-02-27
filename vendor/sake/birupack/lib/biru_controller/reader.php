<?php
namespace biru_controller;

interface attr_reader
{
	public function __get( $name );
	public function __set( $name, $value );
}

abstract class reader implements attr_reader
{
	protected $heap = array();
	
	public function __construct( $heap = array() )
	{
		assert( is_array( $heap ) );
	}
	
	public function __get( $name )
	{
		if( isset( $this->heap[ $name ] ) )
		{
			return $this->heap[ $name ];
		}
		return false;
	}
	
	public function __set( $name, $value )
	{
        $this->heap[ $name ] = $value;
	}

    public function add_all( $array )
    {
        foreach( $array as $key => $value )
            $this->$key = $value;
    }
}
?>
