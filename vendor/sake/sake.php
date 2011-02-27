<?
require_once "birupack/lib/biru_controller.php";
require_once "birupack/lib/biru_view.php";
require_once "birupack/lib/biru_pack.php";

$parts = explode( '/', $_SERVER['PHP_SELF'] );
array_shift( $parts );
array_pop( $parts );
define( 'SAKE_PATH', join( '/', $parts ) );

/**
 * a php magic function that is called whenever an attempt is made to instantiate (or call a static method of) a
 * class that is not currently defined in the global namespace.
 * 
 * the sake implementation of this function allows for "intelligent" run-time class loading. it has the added benefit
 * of attempting to include the associated controller's helper file at load time.
 * 
 * @param $name
 */
function __autoload( $name )
{
	if( stristr( $name, '_controller' ) === false )
		return activerecord_autoload( $name );
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
        throw new \sake_exception( "attempt to call undefined class {$name}" );
}

/**
 * a generic exception class that can be subclassed for various purposes throughout the framework
 * to provide extra utility for more finely-grained exception handling logic
 * 
 * @author bsdlite
 */

class sake_exception extends \Exception
{
    public function unwind()
    {
        $str  = "<pre>";
        $str .= "application error!\n------------------\n\n";
        $str .= "text: {$this->getMessage()}\n";
        $str .= "file: {$this->getFile()}\n";
        $str .= "line: {$this->getLine()}\n";
        $str .= "stack:\n";
        $str .= "{$this->getTraceAsString()}\n";
        $str .= "</pre>";
        return $str;
    }
}

/**
 * this function allows for exception throwing when a function returns false, e.g.:
 * 
 * $result = mysql_query( " sql query here " ) or except( mysql_error() );
 *
 * unfortunately, this isn't very flexible as it only allows a generic sake_exception 
 * to be thrown.
 * 
 * @param $string the error message to pass to the exception
 */
function except( $string )
{
    throw new \sake_exception( $string );
}
?>
