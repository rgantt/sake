<?php
namespace biru_controller;

/**
 * Rewrites URLs for Base.redirect_to and Base.url_for in the controller.
 */
class url_rewriter
{
    static $RESERVED_OPTIONS = array( 
        'anchor', 
        'params', 
        'only_path', 
        'host', 
        'protocol', 
        'trailing_slash', 
        'skip_relative_url_root' 
    );

	public function __construct( &$request, &$parameters )
	{
		$this->request = $request;
		$this->parameters = $parameters;
	}

	public function rewrite( $options = array() )
	{
		return $this->rewrite_url( $options );
	}

	public function __string()
	{
		return "{$this->request->protocol()}, {$this->request->host_with_port()}, {$this->request->path()}, {$this->parameters->page}, {$this->parameters->action}, ".serialize( $this->request->parameters() );
	}

	public function to_str()
	{
		return $this->__string();
	}

	public function to_s()
	{
		return $this->__string();
	}

	private function rewrite_url( $options )
	{
		$rewritten_url = '';
		
		if( !isset( $options['only_path'] ) )
		{
			$rewritten_url .= ( isset( $options['protocol'] ) ? $options['protocol'] : $this->request->protocol() );
			if( !preg_match( "!://!", $rewritten_url ) )
				$rewritten_url .= "://";
			$rewritten_url .= ( isset( $options['host'] ) ? $options['host'] : $this->request->host_with_port() );
			if( isset( $options['port'] ) )
			{
				$rewritten_url .= ":{$options['port']}";
				unset( $options['port'] );
			}
		}
		
		if( isset( $options['skip_relative_url_root'] ) && !$options['skip_relative_url_root'] )
			$rewritten_url .= $this->request->relative_url_root;
		
		$rewritten_url .= $this->rewrite_path( $options );
		if( isset( $options['trailing_slash'] ) && $options['trailing_slash'] )
			$rewritten_url .= '/';
		if( isset( $options['anchor'] ) && $options['anchor'] )
			$rewritten_url .= '#'.$options['anchor'];
			
		return $rewritten_url;
	}

	function rewrite_path( $options )
	{
        foreach( self::$RESERVED_OPTIONS as $k )
            unset( $options[ $k ] ); 

		if( isset( $options['overwrite_params'] ) && $overwrite = $options['overwrite_params'] )
		{
			/**
			foreach( $overwrite as $key => $value )
			{
				if( isset( $this->parameters->$key ) )
					$this->parameters->$key = $value;
			}
			*/
			unset( $options['overwrite_params'] );
		}    
        return $this->build_query_string( $options );
	}

	# Returns a query string with escaped keys and values from the passed hash. If the passed hash contains an "id" it'll
	# be added as a path element instead of a regular parameter pair.
    // it seems like this particular function is very sensitive to use of mod_rewrite... how does rails overcome it?
	function build_query_string( $hash, $only_keys = false )
	{
		$elements = array();
		$query_string = '';
		$only_keys = array_keys( $hash );
		$index = 0;
		foreach( $only_keys as $key )
		{
			if( is_numeric( $key ) && ( $index > 1 ) )
				throw new invalid_query_exception("cannot build query string for numeric key");
			if( !preg_match( "/^[a-zA-Z0-9_]+$/", $key ) )
				throw new invalid_query_exception("cannot build query string for keys with special characters");
			$value = $hash[ $key ];
			if ( is_array( $value ) )
				$key .=  '[]';
			else
				$value = array( $value );
            foreach( $value as $val )
            { 
                if( $key == "page" || $key == "action" )
                    $elements[] = "{$val}";
                else if( is_numeric( $key ) )
                	$elements[] = "{$val}";
                else
                    $elements[] = "{$key}/{$val}";
            }
            $index++;
		}
        if( count( $elements ) > 0 )
			$query_string .= '/'.$this->join( $elements, '/' );
		return $query_string;
	}
	
	private function join( $array, $delim )
	{
		$join = '';
		foreach( $array as $element ){ $join .= $element.$delim; }
		return substr( $join, 0, strlen( $join ) - 1 );
	}
}
