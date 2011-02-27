<?php
namespace biru_controller;

require dirname(__FILE__)."/../sake_unit.php";

class request_test extends SAKE_test_case
{
	public function setUp()
	{
		$this->request = new \biru_controller\test_request();
	}
	
	public function test_remote_ip()
	{
    	$this->assertEquals( '0.0.0.0', $this->request->remote_ip() );
    	
    	$this->request->remote_addr("1.2.3.4");
    	$this->assertEquals( "1.2.3.4", $this->request->remote_ip() );
    	
    	$this->request->env['HTTP_CLIENT_IP'] = "2.3.4.5";
    	$this->assertEquals( "2.3.4.5", $this->request->remote_ip() );
    	unset( $this->request->env['HTTP_CLIENT_IP'] );
    	
    	$this->request->env['HTTP_X_FORWARDED_FOR'] = "3.4.5.6";
    	$this->assertEquals( "3.4.5.6", $this->request->remote_ip() );

    	$this->request->env['HTTP_X_FORWARDED_FOR'] = "unknown,3.4.5.6";
    	$this->assertEquals( "3.4.5.6", $this->request->remote_ip() );
    	
    	$this->request->env['HTTP_X_FORWARDED_FOR'] = "172.16.0.1,3.4.5.6";
    	$this->assertEquals( "3.4.5.6", $this->request->remote_ip() );
    	
    	$this->request->env['HTTP_X_FORWARDED_FOR'] = "192.168.0.1,3.4.5.6";
    	$this->assertEquals( "3.4.5.6", $this->request->remote_ip() );
    	
    	$this->request->env['HTTP_X_FORWARDED_FOR'] = "10.0.0.1,3.4.5.6";
    	$this->assertEquals( "3.4.5.6", $this->request->remote_ip() );

    	$this->request->env['HTTP_X_FORWARDED_FOR'] = "10.0.0.1, 10.0.0.1, 3.4.5.6";
    	$this->assertEquals( "3.4.5.6", $this->request->remote_ip() );
    	
    	$this->request->env['HTTP_X_FORWARDED_FOR'] = "127.0.0.1,3.4.5.6";
    	$this->assertEquals( "127.0.0.1", $this->request->remote_ip() );
    	
    	$this->request->env['HTTP_X_FORWARDED_FOR'] = "unknown,192.168.0.1";
    	$this->assertEquals( "1.2.3.4", $this->request->remote_ip() );
    	unset( $this->request->env['HTTP_X_FORWARDED_FOR'] );
	}
	
	public function test_domains()
	{
		$this->request->host = "www.rubyonrails.org";
		$this->assertEquals( "rubyonrails.org", $this->request->domain() );
		
		$this->request->host = "www.rubyonrails.co.uk";
		$this->assertEquals( "rubyonrails.co.uk", $this->request->domain(2) );
		
		$this->request->host = "192.168.1.200";
		$this->assertNull( $this->request->domain() );
		
		$this->request->host = "foo.192.168.1.200";
		$this->assertNull( $this->request->domain() );
		
		$this->request->host = "192.168.1.200.com";
		$this->assertEquals( "200.com", $this->request->domain() );
		
		$this->request->host = null;
		$this->assertNull( $this->request->domain() );	
	}
	
	public function test_subdomains()
	{
		$this->request->host = "www.rubyonrails.org";
		$this->assertEquals( array( "www" ), $this->request->subdomains() );
		
		$this->request->host = "www.rubyonrails.co.uk";
		$this->assertEquals( array( "www"), $this->request->subdomains(2) );
		
		$this->request->host = "dev.www.rubyonrails.co.uk";
		$this->assertEquals( array( "dev", "www" ), $this->request->subdomains(2) );
		
		$this->request->host = "foobar.foobar.com";
		$this->assertEquals( array( "foobar" ), $this->request->subdomains() );
		
		$this->request->host = "192.168.1.200";
		$this->assertEquals( array(), $this->request->subdomains() );
		
		$this->request->host = "foo.192.168.1.200";
		$this->assertEquals( array(), $this->request->subdomains() );
		
		$this->request->host = "192.168.1.200.com";
		$this->assertEquals( array( "192", "168", "1" ), $this->request->subdomains() );
		
		$this->request->host = null;
		$this->assertEquals( array(), $this->request->subdomains() );	
	}
	
	public function test_port_string()
	{
		$this->request->port("80");
		$this->assertEquals( "", $this->request->port_string() );
		
		$this->request->port("8080");
		$this->assertEquals( ":8080", $this->request->port_string() );
	}
	
	public function test_relative_url_root()
	{
	    $this->request->env['SERVER_SOFTWARE'] = 'apache/1.2.3 some random text';
	
	    $this->request->env['SCRIPT_NAME'] = null;
	    $this->assertEquals( "", $this->request->relative_url_root() );
	
	    $this->request->env['SCRIPT_NAME'] = "/dispatch.cgi";
	    $this->assertEquals( "", $this->request->relative_url_root() );
	
	    $this->request->env['SCRIPT_NAME'] = "/myapp.php";
	    $this->assertEquals( "", $this->request->relative_url_root() );
	
	    $this->request->relative_url_root = null;
	    $this->request->env['SCRIPT_NAME'] = "/hieraki/dispatch.cgi";
	    $this->assertEquals( "/hieraki", $this->request->relative_url_root() );
	
	    $this->request->relative_url_root = null;
	    $this->request->env['SCRIPT_NAME'] = "/collaboration/hieraki/dispatch.cgi";
	    $this->assertEquals( "/collaboration/hieraki", $this->request->relative_url_root() );
	
	    # apache/scgi case
	    $this->request->relative_url_root = null;
	    $this->request->env['SCRIPT_NAME'] = "/collaboration/hieraki";
	    $this->assertEquals( "/collaboration/hieraki", $this->request->relative_url_root() );
	
	    # @env overrides path guess
	    $this->request->relative_url_root = null;
	    $this->request->env['SCRIPT_NAME'] = "/hieraki/dispatch.cgi";
	    $this->request->env['RAILS_RELATIVE_URL_ROOT'] = "/real_url";
	    $this->assertEquals( "/real_url", $this->request->relative_url_root() );
	}
	
	public function test_request_uri()
	{
	    $this->request->env['SERVER_SOFTWARE'] = 'Apache 42.342.3432';
	
	    $this->request->relative_url_root = null;
	    $this->request->set_REQUEST_URI("http://www.rubyonrails.org/path/of/some/uri?mapped=1");
	    $this->assertEquals( "/path/of/some/uri?mapped=1", $this->request->request_uri() );
	    $this->assertEquals( "/path/of/some/uri", $this->request->path() );
	
	    $this->request->relative_url_root = null;
	    $this->request->set_REQUEST_URI("http://www.rubyonrails.org/path/of/some/uri");
	    $this->assertEquals( "/path/of/some/uri", $this->request->request_uri() );
	    $this->assertEquals( "/path/of/some/uri", $this->request->path() );
	
	    $this->request->relative_url_root = null;
	    $this->request->set_REQUEST_URI("/path/of/some/uri");
	    $this->assertEquals( "/path/of/some/uri", $this->request->request_uri() );
	    $this->assertEquals( "/path/of/some/uri", $this->request->path() );
	
	    $this->request->relative_url_root = null;
	    $this->request->set_REQUEST_URI("/");
	    $this->assertEquals( "/", $this->request->request_uri() );
	    $this->assertEquals( "/", $this->request->path() );
	
	    $this->request->relative_url_root = null;
	    $this->request->set_REQUEST_URI("/?m=b");
	    $this->assertEquals( "/?m=b", $this->request->request_uri() );
	    $this->assertEquals( "/", $this->request->path() );
	
	    $this->request->relative_url_root = null;
	    $this->request->set_REQUEST_URI("/");
	    $this->request->env['SCRIPT_NAME'] = "/dispatch.cgi";
	    $this->assertEquals( "/", $this->request->request_uri() );
	    $this->assertEquals( "/", $this->request->path() );
	
	    $this->request->relative_url_root = null;
	    $this->request->set_REQUEST_URI("/hieraki/");
	    $this->request->env['SCRIPT_NAME'] = "/hieraki/dispatch.cgi";
	    $this->assertEquals( "/hieraki/", $this->request->request_uri() );
	    $this->assertEquals( "/", $this->request->path() );
	
	    $this->request->relative_url_root = null;
	    $this->request->set_REQUEST_URI("/collaboration/hieraki/books/edit/2");
	    $this->request->env['SCRIPT_NAME'] = "/collaboration/hieraki/dispatch.cgi";
	    $this->assertEquals( "/collaboration/hieraki/books/edit/2", $this->request->request_uri() );
	    $this->assertEquals( "/books/edit/2", $this->request->path() );
	
	    # The following tests are for when REQUEST_URI is not supplied (as in IIS)
	    $this->request->relative_url_root = null;
	    $this->request->set_REQUEST_URI( null );
	    $this->request->env['PATH_INFO'] = "/path/of/some/uri?mapped=1";
	    $this->request->env['SCRIPT_NAME'] = null; #"/path/dispatch.rb"
	    $this->assertEquals( "/path/of/some/uri?mapped=1", $this->request->request_uri() );
	    $this->assertEquals( "/path/of/some/uri", $this->request->path() );
	
	    $this->request->set_REQUEST_URI( null );
	    $this->request->relative_url_root = null;
	    $this->request->env['PATH_INFO'] = "/path/of/some/uri?mapped=1";
	    $this->request->env['SCRIPT_NAME'] = "/path/dispatch.rb";
	    $this->assertEquals( "/path/of/some/uri?mapped=1", $this->request->request_uri() );
	    $this->assertEquals( "/of/some/uri", $this->request->path() );
	
	    $this->request->set_REQUEST_URI( null );
	    $this->request->relative_url_root = null;
	    $this->request->env['PATH_INFO'] = "/path/of/some/uri";
	    $this->request->env['SCRIPT_NAME'] = null;
	    $this->assertEquals( "/path/of/some/uri", $this->request->request_uri() );
	    $this->assertEquals( "/path/of/some/uri", $this->request->path() );
	
	    $this->request->set_REQUEST_URI( null );
	    $this->request->relative_url_root = null;
	    $this->request->env['PATH_INFO'] = "/";
	    $this->assertEquals( "/", $this->request->request_uri() );
	    $this->assertEquals( "/", $this->request->path() );
	
	    $this->request->set_REQUEST_URI( null );
	    $this->request->relative_url_root = null;
	    $this->request->env['PATH_INFO'] = "/?m=b";
	    $this->assertEquals( "/?m=b", $this->request->request_uri() );
	    $this->assertEquals( "/", $this->request->path() );
	
	    $this->request->set_REQUEST_URI( null );
	    $this->request->relative_url_root = null;
	    $this->request->env['PATH_INFO'] = "/";
	    $this->request->env['SCRIPT_NAME'] = "/dispatch.cgi";
	    $this->assertEquals( "/", $this->request->request_uri() );
	    $this->assertEquals( "/", $this->request->path() );
	
	    $this->request->set_REQUEST_URI( null );
	    $this->request->relative_url_root = null;
	    $this->request->env['PATH_INFO'] = "/hieraki/";
	    $this->request->env['SCRIPT_NAME'] = "/hieraki/dispatch.cgi";
	    $this->assertEquals( "/hieraki/", $this->request->request_uri() );
	    $this->assertEquals( "/", $this->request->path() );
	
	    $this->request->set_REQUEST_URI( '/hieraki/dispatch.cgi' );
	    $this->request->relative_url_root = '/hieraki';
	    $this->assertEquals( "/dispatch.cgi", $this->request->path() );
	    $this->request->relative_url_root = null;
	
	    $this->request->set_REQUEST_URI( '/hieraki/dispatch.cgi' );
	    $this->request->relative_url_root = '/foo';
	    $this->assertEquals( "/hieraki/dispatch.cgi", $this->request->path() );
	    $this->request->relative_url_root = null;
	
	    # This test ensures that Rails uses REQUEST_URI over PATH_INFO
	    $this->request->relative_url_root = null;
	    $this->request->env['REQUEST_URI'] = "/some/path";
	    $this->request->env['PATH_INFO'] = "/another/path";
	    $this->request->env['SCRIPT_NAME'] = "/dispatch.cgi";
	    $this->assertEquals( "/some/path", $this->request->request_uri() );
	    $this->assertEquals( "/some/path", $this->request->path() );		
	}
	
	public function test_host_with_default_port()
	{
		$this->request->host = "rubyonrails.org";
		$this->request->port("80");
		$this->assertEquals( "rubyonrails.org", $this->request->host_with_port() );
	}
	
	public function test_host_with_non_default_port()
	{
		$this->request->host = "rubyonrails.org";
		$this->request->port("81");
		$this->assertEquals( "rubyonrails.org:81", $this->request->host_with_port() );
	}
	
	public function test_server_software()
	{
		$this->assertEquals( null, $this->request->server_software() );
		
		$this->request->env['SERVER_SOFTWARE'] = "Apache3.422";
		$this->assertEquals( "apache", $this->request->server_software() );
	}
	
	public function test_xml_http_request()
	{
	/**
	assert !@request.xml_http_request?
    assert !@request.xhr?

    @request.env['HTTP_X_REQUESTED_WITH'] = "DefinitelyNotAjax1.0"
    assert !@request.xml_http_request?
    assert !@request.xhr?

    @request.env['HTTP_X_REQUESTED_WITH'] = "XMLHttpRequest"
    assert @request.xml_http_request?
    assert @request.xhr?
	 */
	}
	
	public function test_reports_ssl()
	{
		$this->assertTrue( !$this->request->ssl() );
    	$this->request->env['HTTPS'] = 'on';
    	$this->assertTrue( $this->request->ssl() );
	}
	
	public function test_request_methods()
	{
		$m = array( 'get', 'head', 'post', 'put', 'delete' );
		foreach( $m as $method )
		{
			$this->set_request_method_to( $method );
			$this->assertEquals( $method, $this->request->method() );
		}
	}
	
	/**
	 * @expectedException biru_controller\unknown_http_method
	 */
	public function test_invalid_http_method_raises_exception()
	{
		$this->set_request_method_to( "random_method" );
		$this->request->method();
	}
	
	public function test_content_type()
	{
		$this->request->env['CONTENT_TYPE'] = "text/html";
		$this->assertEquals( "text/html", $this->request->content_type() );
	}
	
	public function test_content_no_type()
	{
		$this->assertEquals( "text", $this->request->content_type() );
	}
	
	public function test_user_agent()
	{
		$this->assertNotNull( $this->request->user_agent );
	}  
  /**
  def test_parameters
    @request.instance_eval { @request_parameters = { "foo" => 1 } }
    @request.instance_eval { @query_parameters = { "bar" => 2 } }
    
    assert_equal({"foo" => 1, "bar" => 2}, @request.parameters)
    assert_equal({"foo" => 1}, @request.request_parameters)
    assert_equal({"bar" => 2}, @request.query_parameters)
  end
  */

	protected function set_request_method_to( $method )
	{
		$this->request->env['REQUEST_METHOD'] = strtoupper( $method );
		$this->request->request_method = null;
	}
}