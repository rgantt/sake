<?php
namespace biru_controller;

require_once dirname(__FILE__).'/../sake_unit.php';
require_once dirname(__FILE__).'/../../lib/biru_controller/flash.php';

class test_controller extends \biru_controller\concrete_base
{
	public function set_flash()
	{
		$this->flash['that'] = "hello";
		return $this->render( array( 'inline' => 'hello' ) );
	}
	
	public function set_flash_now()
	{
		$now = $this->flash->now();
		$now['that'] = "hello";
		$now['foo'] = "bar";
		$this->flashy = $now['that'];
		$this->flash_copy = clone $this->flash;
		return $this->render( array( 'inline' => 'hello' ) );
	}
	
	public function attempt_to_use_flash_now()
	{
		$this->flash_copy = clone $this->flash;
		$this->flashy = $this->flash['that'];
		return $this->render( array( 'inline' => 'hello' ) );
	}

	public function use_flash()
	{
		$this->flash_copy = clone $this->flash;
		$this->flashy = $this->flash['that'];
		return $this->render( array( 'inline' => 'hello' ) );
	}
	
	public function use_flash_and_keep_it()
	{
		$this->flash_copy = clone $this->flash;
		$this->flashy = $this->flash['that'];
		$this->flash()->keep();
		return $this->render( array( 'inline' => 'hello' ) );
	}
	
	public function use_flash_and_update_it()
	{
		$this->flash->update( array( "this" => "hello again" ) );
		$this->flash_copy = clone $this->flash;
		return $this->render( array( "inline" => "hello" ) );
	}
	
	public function use_flash_after_reset_session()
	{
		$this->flash['that'] = "hello";
		$this->flashy_that = $this->flash['that'];
		$this->reset_session();
		$this->flashy_that_reset = $this->flash['that'];
		$this->flash['this'] = "good-bye";
		$this->flashy_this = $this->flash['this'];
		return $this->render( array( "inline" => "hello" ) );
	}
}

class flash_test extends SAKE_test_case
{
	public function setUp()
	{
		$this->request = new test_request();
		$this->response = new test_response();
		$this->controller = new test_controller();
	}
	
	public function test_flash()
	{
		$this->get("set_flash");
		$this->get("use_flash");
		$this->assertEquals( "hello", $this->response->template->assigns['flashy'] );
		$this->assertEquals( "hello", $this->response->template->assigns['flash_copy']['that'] );
		
		$this->get("use_flash");
		$this->assertEquals( null, $this->response->template->assigns['flash_copy']['that'] );
	}
	
	public function test_keep_flash()
	{
		$this->get("set_flash");
		$this->get("use_flash_and_keep_it");
		$this->assertEquals( "hello", $this->response->template->assigns['flash_copy']['that'] );
		$this->assertEquals( "hello", $this->response->template->assigns['flashy'] );
		
		$this->get("use_flash");
		$this->assertEquals( "hello", $this->response->template->assigns['flash_copy']['that'] );
		
		$this->get("use_flash");
		$this->assertEquals( null, $this->response->template->assigns['flash_copy']['that'] );
	}
	
	public function test_flash_now()
	{
		$this->get("set_flash_now");
		$this->assertEquals( "hello", $this->response->template->assigns['flash_copy']['that'] );
		$this->assertEquals( "bar", $this->response->template->assigns['flash_copy']['foo'] );
		$this->assertEquals( "hello", $this->response->template->assigns['flashy'] );
		
		$this->get("attempt_to_use_flash_now");
		$this->assertEquals( null, $this->response->template->assigns['flash_copy']['that'] );
		$this->assertEquals( null, $this->response->template->assigns['flash_copy']['foo'] );
		$this->assertEquals( null, $this->response->template->assigns['flashy'] );
	}
	
	public function test_update_flash()
	{
		$this->get("set_flash");
		$this->get("use_flash_and_update_it");
		$this->assertEquals( "hello", $this->response->template->assigns['flash_copy']['that'] );
		$this->assertEquals( "hello again", $this->response->template->assigns['flash_copy']['this'] );
		
		$this->get("use_flash");
		$this->assertEquals( null, $this->response->template->assigns['flash_copy']['that'] );
		$this->assertEquals( "hello again", $this->response->template->assigns['flash_copy']['this'] );
	}
	
	public function test_flash_after_reset_session()
	{
		$this->get("use_flash_after_reset_session");
		$this->assertEquals( "hello", $this->response->template->assigns['flashy_that'] );
		$this->assertEquals( "good-bye", $this->response->template->assigns['flashy_this'] );
		$this->assertEquals( null, $this->response->template->assigns['flashy_that_reset'] );
	}
}