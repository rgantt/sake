<?php
namespace biru_view;

class template
{
    public $locals;
    public $handler;
    public $path;
    public $source;
    public $extension;
    public $filename;
    public $path_without_extension;
    public $method;
    public $method_key;
    
    static $template_handlers = array();
    static $default_template_handlers = null;

    public function __construct( $view, $path_or_source, $use_full_path, $locals = array(), $inline = false, $inline_type = null )
    {
        $this->view = &$view;
        $this->finder = $this->view->finder;

        if( !$inline )
        {
            $this->path = $use_full_path ? ( $path_or_source{0} == '/' ? substr( $path_or_source, 1 ) : $path_or_source ) : $path_or_source;
            $this->view->first_render = $this->view->first_render ? $this->view->first_render : $this->path;
            $this->source = null;
            $this->set_extension_and_file_name( $use_full_path );
        }
        else
        {
            $this->source = "echo <<<END\n".addslashes( $path_or_source )."\r\nEND;";
            $this->extension = $inline_type;
        }
        $this->method = $view->controller->action_name;
        $this->locals = is_array( $locals ) ? $locals : array();
        $name = self::handler_class_for_extension( $this->extension );
        $this->handler = new $name( $this->view );
    }

    public function render()
    {
        $this->prepare();
        return $this->handler->render( $this );
    }

    public function source()
    {
    	$f = addslashes($this->filename);
        $src = "include \"{$f}\";\n";
        $this->source = $this->source ? $this->source : $src;
        return $this->source;
    }

    public function method_key()
    {
        $this->method_key = $this->method_key ? $this->method_key : ( $this->filename ? $this->remove_special( $this->filename ) : $this->method );
        return $this->method_key;
    }
    
    private function remove_special( $string )
    {
    	return str_replace( array( '.', '/', '\\', ':' ), '', $string );
    }

    public function base_path_for_extension()
    {
        $temp = $this->finder->find_base_path_for("{$this->path_without_extension}.{$this->extension}");
        return ( $temp ? $temp : $this->finder->view_paths[0] );
    }

    public function prepare()
    {
        $this->view->evaluate_assigns();
        $this->view->current_render_extension = $this->extension;

        if( $this->handler instanceof \biru_view\template_handlers\compilable_template_handler )
        {
            $this->handler->compile_template( $this );
            $this->method = $this->view->method_names[ $this->method_key() ];
        }
    }

    private function set_extension_and_file_name( $use_full_path )
    {
        list( $this->path_without_extension, $this->extension ) = $this->finder->path_and_extension( $this->path );
        if( $use_full_path )
        {
            if( $this->extension )
            {
                $this->filename = $this->finder->pick_template( $this->path_without_extension, $this->extension );
            }
            else
            {
                $this->extension = $this->finder->pick_template_extension( $this->path );
                if( !$this->extension )
                    throw new \sake_exception("no template found for {$this->path} in {$this->finder->_view_paths}");
                $this->filename = $this->finder->pick_template( $this->path, $this->extension );
                $this->extension = preg_replace( '/^.+\./', '', $this->extension );
            }
        }
        else
            $this->filename = $this->path;
            
        if( empty( $this->filename ) )
            throw new \biru_controller\sake_exception("couldn't find template file for {$this->path}");
    }

    static function register_template_handler( $extension, $klass )
    {
        self::$template_handlers[ $extension ] = $klass;
        \biru_view\template_finder::update_extension_cache_for( $extension );
    }

    static function template_handler_extensions()
    {
    	$keys = array_keys( self::$template_handlers );
    	asort( $keys );
        return $keys;
    }

    static function register_default_template_handler( $extension, $klass )
    {
        self::register_template_handler( $extension, $klass );
        self::$default_template_handlers = $klass;
    }

    static function handler_class_for_extension( $extension )
    {
        return ( $extension && isset( self::$template_handlers[ $extension ] ) ) ? self::$template_handlers[ $extension ] : self::$default_template_handlers;
    }

    private function render_partial_collection_with_known_partial_path( $collection, $partial_path, $local_assigns )
    {
        $template = new partial_template( $this, $partial_path, null, $local_assigns );
        $n_col = array();
        foreach( $collection as $element )
            $n_col[] = $template->render_member( $element );
        return $n_col;
    }

    private function render_partial_collection_with_unknown_partial_path( $collection, $local_assigns )
    {
        return $this->render_partial_collection_with_known_partial_path( $collection, 'app/default', $local_assigns );
    }
}

template::register_default_template_handler( 'phtml', '\biru_view\template_handlers\phtml' );
template::register_template_handler( 'js', '\biru_view\template_handlers\js' );
template::register_template_handler( 'html', '\biru_view\template_handlers\phtml' );
template::register_template_handler( 'xml', '\biru_view\template_handlers\phtml' );
?>
