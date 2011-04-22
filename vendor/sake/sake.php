<?php
require_once dirname(__FILE__)."/birupack/lib/biru_controller.php";
require_once dirname(__FILE__)."/birupack/lib/biru_view.php";

$parts = explode( '/', $_SERVER['PHP_SELF'] );
array_shift( $parts );
array_pop( $parts );
define( 'SAKE_PATH', join( '/', $parts ) );

/**
 * called whenever an attempt is made to instantiate (or call a static method of) a class that 
 * is not currently defined. attempts to include the controller's helper file at load time.
 */
function __autoload( $name )
{
	// are we looking at a namespace situation?
    if( preg_match( '|\\\|', $name ) )
        return;
    $cls = strtolower( substr( $name, 0, strpos( $name, '_controller' ) ) );
    if( file_exists( SAKE_ROOT."/app/controllers/{$cls}.php" ) )
    {
    	require_once SAKE_ROOT."/app/controllers/{$cls}.php";
    	@include_once SAKE_ROOT."/app/helpers/{$cls}_helper.php";
    }
    else
        throw new \biru_controller\sake_exception( "attempt to call undefined class {$name}" );
}