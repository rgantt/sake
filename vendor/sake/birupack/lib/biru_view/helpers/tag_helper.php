<?
/**
 * Examples:
 * * <tt>tag("br") => <br /></tt>
 * * <tt>tag("input", { "type" => "text"}) => <input type="text" /></tt>
 */
function tag( $name, $options = false, $open = false )
{
	return "<".$name._tag_options( $options ).( $open ? '>' : ' />' );
}

/**
 * Examples:
 * * <tt>content_tag("p", "Hello world!") => <p>Hello world!</p></tt>
 * * <tt>content_tag("div", content_tag("p", "Hello world!"), "class" => "strong") => </tt>
 *   <tt><div class="strong"><p>Hello world!</p></div></tt>
 */
function content_tag( $name, $content, $options = false )
{
	return "<".$name._tag_options( $options ).">".$content."</".$name.">";
}

/**
 * Returns a CDATA section for the given +content+.  CDATA sections
 * are used to escape blocks of text containing characters which would
 * otherwise be recognized as markup. CDATA sections begin with the string
 * <tt>&lt;![CDATA[</tt> and end with (and may not contain) the string 
 * <tt>]]></tt>.
 */ 
function cdata_section( $content )
{
	return "<![CDATA[".$content."]]>";
}

function _tag_options( $options )
{
	$val = '';
	foreach( $options as $key => $value )
	{
		$val .= $key.'="'.$value.'" ';
	}
	return ' '.substr( $val, 0, strlen( $val ) - 1 );
}
?>
