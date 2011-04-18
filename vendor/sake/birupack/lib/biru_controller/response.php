<?php
namespace biru_controller;

class abstract_response
{
    static $DEFAULT_HEADERS = array ( 'Cache-Control' => 'no-cache' );
    public $request, $body, $headers, $session, $cookies, $assigns, $template, $redirected_to, $redirect_to_method_params, $layout, $content_type, $charset;

    public function __construct()
    {
        $this->body = '';
        $this->headers = array_merge( self::$DEFAULT_HEADERS, array( 'cookie' => '' ) );
        $this->assigns = array();
    }

    public function content_type( $mime_type = null )
    {
    	if( !is_null( $mime_type ) )
    		$this->headers['Content-Type'] = $this->charset() ? "{$mime_type}; charset={$this->charset()}" : $mime_type;
    	if( !isset( $this->headers['Content-Type'] ) )
    		return null;
        $content_type = explode( ';', $this->headers['Content-Type'] );
        return !empty( $content_type[0] ) ? $content_type[0] : null;
    }

    public function charset( $encoding = null )
    {
    	if( !is_null( $encoding ) )
    	{
    		$ct = $this->content_type() ? $this->content_type() : \Mime\type('HTML');
    		$this->headers['Content-Type'] = "{$ct}; charset={$encoding}";
    	}
    	if( !isset( $this->headers['Content-Type'] ) )
    		return null;
        $content_type = explode( ';', $this->headers['Content-Type'] );
        $charset = explode( '=', ( !empty( $content_type[1] ) ? $content_type[1] : '' ) );
        return !empty( $charset[1] ) ? $charset[1] : null;
    }

    public function redirect( $to_url, $response_status )
    {
        $this->headers['Status'] = $response_status;
        $this->headers['Location'] = $to_url;
        $this->body = "<html><body>You are being <a href=\"{$to_url}\">redirected</a>.</body></html>";
        header("Location: {$this->headers['Location']}");
    }

    public function prepare()
    {
        $this->handle_conditional_get();
        $this->convert_content_type();
        $this->set_content_length();
    }

    private function handle_conditional_get()
    {
        if( is_string( $this->body ) && ( $this->headers['Status'] ? $this->headers['Status'] == '200' : true ) && !$this->body == '' )
        {
            if( !$this->headers['ETag'] )
                $this->headers['ETag'] = md5( $this->body );
            if( $this->headers['Cache-Control'] == self::$DEFAULT_HEADERS['Cache-Control'] )
                $this->headers['Cache-Control'] = 'private, max-age=0, must-revalidate';
            if( $this->request->headers['HTTP_IF_NONE_MATCH'] == $this->headers['ETag'] )
            {
                $this->headers['Status'] = '304 Not Modified';
                $this->body = '';
            }
        }
    }

    private function convert_content_type()
    {
    	$content_type = false;
        $content_type = isset( $this->headers['Content-Type'] ) ? $this->headers['Content-Type'] : false;
        $content_type = $content_type ? $content_type : ( isset( $this->headers['Content-type'] ) ? $this->headers['Content-type'] : false );
        $content_type = $content_type ? $content_type : ( isset( $this->headers['content-type'] ) ? $this->headers['content-type'] : null );
        $this->headers['type'] = $content_type;
    }

    private function set_content_length()
    {
        $this->headers['Content-Length'] = strlen( $this->body );
    }
}
?>
