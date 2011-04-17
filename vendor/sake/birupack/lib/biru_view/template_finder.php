<?php
namespace biru_view;

class template_finder
{
    static $processed_view_paths = array();
    static $file_extension_cache = array( array() );
    static $view_paths = array();

    public $_view_paths;
    public $template;

    static function process_view_paths( $view_paths )
    {
        foreach( $view_paths as $dir )
        {
            //if( !isset( self::$processed_views_paths[ $dir ] ) )
            if( isset( self::$processed_views_paths[ $dir ] ) )
                next;
            self::$processed_view_paths[ $dir ] = array();
            $files = glob("{$dir}/**/*/**");
            foreach( $files as $file )
            {
                if( !is_dir( $file ) )
                {
                    $paths = explode( '/', $file );
                    self::$processed_view_paths[ $dir ] = substr( $paths[ count( $paths ) ] - 1, 1 ); // remove preceding slash from last element

                    $extensions = explode( '.', $file );
                    $extension = $extensions[ count( $extensions ) - 1 ];
                    if( in_array( $extension, $this->template_handler_extensions() ) )
                    {
                        $k = explode( $dir, $file );
                        $key = preg_replace( '/^\//', '', preg_replace( '/\.(\w+)$/', '', $k[ count( $k ) - 1 ] ) );
                        self::$file_extension_cache[ $dir ][ $key ] = $extension;
                    }
                }
            }
        }
    }
    
    static function update_extension_cache_for( $extension )
    {
        foreach( self::$processed_view_paths as $dir )
        {
            $dirs = glob("{$dir}/**/*.{$extension}");
            foreach( $dirs as $file )
            {
                // key = file.split(dir).last.sub(/^\//, '').sub(/\.(\w+)$/, '')
                $key = $file;
                self::$file_extension_cache[ $dir ][ $key ] = $extension;
            }
        }
    }

    static function template_handler_extensions()
    {
        return \ActionView\template::template_handler_extensions();
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
        $this->_view_paths = $view_paths; // args.flatten
        //$this->_view_paths = ''; // @view_paths.respond_to?(:find) ? @view_paths.dup : [*@view_paths].compact
        //$this->check_view_paths( $this->_view_paths );
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
        $file_name = "{$template_path}.{$extension}";
        $base_path = $this->find_base_path_for( $file_name );
        return( !$base_path ? false : "{$base_path}/{$file_name}" );
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
    	# this is extremely silly and need rewritten badly
    	#print_r( self::$view_paths );
    	#print_r( $this->_view_paths );
        #if( file_exists( "app/views/{$template_file_name}" ) )
        #    return 'views';
        #return false;
        #foreach( self::$view_paths as $path )
        #print_r( self::$processed_view_paths );
        foreach( $this->_view_paths as $path )
        {
        	return $path;
            #if( preg_match( '/'.self::$processed_view_paths[ $path ].'/', $template_file_name ) )
            #    return self::$processed_view_paths[ $path ];
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
        $extension1 = $this->find_template_extension_from_handler( $template_path, $this->template->template_format );
        $extension2 = $this->find_template_extension_from_handler( $template_path, 'html' );
        if( $extension1 or $this->find_template_extension_from_first_render() )
            return $extension1;
        else if( $this->template->template_format == 'js' && $extension2 )
        {
            $this->template->template_format = 'html';
            return $extension2;
        }
        throw new \biru_controller\sake_exception("could not find appropriate template extension");
    }

    public function find_template_extension_from_handler( $template_path, $template_format = null )
    {
        $template_format = ( $template_format == null ) ? $this->template->template_format : $template_format;
        $formatted_template_path = "{$template_path}.{$template_format}";

        foreach( self::$view_paths as $path )
        {
            $extensions1 = self::$file_extension_cache[ $path ][ $formatted_template_path ];
            $extensions2 = self::$file_extension_cache[ $path ][ $template_path ];
            if( $extensions1 )
                return "{$template_format}.{$extensions1[0]}";
            elseif( $extensions2 )
                return (string)$extensions2[0];
        }
        //return null;
        return 'phtml';
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
        // File.basename(@template.first_render.to_s)[/^[^.]+\.(.+)$/, 1]
        return basename( $this->template->first_render );
    }

    private function check_view_paths( $view_paths )
    {
        foreach( $view_paths as $path )
        {
            if( !isset( self::$processed_view_paths[ $path ] ) )
                throw new \sake_exception( $path );
        }
    }
}
?>
