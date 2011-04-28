<?php
namespace biru_view;

class template_finder
{
	public $_view_paths;
    public $template;
    
    static $processed_view_paths = array();
    static $file_extension_cache = array();
    
    static $view_paths = array();

    static function process_view_paths( $view_paths )
    {
        foreach( $view_paths as $dir )
        {
            if( isset( self::$processed_view_paths[ $dir ] ) )
                continue;
            self::$processed_view_paths[ $dir ] = array();
            $f1 = glob("{$dir}/*");
            $f2 = glob("{$dir}/*/*.*");
            $f3 = glob("{$dir}/*.*");
            $files = array_unique( array_merge( $f1, $f2, $f3 ) );
            foreach( $files as $file )
            {
                if( !is_dir( $file ) )
                {
                    $rel_path = explode( $dir, $file );
                    $rel_path = end( $rel_path );
                    $rel_path = preg_replace( '/^\//', '', $rel_path );
                    self::$processed_view_paths[ $dir ][] = $rel_path;
                    $extensions = explode( '.', $file );
                    $extension = end( $extensions );
                    if( in_array( $extension, self::template_handler_extensions() ) )
                    {
                        $k = explode( $dir, $file );
                        $key = preg_replace( '/^\//', '', preg_replace( '/\.(\w+)$/', '', end( $k ) ) );
                        self::$file_extension_cache[ $dir ][ $key ][] = $extension;
                    }
                }
            }
        }
    }
    
    static function update_extension_cache_for( $extension )
    {
    	foreach( self::$processed_view_paths as $dir )
        {
        	$f1 = glob("{$dir}/*");
            $f2 = glob("{$dir}/*/*.*");
            $f3 = glob("{$dir}/*.*");
            $files = array_unique( array_merge( $f1, $f2, $f3 ) );
            foreach( $files as $file )
            {
            	if( is_dir( $file ) )
            		continue;
                $k = explode( $dir, $file );
                $key = preg_replace( '/^\//', '', preg_replace( '/\.(\w+)$/', '', end( $k ) ) );
                self::$file_extension_cache[ $dir ][ $key ][] = $extension;
            }
        }
    }

    static function template_handler_extensions()
    {
        return \biru_view\template::template_handler_extensions();
    }

    static function reload()
    {
        $view_paths = array_keys( self::$processed_view_paths );
        self::$processed_view_paths = array();
        self::$file_extension_cache = array( array() );
        self::process_view_paths( $view_paths );
    }

    public function __construct( &$template, $view_paths )
    {
        $this->template = $template;
        $this->_view_paths = $view_paths;
        $this->check_view_paths( $this->_view_paths );
    }

    public function prepend_view_path( $path )
    {
        array_unshift( $this->_view_paths, $path );
        self::process_view_paths( $this->_view_paths );
    }

    public function append_view_path( $path )
    {
        $this->_view_paths[] = $path;
        self::process_view_paths( $this->_view_paths );
    }

    public function view_paths( $path )
    {
        $this->_view_paths = $path;
        self::process_view_paths( $path );
    }

    public function pick_template( $template_path, $extension )
    {
    	$extension = substr( $extension, 0, 1 ) == '.' ? $extension : ".{$extension}";
        $file_name = "{$template_path}{$extension}";
        $base_path = $this->find_base_path_for( $file_name );
        return( empty( $base_path ) ? false : "{$base_path}/{$file_name}" );
    }

    public function template_exists( $template_path, $extension )
    {
        return $this->pick_template( $template_path, $extension );
    }

    public function file_exists( $template_path )
    {
        $template_path = preg_replace( '/^\//', '', $template_path );
        list( $template_file_name, $template_file_extension ) = $this->path_and_extension( $template_path );

        if( $template_file_extension )
            return $this->template_exists( $template_file_name, $template_file_extension );
        else
            return $this->template_exists( $template_file_name, $this->pick_template_extension( $template_path ) );
    }

    public function find_base_path_for( $template_file_name )
    {
    	foreach( $this->_view_paths as $path )
    	{
    		if( in_array( $template_file_name, self::$processed_view_paths[ $path ] ) )
    			return $path;
    	}
    	return false;
    }

    public function extract_base_path_from( $full_path )
    {
        foreach( $this->_view_paths as $p )
        {
            if( substr( $full_path, 0, strlen( $p ) - 1 ) == $p )
                return $p;
        }
        return '';
    }

    public function pick_template_extension( $template_path )
    {
        $extension1 = $this->find_template_extension_from_handler( $template_path, $this->template->template_format() );
        $extension2 = $this->find_template_extension_from_handler( $template_path, 'html' );
        if( $extension1 or $this->find_template_extension_from_first_render() )
        {
            return $extension1;
        }
        else if( $this->template->template_format() == 'js' && $extension2 )
        {
            $this->template->template_format = 'html';
            return $extension2;
        }
        throw new \biru_controller\sake_exception("could not find appropriate template extension");
    }

    public function find_template_extension_from_handler( $template_path, $template_format = null )
    {
        $template_format = ( $template_format === null ) ? $this->template->template_format() : $template_format;
        $formatted_template_path = "{$template_path}.{$template_format}";
        foreach( $this->_view_paths as $path )
        {
            $extensions1 = isset( self::$file_extension_cache[ $path ][ $formatted_template_path ] ) ?self::$file_extension_cache[ $path ][ $formatted_template_path ] : null;
            $extensions2 = isset( self::$file_extension_cache[ $path ][ $template_path ] ) ? self::$file_extension_cache[ $path ][ $template_path ] : null;
            if( !empty( $extensions1 ) )
            {
                return "{$template_format}.{$extensions1[0]}";
            }
            elseif( !empty( $extensions2 ) )
            {
                return "{$extensions2[0]}";
            }
        }
        //return null;
        return "phtml";
    }

    public function path_and_extension( $template_path )
    {
        $matches = array();
        preg_match( '/\.(\w+)$/', $template_path, $matches );
        $template_path_without_extension = preg_replace( '/\.(\w+)$/', '', $template_path );
        $match = isset( $matches[0] ) ? $matches[0] : false;
        return array( $template_path_without_extension, $match );
    }

    public function find_template_extension_from_first_render()
    {
    	$matches = array();
    	preg_match( '/^[^.]+\.(.+)$/', (string)$this->template->first_render, $matches );
        return isset( $matches[1] ) ? $matches[1] : null;
    }

    private function check_view_paths( $view_paths )
    {
        foreach( $view_paths as $path )
        {
            if( !isset( self::$processed_view_paths[ $path ] ) )
                throw new \biru_controller\sake_exception( $path );
        }
    }
}