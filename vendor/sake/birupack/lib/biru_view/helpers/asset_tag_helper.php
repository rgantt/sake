<?
require_once dirname(__FILE__).'/url_helper.php';
require_once dirname(__FILE__).'/tag_helper.php';

# Provides methods for linking a HTML page together with other assets, such as javascripts, stylesheets, and feeds.
# Returns a link tag that browsers and news readers can use to auto-detect a RSS or ATOM feed for this page. The +type+ can
# either be <tt>:rss</tt> (default) or <tt>:atom</tt> and the +options+ follow the url_for style of declaring a link target.
#
# Examples:
#   auto_discovery_link_tag # =>
#     <link rel="alternate" type="application/rss+xml" title="RSS" href="http://www.curenthost.com/controller/action" />
#   auto_discovery_link_tag(:atom) # =>
#     <link rel="alternate" type="application/atom+xml" title="ATOM" href="http://www.curenthost.com/controller/action" />
#   auto_discovery_link_tag(:rss, {:action => "feed"}) # =>
#     <link rel="alternate" type="application/rss+xml" title="RSS" href="http://www.curenthost.com/controller/feed" />
#   auto_discovery_link_tag(:rss, {:action => "feed"}, {:title => "My RSS"}) # =>
#     <link rel="alternate" type="application/rss+xml" title="My RSS" href="http://www.curenthost.com/controller/feed" />
function auto_discovery_link_tag( $type = 'rss', $url_options = array(), $tag_options = array() )
{
	return tag(
	'link', array(
	'rel'   => ( isset( $tag_options['rel'] ) ? $tag_options['rel'] : 'alternate' ),
	'type'  => ( isset( $tag_options['type'] ) ? $tag_options['type'] : 'application/'.$type.'+xml' ),
	'title' => ( isset( $tag_options['title'] ) ? $tag_options['title'] : ucfirst( (string) $type ) ),
	'href'  => ( is_array( $url_options ) ? url_for( array_merge( $url_options, array( 'only_path' => false ) ) ) : $url_options )
	));
}

# Returns path to a javascript asset. Example:
#
#   javascript_path "xmlhr" # => /javascripts/xmlhr.js
function javascript_path( $source )
{
	return compute_public_path( $source, 'javascripts', 'js' );
}

$javascript_default_sources = array();

# Returns a script include tag per source given as argument. Examples:
#
#   javascript_include_tag "xmlhr" # =>
#     <script type="text/javascript" src="/javascripts/xmlhr.js"></script>
#
#   javascript_include_tag "common.javascript", "/elsewhere/cools" # =>
#     <script type="text/javascript" src="/javascripts/common.javascript"></script>
#     <script type="text/javascript" src="/elsewhere/cools.js"></script>
#
#   javascript_include_tag :defaults # =>
#     <script type="text/javascript" src="/javascripts/prototype.js"></script>
#     <script type="text/javascript" src="/javascripts/effects.js"></script>
#     ...
#     <script type="text/javascript" src="/javascripts/application.js"></script> *see below
#   
# If there's an <tt>application.js</tt> file in your <tt>public/javascripts</tt> directory,
# <tt>javascript_include_tag :defaults</tt> will automatically include it. This file
# facilitates the inclusion of small snippets of JavaScript code, along the lines of
# <tt>controllers/application.rb</tt> and <tt>helpers/application_helper.rb</tt>.
function javascript_include_tag( $sources )
{
	global $javascript_default_sources;
	
	$options = ( is_array( $sources[ count( $sources ) - 1 ] ) ? array_pop( $sources ) : array() );
	
	if ( in_array( 'defaults', $sources ) )
	{
		$sources = array_merge( array_merge( array_slice( $sources, 0, array_search( 'defaults', $sources ) ), $javascript_default_sources ),  array_slice( $sources, ( array_search( 'defaults', $sources ) + 1 ), count( $sources ) ) );
		
		unset( $sources['defaults'] );
		if ( defined( 'SAKE_ROOT' ) && file_exists( SAKE_ROOT.'/public/javascripts/application.js' ) )
			array_push( $sources, 'application' ); 
	}
	
	$n_sources = array();
	foreach( $sources as $source )
	{
		$source = javascript_path( $source );
		array_push( $n_sources, content_tag( 'script', '', array_merge( array( 'type' => 'text/javascript', 'src' => $source ), $options ) ) );
	}
	return join( "\n", $n_sources )."\n";
}

# Register one or more additional JavaScript files to be included when
#   
#   javascript_include_tag :defaults
#
# is called. This method is intended to be called only from plugin initialization
# to register extra .js files the plugin installed in <tt>public/javascripts</tt>.
function register_javascript_include_default( $sources )
{
	global $javascript_default_sources;
	$javascript_default_sources = array_merge( $javascript_default_sources, $sources );
}

function reset_javascript_include_default()
{
	global $javascript_default_sources;
	//@@javascript_default_sources = JAVASCRIPT_DEFAULT_SOURCES.dup
	$javascript_default_sources = JAVASCRIPT_DEFAULT_SOURCES;
}

# Returns path to a stylesheet asset. Example:
#
#   stylesheet_path "style" # => /stylesheets/style.css
function stylesheet_path( $source )
{
	return compute_public_path( $source, 'stylesheets', 'css' );
}

# Returns a css link tag per source given as argument. Examples:
#
#   stylesheet_link_tag "style" # =>
#     <link href="/stylesheets/style.css" media="screen" rel="Stylesheet" type="text/css" />
#
#   stylesheet_link_tag "style", :media => "all" # =>
#     <link href="/stylesheets/style.css" media="all" rel="Stylesheet" type="text/css" />
#
#   stylesheet_link_tag "random.styles", "/css/stylish" # =>
#     <link href="/stylesheets/random.styles" media="screen" rel="Stylesheet" type="text/css" />
#     <link href="/css/stylish.css" media="screen" rel="Stylesheet" type="text/css" />
function stylesheet_link_tag( $sources )
{
	$options = ( is_array( $sources[ count( $sources ) - 1 ] ) ? array_pop( $sources ) : array() );
	$n_sources = array();
	foreach( $sources as $source )
	{
		$source = stylesheet_path( $source );
		array_push( $n_sources, tag( 'link', array_merge( array( 'rel' => 'stylesheet', 'type' => 'text/css', 'media' => 'screen', 'href' => $source ), $options ) ) );
	}
	return join( "\n", $n_sources )."\n";
}

# Returns path to an image asset. Example:
#
# The +src+ can be supplied as a...
# * full path, like "/my_images/image.gif"
# * file name, like "rss.gif", that gets expanded to "/images/rss.gif"
# * file name without extension, like "logo", that gets expanded to "/images/logo.png"
function image_path( $source )
{
	return compute_public_path( $source, 'images', 'png' );
}

# Returns an image tag converting the +options+ into html options on the tag, but with these special cases:
#
# * <tt>:alt</tt>  - If no alt text is given, the file name part of the +src+ is used (capitalized and without the extension)
# * <tt>:size</tt> - Supplied as "XxY", so "30x45" becomes width="30" and height="45"
#
# The +src+ can be supplied as a...
# * full path, like "/my_images/image.gif"
# * file name, like "rss.gif", that gets expanded to "/images/rss.gif"
# * file name without extension, like "logo", that gets expanded to "/images/logo.png"
function image_tag( $source, $options = array() )
{
	$options['src'] = image_path( $source );
	$options['alt'] = ( isset( $options['alt'] ) ? $options['alt'] : ucfirst( array_pop( explode( ',', basename( $options['src'], '.*' ) ) ) ) );
	
	if ( $options['size'] )
	{
		list( $options['width'], $options['height'] ) = split( 'x', $options['size'] );
		unset( $options['size'] );
	}
	return tag( 'img', $options );
}

function compute_public_path( $source, $dir, $ext )
{
	global $controller;
	if ( ( $source{0} != "/" ) || !strstr( $source, ':' ) )
	{
		$parts = explode( '/', $_SERVER['SCRIPT_NAME'] );
		$subdir = strpos( $parts[1], '.' ) ? '' : $parts[1];
		$source  = "http://{$_SERVER['SERVER_NAME']}/".SAKE_PATH."public/{$dir}/{$source}";
	}
	$arr = explode( '/', $source );
	if ( !strstr( array_pop( $arr ), '.' ) )
		$source .= '.'.$ext;
	if ( defined('SAKE_ROOT') && !preg_match( '{^[-a-z]+://}', $source ) )
		$source .= ''; //?'.sake_asset_id( $source );
	if ( !preg_match( '{^[-a-z]+://}', $source ) )
		$source = $controller->request->relative_url_root.$source;
	if( !strstr( $source, ':' ) )
		$source = \biru_controller\base::$asset_host.$source;
	return $source;
}

function sake_asset_id( $source )
{
	return '';
	return ( defined('SAKE_ASSET_ID') ? SAKE_ASSET_ID : (string) filemtime( SAKE_ROOT.'/'.$source ) );
}
?>
