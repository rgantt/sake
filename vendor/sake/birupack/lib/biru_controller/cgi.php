<?php
namespace biru_controller;

class cgi
{
    public $env_table;

    public function __construct()
    {
        $this->env_table = $_SERVER;
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
