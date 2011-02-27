<?php
namespace biru_controller;

require_once dirname(__FILE__)."/../sake_unit.php";
require_once dirname(__FILE__)."/../../lib/biru_controller/cgi_process.php";
require_once dirname(__FILE__)."/../../lib/biru_controller/cgi.php";

abstract class base_cgi_test extends SAKE_test_case
{
	public function setUp()
	{
		$this->request_hash = array(
	    	'HTTP_MAX_FORWARDS' => '10', 
	    	'SERVER_NAME' => 'glu.ttono.us:8007', 
	    	'FCGI_ROLE' => 'RESPONDER', 
	    	'HTTP_X_FORWARDED_HOST' => 'glu.ttono.us', 
	    	'HTTP_ACCEPT_ENCODING' => 'gzip, deflate', 
	    	'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en) AppleWebKit/312.5.1 (KHTML, like Gecko) Safari/312.3.1', 
	    	'PATH_INFO' => '', 
	    	'HTTP_ACCEPT_LANGUAGE' => 'en', 
	    	'HTTP_HOST' => 'glu.ttono.us:8007', 
	    	'SERVER_PROTOCOL' => 'HTTP/1.1', 
	    	'REDIRECT_URI' => '/index.php', 
	    	'SCRIPT_NAME' => '/index.php', 
	    	'SERVER_ADDR' => '207.7.108.53', 
	    	'REMOTE_ADDR' => '207.7.108.53', 
	    	'SERVER_SOFTWARE' => 'lighttpd/1.4.5', 
	    	'HTTP_COOKIE' => '_session_id=c84ace84796670c052c6ceb2451fb0f2; is_admin=yes', 
	    	'HTTP_X_FORWARDED_SERVER' => 'glu.ttono.us', 
	    	'REQUEST_URI' => '/admin', 
	    	'DOCUMENT_ROOT' => '/home/bsdlite/sites/sake', 
	    	'SERVER_PORT' => '8007', 
	    	'QUERY_STRING' => '', 
	    	'REMOTE_PORT' => '63137', 
	    	'GATEWAY_INTERFACE' => 'CGI/1.1', 
	    	'HTTP_X_FORWARDED_FOR' => '65.88.180.234', 
	    	'HTTP_ACCEPT' => '*/*', 
	    	'SCRIPT_FILENAME' => '/home/bsdlite/sites/sake/index.php', 
	    	'REDIRECT_STATUS' => '200', 
	    	'REQUEST_METHOD' => 'GET'
	    	);
	    	# cookie as returned by some Nokia phone browsers (no space after semicolon separator)
	    	$this->alt_cookie_fmt_request_hash = array(
	    	"HTTP_COOKIE"=>"_session_id=c84ace84796670c052c6ceb2451fb0f2;is_admin=yes"
	    	);
	    	$this->fake_cgi = new cgi( $this->request_hash );
	    	$this->request = new cgi_request( $this->fake_cgi );
	}
}

class cgi_test extends base_cgi_test // CgiRequestTest
{
	public function test_proxy_request()
	{
		$this->assertEquals( 'glu.ttono.us', $this->request->host_with_port() );
	}

	public function test_http_host()
	{
		unset( $this->request_hash['HTTP_X_FORWARDED_HOST'] );
		$this->request_hash['HTTP_HOST'] = "rubyonrails.org:8080";
		$this->assertEquals( "rubyonrails.org:8080", $this->request->host_with_port() );

		$this->request_hash['HTTP_X_FORWARDED_HOST'] = "www.firsthost.org, www.secondhost.org";
		$this->assertEquals( "www.secondhost.org", $this->request->host() );
	}

	public function test_http_host_with_default_port_overrides_server_port()
	{
		unset( $this->request_hash['HTTP_X_FORWARDED_HOST'] );
		$this->request_hash['HTTP_HOST'] = "rubyonrails.org";
		$this->assertEquals( "rubyonrails.org", $this->request->host_with_port() );
	}

	public function test_host_with_port_defaults_to_server_name_if_no_host_headers()
	{
		unset( $this->request_hash['HTTP_X_FORWARDED_HOST'] );
		unset( $this->request_hash['HTTP_HOST'] );
		$this->assertEquals( "glu.ttono.us:8007", $this->request->host_with_port() );
	}

	public function test_host_with_port_falls_back_to_server_addr_if_necessary()
	{
		unset( $this->request_hash['HTTP_X_FORWARDED_HOST'] );
		unset( $this->request_hash['HTTP_HOST'] );
		unset( $this->request_hash['SERVER_NAME'] );
		$this->assertEquals( "207.7.108.53:8007", $this->request->host_with_port() );
	}

	public function test_host_with_port_if_http_standard_port_is_defined()
	{
		$this->request_hash['HTTP_X_FORWARDED_HOST'] = "glu.ttono.us:80";
		$this->assertEquals( "glu.ttono.us", $this->request->host_with_port() );
	}

	public function test_host_with_port_if_https_standard_port_is_defined()
	{
		$this->request_hash['HTTP_X_FORWARDED_PROTO'] = "https";
		$this->request_hash['HTTP_X_FORWARDED_HOST'] = "glu.ttono.us:443";
		$this->assertEquals( "glu.ttono.us", $this->request->host_with_port() );
	}

	public function test_host_if_ipv6_reference()
	{
		unset( $this->request_hash['HTTP_X_FORWARDED_HOST'] );
		$this->request_hash['HTTP_HOST'] = "[2001:1234:5678:9abc:def0::dead:beef]";
		$this->assertEquals( "[2001:1234:5678:9abc:def0::dead:beef]", $this->request->host() );
	}

	public function test_host_if_ipv6_reference_with_port()
	{
		unset( $this->request_hash['HTTP_X_FORWARDED_HOST'] );
		$this->request_hash['HTTP_HOST'] = "[2001:1234:5678:9abc:def0::dead:beef]:8008";
		$this->assertEquals( "[2001:1234:5678:9abc:def0::dead:beef]", $this->request->host() );
	}

	/** NOT YET IMPLEMENTED!
	 public function test_cookie_syntax_resilience()
	 {
		$c = cgi::$cookie;
		$cookie = $c::parse( $this->request_hash['HTTP_COOKIE'] );
		$this->assertEquals( array("c84ace84796670c052c6ceb2451fb0f2"), $cookies['_session_id'] );

		$ac = cgi::$cookie;
		$alt_cookies = $ac::parse( $this->alt_cookie_fmt_request_hash['HTTP_COOKIE'] );
		$this->assertEquals( array( "c84ace84796670c052c6ceb2451fb0f2" ), $alt_cookies['_session_id'] );
		$this->assertEquals( array( "yes" ), $alt_cookies['is_admin'] );
		}
		*/

	public function test_doesnt_break_when_content_type_has_charset()
	{
		$data = 'flamenco=love';
		$this->request->env['CONTENT_LENGTH'] = strlen( $data );
		$this->request->env['CONTENT_TYPE'] = 'application/x-www-form-urlencoded; charset=utf-8';
		$this->request->env['RAW_POST_DATA'] = $data;
		$this->assertEquals( array( 'flamenco' => 'love' ), $this->request->request_parameters() );
	}

	public function test_doesnt_interpret_request_uri_as_query_string_when_missing()
	{
		$this->request->env['REQUEST_URI'] = 'foo';
		$this->assertEquals( array(), $this->request->query_parameters() );
	}
}