<?php
namespace biru_controller;

class flash implements \ArrayAccess
{
	private $flash = array();
	private $used = array();
      
	public function update( $h )
	{
		foreach( $h as $key => $value )
			$this->keep( $key );
		$this->flash = array_merge( $this->flash, $h );
	}

	private function keys()
	{
		return array_keys( $this->flash );
	}
	
	public function sweep()
	{
		foreach( $this->keys() as $key )
		{
			if( $this->used[ $key ] == 0 )
				$this->_use( $key );
			else
				unset( $this->used[ $key ] );
		}
		$d = array_diff( array_keys( $this->used ), $this->keys() );
		foreach( $d as $key )
			unset( $this->used[ $key ] );
	}
	
	public function keep( $k = null )
	{
		$this->_use( $k, 0 );
	}
	
	public function discard( $k = null )
	{
		$this->_use( $k );
	}
	
	# Used internally by the <tt>keep</tt> and <tt>discard</tt> methods
    #     _use()               # marks the entire flash as used
    #     _use( 'msg' )          # marks the "msg" entry as used
    #     _use( null, false )     # marks the entire flash as unused (keeps it around for one more action)
    #     _use( 'msg', false )   # marks the "msg" entry as unused (keeps it around for one more action)
	private function _use( $k = null, $v = 1 )
	{
		if( $k !== null )
		{
			$this->used[ $k ] = $v;
		}
		else
		{
			foreach( $this->keys() as $key )
				$this->_use( $key, $v );
		}
	}
	
	public function offsetExists( $k )
	{
		return isset( $this->flash[ $k ] );
	}
	
	public function offsetGet( $k )
	{
		$this->keep( $k );
		return $this->offsetExists( $k ) ? $this->flash[ $k ] : null;
	}
	
	public function offsetSet( $k, $v )
	{
		if( is_null( $k ) )
			throw new \sake_exception("Flash offset requires named key");
		$this->keep( $k );
		$this->flash[ $k ] = $v;
	}
	
	public function offsetUnset( $k )
	{
		unset( $this->flash[ $k ] );
	}
}
