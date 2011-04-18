<?php
namespace biru_controller;

require_once dirname(__FILE__)."/../sake_unit.php";
require_once dirname(__FILE__).'/../../lib/biru_controller/flash.php';
require_once dirname(__FILE__).'/../../lib/biru_controller/mime.php';

class content_type_controller extends concrete_base
{
	public function render_content_type_from_body()
	{
		$this->response->content_type( \Mime\type('RSS') );
		return $this->render( array( 'text' => 'hello world!' ) );
	}
	
	public function render_defaults()
	{
		return $this->render( array( 'text' => 'hello world!' ) );
	}
	
	public function render_content_type_from_render()
	{
		return $this->render( array( 'text' => 'hello world!', 'content_type' => \Mime\type('RSS') ) );
	}
	
	public function render_charset_from_body()
	{
		$this->response->charset("utf-16");
		return $this->render( array( 'text' => 'hello world!' ) );
	}
	
	public function render_default_for_html(){}
}
content_type_controller::$view_paths = array( dirname(__FILE__)."/../fixtures/" );

class content_type_test extends SAKE_test_case
{
	public function setup()
	{
		$this->controller = new content_type_controller;
		$this->request = new test_request;
		$this->response = new test_response;
	}
	
	public function test_render_defaults()
	{
		$this->get('render_defaults');
		$this->assertEquals( "utf-8", $this->response->charset() );
		$this->assertEquals( \Mime\type('HTML'), $this->response->content_type() );
	}
	
	public function test_render_changed_charset_default()
	{
		content_type_controller::$default_charset = "utf-16";
		$this->get('render_defaults');
		$this->assertEquals( "utf-16", $this->response->charset() );
		$this->assertEquals( \Mime\type('HTML'), $this->response->content_type() );
		content_type_controller::$default_charset = "utf-8";
	}
	
	public function test_content_type_from_body()
	{
		$this->get('render_content_type_from_body');
		$this->assertEquals( "application/rss+xml", $this->response->content_type() );
		$this->assertEquals( "utf-8", $this->response->charset() );
	}
	
	public function test_content_type_from_render()
	{
		$this->get('render_content_type_from_render');
		$this->assertEquals( "application/rss+xml", $this->response->content_type() );
		$this->assertEquals( "utf-8", $this->response->charset() );
	}
	
	public function test_charset_from_body()
	{
		$this->get('render_charset_from_body');
		$this->assertEquals( "utf-16", $this->response->charset() );
		$this->assertEquals( \Mime\type('HTML'), $this->response->content_type() );
	}
	
	public function test_default_for_html()
	{
		$this->get('render_default_for_html');
		$this->assertEquals( \Mime\type('HTML'), $this->response->content_type() );
		$this->assertEquals( "utf-8", $this->response->charset() );
	}
}