<?php
namespace biru_controller;

class flash
{
	public $warning;
	public $error;
	public $notice;
	
	private $should_expire;
	
	public function set_to_expire( $bool )
	{
		$this->should_expire = $bool;
	}
	
	public function should_expire()
	{
		return $this->should_expire;
	}
	
	public $names = array( 'warning', 'error', 'notice' );
}

class flash_reader extends reader
{
	public function __get( $name ){}
	public function __set( $name, $value ){}
}
?>
