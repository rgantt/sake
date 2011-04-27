<?php
namespace biru_controller;

define( 'DEFAULT_RENDER_STATUS_CODE', "200 OK" );

class sake_exception extends \Exception
{
    public function unwind()
    {
        return <<< END
        <pre>
        application error!
        ------------------
        text: {$this->getMessage()}
        file: {$this->getFile()}
        line: {$this->getLine()}
        stack:
        {$this->getTraceAsString()}
        </pre>
END;
    }
}

class render_error extends sake_exception{}
class double_render_error extends sake_exception{}
class unknown_action extends sake_exception{}
class invalid_query_exception extends sake_exception {}

interface controller 
{
    public function properties();
}

# Process a request extracted from a CGI object and return a response. Pass false as <tt>session_options</tt> to disable
# sessions (large performance increase if sessions are not needed). The <tt>session_options</tt> are the same as for CGI::Session:
#
# * <tt>:database_manager</tt> - standard options are CGI::Session::FileStore, CGI::Session::MemoryStore, and CGI::Session::PStore
#   (default). Additionally, there is CGI::Session::DRbStore and CGI::Session::ActiveRecordStore. Read more about these in
#   lib/action_controller/session.
# * <tt>:session_key</tt> - the parameter name used for the session id. Defaults to '_session_id'.
# * <tt>:session_id</tt> - the session id to use.  If not provided, then it is retrieved from the +session_key+ cookie, or 
#   automatically generated for a new session.
# * <tt>:new_session</tt> - if true, force creation of a new session.  If not set, a new session is only created if none currently
#   exists.  If false, a new session is never created, and if none currently exists and the +session_id+ option is not set,
#   an ArgumentError is raised.
# * <tt>:session_expires</tt> - the time the current session expires, as a +Time+ object.  If not set, the session will continue
#   indefinitely.
# * <tt>:session_domain</tt> - the hostname domain for which this session is valid. If not set, defaults to the hostname of the
#   server.
# * <tt>:session_secure</tt> - if +true+, this session will only work over HTTPS.
# * <tt>:session_path</tt> - the path for which this session applies.  Defaults to the directory of the CGI script.
# * <tt>:cookie_only</tt> - if +true+ (the default), session IDs will only be accepted from cookies and not from
#   the query string or POST parameters. This protects against session fixation attacks.

abstract class base implements controller
{
	protected $flash;
	protected $params;
    protected $session;
    protected $should_forward = false;
    protected $forward_to = null;
    protected $name;
    protected $b_filters = array();
    protected $a_filters = array();
    protected $layout;
    
    private $url;

    public static $has_rendered = false;
    public static $asset_host = '';
    public static $view_paths = array();
    public static $default_charset = "utf-8";
    public static $action_methods = array( 'index' );
    public static $controller_path = '';
    public static $protected_variables_cache = array();
    public static $layout_conditions = array();
    public static $protected_instance_variables = array();
    public static $view_controller_internals = false;

    public static $param_parsers = array(
    	'MULTIPART_FORM' 	=> 'multipart_form',
    	'URL_ENCODED_FORM' 	=> 'url_encoded_form',
    	'XML' 				=> 'xml_simple',
    	'JSON' 				=> 'json'
	);
    
    abstract public function initialize();

    public static function construct()
    {
    	self::$view_paths = array( 'views' );
    	\biru_view\template_finder::process_view_paths( self::$view_paths );
    }
    
    final public function __construct()
    {
        if (get_magic_quotes_gpc()) 
        {
            function stripslashes_deep( $value )
            {
                $value = is_array($value) ? array_map('biru_controller\stripslashes_deep', $value) : stripslashes($value);
                return $value;
            }
            $_POST = array_map('biru_controller\stripslashes_deep', $_POST);
            $_GET = array_map('biru_controller\stripslashes_deep', $_GET);
            $_COOKIE = array_map('biru_controller\stripslashes_deep', $_COOKIE);
            $_REQUEST = array_map('biru_controller\stripslashes_deep', $_REQUEST);
        }
        $this->layout('default'); 
        $ref = new \ReflectionObject( $this );
        $this->name = $ref->name;
        self::$controller_path = preg_replace( '/^[^\r\n]+\\\/', '', preg_replace( '/_controller$/', '', $this->name ) );
    }
    
    public function _process_cgi( $cgi, $session_options = array() )
    {
        return $this->process( new cgi_request( $cgi, $session_options ), new cgi_response( $cgi ) )->out();
    }

    public function process( &$request, &$response, $method = 'perform_action', $arguments = array() )
    {
        $this->initialize_template_class( $response );
        $this->assign_shortcuts( $request, $response );
        $this->initialize_current_url();
        $this->assign_names();
        $this->forget_variables_added_to_assigns();
        $this->initialize();

        //$this->log_processing();
        // this line might be messing with stuff
        $this->extract_globals( array_merge( $this->properties(), array( 'controller' => $this ) ) );
        $this->$method( $arguments );

        $this->assign_default_content_type_and_charset();
        $response->request = &$request;
        $response->prepare();
        $clone = $response;
        $this->process_cleanup();
        return $clone;
    }

    public function template_public( $template_name )
    {
        return $this->template->file_public( $template_name );
    }
    
    public function assert_existence_of_template_file( $template_name )
    {
        if( !( $this->template_exists( $template_name ) || ( isset( $this->ignore_missing_templates ) && !$this->ignore_missing_templates ) ) )
        {
            $full_template_path = ( strpos( $template_name, '.' ) ? $template_name : "{$template_name}.phtml" );
            $display_paths = implode( ':', self::view_paths() );
            $template_type = ( preg_match( '/layouts/i', $template_name ) ? 'layout' : 'template' );
            throw new \biru_controller\sake_exception("missing {$template_type} {$full_template_path} in view path {$display_paths}");
        }
    }

    public function __call( $name, $arguments )
    {
        throw new \biru_controller\sake_exception("call to undefined method {$name}");
    }

    public function url_for( $options = array() )
    {
        switch( gettype( $options ) )
        {
        case 'string': 
        	return $options; 
        	break;
        case 'array': 
        	if( $options === array( 'back' ) && !empty( $this->request->env['HTTP_REFERRER'] ) )
        		return $this->request->env['HTTP_REFERRER'];
        	else if( $options === array( 'back' ) )
				return " ";
        	return $this->url->rewrite( $this->rewrite_options( $options ) ); 
        	break;
        }
    }

    # Converts the class name from something like "OneModule::TwoModule::NeatController" to "NeatController".
    public function controller_class_name()
    {
        return $this->name;
    }

    # Converts the class name from something like "OneModule::TwoModule::NeatController" to "neat".
    public function controller_name()
    {
        return preg_replace( '/_controller$/', '', $this->name );
    }

    /*
    # Converts the class name from something like "OneModule::TwoModule::NeatController" to "one_module/two_module/neat".
    def controller_path
        @controller_path ||= name.gsub(/Controller$/, '').underscore
    end
     */

    public function rewrite_options( $options )
    {
        $defaults = $this->default_url_options( $options );
        if( $defaults )
            return array_merge( $defaults, $options );
        else
            return (array)$options;
    }

    public function initialize_current_url()
    {
        $this->url = new url_rewriter( $this->request, $this->params );
    }

    public function apply_filters( $action )
    {
        $this->do_filter( $action, $this->get_before_filters() );
        $this->$action();
        $this->do_filter( $action, $this->get_after_filters() );
    }

    public function get_before_filters()
    {
        return $this->b_filters;
    }

    public function get_after_filters()
    {
        return $this->a_filters;
    }

    public function before_filter( $name, $options = '' )
    {
        $this->b_filters[ $name ] = $options;
    }

    public function after_filter( $name, $options = '' )
    {
        $this->a_filters[ $name ] = $options;
    }

    public function get_forward_to()
    {
        return $this->forward_to;
    }

    public function should_forward()
    {
        return $this->should_forward;
    }

    public function get_page_title()
    { 
        return $this->page_title; 
    }

    public function set_page_title( $title )
    { 
        $this->page_title = $title;
    }

    public function get_image_dir()
    { 
        return $this->image_basedir;
    }

    public function set_image_dir( $dir )
    { 
        $this->image_basedir = $dir;
    }

    public function set_default_layout( $layout )
    {
        $this->layout = $layout;
    }

    public function get_default_layout()
    {
        return $this->layout;
    }

    public function properties()
    {
        $props = get_object_vars( $this );
        return $props;
    }

    public function redirect( $controller, $action = null, $options = array() )
    {
        return $this->redirect_to( array_merge( array( 'page' => $controller, 'action' => ( $action ? $action : 'index' ) ), $options ) );
    }

    public function redirect_to( $options = array(), $response_status = array() )
    {
        if( $options == array() || empty( $options ) )
            throw new \biru_controller\sake_exception("cannot redirect to null");
        
        if( is_array( $options ) && ( $status = $options['status'] ) )
            $options['status'] = null;
        else if( is_array( $response_status ) && ( $status = $response_status['status'] ) )
            $response_status['status'] = null;
        else
            $status = '302';

        if( is_string( $options ) && preg_match( '/^\w+:\/\/.*/', $options ) )
        {
            if( $this->performed() )
                throw new \biru_controller\sake_exception("double render error");
            $this->response->redirected_to = $options;
            $this->performed_redirect = true;
            return $this->response->redirect( $options, \status_codes\interpret_status( $status ) );
        }
        else if( $options == 'back' )
        {
            if( $this->request->env['HTTP_REFERRER'] )
                return $this->redirect_to( $this->request->env['HTTP_REFERRER'], array( 'status' => $status ) );
            throw new \biru_controller\sake_exception("redirect back error");
        }
        else if( is_string( $options ) && $options !== '' )
        {
            return $this->redirect_to( "http://{$this->request->env['SERVER_NAME']}/{$options}", array( 'status' => $status ) );
        }
        else if( is_array( $options ) )
        {
            $this->response->redirected_to = $options;
            return $this->redirect_to( $this->url_for( $options ), array( 'status' => $status ) );
        }
        else
            return $this->redirect_to( $this->url_for( $options ), array( 'status' => $status ) );
    }

    /**
     * Overwrite to implement a number of default options that all url_for-based methods will use. The default options should come in
     * the form of a hash, just like the one you would use for url_for directly. Example:
     *
     *   def default_url_options(options)
     *     { :project => @project.active? ? @project.url_name : "unknown" }
     *   end
     *
     * As you can infer from the example, this is mostly useful for situations where you want to centralize dynamic decisions about the
     * urls as they stem from the business domain. Please note that any individual url_for call can always override the defaults set
     * by this method.
     */
    public function default_url_options( $options )
    {
    	return;
    }

    # Renders the content that will be returned to the browser as the response body.
    #
    # === Rendering an action
    #
    # Action rendering is the most common form and the type used automatically by Action Controller when nothing else is
    # specified. By default, actions are rendered within the current layout (if one exists).
    #
    #   # Renders the template for the action "goal" within the current controller
    #   render :action => "goal"
    #
    #   # Renders the template for the action "short_goal" within the current controller,
    #   # but without the current active layout
    #   render :action => "short_goal", :layout => false
    #
    #   # Renders the template for the action "long_goal" within the current controller,
    #   # but with a custom layout
    #   render :action => "long_goal", :layout => "spectacular"
    #
    # === Rendering partials
    #
    # Partial rendering in a controller is most commonly used together with Ajax calls that only update one or a few elements on a page
    # without reloading. Rendering of partials from the controller makes it possible to use the same partial template in
    # both the full-page rendering (by calling it from within the template) and when sub-page updates happen (from the
    # controller action responding to Ajax calls). By default, the current layout is not used.
    #
    #   # Renders the same partial with a local variable.
    #   render :partial => "person", :locals => { :name => "david" }
    #
    #   # Renders the partial, making @new_person available through
    #   # the local variable 'person'
    #   render :partial => "person", :object => @new_person
    #
    #   # Renders a collection of the same partial by making each element
    #   # of @winners available through the local variable "person" as it
    #   # builds the complete response.
    #   render :partial => "person", :collection => @winners
    #
    #   # Renders the same collection of partials, but also renders the
    #   # person_divider partial between each person partial.
    #   render :partial => "person", :collection => @winners, :spacer_template => "person_divider"
    #
    #   # Renders a collection of partials located in a view subfolder
    #   # outside of our current controller.  In this example we will be
    #   # rendering app/views/shared/_note.r(html|xml)  Inside the partial
    #   # each element of @new_notes is available as the local var "note".
    #   render :partial => "shared/note", :collection => @new_notes
    #
    #   # Renders the partial with a status code of 500 (internal error).
    #   render :partial => "broken", :status => 500
    #
    # Note that the partial filename must also be a valid Ruby variable name,
    # so e.g. 2005 and register-user are invalid.
    #
    #
    # == Automatic etagging
    #
    # Rendering will automatically insert the etag header on 200 OK responses. The etag is calculated using MD5 of the
    # response body. If a request comes in that has a matching etag, the response will be changed to a 304 Not Modified
    # and the response body will be set to an empty string. No etag header will be inserted if it's already set.
    #
    # === Rendering a template
    #
    # Template rendering works just like action rendering except that it takes a path relative to the template root.
    # The current layout is automatically applied.
    #
    #   # Renders the template located in [TEMPLATE_ROOT]/weblog/show.r(html|xml) (in Rails, app/views/weblog/show.erb)
    #   render :template => "weblog/show"
    #
    # === Rendering a file
    #
    # File rendering works just like action rendering except that it takes a filesystem path. By default, the path
    # is assumed to be absolute, and the current layout is not applied.
    #
    #   # Renders the template located at the absolute filesystem path
    #   render :file => "/path/to/some/template.erb"
    #   render :file => "c:/path/to/some/template.erb"
    #
    #   # Renders a template within the current layout, and with a 404 status code
    #   render :file => "/path/to/some/template.erb", :layout => true, :status => 404
    #   render :file => "c:/path/to/some/template.erb", :layout => true, :status => 404
    #
    #   # Renders a template relative to the template root and chooses the proper file extension
    #   render :file => "some/template", :use_full_path => true
    #
    # === Rendering text
    #
    # Rendering of text is usually used for tests or for rendering prepared content, such as a cache. By default, text
    # rendering is not done within the active layout.
    #
    #   # Renders the clear text "hello world" with status code 200
    #   render :text => "hello world!"
    #
    #   # Renders the clear text "Explosion!"  with status code 500
    #   render :text => "Explosion!", :status => 500
    #
    #   # Renders the clear text "Hi there!" within the current active layout (if one exists)
    #   render :text => "Hi there!", :layout => true
    #
    #   # Renders the clear text "Hi there!" within the layout
    #   # placed in "app/views/layouts/special.r(html|xml)"
    #   render :text => "Hi there!", :layout => "special"
    #
    # The :text option can also accept a Proc object, which can be used to manually control the page generation. This should
    # generally be avoided, as it violates the separation between code and content, and because almost everything that can be
    # done with this method can also be done more cleanly using one of the other rendering methods, most notably templates.
    #
    #   # Renders "Hello from code!"
    #   render :text => proc { |response, output| output.write("Hello from code!") }
    #
    # === Rendering JSON
    #
    # Rendering JSON sets the content type to application/json and optionally wraps the JSON in a callback. It is expected
    # that the response will be parsed (or eval'd) for use as a data structure.
    #
    #   # Renders '{"name": "David"}'
    #   render :json => {:name => "David"}.to_json
    #
    # It's not necessary to call <tt>to_json</tt> on the object you want to render, since <tt>render</tt> will
    # automatically do that for you:
    #
    #   # Also renders '{"name": "David"}'
    #   render :json => {:name => "David"}
    #
    # Sometimes the result isn't handled directly by a script (such as when the request comes from a SCRIPT tag),
    # so the <tt>:callback</tt> option is provided for these cases.
    #
    #   # Renders 'show({"name": "David"})'
    #   render :json => {:name => "David"}.to_json, :callback => 'show'
    #
    # === Rendering an inline template
    #
    # Rendering of an inline template works as a cross between text and action rendering where the source for the template
    # is supplied inline, like text, but its interpreted with ERb or Builder, like action. By default, ERb is used for rendering
    # and the current layout is not used.
    #
    #   # Renders "hello, hello, hello, again"
    #   render :inline => "<%= 'hello, ' * 3 + 'again' %>"
    #
    #   # Renders "<p>Good seeing you!</p>" using Builder
    #   render :inline => "xml.p { 'Good seeing you!' }", :type => :builder
    #
    #   # Renders "hello david"
    #   render :inline => "<%= 'hello ' + name %>", :locals => { :name => "david" }
    #
    # === Rendering inline JavaScriptGenerator page updates
    #
    # In addition to rendering JavaScriptGenerator page updates with Ajax in RJS templates (see ActionView::Base for details),
    # you can also pass the <tt>:update</tt> parameter to +render+, along with a block, to render page updates inline.
    #
    #   render :update do |page|
    #     page.replace_html  'user_list', :partial => 'user', :collection => @users
    #     page.visual_effect :highlight, 'user_list'
    #   end
    #
    # === Rendering with status and location headers
    #
    # All renders take the :status and :location options and turn them into headers. They can even be used together:
    #
    #   render :xml => post.to_xml, :status => :created, :location => post_url(post)

    public function render_with_no_layout( $options = null, $extra_options = array(), $block = '' )
    {
        return $this->_render( $options, $extra_options, $block );
    }

    // alias_method :render, :render_with_a_layout
    public function render( $options = null, $extra_options = array(), $block = '' )
    {
        return $this->render_with_a_layout( $options, $extra_options, $block );
    }

    // alias_method :render_with_no_layout, :render
    public function _render( $options = null, $extra_options = array(), $block='' )
    {
        if( $this->performed() )
            throw new \biru_controller\sake_exception("can only render or redirect once per action");
        if( $options == null )
            return $this->render_for_file( $this->default_template_name(), null, true );
        else if( !is_array( $extra_options ) )
            throw new \biru_controller\sake_exception("you called render with invalid options");
        else
        {
            if( $options == 'update' )
                $options = array_merge( $extra_options, array( 'update' => 'true' ) );
            else if( !is_array( $options ) )
                throw new \biru_controller\render_error("you called render with invalid options");
        }

        if( !isset( $options['status'] ) )
        	$options['status'] = '';
        if( isset( $options['content_type'] ) )
            $this->response->content_type( $options['content_type'] );
        if( isset( $options['location'] ) )
            $this->response->headers['Location'] = $this->url_for( $options['location'] );

        if( isset( $options['text'] ) )
            return $this->render_for_text( $options['text'], $options['status'] );
        else
        {
        	$options['locals'] = ( isset( $options['locals'] ) ? $options['locals'] : array() );
        	$options['type'] = ( isset( $options['type'] ) ? $options['type'] : null );
            if( isset( $options['file'] ) )
                return $this->render_for_file( $options['file'], $options['status'], $options['use_full_path'], $options['locals'] );
            else if( isset( $options['template'] ) )
                return $this->render_for_file( $options['template'], $options['status'], true );
            else if( isset( $options['inline'] ) )
            {
                $this->add_variables_to_assigns();
                $tmpl = new \biru_view\template( $this->template, $options['inline'], false, $options['locals'], true, $options['type'] );
                return $this->render_for_text( $this->template->render_template( $tmpl ), $options['status'] );
            }
            else if( isset( $options['action'] ) )
            {
                $template = $this->default_template_name( $options['action'] );
                if( isset( $options['layout'] ) && $options['layout'] && !$this->template_exempt_from_layout( $template ) )
                    return $this->render_with_a_layout( array( 'file' => $template, 'status' => $options['status'], 'use_full_path' => true, 'layout' => true ) );
                else
                    return $this->render_with_no_layout( array( 'file' => $template, 'status' => $options['status'], 'use_full_path' => true ) );
            }
            else if( isset( $options['xml'] ) )
            {
                if( !$this->reponse->content_type )
                    $this->response->content_type = \Mime\type('XML');
                return $this->render_for_text( $options['xml'], $options['status'] );
            }
            else if( isset( $options['json'] ) )
            {
            	if( is_array( $options['json'] ) )
            		$options['json'] = json_encode( $options['json'] );
            	if( isset( $options['callback'] ) && !empty( $options['callback'] ) )
                	$options['json'] = $options['callback'].'('.$options['json'].')';
            	$this->response->content_type( \Mime\type('JSON') );
            	return $this->render_for_text( $options['json'], $options['status'] );
            }
            else if( isset( $options['partial'] ) && ( $partial = $options['partial'] ) )
            {
                if( $partial === true )
                    $partial = $this->default_template_name();
                $this->add_variables_to_assigns();
                if( isset( $options['collection'] ) )
                    return $this->render_for_text( $this->template->render_partial_collection( $partial, $options['collection'], $options['spacer_template'], $options['locals'], $options['status'] ) );
                else
                {
                	$obj = isset( $options['object'] ) ? $options['object'] : null;
                    return $this->render_for_text( $this->template->render_partial( $partial, $obj, $options['locals'] ), $options['status'] );
                }
            }
            else if( isset( $options['update'] ) )
            {
                $this->add_variables_to_assigns();
                $this->template->evaluate_assigns();

                //$generator = new biru_view\helpers\prototype_helper\javascript_generator( $this->template, $block );
                $this->response->content_type = Mime\type('JS');
                return $this->render_for_text( $generator, $options['status'] );
            }
            else if( isset( $options['nothing'] ) )
                return $this->render_for_text( ' ', $options['status'] );
            else
                return $this->render_for_file( $this->default_template_name(), $options['status'], true );
        }
    }
    
    public function default_layout( $format )
    {
        $layout = $this->layout;
        if( $this->auto_layout )
        {
            if( !$this->default_layout )
                $this->default_layout = array();
            if( !$this->default_layout[ $format ] )
                $this->default_layout[ $format ] = $this->default_layout_with_format( $format, $layout );
            return $this->default_layout[ $format ];
        }
        else
            return $layout;
    }
    
    public function active_layout( $passed_layout = null )
    {
        $active_layout = !is_null( $passed_layout ) ? $passed_layout : $this->default_layout( $this->response->template->template_format );
        if( preg_match( '/\//', $active_layout ) ) #&& !$this->layout_directory( $active_layout ) )
            return $active_layout;
        else
            return "layouts/{$active_layout}";
    }
    
    public function layout( $template_name, $conditions = array(), $auto = false )
    {
        //$this->add_layout_conditions( $conditions );
        $this->layout = $template_name;
        $this->auto_layout = $auto;
    }

    public function render_to_string( $options = null )
    {
        $m = $this->render( $options );
        $this->erase_render_results();
        $this->forget_variables_added_to_assigns();
        $this->reset_variables_added_to_assigns();
        return $m;
    }
    
    protected function reset_session()
    {
    	$this->request->reset_session();
        $this->session = &$this->request->session;
        $this->response->session = $this->session;
        $this->flash( true );
    }
    
    protected function &flash( $refresh = false )
    {
    	if( !isset( $this->flash ) || $refresh )
    	{
    		if( !($this->session['flash'] instanceof flash) )
    			$this->session['flash'] = new flash();
    		$this->flash = $this->session['flash'];
    	}
    	return $this->flash;
    }

    protected function render_with_a_layout( $options = null, $extra_options = array(), $block )
    {
        $template_with_options = is_array( $options );

        $layout = $this->pick_layout( $template_with_options, $options );
        if( $this->apply_layout( $template_with_options, $options ) && $layout )
        {
            $this->assert_existence_of_template_file( $layout );
            if( $template_with_options )
                $options = array_merge( $options, array( 'layout' => false ) );

            $content_for_layout = $this->render_with_no_layout( $options, $extra_options, $block );
            $this->content_for_layout = $content_for_layout;

            $this->erase_render_results();
            $this->add_variables_to_assigns();
            $this->template->content_for_layout = $content_for_layout;

            $this->response->layout = $this->layout;
            $status = $template_with_options ? $options['status'] : null;
            return $this->render_for_text( $this->template->render_file( $layout, true ), $status );
        }
        else
        {
            return $this->render_with_no_layout( $options, $extra_options, $block );
        }
    }
    
    protected function parse_query_string( $query )
    {
        $array = array();
        if( $query{0} == '&' )
            $query = substr( $query, 1 );
        $pairs = explode( '&', $query );
        foreach( $pairs as $pair )
        {
            list( $key, $value ) = explode( '=', $pair );
            $array[ $key ] = $value;
        }
        return $array;
    }
    
    protected function erase_render_results()
    {
        $this->reponse->body = null;
        $this->performed_render = false;
    }
    
    private function default_template_name( $action_name = -1 )
    {
        if( $action_name == -1 )
            $action_name = $this->action_name;
        if( strpos( $action_name, '/' ) && $this->template_path_includes_controller( $action_name ) )
            $action_name = $this->strip_out_controller( $action_name );
       	$tr = self::$controller_path."/{$action_name}";
        //$tr = $action_name;
        return $tr;
    }
    
    private function do_filter( $action, $filters )
    {
        foreach( $filters as $key => $value )
        {
            if( is_array( $value ) )
            {
                switch( $value[0] )
                {
                case 'only':
                    if( in_array( $value[1], $action ) )
                    {
                        $this->$key();
						next;
                    }
                    break;
                }
            }
            else
                $this->$key();
        }
    }

    private function template_exempt_from_layout( $template_name = 'default' )
    {
        $extension = $this->template->finder->pick_template_extension( $template_name );
        $name_with_extension = $extension ? "{$template_name}.{$extension}" : $template_name;
        if( isset( self::$exempt_from_layout ) && in_array( $name_with_extension, self::$exempt_from_layout ) )
            return true;
        return false;
    }

    private function apply_layout( $template_with_options, $options )
    {
        if( $options == 'update' )
            return false;
        return ( $template_with_options ? $this->candidate_for_layout( $options ) : !$this->template_exempt_from_layout() );
    }

    private function candidate_for_layout( $options )
    {
        $val1 = ( isset( $options['layout'] ) && $options['layout'] !== false );
        $action = isset( $options['action'] ) ? $options['action'] : null;
        $tmp = isset( $options['template'] ) ? $options['template'] : $this->default_template_name( $action );
        $val2 = ( !isset( $options['text'], $options['xml'], $options['json'], $options['file'], $options['inline'], $options['partial'], $options['nothing'] ) && $this->template_exempt_from_layout( $tmp ) );
        return( $val1 || $val2 );
    }

    private function pick_layout( $template_with_options, $options )
    {
        if( $template_with_options )
        {
            $layout = isset( $options['layout'] ) ? $options['layout'] : null;
            if( $layout === false )
                return null;
            else if( $layout == null || $layout == true )
            {
                if( $this->action_has_layout() )
                    return $this->active_layout();
            }
            else
                return $this->active_layout( $layout );
        }
        else
        {
            if( $this->action_has_layout() )
                return $this->active_layout();
        }
    }

    private function default_layout_with_format( $format, $layout )
    {
        return "{$layout}.{$format}";
    }

    private function action_has_layout()
    {
        $conditions = self::$layout_conditions;
        if( $conditions )
        {
            $only = $conditions['only'];
            $except = $conditions['except'];
            if( $only )
                return preg_match( $only, $this->action_name() );
            if( $except )
                return !( preg_match( $except, $this->action_name() ) );
            return true;
        }
        else
            return true;
    }

    private function render_for_file( $template_path, $status = null, $use_full_path = false, $locals = array() )
    {
        $this->add_variables_to_assigns();
        if( $use_full_path )
            $this->assert_existence_of_template_file( $template_path );
        // logger
        return $this->render_for_text( $this->template->render_file( $template_path, $use_full_path, $locals ), $status );
    }

    private function render_for_text( $text = null, $status = null, $append_response = false )
    {
        $this->performed_render = true;
        $this->response->headers['Status'] = ( $status ? $status : DEFAULT_RENDER_STATUS_CODE );
        if( $append_response )
        {
            if( !$this->response->body )
                $this->response->body = '';
            $this->response->body .= $text;
        }
        else
            $this->response->body = $text;
        return $this->response->body;
    }

    private function add_variables_to_assigns()
    {
        if( !$this->variables_added )
        {
            $this->add_instance_variables_to_assigns();
            if( self::$view_controller_internals )
                $this->add_class_variables_to_assigns();
            $this->variables_added = true;
        }
    }

    private function add_instance_variables_to_assigns()
    {
        if( !self::$protected_variables_cache )
            self::$protected_variables_cache = $this->protected_instance_variables();
        $props = get_object_vars( $this );
       	foreach( $props as $name => $value )
        {
            if( in_array( $name, self::$protected_variables_cache ) )
                continue;
            if( !is_string( $name ) && isset( $this->{$name} ) )
            	continue;
            $this->assigns[ $name ] = $value;
        }
    }
    
    private function protected_instance_variables()
    {
    	if ( self::$view_controller_internals )
    		return array( "assigns", "performed_redirect", "performed_render" );
    	else
    	{
    		return array( 
    			"url", "a_filters", "b_filters", "assigns", "performed_redirect", "performed_render", "request", 
    			"response", "params", "session", "cookies", "template", "request_origin", "parent_controller", "name" 
          	);
    	}
    }
    
    private function performed()
    {
        return ( $this->performed_render || $this->performed_redirect );
    }

    private function default_render()
    {
        return $this->render();
    }

    private function template_exists( $template_name )
    {
        return $this->template->finder->file_exists( $template_name );
    }
    
    private function extract_globals( $array )
    {
        foreach( $array as $key => $value )
            $GLOBALS[ $key ] = $value;
    }

    private function initialize_template_class( &$response )
    {
        $response->template = new \biru_view\base( self::view_paths(), array(), $this );
        $response->redirected_to = null;
        $this->performed_render = $this->performed_redirect = false;
    }

    private function assign_shortcuts( &$request, &$response )
    {
        $this->request = &$request;
        $this->params = (object) $this->request->parameters();
        $this->cookies = $this->request->cookies(); // was &, threw notice

        $this->response = &$response;
        $this->response->session = &$this->request->session();

        $this->session = &$this->response->session;
        $this->template = &$this->response->template;
        $this->assigns = &$this->response->template->assigns;

        $this->headers = &$this->response->headers;
        
        $this->flash( true );
    }

    // this isn't doing its job right now!
    private function assign_names()
    {
        $this->action_name = ( !empty( $this->params->action ) ? $this->params->action : 'index' );
    }

    private function forget_variables_added_to_assigns()
    {
        $this->variables_added = false;
    }

    private function reset_variables_added_to_assigns()
    {
    	$this->template->assigns_added = null;
    }

    private function assign_default_content_type_and_charset()
    {
    	$ct = $this->response->content_type();
    	$cs = $this->response->charset();
        $this->response->content_type( !empty( $ct ) ? $ct : \Mime\type('HTML') );
        $this->response->charset( !empty( $cs ) ? $cs : self::$default_charset );
    }

    private function perform_action()
    {
        $this->action_methods();
        if( in_array( $this->action_name, self::$action_methods ) )
        {
            $method = $this->action_name;
            $this->$method();
            if( !$this->performed() )
                return $this->default_render();
        }
        else if( method_exists( $this, 'method_missing' ) )
        {
            $this->method_missing( $this->action_name );
            if( !$this->performed() )
                return $this->default_render();
        }
        //else if( $this->template_exists() && $this->template_public() )
        else if( $this->template_exists( $this->action_name ) && $this->template_public( $this->action_name ) )
            return $this->default_render();
        else
            throw new \biru_controller\unknown_action("no action responded to {$this->action_name}");
    }
    
    private function action_methods()
    {
        $m = new \ReflectionClass( get_class( $this ) );
        $n = $m->getMethods( \ReflectionMethod::IS_PUBLIC );
        foreach( $n as $obj )
        {
            if( $obj->class == get_class( $this ) && $obj->name != 'initialize' )
                self::$action_methods[] = $obj->name;
        }
        self::$action_methods = array_unique( self::$action_methods );
    }

    public static function view_paths( $value = null )
    {
    	if( $value === null )
    		return self::$view_paths;
    	else
    	{
    		self::$view_paths = $value;
        	\biru_view\template_finder::process_view_paths( $value );
    	}
    }

    public static function process_cgi( $cgi = null, $session_options = array() )
    {
        $m = new base();
        return $m->_process_cgi( new cgi(), $session_options );
    }
    
    private function process_cleanup()
    {
    	if( $this->session && ( $this->flash instanceof flash ) )
    		$this->flash->sweep();
    	$this->close_session();
    }
    
    private function close_session()
    {
    	$this->session->close();
    }
}
?>
