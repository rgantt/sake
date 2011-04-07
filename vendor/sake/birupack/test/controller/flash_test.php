<?php
namespace biru_controller;

require_once dirname(__FILE__).'/../sake_unit.php';

class test_controller extends \biru_controller\base
{
	public function set_flash()
	{
		$this->flash['that'] = "hello";
		return $this->render( array( 'inline' => 'hello' ) );
	}
	
	public function set_flash_now()
	{
		$this->flash->now['that'] = "hello";
		$this->flash->now['foo'] = ( isset( $this->flash->now['foo'] ) ? $this->flash->now['foo'] : "bar" );
		$this->flash->now['foo'] = ( isset( $this->flash->now['foo'] ) ? $this->flash->now['foo'] : "err" );
		$this->flashy = $this->flash->now['that'];
		$this->flash_copy = $this->flash;
		return render( array( 'inline' => 'hello' ) );
	}
	
	public function attempt_to_use_flash_now()
	{
		$this->flash_copy = $this->flash;
		$this->flashy = $this->flash['that'];
		return render( array( 'inline' => 'hello' ) );
	}

	public function use_flash()
	{
		$this->flash_copy = $this->flash;
		$this->flashy = $this->flash['that'];
		return render( array( 'inline' => 'hello' ) );
	}
}
