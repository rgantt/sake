<?php
namespace biru_controller;

require_once dirname(__FILE__)."/../sake_unit.php";
require_once dirname(__FILE__)."/../../lib/biru_controller/url_rewriter.php";

class url_rewriter_test extends SAKE_test_case
{
	protected function setUp()
	{
		$this->request = new \biru_controller\test_request;
		$this->params = new \stdClass;
		$this->rewriter = new \biru_controller\url_rewriter( $this->request, $this->params );
	}

	public function test_build_from_nice_query()
	{
		$nice_request = array( "page" => "controller", "action" => "method", "param1" => "value1", "something_id" => "the_value" );
		$this->assertContains( 
			'/controller/method/param1/value1/something_id/the_value',
			$this->rewriter->rewrite( $nice_request )
		);
	}

	public function test_build_from_shortened_query()
	{
		$nice_request = array( "controller", "method", "param1" => "value1", "something_id" => "the_value" );
		$this->assertContains( 
			'/controller/method/param1/value1/something_id/the_value',
			$this->rewriter->rewrite( $nice_request )
		);
	}

	/**
	 * @expectedException biru_controller\invalid_query_exception
	 */
	public function test_build_numeric_param_name()
	{
		$naughty_request = array( "page" => "naughty", "action" => "nsfw", "3" => "value" );
		$this->rewriter->build_query_string( $naughty_request );
	}

	/**
	 * @expectedException biru_controller\invalid_query_exception
	 */
	public function test_build_special_char_param_name()
	{
		$naughty_request = array( "page" => "naughty", "action" => "nsfw", "+&" => "=nice" );
		$this->rewriter->build_query_string( $naughty_request );
	}

	public function test_port()
	{
		$this->assertEquals( 
			'http://test.host:1271/c/a/id/i',
			$this->rewriter->rewrite( array( 'page' => 'c', 'action' => 'a', 'id' => 'i', 'port' => 1271 ) )
		);
	}

	public function test_protocol_with_and_without_separator()
	{
		$this->assertEquals( 
			'https://test.host/c/a/id/i',
			$this->rewriter->rewrite( array( 'protocol' => 'https', 'page' => 'c', 'action' => 'a', 'id' => 'i' ) )
		);

		$this->assertEquals( 
			'https://test.host/c/a/id/i',
			$this->rewriter->rewrite( array( 'protocol' => 'https://', 'page' => 'c', 'action' => 'a', 'id' => 'i' ) )
		);
	}

	public function test_anchor()
	{
		$this->assertEquals( 
			'http://test.host/c/a/id/i#anchor',
			$this->rewriter->rewrite( array( 'page' => 'c', 'action' => 'a', 'id' => 'i', 'anchor' => 'anchor' ) )
		);
	}

	public function test_to_str()
	{
		$this->params->page = 'hi';
		$this->params->action = 'bye';
		//$this->parameters->id = '2';
		$this->params->id = '2';

		//$this->assertEquals( 'http://, test.host, /, hi, bye, {"id"=>"2"}',
		$this->assertEquals( 
			'http://, test.host, /, hi, bye, a:0:{}',
			$this->rewriter->to_str()
		);
	}

	public function test_trailing_slash()
	{
		$options = array( 'page' => 'foo', 'action' => 'bar', 'id' => '3', 'only_path' => true );

		$this->assertEquals( '/foo/bar/id/3', $this->rewriter->rewrite( $options ) );
		$this->assertEquals( '/foo/bar/id/3/query/string', $this->rewriter->rewrite( array_merge( $options, array( 'query' => 'string' ) ) ) );

		$options['trailing_slash'] = true;

		$this->assertEquals( '/foo/bar/id/3/', $this->rewriter->rewrite( $options ) );
	}
}