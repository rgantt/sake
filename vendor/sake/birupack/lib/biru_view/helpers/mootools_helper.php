<?
// <%= link_to_remote( "click here", :update => "time_div", :url =>{ :action => :say_when }) %>

function link_to_remote( $link_text, $div_id, $options )
{
    $path = url_for( $options );
    $html_options = array( 'onclick' => "$('{$div_id}').load('{$path}')" );
    return link_to( $link_text, "javascript:void(0);", $html_options );
}

function form_remote_tag( $options )
{
	if( !isset( $options['url'] ) || !is_array( $options['url'] ) )
		throw new sake_exception("form_remote_tag requires array for 'url' parameter");
	$url = url_for( $options['url'] );
	$update = isset( $options['update'] ) ? ",update: $('{$options['update']}')" : null;
	$append = isset( $options['append'] ) ? ",append: $('{$options['append']}')" : null;
	$ajax_string = "new Request.HTML({url:'{$url}'{$append}{$update}}).post($('ajax_form'))";
	return "<form id=\"ajax_form\" action=\"{$url}\" method=\"post\" onsubmit=\"{$ajax_string}; \$('ajax_form').getParent().empty(); return false;\">";	
}
?>
