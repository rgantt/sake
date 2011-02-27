<?
require_once dirname(__FILE__).'/javascript_helper.php';

/**
 * Returns the URL for the set of +options+ provided. This takes the same options 
 * as url_for. For a list, see the documentation for ActionController::Base#url_for.
 * Note that it'll set :only_path => true so you'll get /controller/action instead of the 
 * http://example.com/controller/action part (makes it harder to parse httpd log files)
 * 
 * When called from a view, url_for returns an HTML escaped url. If you need an unescaped
 * url, pass :escape => false to url_for.
 */ 
function url_for( $options = array() )
{
	global $controller;
	$parameters_for_method_reference = func_get_args();
	if ( is_array( $options ) )
	{
		$options['only_path'] = true;
		$escape = ( isset( $options['escape'] ) ? $options['escape'] : true );
		unset( $options['escape'] );
	}
	else
	{
		$escape = true;
	}
	$url = $controller->url_for( $options, $parameters_for_method_reference );
	return ( $escape ? htmlentities( $url ) : $url );
}

/**
 *
 * Creates a link tag of the given +name+ using an URL created by the set of +options+. See the valid options in
 * the documentation for ActionController::Base#url_for. It's also possible to pass a string instead of an options hash to
 * get a link tag that just points without consideration. If nil is passed as a name, the link itself will become the name.
 *
 * The html_options has three special features. One for creating javascript confirm alerts where if you pass :confirm => 'Are you sure?',
 * the link will be guarded with a JS popup asking that question. If the user accepts, the link is processed, otherwise not.
 *
 * Another for creating a popup window, which is done by either passing :popup with true or the options of the window in 
 * Javascript form.
 *
 * And a third for making the link do a POST request (instead of the regular GET) through a dynamically added form element that
 * is instantly submitted. Note that if the user has turned off Javascript, the request will fall back on the GET. So its
 * your responsibility to determine what the action should be once it arrives at the controller. The POST form is turned on by
 * passing :post as true. Note, it's not possible to use POST requests and popup targets at the same time (an exception will be thrown).
 *
 * Examples:
 *   link_to "Delete this page", { :action => "destroy", :id => @page.id }, :confirm => "Are you sure?"
 *   link_to "Help", { :action => "help" }, :popup => true
 *   link_to "Busy loop", { :action => "busy" }, :popup => ['new_window', 'height=300,width=600']
 *   link_to "Destroy account", { :action => "destroy" }, :confirm => "Are you sure?", :post => true
 */
function link_to( $name, $options = array(), $html_options = false )
{
	$arg = func_get_args();
	if ( $html_options )
	{
		//convert_options_to_javascript( $html_options );
		$tag_options = _tag_options( $html_options );
	}
	else
	{
		$tag_options = null;
	}
	$url = is_string( $options ) ? $options : url_for( $options, $arg );
	return "<a href=\"$url\"$tag_options>".( $name ? $name : $url )."</a>";
}

/**
 * Generates a form containing a sole button that submits to the
 * URL given by _options_.  Use this method instead of +link_to+
 * for actions that do not have the safe HTTP GET semantics
 * implied by using a hypertext link.
 *
 * The parameters are the same as for +link_to+.  Any _html_options_
 * that you pass will be applied to the inner +input+ element.
 * In particular, pass
 * 
 *   :disabled => true/false
 *
 * as part of _html_options_ to control whether the button is
 * disabled.  The generated form element is given the class
 * 'button-to', to which you can attach CSS styles for display
 * purposes.
 *
 * Example 1:
 *
 *   # inside of controller for "feeds"
 *   button_to "Edit", :action => 'edit', :id => 3
 *
 * Generates the following HTML (sans formatting):
 *
 *   <form method="post" action="/feeds/edit/3" class="button-to">
 *     <div><input value="Edit" type="submit" /></div>
 *   </form>
 *
 * Example 2:
 *
 *   button_to "Destroy", { :action => 'destroy', :id => 3 },
 *             :confirm => "Are you sure?"
 *
 * Generates the following HTML (sans formatting):
 *
 *   <form method="post" action="/feeds/destroy/3" class="button-to">
 *     <div><input onclick="return confirm('Are you sure?');"
 *                 value="Destroy" type="submit" />
 *     </div>
 *   </form>
 *
 * *NOTE*: This method generates HTML code that represents a form.
 * Forms are "block" content, which means that you should not try to
 * insert them into your HTML where only inline content is expected.
 * For example, you can legally insert a form inside of a +div+ or
 * +td+ element or in between +p+ elements, but not in the middle of
 * a run of text, nor can you place a form within another form.
 * (Bottom line: Always validate your HTML before going public.)
 */
function button_to( $name, $options = array(), $html_options = false )
{
	$html_options = ( $html_options ? $html_options : array() );
	convert_boolean_attributes( $html_options, 'disabled' );
	
	if ( $confirm = $html_options['confirm'] )
	{
		unset( $html_options['confirm'] );
		$html_options['onclick'] = "return ".confirm_javascript_function( $confirm ).";";
	}
	
	$url = is_string( $options ) ? $options : url_for( $options );
	$name = ( $name ? $name : $url );
	
	$html_options['type'] = 'submit';
	$html_options['value'] = $name;
	
	return "<form method=\"post\" action=\"".$url."\" class=\"button-to\"><div>".tag( 'input', $html_options )."</div></form>";
}

/**
 * Creates a link tag of the given +name+ using an URL created by the set of +options+, unless the current
 * request uri is the same as the link's, in which case only the name is returned (or the
 * given block is yielded, if one exists). This is useful for creating link bars where you don't want to link
 * to the page currently being viewed.
 */
function link_to_unless_current( $name, $options = array(), $html_options = array() )
{
	return link_to_unless( is_current_page( $options ), $name, $options, $html_options, func_get_args() );
}

/**
 * Create a link tag of the given +name+ using an URL created by the set of +options+, unless +condition+
 * is true, in which case only the name is returned (or the given block is yielded, if one exists).
 */ 
function link_to_unless( $condition, $name, $options = array(), $html_options = array() )
{
	if ( $condition )
		return $name;
	else
		return link_to( $name, $options, $html_options, func_get_args() );
}

/**
 * Create a link tag of the given +name+ using an URL created by the set of +options+, if +condition+
 * is true, in which case only the name is returned (or the given block is yielded, if one exists).
 */ 
function link_to_if( $condition, $name, $options = array(), $html_options = array() )
{
	return link_to_unless( !$condition, $name, $options, $html_options, func_get_args() );
}

/**
 * Returns true if the current page uri is generated by the options passed (in url_for format).
 */
function is_current_page( $options )
{
	global $controller;
	return ( url_for( $options ) == $controller->request->request_uri );
}
