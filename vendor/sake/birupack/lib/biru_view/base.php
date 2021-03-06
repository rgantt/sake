<?php
namespace biru_view;

class object_wrapper extends \stdClass{}

class base
{
    public $finder;
    public $base_path, $assigns, $template_extension, $first_render;
    public $controller;

    public $logger, $reponse, $headers;
    public $_cookies, $_flash, $_headers, $_params, $_request, $_response, $_session;

    public $template_format;
    public $current_render_extension;

    public $__compiled_templates = array();
    public $_content_for = array();

    static $phtml_trim_mode = '-';
    static $cache_template_loading = false;
    static $cache_template_extensions = true;
    static $debug_rjs = false;
    static $pthml_variable = '_phtmlout';

    public $method_names = array();
    static $template_args = array();
    static $computed_public_paths = array();

    static function load_helpers()
    {
    	echo "trying to load helpers!\n";
        $d = dir(dirname(__FILE__)."/helpers");
        while( false !== ( $file = $d->read() ) )
        {
            $matches = array();
            if( preg_match_all( '/^([a-z][a-z_]*_helper).php$/', $file, $matches ) )
            {
                require "biru_view/helpers/{$matches[0]}.php";
                $helper_module_name = ucfirst($matches[0]);
                // if Helpers.const_defined?( helper_module_name )
                //   include Helpers.const_get( helper_module_name )
            }
        }
    }

    public function __construct( $view_paths = array(), $assigns_for_first_render = array(), $controller = null )
    {
        $this->assigns = $assigns_for_first_render;
        $this->assigns_added = false;
        $this->controller = &$controller;
        $this->logger = ( $controller instanceof base ? $controller->logger : null );
        $this->finder = new template_finder( $this, $view_paths );
    }

    public function render_file( $template_path, $use_full_path = true, $local_assigns = array() )
    {
        $template = new template( $this, $template_path, $use_full_path, $local_assigns );
        try
        {
            return $this->render_template( $template );
        }
        catch( sake_exception $e )
        {
            throw $e;        
        }
    }

    public function render( $options = array(), $local_assigns = array(), $block )
    {
        if( is_string( $options ) )
            return $this->render_file( $options, true, $local_assigns );
        else if( $options == 'update' )
            return $this->update_page( $block );
        else if( is_array( $options ) )
        {
            $options = array_merge( $options, array( 'use_full_path' => true ) );
            $partial_layout = $options['layout'];
            unset( $options['layout'] );
            if( $partial_layout )
                return $this->wrap_content_for_layout( $this->render( array_merge( $options, array( 'partial' => $partial_layout ) ) ) );
            else if( $options['file'] )
                return $this->render_file( $options['file'], $options['use_full_path'], $options['locals'] );
            else if( $options['partial'] && $options['collection'] )
                return $this->render_partial_collection( $options['partial'], $options['collection'], $options['spacer_template'], $options['locals'] );
            else if( $options['partial'] )
                return $this->render_partial( $options['partial'], $options['object'], $options['locals'] );
            else if( $options['inline'] )
                return $this->render_template( new template( $this, $options['inline'], false, $options['locals'], true, $options['type'] ) );
        }
    }

    public function render_partial( $partial_path, $object_assigns = null, $local_assigns = array() )
    { 
        if( is_string( $partial_path ) || $partial_path == null )
        {
            $m = new \biru_view\partial_template( $this, $partial_path, $object_assigns, $local_assigns );
            return $m->render();
        }
        else if( is_array( $partial_path ) )
        {
            $collection = $partial_path;
            return $this->render_partial_collection( null, $collection, null, $local_assigns );
        }
        else
        {
        	$cls = get_class( $this->controller );
            return $this->render_partial( "{$cls::$controller_path}/{$this->controller->action_name}", $partial_path, $local_assigns );
        }
    }

    public function render_template( &$template )
    {
        return $template->render();
    }

    public function file_public( $template_path )
    {
        $parts = explode( '/', $template_path );
        return ( $parts[ count( $parts ) - 1 ]{0} == '_' );
    }

    public function template_format()
    {
        if( !is_null( $this->template_format ) )
            return $this->template_format;

        if( isset( $this->controller ) && isset( $this->controller->request ) )
        {
            $parameter_format = isset( $this->controller->request->parameters['format'] ) ? $this->controller->request->parameters['format'] : '';
            $accept_format = $this->controller->request->accepts[0];

            if( empty( $parameter_format ) && $accept_format != 'js' )
                $this->template_format = 'html';
            else if( empty( $parameter_format ) && $accept_format == 'js' )
                $this->template_format = 'js';
            else
                $this->template_format = $parameter_format;
        }
        else
            $this->template_format = 'html';
    }

    private function wrap_content_for_layout( $content )
    {
        $original_content_for_layout = $this->content_for_layout;
        $this->content_for_layout = $content;
        // returning(yield) { @content_for_layout = original_content_for_layout }
    }

    public function evaluate_assigns()
    {
        if( !$this->assigns_added )
        {
            $this->assign_variables_from_controller();
            $this->assigns_added = true;
        }
    }

    private function assign_variables_from_controller()
    {
        foreach( $this->assigns as $key => &$value )
            $this->{$key} = &$value;
    }

    public function execute( &$template )
    {
        $this->_template = &$template;
        $method = $template->method;
        $locals = array_merge( $template->locals, $this->controller->properties() );
        return $this->$method( $locals );
    } 

    public function __call( $name, $args )
    {
        set_error_handler( function ( $level, $text, $file, $line ){ return; } ); 
        if( !function_exists( $name ) )
            eval( $this->_template->__compiled_templates[ $name ] );
        if( !function_exists( $name ) )
        	throw new \biru_controller\sake_exception("Could not create dynamic method {$name}!");
        ob_start();
        echo $name( $args );
        return ob_get_clean();
    }
}
?>
