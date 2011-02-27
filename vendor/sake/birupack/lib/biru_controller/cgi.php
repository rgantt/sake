<?php
namespace biru_controller;

class cgi
{
    public $env_table;
    static $cookie;

    public function __construct( &$request_hash = array() )
    {
    	// can't assign-by-reference in a ternary
    	if( empty( $request_hash ) && isset( $_SESSION ) )
    		$this->env_table = &$_SESSION;
    	else
    		$this->env_table = &$request_hash;
    }

    public function header( $headers )
    {
        /**
        foreach( $headers as $name => $value )
        {
            header("{$name}: {$value}");
        }
         */
        return '';
    }

    public function cookies()
    {
        return $_COOKIE;
    }

    public function query_string()
    {
        return $this->env_table['QUERY_STRING'];
    }

    static function unescape( $string )
    {
        return urldecode( $string );
    }
}
