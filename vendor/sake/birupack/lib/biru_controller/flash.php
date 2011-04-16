<?php
namespace biru_controller;

class flash implements \ArrayAccess
{
	private $flash = array();
	private $consumed = array();
	
	public function update( array $h )
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
		$keys = $this->keys();
		foreach( $keys as $key )
		{
			if( $this->consumed[ $key ] === false )
				$this->consume( $key );
			else
			{
				unset( $this[ $key ] );
				unset( $this->consumed[ $key ] );
			}
		}
		$d = array_diff( array_keys( $this->consumed ), $this->keys() );
		foreach( $d as $key )
			unset( $this->consumed[ $key ] );
	}
	
	public function keep( $k = null )
	{
		$this->consume( $k, false );
	}
	
	public function discard( $k = null )
	{
		$this->consume( $k );
	}
	
	# Used internally by the <tt>keep</tt> and <tt>discard</tt> methods
    #     consume()               # marks the entire flash as used
    #     consume( 'msg' )        # marks the "msg" entry as used
    #     consume( null, false )  # marks the entire flash as unused (keeps it around for one more action)
    #     consume( 'msg', false ) # marks the "msg" entry as unused (keeps it around for one more action)
	private function consume( $k = null, $v = true )
	{
		if( $k !== null )
			$this->consumed[ $k ] = $v;
		else
		{
			foreach( $this->keys() as $key )
				$this->consume( $key, $v );
		}
	}
	
	public function offsetExists( $k )
	{
		return isset( $this->flash[ $k ] );
	}
	
	public function offsetGet( $k )
	{
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
	
      # Sets a flash that will not be available to the next action, only to the current.
      # 
      # This method enables you to use the flash as a central messaging system in your app.
      # When you need to pass an object to the next action, you use the standard flash assign (<tt>[]=</tt>).
      # When you need to pass an object to the current action, you use <tt>now</tt>, and your object will
      # vanish when the current action is done.
      #
      # Entries set via <tt>now</tt> are accessed the same way as standard entries: <tt>flash['my-key']</tt>.
      public function now()
      {
      	return new flash_now( $this );
      }
}

class flash_now implements \ArrayAccess
{
	private $flash;
	
	public function __construct( flash $flash )
	{
		$this->flash = $flash;
	}
	
	public function offsetSet( $k, $v )
	{
		$this->flash[ $k ] = $v;
		$this->flash->discard( $k );
		return $v;
	}
	
	public function offsetGet( $k )
	{
		return $this->flash[ $k ];
	}
	
	public function offsetExists( $k )
	{
		return isset( $this->flash[ $k ] );
	}
	
	public function offsetUnset( $k )
	{
		unset( $this->flash[ $k ] );
	}
}