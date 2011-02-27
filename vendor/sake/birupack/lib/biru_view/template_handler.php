<?
namespace biru_view;

class template_handler
{
    public $view;

    public function line_offset()
    {
        return 0;
    }

    public function __construct( &$view )
    {
        $this->view = $view;
    }

    public function render( &$template )
    {
        return $this->view->execute( $template );
    }

    public function compile( &$template )
    {
        return;
    }

    public function cache_fragment( $block, $name = array(), $options = null )
    {
        return;
    }
}
?>
