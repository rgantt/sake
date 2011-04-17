<?php
namespace biru_view\template_handlers;

abstract class compilable_template_handler extends \biru_view\template_handler
{
    static $compile_time;
    static $template_args;
    static $inline_template_count;
    static $line_offset = 0;

    public function render( &$template )
    {
        return $this->view->execute( $template );
    }
      
    public function compile_template( &$template )
    {
        if( !$this->can_compile_template( $template ) )
            return;

        $render_symbol = $this->assign_method_names( $template );
        $render_source = $this->create_template_source( $template, $render_symbol );
        $line_offset = count( self::$template_args[ $render_symbol ] ) + self::$line_offset;

        try
        {
            $file_name = $template->filename ? $template->filename : 'compiled-template';
            $template->__compiled_templates[ $render_symbol ] = $render_source;
            // INDISTINGUISHABLE FROM MAGIC
            // ActionView::Base::CompiledTemplates.module_eval(render_source, file_name, -line_offset)
        }
        catch( sake_exception $e )
        { 
            throw $e; 
        }
        self::$compile_time[ $render_symbol ] = time();
    }

    private function can_compile_template( &$template )
    {
        $method_key = $template->method_key();
        $render_symbol = isset( $this->view->method_names[ $method_key ] ) ? $this->view->method_names[ $method_key ] : null;
        $compile_time = self::$compile_time[ $render_symbol ];
        if( $compile_time && $this->supports_local_assigns( $render_symbol, $template->locals ) )
        {
            if( $template->filename && $this->view->cache_template_loading() )
                return $this->template_changed_since( $template->filename, $compile_time );
        }
        return true;
    }

    private function assign_method_names( &$template )
    {
        if( !isset( $this->view->method_names[ $template->method_key ] ) )
            $this->view->method_names[ $template->method_key ] = $this->compiled_method_name( $template );
        return $this->view->method_names[ $template->method_key ];
    }

    private function compiled_method_name( &$template )
    {
        $path = str_replace( '.phtml', '', str_replace( 'views', '', str_replace( '/', '_', str_replace( '_', '', $template->method_key ) ) ) );
        return "_sake{$path}";
    }

    private function compiled_method_name_file_path_segment( $file_name )
    {
        if( $file_name )
        {
            $s = realpath( $file_name );
            $s = preg_replace( '/^'.preg_quote( SAKE_ROOT, '/' ).'/', '', $s );
            return $s;
        }
        return self::$inline_template_count++;
    }

    private function create_template_source( &$template, $render_symbol )
    {
        //$body = $this->compile( $template->source() );
        $body = $template->source();
        if( !self::$template_args[ $render_symbol ] )
            self::$template_args[ $render_symbol ] = array();
        $locals_keys = self::$template_args[ $render_symbol ] != array() ? array_keys( self::$template_args[ $render_symbol ] ) : array_keys( $template->locals );

        $locals_code = '';
        foreach( $locals_keys as $key )
        {
            if( !empty( $key ) )
                $locals_code .= "\${$key} = \$local_assigns['{$key}'];\n";
        }
        $locals_code .= "extract( \$local_assigns[0] );\n";
        return "function {$render_symbol}( \$local_assigns ){\n{$locals_code}{$body}\n};";
    }

    private function supports_local_assigns( $render_symbol, $local_assigns )
    {
        $args = self::$template_args[ $render_symbol ];
        $subset = true;
        foreach( $local_assigns as $local )
        {
            $subset *= isset( $args[ $local ] );
        }
        return ( empty( $local_assigns ) || $subset );
    }

    public function template_changed_since( $file_name, $compile_time )
    {
        $lstat = lstat( $file_name );
        return $compile_time < $lstat;
    }
}
?>
