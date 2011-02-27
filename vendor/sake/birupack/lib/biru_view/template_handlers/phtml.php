<?
namespace biru_view\template_handlers;

class phtml extends \biru_view\template_handlers\compilable_template_handler // implements \BiruView\TemplateHandlers\compilable
{
    public function compile( $template )
    {
        $n = new phtml( $template, null, $this->view->phtml_trim_mode );
        return $n->src;
    }

    public function cache_fragment( $block, $name = array(), $options = null )
    {
        $this->view->fragment_for( $block, $name, $options );
        // eval( BiruView\Base::erb_variable, $block->binding );
    }   
}
?>
