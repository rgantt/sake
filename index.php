<?php
ob_start();
session_start();
define( 'SAKE_ROOT', dirname( __FILE__ ) );

require_once SAKE_ROOT.'/config/config.php';
require_once SAKE_ROOT.'/vendor/sake/sake.php';

try
{
	/**
	 * bootstrap the dispatcher and then process the request
	 */
	list( $controller, $request, $response, $output ) = biru_controller\dispatcher::dispatch( new biru_controller\cgi() );
	echo $controller->process( $request, $response )->out( $output );
}
catch( \sake_exception $e )
{
	echo $e->unwind();
}