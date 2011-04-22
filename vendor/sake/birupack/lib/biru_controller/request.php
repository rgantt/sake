<?php
namespace biru_controller;

require_once dirname(__FILE__)."/reader.php";
require_once dirname(__FILE__)."/mime.php";
require_once dirname(__FILE__)."/cgi.php";

$ACCEPTED_HTTP_METHODS = array( 
    'get', 'head', 'put', 'post', 'delete', 'options' 
);
$param_parsers = array(
    'MULTIPART_FORM' 	=> 'multipart_form',
    'URL_ENCODED_FORM' 	=> 'url_encoded_form',
    'XML' 				=> 'xml_simple',
    'JSON' 				=> 'json'
);

class unknown_http_method extends \Exception {}

abstract class abstract_request
{
    public $relative_url_root;
    public $env;
    public $protocol;
    public $host_with_port;
    public $port;
    public $content_length;

    protected $parameters;
    protected $path_parameters;
    
    abstract public function body();
    abstract public function query_parameters();
    abstract public function request_parameters();
    abstract public function &cookies();
    abstract public function &session();
    abstract public function reset_session();
    
    public function request_method()
    {
        global $ACCEPTED_HTTP_METHODS;
        $params = $this->parameters();
        $method = ( $this->env['REQUEST_METHOD'] == 'POST' && isset( $params['_method'] ) ) ? strtolower( $params['_method'] ) : strtolower( $this->env['REQUEST_METHOD'] );
        if( in_array( $method, $ACCEPTED_HTTP_METHODS ) )
            return $method;
        else
            throw new unknown_http_method("{$method} not a valid request method");
    }

    public function method()
    {
        return ( $this->request_method() ? $this->request_method() : 'get' );
    }

    public function get()
    {
        return ( $this->request_method() == 'get' );
    }

    public function post()
    {
        return ( $this->request_method() == 'post' );
    }

    public function put()
    {
        return( $this->request_method() == 'put' );
    }

    public function delete()
    {
        return ( $this->request_method() == 'delete' );
    }

    public function head()
    {
        return ( $this->request_method() == 'head' );
    }

    public function headers()
    {
        if( !$this->headers )
            $this->headers = new biru_controller\http\headers( $this->env );
        return $this->headers;
    }

    public function content_length()
    {
        if( !$this->content_length )
            $this->content_length = (int) $this->env['CONTENT_LENGTH'];
        return $this->content_length;
    }

    public function content_type()
    {
        if( !isset( $this->content_type ) )
            $this->content_type = \mime\type::lookup( $this->content_type_without_parameters() );
        return $this->content_type;
    }

    public function accepts()
    {
        if( !$this->accepts )
        {
            if( $this->env['HTTP_ACCEPT'] == '' )
                $this->accepts = array( $this->content_type(), mime::ALL );
            else
                $this->accepts = \mime\type::parse( $this->env['HTTP_ACCEPT'] );
        }
        return $this->accepts;
    }

    public function format()
    {
        if( !$this->format )
            $this->format = ( $this->parameters['format'] ? \mime\type::lookup_by_extension( $this->parameters['format'] ) : $this->accepts[0] );
        return $this->format;
    }

    public function xml_http_request()
    {
        return preg_match( '/XMLHttpRequest/i', $this->env['HTTP_X_REQUESTED_WITH'] );
    }

    public function xhr(){ return $this->xml_http_request(); }

    public function remote_ip()
    {
    	if( isset( $this->env['HTTP_CLIENT_IP'] ) && $this->env['HTTP_CLIENT_IP'] )
    		return $this->env['HTTP_CLIENT_IP'];
    	
    	if( isset( $this->env['HTTP_X_FORWARDED_FOR'] ) )
    	{
    		$remote_ips = explode( ',', $this->env['HTTP_X_FORWARDED_FOR'] );
    		foreach( $remote_ips as $key => $ip )
    		{
    			if( preg_match( '/^unknown$|^(10|172\.(1[6-9]|2[0-9]|30|31)|192\.168)\./i', trim( $ip ) ) )
    				unset( $remote_ips[ $key ] );
    		}
    		if( ( count( $remote_ips ) > 0 ) && is_string( reset( $remote_ips ) ) )
    			return trim( reset( $remote_ips ) );
    	}	
    		
        return $this->env['REMOTE_ADDR'];
    }

    public function server_software()
    {
        $matches = array();
        @( $n = preg_match_all( '/^([a-zA-Z]+)/', $this->env['SERVER_SOFTWARE'], $matches ) );
        return ( $n ? strtolower( $matches[0][0] ) : null );
    }

    public function url()
    {
        return $this->protocol().$this->host_with_port().$this->request_uri();
    }

    public function protocol()
    {
        return ( $this->ssl() ? 'https://' : 'http://' );
    }

    public function ssl()
    {
        return ( 
        	( isset( $this->env['HTTPS'] ) && $this->env['HTTPS'] == 'on' ) || 
        	( isset( $this->env['HTTP_X_FORWARDED_PROTO'] ) && $this->env['HTTP_X_FORWARDED_PROTO'] == 'https' ) 
        );
    }

    public function host()
    {
    	return $this->host;
    }

    public function host_with_port()
    {
        if( !$this->host_with_port )
            $this->host_with_port = $this->host().$this->port_string();
        return $this->host_with_port;
    }

    public function port()
    {
        return $this->env['SERVER_PORT'];
    }

    public function standard_port()
    {
        if( $this->protocol() == 'https://' )
            return '443';
        return '80';
    }

    public function port_string()
    {
        return ( $this->port() == $this->standard_port() ) ? '' : ":{$this->port()}";
    }

    public function domain( $tld_length = 1 )
    {
        if( !$this->named_host( $this->host() ) )
            return null;
        $parts = explode( '.', $this->host() );
        return implode( '.', array_slice( $parts, -( 1 + $tld_length ) ) );
    }

    public function subdomains( $tld_length = 1 )
    {
    	if( !$this->named_host( $this->host() ) )
    		return array();
    	$parts = explode( '.', $this->host() );
    	return array_slice( $parts, 0, -( 1 + $tld_length ) );
    }

    public function query_string()
    {
        $uri = $this->request_uri();
        if( $uri )
        {
            $parts = explode( '?', $uri );
            return ( isset( $parts[1] ) ? $parts[1] : '' );
        }
        else
            return $this->env['QUERY_STRING'];
    }

    public function request_uri()
    {
    	if( isset( $this->env['REQUEST_URI'] ) && ( $uri = $this->env['REQUEST_URI'] ) )
    	{
    		$matches = array();
    		preg_match( '@^\w+\://[^/]+(/.*|$)$@', $uri, $matches );
    		if( !empty( $matches ) )
    			return $matches[1];
    		else
    			return $uri;
    	}
    	else
    	{
    		$matches = array();
    		preg_match( '|([^/]+)$|', $this->env['SCRIPT_NAME'], $matches );
    		$script_filename = !empty( $matches[0] ) ? $matches[0] : null;
         	# Construct IIS missing REQUEST_URI from SCRIPT_NAME and PATH_INFO.
        	//script_filename = @env['SCRIPT_NAME'].to_s.match(%r{[^/]+$})
        	$uri = $this->env['PATH_INFO'];
    	   	if( !is_null( $script_filename ) )
       			$uri = preg_replace( "/{$script_filename}\//", "", $uri );
        	if( isset( $this->env['QUERY_STRING'] ) && !is_null( $this->env['QUERY_STRING'] ) && !empty( $this->env['QUERY_STRING'] ) )
        		$uri .= "?{$this->env['QUERY_STRING']}";
    	}

		if( !isset( $uri ) )
			return null;
		else
			$this->env['REQUEST_URI'] = $uri;
		return $this->env['REQUEST_URI'];
    }

    public function path()
    {
        $parts = explode( '?', $this->request_uri() );
        @( $path = $parts[0] );
        $path = preg_replace( "|^{$this->relative_url_root()}|", '', $path );
        return $path;
    }

    public function relative_url_root()
    {
    	if( !isset( $this->relative_url_root ) )
    	{
    		if( isset( $this->env['RAILS_RELATIVE_URL_ROOT'] ) )
    			$this->relative_url_root = $this->env['RAILS_RELATIVE_URL_ROOT'];
    		else if( $this->server_software() == 'apache' )
    			$this->relative_url_root = preg_replace( '/\/dispatch\.(fcgi|rb|cgi)$/', '', $this->env['SCRIPT_NAME'] );
    		else
    			$this->relative_url_root = '';
    	}
    	return $this->relative_url_root;
    }

    public function raw_post()
    {
        if( !isset( $this->env['RAW_POST_DATA'] ) )
        {
            $this->env['RAW_POST_DATA'] = $this->body->read( $this->content_length() );
            if( $body->responds_to('rewind') )
                $body->rewind();
        }
        return $this->env['RAW_POST_DATA'];
    }

    public function parameters()
    {
        if( !$this->parameters )
        {
            $this->parameters = array_merge( 
            	is_array( $this->request_parameters() ) ? $this->request_parameters() : array(), 
            	is_array( $this->query_parameters() ) ? $this->query_parameters() : array(), 
            	$this->path_parameters()
            );
        }
        return $this->parameters;
    }

    public function path_parameters()
    {
        if( !$this->path_parameters )
            $this->path_parameters = array();
        return $this->path_parameters;
    }

    protected function content_type_with_parameters()
    {
        $a = $this->content_type_from_legacy_post_data_format_header();
        if( $a )
            return $a;
        return ( isset( $this->env['CONTENT_TYPE'] ) ? $this->env['CONTENT_TYPE'] : null );
    }

    protected function content_type_without_parameters()
    {
        if( !isset( $this->content_type_without_parameters ) )
            $this->content_type_without_parameters = self::extract_content_type_without_parameters( $this->content_type_with_parameters() );
        return $this->content_type_without_parameters;
    }

    protected function content_type_from_legacy_post_data_format_header()
    {
        $x_post_format = isset( $this->env['HTTP_X_POST_DATA_FORMAT'] ) ? $this->env['HTTP_X_POST_DATA_FORMAT'] : '';
        switch( strtolower( $x_post_format ) )
        {
            case 'yaml':
                return 'application/x-yaml';
            case 'xml':
                return 'application/xml';
        }
        return false;
    }

    protected function parse_formatted_request_parameters()
    {
    	if( $this->content_length() == 0 )
    		return array();
    		
    	$mpb = self::extract_multipart_boundary( $this->content_type_with_parameters() );
    	if( count( $mpb ) > 1 )
    		list( $content_type, $boundary ) = $mpb;
    	else
    		$content_type = $mpb;
    	
    	# Don't parse params for unknown requests
    	if( empty( $content_type ) )
    		return array();
    		
    	$mime_type = \mime\type::lookup( $content_type );
    	$strategy = base::$param_parsers[ strtoupper( $mime_type->symbol ) ];
    	
    	$body = ( ( $strategy && ( $strategy != 'multipart_form' ) ) ? $this->raw_post() : $this->body() );

    	try
    	{
    		switch( $strategy )
    		{
	    		case 'url_encoded_form':
    				self::clean_up_ajax_request_body( $body );
    				return self::parse_query_parameters( $body );
    			case 'multipart_form':
	    			return self::parse_multipart_form_parameters( $body, $boundary, $content_length, $env );
    			case 'xml_simple':
    			case 'xml_node':
	    			break;
    			case 'yaml':
	    			return yaml::load( $body );
    				break;
    			default:
	    			return array();
    		}
    	}
    	catch( \biru_controller\sake_exception $e )
    	{
    		throw $e;
    	}
    }
    
    private function named_host( $host )
    {
        return !(preg_match( '/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $host ) || is_null( $host ));
    }

    static function extract_multipart_boundary( $content_type_with_parameters )
    {
        if( preg_match( '|\Amultipart/form-data.*boundary=\"?([^\";,]+)\"?|', $content_type_with_parameters ) )
            return array( 'multipart/form-data', 0 ); // ['multipart/form-data', $1.dup]
        else
            return self::extract_content_type_without_parameters( $content_type_with_parameters );
    }

    static function extract_content_type_without_parameters( $content_type_with_parameters )
    {
    	$matches = array();
    	preg_match( '/^([^,\;]*)/', $content_type_with_parameters, $matches );
    	if( $matches[0] )
    		return reset( $matches );
    	return null;
    }
    
    static function parse_query_parameters( $query_string )
    {
    	$results = array();
    	parse_str( $query_string, $results );
    	return $results;
    }
    
    static function clean_up_ajax_request_body( &$body )
    {
    	if( substr( $body, -1 ) == '0' )
    		$body = substr( $body, 0, strlen( $body ) - 1 );
    	preg_replace( '/&_=$/', '', $body );
    }
    
    public static function parse_request_parameters( $params )
    {
    	list( $p, $pass ) = array( array(), '' );
    	foreach( $params as $key => $value )
    	{
    		$value = isset( $value[0] ) ? $value[0] : null;
    		$pass .= "{$key}={$value}&";
    	}
    	parse_str( $pass, $p );
    	return $p;
    }
}
