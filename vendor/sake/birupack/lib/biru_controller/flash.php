<?php
namespace biru_controller;

class flash implements \ArrayAccess
{
	private $flash;
	private $should_expire = true;
	
	public function __construct( $flash = array() )
	{
		$this->flash = (array) $flash;
	}
	
	public function offsetExists( $offset )
	{
		return isset( $this->flash[ $offset ] );
	}
	
	public function offsetGet( $offset )
	{
		return $this->offsetExists( $offset ) ? $this->flash[ $offset ] : null;
	}
	
	public function offsetSet( $offset, $value )
	{
		if( is_null( $offset ) )
			throw new \sake_exception("Flash offset requires named key");
		$this->flash[ $offset ] = $value;
	}
	
	public function offsetUnset( $offset )
	{
		unset( $this->flash[ $offset ] );
	}
	
	public function set_to_expire( $bool )
	{
		$this->should_expire = $bool;
	}
	
	public function should_expire()
	{
		return $this->should_expire;
	}
}
