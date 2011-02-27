<?
require_once 'biru_controller/reader.php';
require_once 'biru_controller/request.php';
require_once 'biru_controller/response.php';
require_once 'biru_controller/session.php';
require_once 'biru_controller/base.php';
require_once 'biru_controller/cgi_process.php';
require_once 'biru_controller/cgi.php';
require_once 'biru_controller/dispatcher.php';
require_once 'biru_controller/flash.php';
require_once 'biru_controller/filters.php';
require_once 'biru_controller/url_rewriter.php';
require_once 'biru_controller/routing/routes.php';
require_once 'biru_controller/mime.php';
require_once 'biru_controller/status.php';


function send_mail( $to, $subject, $message, $additional_headers = '', $additional_parameters = '' )
{
	static $sent = array( 'to' => '', 'subj' => '' );
	static $val = false;

	if( $sent['to'] == $to && $sent['subj'] == $subject )
	{
		return $val;
	}

	$val = mail( $to, $subject, $message, $additional_headers, $additional_parameters );
	$sent['to'] = $to;
	$sent['subj'] = $subject;

	return $val;
}
?>
