<?php
namespace biru_view;

class partial_template extends template
{
    public $variable_name;
    public $object;

    public function __construct( $view, $partial_path, $object = null, $locals = array() )
    {
        list( $this->path, $this->variable_name ) = $this->extract_partial_name_and_path( $view, $partial_path );
        parent::__construct( $view, $this->path, true, $locals );
        $this->add_object_to_local_assigns( $object );

        $this->initialize_counter();
        $this->prepare();
    }

    public function render()
    {
        return $this->handler->render( $this );
    }

    public function render_member( $object )
    {
        $this->locals[ $this->counter_name ] += 1;
        $this->locals['object'] = $this->locals[ $this->variable_name ] = $object;
        $render = $this->render();
        unset( $this->locals[ $this->variable_name ] );
        unset( $this->locals['object'] );
        return $render;
    }

    public function counter( $num )
    {
        $this->locals[ $this->counter_name ] = $num;
    }
    
    private function add_object_to_local_assigns( $object )
    {
        $tmp = null;
        if( $object instanceof object_wrapper )
            $tmp = $object->value;
        else
            $tmp = $object;
        $this->locals[ $this->variable_name ] = ( ( $tmp !== false ) ? $tmp : $this->view->controller->instance_variable_get( $this->variable_name ) );
        $this->locals['object'] = ( isset( $this->locals['object'] ) ? $this->locals['object'] : $this->locals[ $this->variable_name ] );
    }

    private function extract_partial_name_and_path( $view, $partial_path )
    {
        list( $path, $partial_name ) = $this->partial_pieces( $view, $partial_path );
        $old_partial_name = $partial_name;
        $partial_name = explode( '/', $partial_name );
        $partial_name = end( $partial_name );
        $partial_name = explode( '.', $partial_name );
        $partial_name = reset( $partial_name );
        $fullpath = empty( $path ) ? "_{$old_partial_name}" : "{$path}/_{$old_partial_name}";
        return array( $fullpath, $partial_name );
    }

    private function partial_pieces( $view, $partial_path )
    {
        if( strpos( $partial_path, '/' ) !== false )
            return array( dirname( $partial_path ), basename( $partial_path ) );
        else
        {
        	$cls = get_class( $view->controller );
            return array( $cls::$controller_path, $partial_path );
        }
    }

    private function initialize_counter()
    {
        $tmp = "{$this->variable_name}_counter";
        if( !isset( $this->{$tmp} ) )
        	$this->{$tmp} = 0;
        $this->counter_name = ( isset( $this->counter_name ) ? $this->counter_name : $this->{$tmp} );
        $this->locals[ $this->counter_name ] = 0;
    }
}