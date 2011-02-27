<?
require_once dirname(__FILE__).'/tag_helper.php';

/**
 * Provides functionality for working with JavaScript in your views.
 * 
 * == Ajax, controls and visual effects
 * 
 * * For information on using Ajax, see 
 *   ActionView::Helpers::PrototypeHelper.
 * * For information on using controls and visual effects, see
 *   ActionView::Helpers::ScriptaculousHelper.
 *
 * == Including the JavaScript libraries into your pages
 *
 * Rails includes the Prototype JavaScript framework and the Scriptaculous
 * JavaScript controls and visual effects library.  If you wish to use
 * these libraries and their helpers (ActionView::Helpers::PrototypeHelper
 * and ActionView::Helpers::ScriptaculousHelper), you must do one of the
 * following:
 *
 * * Use <tt><%= javascript_include_tag :defaults %></tt> in the HEAD 
 *   section of your page (recommended): This function will return 
 *   references to the JavaScript files created by the +rails+ command in
 *   your <tt>public/javascripts</tt> directory. Using it is recommended as
 *   the browser can then cache the libraries instead of fetching all the 
 *   functions anew on every request.
 * * Use <tt><%= javascript_include_tag 'prototype' %></tt>: As above, but 
 *   will only include the Prototype core library, which means you are able
 *   to use all basic AJAX functionality. For the Scriptaculous-based 
 *   JavaScript helpers, like visual effects, autocompletion, drag and drop 
 *   and so on, you should use the method described above.
 * * Use <tt><%= define_javascript_functions %></tt>: this will copy all the
 *   JavaScript support functions within a single script block. Not
 *   recommended.
 *
 * For documentation on +javascript_include_tag+ see 
 * ActionView::Helpers::AssetTagHelper.
 */
if( !defined( 'JAVASCRIPT_PATH' ) )
{
	define( 'JAVASCRIPT_PATH', dirname(__FILE__).'/javascripts' );
	//define( 'JAVASCRIPT_PATH', SAKE_ROOT.'/public/javascripts' );
}

/**
 * Returns a link that'll trigger a JavaScript +function+ using the 
 * onclick handler and return false after the fact.
 *
 * Examples:
 *   link_to_function "Greeting", "alert('Hello world!')"
 *   link_to_function(image_tag("delete"), "if confirm('Really?'){ do_delete(); }")
 */
function link_to_function( $name, $function, $html_options = array() )
{
	content_tag( 'a', $name, array_merge( $html_options, array( 'href' => ( $html_options['href'] || '#' ), 'onclick' => ( $html_options['onclick'] ? $html_options['onclick'].'; ' : '').$function.'; return false;' ) ) );
}

/**
 * Returns a link that'll trigger a JavaScript +function+ using the 
 * onclick handler.
 *
 * Examples:
 *   button_to_function "Greeting", "alert('Hello world!')"
 *   button_to_function "Delete", "if confirm('Really?'){ do_delete(); }")
 */
function button_to_function( $name, $function, $html_options = array() )
{
	tag( 'input', array_merge( $html_options, array( 'type' => 'button', 'value' => 'name', 'onclick' => ( $html_options['onclick'] ? $html_options['onclick'].'; ' : '').$function.';' ) ) );
}

/**
 * Includes the Action Pack JavaScript libraries inside a single <script> 
 * tag. The function first includes prototype.js and then its core extensions,
 * (determined by filenames starting with "prototype").
 * Afterwards, any additional scripts will be included in undefined order.
 *
 * Note: The recommended approach is to copy the contents of
 * lib/action_view/helpers/javascripts/ into your application's
 * public/javascripts/ directory, and use +javascript_include_tag+ to 
 * create remote <script> links.
 */
function define_javascript_functions()
{
	$javascript = '<script type="text/javascript">';
	
	// load prototype.js and its extensions first 
	$prototype_libs = glob( JAVASCRIPT_PATH.'/prototype*' );
	foreach( $prototype_libs as $filename )
	{
		$javascript .= "\n".file_get_contents( $filename );
	}
	
	// load other librairies
	$other_libs = glob( SAKE_ROOT.'/public/javascripts/*' );
	//echo 'Splicing: <pre>'; print_r( $other_libs ); echo '</pre>';
	foreach( $other_libs as $filename )
	{
		if( !in_array( $filename, $prototype_libs ) )
		{
			$javascript .= "\n".file_get_contents( $filename );
		}
	}
	$javascript .= '</script>';
	return $javascript;
}

/**
 * Escape carrier returns and single and double quotes for JavaScript segments.
 */
function escape_javascript( $javascript )
{
	return preg_replace( '/\r\n|\n|\r/', '\\n', $javascript ); // missing one thing (second gsub below)
	// WTF (javascript || '').gsub(/\r\n|\n|\r/, "\\n").gsub(/["']/) { |m| "\\#{m}" }
}

/**
 * Returns a JavaScript tag with the +content+ inside. Example:
 * javascript_tag "alert('All is good')" # => <script type="text/javascript">alert('All is good')</script>
 */
function javascript_tag( $content )
{
	return content_tag( 'script', javascript_cdata_section( $content ), array( 'type' => 'text/javascript' ) );
}

function javascript_cdata_section( $content )
{
	return "\n//".cdata_section("\n".$content."\n//")."\n";
}
