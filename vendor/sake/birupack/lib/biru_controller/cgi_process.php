<?php
namespace biru_controller;

require_once dirname(__FILE__).'/session.php';

class cgi_request extends abstract_request
{
    public $cgi;
    public $session_options;
    
    private $request_parameters;
    private $query_parameters;

    static $DEFAULT_SESSION_OPTIONS = array(
      //'database_manager' => CGI\Session\CookieStore::$store, # store data in cookie
      'prefix'           => "sake_sess.",    # prefix session file names
      'session_path'     => "/",             # available to all paths in app
      'session_key'      => "_session_id",
      'cookie_only'      => true
    );

    public function __construct( &$cgi, $session_options = array() )
    {
        //parent::__construct();
        $this->cgi = &$cgi;
        $this->session_options = $session_options;
        $this->session = new lazy_session_reader();
        $this->env = &$this->cgi->env_table;
    }

    public function query_string()
    {
        $qs = '';
        if( method_exists( $this->cgi, 'query_string' ) )
            $qs = $this->cgi->query_string();
        return ( $qs != '' ? $qs : parent::query_string() );
    }

    public function body()
    {
        return $this->env['RAW_POST_DATA'];
    }

    public function &query_parameters()
    {
        if( !$this->query_parameters )
            $this->query_parameters = self::parse_query_parameters( $this->query_string() );
        return $this->query_parameters;
    }

    public function request_parameters()
    {
        if( !$this->request_parameters )
            $this->request_parameters = $this->parse_formatted_request_parameters();
        return $this->request_parameters;
    }

    public function &cookies()
    {
        return $this->cgi->cookies();
    }

    public function host_with_port_without_standard_port_handling()
    {
		if( isset( $this->env['HTTP_X_FORWARDED_HOST'] ) && ( $forwarded = $this->env['HTTP_X_FORWARDED_HOST'] ) )
		{
			$parts = preg_split( '/,\s?/', $forwarded );
			return $parts[ count( $parts ) - 1 ];
		}
		else if( isset( $this->env['HTTP_HOST'] ) && ( $http_host = $this->env['HTTP_HOST'] ) )
			return $http_host;
		else if( isset( $this->env['SERVER_NAME'] ) && ( $server_name = $this->env['SERVER_NAME'] ) )
			return $server_name;
		else
			return "{$this->env['SERVER_ADDR']}:{$this->env['SERVER_PORT']}";
        //return "{$this->env['HTTP_HOST']}:{$this->env['SERVER_PORT']}";
    }

    public function host()
    {
        return preg_replace( '/:\d+$/', '', $this->host_with_port_without_standard_port_handling() );
    }

    public function port()
    {
        $matches = array();
        if( preg_match( '/:(\d+)$/', $this->host_with_port_without_standard_port_handling(), $matches ) )
            return $matches[1];
        return $this->standard_port();
    }

    public function &session()
    {
        throw new \biru_controller\sake_exception("session");
        if( !$this->session )
        {
            if( $this->session_options == false )
                $this->session = array();
            else
            {
                $sk = $this->session_options_with_string_key();
                $qp = $this->query_parameters();
                iF( $this->cookie_only() && $qp[ $sk['session_key'] ] )
                    throw new \biru_controller\sake_exception("session fixation attempt");
                $value = $sk['new_session'];
                if( $value == 'true' )
                    $this->session = $this->new_session();
                else if( $value == 'false' )
                {
                    try
                    {
                        $this->session = new \cgi\session( $this->cgi, $sk );
                    }
                    catch( \biru_controller\sake_exception $e )
                    {
                        $this->session = array();
                    }
                }
                else if( $value == null )
                    $this->session = new \cgi\session( $this->cgi, $sk );
                else
                    throw new \biru_controller\sake_exception("invalid new_session option: {$value}");
            }
            // stale_session_check! do ... @session['__valid_session']
        }
        return $this->session;
    }

    public function reset_session()
    {
        unset( $this->session );
        $this->session = $this->new_session();
    }

    /**
    def method_missing(method_id, *arguments)
      @cgi.send!(method_id, *arguments) rescue super
    end
    */

    private function new_session()
    {
        if( $this->session_options == false )
            return array();
        else
            return new \cgi\session( $this->cgi, array_merge( $this->session_options_with_string_keys, array( 'new_session' => 'true' ) ) );
    }

    private function cookie_only()
    {
        $a = $this->session_options_with_string_keys();
        return $a['cookie_only'];
    }

    private function session_options_with_string_keys()
    {
        if( $this->session_options_with_string_keys )
            $this->session_options_with_string_keys = array_merge( self::DEFAULT_SESSION_OPTIONS, $this->session_options );
        return $this->session_options_with_string_keys;
    }
}

class cgi_response extends abstract_response
{
    public function __construct( $cgi )
    {
        $this->cgi = $cgi;
        parent::__construct();
    }

    public function out( $output = '' )
    {
        $output = $this->cgi->header( $this->headers );
        if( $this->cgi->env_table['REQUEST_METHOD'] == 'HEAD' )
            return $output;
        else
            return $output.$this->body;
    }
}
?>
