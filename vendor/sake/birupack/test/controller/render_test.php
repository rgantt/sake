<?php
namespace biru_controller;

require_once dirname(__FILE__).'/../sake_unit.php';
require_once dirname(__FILE__).'/../../lib/biru_controller/flash.php';
require_once dirname(__FILE__).'/../../lib/biru_view/partial_template.php';

class test_controller extends \biru_controller\concrete_base
{
	public function initialize()
	{
		$this->layout( $this->determine_layout() );
	}
	
	public function hello_world(){}
	
	public function render_hello_world()
	{
		return $this->render( array( 'template' => 'test/hello_world' ) );
	}
	
	public function render_hello_world_with_forward_slash()
	{
		return $this->render( array( 'template' => '/test/hello_world' ) );
	}
	
	public function render_template_in_top_directory()
	{
		return $this->render( array( 'template' => 'shared' ) );
	}
	
	public function render_template_in_top_directory_with_slash()
	{
		return $this->render( array( 'template' => '/shared' ) );
	}
	
	public function render_hello_world_from_variable()
	{
		$this->person = "ryan";
		return $this->render( array( 'text' => "hello {$this->person}" ) );
	}
	
	public function render_action_hello_world()
	{
		return $this->render( array( 'action' => 'hello_world' ) );
	}
	
	public function render_text_hello_world()
	{
		return $this->render( array( 'text' => "hello world") );
	} 
	
	public function render_json_hello_world()
	{
		return $this->render( array( 'json' => json_encode( array( 'hello' => 'world' ) ) ) );
	}
	
	public function render_json_hello_world_with_callback()
	{
		return $this->render( array( 'json' => json_encode( array( 'hello' => 'world' ) ), 'callback' => 'alert' ) );
	}
	
	public function render_custom_code()
	{
		return $this->render( array( 'text' => "hello world", 'status' => 404 ) );
	}
	
	public function render_nothing_with_appendix()
	{
		return $this->render( array( 'text' => "appended") );
	}
	
	public function render_invalid_args()
	{
		return $this->render("test/hello");
	}
	
	public function render_xml_hello()
	{
		$this->name = "Ryan";
		return $this->render( array( 'template' => "test/hello" ) );
	}
	
	public function render_xml_with_custom_content_type()
	{
		return $this->render( array( 'xml' => "<blah/>", 'content_type' => "application/atomsvc+xml" ) );
	}
	
	public function builder_layout_test()
	{
		return $this->render( array( 'action' => 'hello' ) );
	}
	
	public function heading()
	{
		$this->head('ok');
	}
	
	public function greeting(){}
	
	public function layout_test()
	{
		return $this->render( array( 'action' => 'hello_world' ) );
	} 
	
	public function partials_list()
	{
		$this->test_unchanged = 'hello';
		$this->customers = array( (object) array( 'name' => 'david' ), (object) array( 'name' => 'mary' ) );
		return $this->render( array( 'action' => 'list' ) );
	}
	
	public function partial_only()
	{
		return $this->render( array( 'partial' => true ) );
	}
	
	public function hello_in_a_string()
	{
		$this->customers = array( (object) array( 'name' => 'david' ), (object) array( 'name' => 'mary' ) );
		return $this->render( array( 'text' => "How's there? ".$this->render_to_string( array( 'template' => 'test/list' ) ) ) );
	}
	
	/** NEED A FIXTURE FOR THIS, OR TO CHANGE COMPILATION LOGIC
	public function accessing_params_in_template()
	{
		return $this->render( array( 'inline' => "Hello: <?=\$params['name']; ?>" ) );
	}
	*/
	
	public function accessing_local_assigns_in_inline_template()
	{
		$name = $this->params->local_name;
		return $this->render( array( 'inline' => "Goodbye, \$local_name", 'locals' => array( 'local_name' => $name ) ) );
	}
	
	public function formatted_html_erb(){}
	public function formatted_xml_erb(){}
	
	public function render_to_string_test()
	{
		$this->foo = $this->render_to_string( array( 'inline' => "this is a test" ) );
	}
	
	public function partial()
	{
		return $this->render( array( 'partial' => 'partial' ) );
	}
	
	public function partial_dot_html()
	{
		return $this->render( array( 'partial' => 'partial.html.phtml' ) );
	}
	
	public function default_render()
	{
		if( !empty( $this->alternate_default_render ) )
		{
			$func = $this->alternate_default_render;
			return $func();
		}
		return $this->render();
	}
	
	public function render_alternate_default()
	{
		$this->alternate_render_default = function(){
			return $this->render( array( 'update' => '' ) );
			#render :update do |page|
	  			#page.replace :foo, :partial => 'partial'
			#end
		};
	}
	
	/**
	 * only layout_test should be wrapped in a layout
	 */
	private function determine_layout()
	{
		switch( $this->action_name )
		{
			case 'layout_test':
				return 'standard';
		}
	}
}
test_controller::view_paths( array( dirname(__FILE__)."/../fixtures" ) );

class render_test extends SAKE_test_case
{
	public function setup()
	{
		$this->request = new test_request;
		$this->response = new test_response;
		$this->controller = new test_controller;
		$this->request->host = "www.google.com";
	}
	
	public function test_simple_show()
	{
		$this->get('hello_world');
		$this->assert_response('200');
		$this->assert_template( "test/hello_world" );
	}
	
	public function test_do_with_render()
	{
		$this->get('render_hello_world');
		$this->assert_template( "test/hello_world" );
	}
	
	public function test_do_with_render_from_variable()
	{
		$this->get('render_hello_world_from_variable');
		$this->assertEquals( "hello ryan", $this->response->body );
	}
	
	public function test_do_with_render_action()
	{
		$this->get('render_action_hello_world');
		$this->assert_template( "test/hello_world" );
	}
	
	public function test_do_with_render_text()
	{
		$this->get('render_text_hello_world');
		$this->assertEquals( "hello world", $this->response->body );
	}
	
	public function test_do_with_render_json()
	{
		$this->get('render_json_hello_world');
		$this->assertEquals( '{"hello":"world"}', $this->response->body );
		$this->assertEquals( 'application/json', $this->response->content_type() );
	}
	
	public function test_do_with_render_json_with_callback()
	{
		$this->get('render_json_hello_world_with_callback');
		$this->assertEquals( 'alert({"hello":"world"})', $this->response->body );
		$this->assertEquals( 'application/json', $this->response->content_type() );
	}
	
	public function test_do_with_render_custom_code()
	{
		$this->get('render_custom_code');
		$this->assert_response('404');
		$this->assertEquals( 'hello world', $this->response->body );
	}
	
	public function test_do_with_render_nothing_with_appendix()
	{
		$this->get('render_nothing_with_appendix');
		$this->assert_response('200');
		$this->assertEquals( 'appended', $this->response->body );
	}
	
	/**
	 * @expectedException \biru_controller\render_error
	 */
	public function test_attempt_to_render_with_invalid_arguments()
	{
		$this->get('render_invalid_args');
	}
	
	/**
	 * @expectedException \biru_controller\unknown_action
	 */
	public function test_attempt_to_access_object_method()
	{
		$this->get('properties');
	}
	
	/**
	 * @expectedException \biru_controller\unknown_action
	 */
	public function test_private_methods()
	{
		$this->get('determine_layout');
	}
	
	public function test_render_xml()
	{
		$this->get('render_xml_hello');
		$this->assertEquals( "<html><p>Hello Ryan</p><p>This is grand!</p></html>", $this->response->body );
	}
	
	public function test_render_xml_with_default()
	{
		$this->get('greeting');
		$this->assertEquals( "<p>This is grand!</p>", $this->response->body );
	}
	
	public function test_layout_rendering()
	{
		$this->get('layout_test');
		$this->assertEquals( "<html>Hello world!</html>", $this->response->body );
	}
	
	public function test_partial_only()
	{
		$this->get('partial_only');
		$this->assertEquals( "only partial", $this->response->body );
	}
	
	public function test_render_to_string()
	{
		$this->get('hello_in_a_string');
		$this->assertEquals( "How's there? Hello: davidHello: marygoodbye", $this->response->body );
	}
	
	public function test_render_to_string_resets_assigns()
	{
		$this->get('render_to_string_test');
		$this->assertEquals( "The value of foo is: ::this is a test::", $this->response->body );
	}
	
	/*
	public function test_accessing_params_in_template()
	{
		$this->get('accessing_params_in_template', array( 'name' => "Ryan" ) );
		$this->assertEquals( "Hello: Ryan", $this->response->body );
	}
	*/
	
	public function test_accessing_local_assigns_in_inline_template()
	{
		$this->get('accessing_local_assigns_in_inline_template', array( 'local_name' => 'Local Ryan' ) );
		$this->assertEquals( "Goodbye, Local Ryan", $this->response->body );
	}
	
	public function test_render_200_should_set_etag()
	{
		$this->get('render_hello_world_from_variable');
		$this->assertEquals( $this->etag_for("hello ryan"), $this->response->headers['ETag'] );
		$this->assertEquals( "private, max-age=0, must-revalidate", $this->response->headers['Cache-Control'] );
	}
	
	public function test_render_against_etag_request_should_304_when_match()
	{
		$this->request->headers['HTTP_IF_NONE_MATCH'] = $this->etag_for("hello ryan");
		$this->get('render_hello_world_from_variable');
		$this->assertEquals( "304 Not Modified", $this->response->headers['Status'] );
		$this->assertEquals( '', $this->response->body );
	}
	
	public function test_render_against_etag_request_should_200_when_no_match()
	{
		$this->request->headers['HTTP_IF_NONE_MATCH'] = $this->etag_for("hello somewhere else");
		$this->get('render_hello_world_from_variable');
		$this->assertEquals( "200 OK", $this->response->headers['Status'] );
		$this->assertNotEquals( '', $this->response->body );
	}
	
	public function test_render_with_etag()
	{
		$this->get('render_hello_world_from_variable');
		$expected_etag = $this->etag_for("hello ryan");
		$this->assertEquals( $expected_etag, $this->response->headers['ETag'] );
		
		$this->request->headers['HTTP_IF_NONE_MATCH'] = $expected_etag;
		$this->get('render_hello_world_from_variable');
		$this->assertEquals( "304 Not Modified", $this->response->headers['Status'] );
		
		$this->request->headers['HTTP_IF_NONE_MATCH'] = "\"diftag\"";
		$this->get('render_hello_world_from_variable');
		$this->assertEquals( "200 OK", $this->response->headers['Status'] );
	}
	
	public function render_with_404_shouldnt_have_etag()
	{
		$this->get('render_custom_code');
		$this->assertNull( $this->response->headers['ETag'] );
	}
	
	public function test_etag_should_not_be_changed_when_already_set()
	{
		$expected_etag = $this->etag_for("hello somewhere else");
		$this->response->headers['ETag'] = $expected_etag;
		$this->get('render_hello_world_from_variable');
		$this->assertEquals( $expected_etag, $this->response->headers['ETag'] );
	}
	
	public function test_should_render_html_formatted_partial()
	{
		$this->get('partial');
		$this->assertEquals( 'partial html', $this->response->body );
	}
	
	public function test_should_render_html_partial_with_dot()
	{
		$this->get('partial_dot_html');
		$this->assertEquals( 'partial html', $this->response->body );
	}
	
	public function test_should_render_js_partial()
	{
		$this->xhr( 'get', 'partial', array( 'format' => 'js' ) );
		$this->assertEquals( 'partial js', $this->response->body );
	}
	
	public function test_render_with_forward_slash()
	{
		$this->get('render_hello_world_with_forward_slash');
		$this->assert_template( "test/hello_world" );
	}
	
	public function test_render_in_top_directory()
	{
		$this->get('render_template_in_top_directory');
		$this->assert_template('shared');
		$this->assertEquals( "Elastica", $this->response->body );
	}
	
	public function test_render_in_top_directory_with_slash()
	{
		$this->get('render_template_in_top_directory_with_slash');
		$this->assert_template('shared');
		$this->assertEquals( "Elastica", $this->response->body );
	}
	
	public function test_should_render_xml_but_keep_custom_content_type()
	{
		$this->get('render_xml_with_custom_content_type');
		$this->assertEquals( "application/atomsvc+xml", $this->response->content_type() );
	}
	
	private function etag_for( $text )
	{
		return md5( $text );
	}
}
