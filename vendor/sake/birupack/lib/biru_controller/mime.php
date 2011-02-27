<?php
namespace mime;

$SET = array();
$EXTENSION_LOOKUP = array(); //Hash.new { |h, k| h[k] = Type.new(k) unless k.blank? }
$LOOKUP = array(); //Hash.new { |h, k| h[k] = Type.new(k) unless k.blank? }

class accept_item
{
    public $order;
    public $name;
    public $q;

    public function __construct( $order, $name, $q=null )
    {
        $this->order = $order;
        $this->name = $name;
        if( !$q && $this->name == mime::ALL )
            $q = 0.0;
        $this->q = $q ? (int)( (float)( $q * 100 ) ) : 100;
    }

    public function to_s()
    {
        return $this->name;
    }

    public function cmp( accept_item $i )
    {
        $result = $i->q > $this->q;
        if( !$result )
            $result = $this->order > $i->order;
        return $result;
    }
}

class type
{
    static function lookup( $string )
    {
        global $LOOKUP;
        return isset( $LOOKUP[ $string ] ) ? $LOOKUP[ $string ] : $string;
    }

    static function lookup_by_extension( $extension )
    {
        global $EXTENSION_LOOKUP;
        return $EXTENSION_LOOKUP[ $extension ];
    }

    static function register_alias( $string, $symbol, $extension_synonyms = array() )
    {
        return self::register( $string, $symbol, array(), $extension_synonyms, true );
    }

    static function register( $string, $symbol, $mime_type_synonyms = array(), $extension_synonyms = array(), $skip_lookup = false )
    {
        global $SET, $LOOKUP, $EXTENSION_LOOKUP;
        define( strtoupper( $symbol ), new Type( $string, $symbol, $mime_type_synonyms ) );
        $SET[] = constant( strtoupper( $symbol ) );

        $a1 = array_merge( array( $string ), $mime_type_synonyms );
        $a2 = array_merge( array( $symbol ), $extension_synonyms );
        foreach( $a1 as $string )
            $LOOKUP[ $string ] = $SET[ count( $SET ) - 1 ];
        foreach( $a2 as $ext )
            $EXTENSION_LOOKUP[ $ext ] = $SET[ count( $SET ) - 1 ];
    }

    static function parse( $accept_header )
    {
        $list = array();
        $accepts = explode( ',', $accept_header );
        foreach( $accepts as $index => $header )
        {
            $params = preg_split( '/;\s*q=/', $header );
            if( !empty( $params ) )
                $list[] = new accept_item( $index, $params );
        }
        $list = sort( $list );

        $text_xml = $list['text/xml'];
        $app_xml = $list[ mime::XML ];

        if( $text_xml && $app_xml )
        {
            $list[ $app_xml ]->q = max( $list[ $text_xml ]->q, $list[ $app_xml ]->q );
        }
    }

    public function __construct( $string, $symbol = null, $synonyms = array() )
    {
        $this->symbol = $symbol;
        $this->synonyms = $synonyms;
        $this->string = $string;
    }

    public function __toString()
    {
        return $this->string;
    }

    public function to_str()
    {
        return $this->__toString();
    }

    public function to_s()
    {
        return $this->__toString();
    }

    private function method_missing( $method, $args )
    {
        $matches = array();
        if( preg_match( '/(\w+)\?$/', $method, $matches ) )
        {
            $mime_type = strtolower( $matches[0] );
            return ( $mime_type == $this->symbol || ( $mime_type == 'html' && $this->symbol == 'all' ) );
        }
        return parent::method_missing();
    }
}

type::register( "*/*", "all" );

type::register( "text/plain", "text", array(), array( 'txt' ) );
type::register( "text/html", "html", array( 'application/xhtml+xml' ), array( 'xhtml' ));
type::register( "text/javascript", "js", array( 'application/javascript', 'application/x-javascript' ) );
type::register( "text/css", "css" );
type::register( "text/calendar", "ics" );
type::register( "text/csv", "csv" );

type::register( "application/xml", "xml", array( 'text/xml', 'application/x-xml' ) );
type::register( "application/rss+xml", "rss" );
type::register( "application/atom+xml", "atom" );
type::register( "application/x-yaml", "yaml", array( 'text/yaml' ) );

type::register( "multipart/form-data", "multipart_form" );
type::register( "application/x-www-form-urlencoded", "url_encoded_form" );

# http://www.ietf.org/rfc/rfc4627.txt
type::register( "application/json", "json", array( 'text/x-json' ) );
?>
