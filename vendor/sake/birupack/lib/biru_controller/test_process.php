<?php 
namespace biru_controller;

require_once dirname(__FILE__).'/base.php';
require_once dirname(__FILE__).'/request.php';
require_once dirname(__FILE__).'/response.php';
require_once dirname(__FILE__).'/routing/routes.php';
require_once dirname(__FILE__).'/url_rewriter.php';
require_once dirname(__FILE__).'/../biru_view/base.php';
require_once dirname(__FILE__).'/../biru_view/template_finder.php';
require_once dirname(__FILE__).'/../biru_view/template_handler.php';
require_once dirname(__FILE__).'/../biru_view/template.php';
require_once dirname(__FILE__).'/../biru_view/template_handlers/compilable.php';
require_once dirname(__FILE__).'/../biru_view/template_handlers/phtml.php';

class concrete_base extends base
{
	public function initialize()
	{
		$this->set_default_layout("blank");
	}
	
    static function process_test( &$request )
    {
    	$base = new self;
    	return $base->_process_test( $request );
    }

    public function _process_test( &$request )
    {
    	return $this->process( $request, new test_response );
    }

    public function process_with_test()
    {
    	$this->add_variables_to_assigns();
    	return call_user_func_array( array( $this, 'process_without_test' ), func_get_args() );
	}

    //alias_method_chain :process, :test
	public function process_without_test()
	{
		return call_user_func_array( array( $this, 'process' ), func_get_args() );
	}
}

class test_request extends abstract_request
{
	public $cookies;
	public $session_options;
	public $query_parameters = array();
	public $request_parameters = array();
	public $path;
	public $session;
	public $env;
	public $host;
	public $user_agent;

	public function __construct( $query_parameters = null, $request_parameters = null, $session = null )
	{
		$this->query_parameters = !is_null( $query_parameters ) ? $query_parameters : array();
		$this->request_parameters = !is_null( $request_parameters ) ? $request_parameters : array();
		$this->session = !is_null( $session ) ? $session : new test_session;
		
		$this->initialize_containers();
		$this->initialize_default_values();
	}
	
	public function &query_parameters()
	{
		return $this->query_parameters;
	}
	
	public function &request_parameters()
	{
		return $this->request_parameters;
	}

	public function &cookies()
	{
		return $this->cookies;
	}
	
	public function &session()
	{
		return $this->session;
	}
	
	public function reset_session()
	{
		$this->session = new test_session;
	}

	public function body()
	{
		return $this->raw_post();
	}
	
	public function raw_post()
	{
		$this->env['RAW_POST_DATA'] = ( isset( $this->env['RAW_POST_DATA'] ) ? $this->env['RAW_POST_DATA'] : $this->url_encoded_request_parameters() );
		return $this->env['RAW_POST_DATA'];
	}
	
	public function port( $number = false )
	{
		if( $number !== false )
			$this->env['SERVER_PORT'] = $number;
		return $this->env['SERVER_PORT'];
	}
	
	public function action( $action_name )
	{
		$this->query_parameters['action'] = $action_name;
		$this->parameters = null;
	}
	
	# Used to check AbstractRequest's request_uri functionality.
    # Disables the use of @path and @request_uri so superclass can handle those.
	public function set_REQUEST_URI( $value )
	{
		$this->env['REQUEST_URI'] = $value;
		$this->request_uri = null;
		$this->path = null;
	}

	public function request_uri( $uri = '' )
	{
		if( isset( $this->request_uri ) )
			return $this->request_uri;
		return parent::request_uri();
	}
	
	public function accept( $mime_types )
	{
		$this->env['HTTP_ACCEPT'] = implode( ",", $mime_types );
	}
	
	public function remote_addr( $addr )
	{
		$this->env['REMOTE_ADDR'] = $addr;
	}
	
	public function assign_parameters( $controller_path, $action, $parameters )
	{
		$parameters = array_merge( ( is_array( $parameters ) ? $parameters : array() ), array( 'page' => $controller_path, 'action' => $action ) );
		$extra_keys = \biru_controller\routing\routes::extra_keys( $parameters );
		$non_path_parameters = $this->get() ? $this->query_parameters() : $this->request_parameters();
		foreach( $parameters as $key => $value )
		{
			if( is_array( $value ) )
				$value = new \biru_controller\routing\path_segment\result( $value );
			if( isset( $extra_keys[ $key ] ) )
				$this->non_path_parameters[ $key ] = $value;
			else 
				$this->path_parameters[ $key ] = $value;
		}
		$this->parameters = null;
	}

	public function recycle()
	{
		$this->request_parameters = array();
		$this->query_parameters = array();
		$this->path_parameters = array();
		$this->request_method = null;
		$this->accepts = null;
		$this->content_type = null;
	}

    private function initialize_containers()
    {
    	$this->env = array();
    	$this->cookies = array();
    }
    
    private function initialize_default_values()
    {
    	$this->host = "test.host";
    	$this->request_uri = "/";
    	$this->user_agent = "SAKE Testing";
    	$this->remote_addr( "0.0.0.0" );
    	$this->env['SERVER_PORT'] = 80;
    	$this->env['REQUEST_METHOD'] = "GET";
    	
    	$this->env['SERVER_NAME'] = 'localhost';
    	$this->env['REQUEST_URI'] = '';
    	$this->env['SCRIPT_NAME'] = '';
    	
    	$this->env['CONTENT_TYPE'] = 'text';
    }
    
    private function url_encoded_request_parameters()
    {
    	$params = $this->request_parameters();
    	foreach( array( 'controller', 'action', 'only_path' ) as $k )
    		unset( $params[ $k ] );
    	return $params;
    }
}

class test_response extends abstract_response
{
	public function response_code()
	{
		return (int)substr( $this->headers['Status'], 0, 3 );
	}
	
	public function code()
	{
		$code = explode( ' ', $this->headers['Status'] );
		return (int)$code[0];
	}
	
	public function message()
	{
		$message = explode( ' ', $this->headers['Status'] );
		return (int)$message[1];
	}
	
	public function success()
	{
		return $this->response_code() == 200;
	}
	
	public function missing()
	{
		return $this->response_code() == 404;
	}
	
	public function redirect( $to_url=null, $response_status=null )
	{
		return ( $this->response_code() <= 399 ) && ( $this->response_code() >= 300 );
	}
	
	public function error()
	{
		return ( $this->response_code() <= 599 ) && ( $this->response_code() >= 500 );
	}
	
	public function redirect_url()
	{
		return $this->headers['Location'];
	}
	
	// alias_method :server_error?, :error?
	
	public function redirect_url_match( $pattern )
	{
		if( !$this->redirect_url )
			return false;
		return preg_match( $pattern, $this->redirect_url() );
	}
	
	public function rendered_file( $with_controller = false )
	{
		if( $this->template->first_render != null )
		{
			if( $with_controller !== false )
				return $this->template->first_render;
			else
			{
				$tmp = $this->template->first_render;
				$spl = explode( "/", $tmp );
				return( !empty( $spl[ count( $spl ) ] ) ? $spl[ count( $spl ) ] : $tmp );
			}
		}
		return null;
	}
	
	public function rendered_with_file()
	{
		return !( $this->rendered_file == null );
	}
	
	public function &flash()
	{
		if( isset( $this->session['flash'] ) )
			$this->session['flash'] = new flash();
		return $this->session['flash'];
	}
	
	public function has_flash()
	{
		return !empty( $this->session['flash'] );
	}
	
	public function has_flash_with_contents()
	{
		$val = $this->flash();
		return !empty( $val );
	}
	
	public function has_flash_object( $name )
	{
		$flash = $this->flash();
		return isset( $flash[ $name ] );
	}
	
	public function has_session_object( $name )
	{
		return is_null( $this->session[ $name ] );
	}
	
	public function template_objects()
	{
		return( isset( $this->template->assigns ) ? $this->template->assigns : array() );
	}
	
	public function has_template_object( $name )
	{
		$to = $this->template_object();
		return isset( $to[ $name ] );
	}
	
	public function &cookies()
	{
		return $this->cookies;
	}
	
	public function binary_content()
	{
		return $this->body();
	}
}

class test_session implements \ArrayAccess
{
	public $session_id;
	private $attributes;
	
	public function __construct( $attributes = null )
	{
		$this->session_id = '';
		$this->attributes = $attributes;
		$this->saved_attributes = null;
	}
	
	public function offsetExists( $offset )
	{
		return isset( $this->attributes[ $offset ] );
	}
	
	public function offsetGet( $offset )
	{
		return $this->offsetExists( $offset ) ? $this->attributes[ $offset ] : null;
	}
	
	public function offsetSet( $offset, $value )
	{
		if( is_null( $offset ) )
			throw new \sake_exception("Session offset requires named key");
		$this->attributes[ $offset ] = $value;
	}
	
	public function offsetUnset( $offset )
	{
		unset( $this->attributes[ $offset ] );
	}
	
	public function data()
	{
		if( !$this->attributes )
			$this->attributes = isset( $this->saved_attributes ) ? $this->saved_attributes : new stdClass;
		return $this->attributes;
	}
	
	public function update()
	{
		$this->saved_attributes = $this->attributes;
	}
	
	public function delete()
	{
		$this->attributes = null;
	}
	
	public function close()
	{
		$this->update();
		$this->delete();
	}
}
  
class SAKE_test_case extends \PHPUnit_Framework_TestCase
{
    public function get( $action, $parameters = null, $session = null, $flash = null )
    {
    	if( isset( $this->request ) )
    		$this->request->env['REQUEST_METHOD'] = "GET";
    	return $this->process( $action, $parameters, $session, $flash ); 
    }
    
    public function post( $action, $parameters = null, $session = null, $flash = null )
    {
    	if( isset( $this->request ) )
    		$this->request->env['REQUEST_METHOD'] = "POST";
    	return $this->process( $action, $parameters, $session, $flash ); 
    }
	
    public function put( $action, $parameters = null, $session = null, $flash = null )
    {
    	if( isset( $this->request ) )
    		$this->request->env['REQUEST_METHOD'] = "PUT";
    	return $this->process( $action, $parameters, $session, $flash ); 
    }
	
    public function delete( $action, $parameters = null, $session = null, $flash = null )
    {
    	if( isset( $this->request ) )
    		$this->request->env['REQUEST_METHOD'] = "DELETE";
    	return $this->process( $action, $parameters, $session, $flash ); 
    }
	
    public function head( $action, $parameters = null, $session = null, $flash = null )
    {
    	if( isset( $this->request ) )
    		$this->request->env['REQUEST_METHOD'] = "HEAD";
    	return $this->process( $action, $parameters, $session, $flash ); 
    }
	
	public function process( $action, $parameters = null, $session = null, $flash = null )
	{
		$vals = array( 'controller', 'request', 'response' );
		foreach( $vals as $iv_name )
		{
			if( !isset( $this->$iv_name ) )
				throw new \sake_exception("{$iv_name} is null: make sure you set it in your test's setup method"); 
		}
		
		$this->request->recycle();
		$this->html_document = null;
		$this->request->env['REQUEST_METHOD'] = ( isset( $this->request->env['REQUEST_METHOD'] ) ? $this->request->env['REQUEST_METHOD'] : "GET" );
		$this->request->action = $action;
		
		$parameters = !is_null( $parameters ) ? $parameters : new \stdClass;
		$this->request->assign_parameters( $this->controller, $action, $parameters );
		
		if( $session !== null )
			$this->request->session = new test_session( $session );
		if( $flash !== null )
			$this->request->session['flash'] = new flash( $flash );
		$this->build_request_uri( $action, $parameters );
		return $this->controller->process( $this->request, $this->response );
	}
	
	public function xml_http_request( $request_method, $action, $parameters = null, $session = null, $flash = null )
	{
		$this->request->env['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		$this->request->env['HTTP_ACCEPT'] = 'text/javascript, text/html, application/xml, text/xml, */*';
		
		$val = $this->$request_method( $action, $parameters, $session, $flash );
		unset( $this->request->env['HTTP_X_REQUESTED_WITH'] );
		unset( $this->request->env['HTTP_ACCEPT'] );
		return $val;
	}
	
	public function xhr()
	{
		return call_user_func_array( array( $this, 'xml_http_request' ), func_get_args() );
	}
	
	public function follow_redirect()
	{
		$redirected_controller = $this->response->redirect_to['controller'];
		if( $redirected_controller && ( $redirected_controllerd != $this->controller->controller_name ) )
			throw new \sake_exception("Can't follow redirects outside of current controller (from {$this->controller->controller_name} to {$redirected_controller}");
		unset( $this->response->redirected_to[ $action ] );
		$this->get( true, $this->response->redirected_to );
	}

	public function assigns( $key = null )
	{
		if( $key === null )
			return $this->response->template->assigns;
		else
			return $this->response->template->assigns[ $key ];
	}
	
	public function &session()
	{
		return $this->response->session;
	}
	
	public function &flash()
	{
		return $this->response->flash;
	}
	
	public function &cookies()
	{
		return $this->response->cookies;
	}
	
	public function redirect_to_url()
	{
		return $this->response->redirect_url;
	}

	public function build_request_uri( $action, $parameters )
	{
		if( !$this->request->env['REQUEST_URI'] )
		{
			$options = $this->controller->rewrite_options( $parameters );
			$options['only_path'] = true;
			$options['action'] = $action;
			
			$url = new \biru_controller\url_rewriter( $this->request, $this->response );
			$this->request->set_REQUEST_URI( $url->rewrite( $options ) );
		}
	}

	public function html_document()
	{
		$xml = preg_match( '/xml$/', $this->response->content_type );
		$this->html_document = ( isset( $this->html_document ) ? $this->html_document : new \html\document( $this->response->body(), false, $xml ) );
	}

	public function find_tag( $conditions )
	{
		return $this->html_document()->find( $conditions );
	}
	
	public function find_all_tag( $conditions )
	{
		return $this->html_document()->find_all( $conditions );
	}
	
	public function method_missing( $selector )
	{
		if( in_array( \biru_controller\routing\routes::$name_routes->helpers, $selector ) )
			return call_user_func_array( array( $this->controller, $selector ), array_slice( func_get_args, 1, count( func_get_args() - 1 ) ) );
		return call_user_func_array( array( parent, 'method_missing' ), func_get_args() );
	}
}
