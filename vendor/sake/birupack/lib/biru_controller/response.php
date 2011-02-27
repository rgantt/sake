<?php
namespace biru_controller;

class abstract_response
{
    static $DEFAULT_HEADERS = array ( 'Cache-Control' => 'no-cache' );

    public $request;
    public $body;
    public $headers;
    public $session;
    public $cookies;
    public $assigns;
    public $template;
    public $redirected_to;
    public $redirect_to_method_params;
    public $layout;
    public $content_type;
    public $charset;

    public function __construct()
    {
        $this->body = '';
        $this->headers = array_merge( self::$DEFAULT_HEADERS, array( 'cookie' => '' ) );
        $this->assigns = array();
    }

    public function content_type()
    {
        $content_type = explode( ';', $this->headers['Content-Type'] );
        return $content_type[0];
    }

    public function charset()
    {
        $content_type = explode( ';', $this->headers['Content-Type'] );
        return $content_type[1];
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
