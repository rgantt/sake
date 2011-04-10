<?php
namespace biru_controller\routing;

class routes
{
    static function recognize( $request )
    {
    	global $default;
        if( isset( $_GET['page'] ) && $_GET['page'] )
            $page = $_GET['page'];
        else
            $page = $default['page'];
        $class = ucfirst( strtolower( $page ) ).'_controller';
        return new $class(); 
    }
    
    static function extra_keys( $hash, $recall = array() )
    {
    	//(hash || {}).keys.map { |k| k.to_sym } - (recall || {}).keys - significant_keys
    	return array();
    }
}
?>
