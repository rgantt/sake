<?
function render( $options = array(), $locals = array(), $block = null )
{
    global $controller;
    return $controller->template->render( $options, $locals, $block );
}
?>
