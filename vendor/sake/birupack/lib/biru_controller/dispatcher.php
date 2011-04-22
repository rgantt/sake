<?
namespace biru_controller;

class dispatcher
{
    static $guard = ''; // Mutex.new

    static function dispatch( $cgi = null, $session_options = -1, $output = '' )
    {
        if( $session_options == -1 )
            $session_options = cgi_request::$DEFAULT_SESSION_OPTIONS;
        $n = new dispatcher( $output );
        return $n->dispatch_cgi( $cgi, $session_options );
    }

    static function to_prepare( $identifier = null, $block )
    {
        $this->prepare_dispatch_callbacks = $this->prepare_dispatch_callbacks ? $this->prepare_dispatch_callbacks : '';
        //@prepare_dispatch_callbacks ||= ActiveSupport::Callbacks::CallbackChain.new
        //callback = ActiveSupport::Callbacks::Callback.new(:prepare_dispatch, block, :identifier => identifier)
        //@prepare_dispatch_callbacks.replace_or_append_callback(callback)
    }

    public function failsafe_response( $fallback_output, $status, $originating_exception = nil )
    {
        //$this->log_failsafe_exception( $status, $originating_exception );
        $body = $this->failsafe_response_body( $status );
        $fallback_output .= "Status: {$status}\r\nContent-Type: text/html\r\n\r\n{$body}";
        return $fallback_output;
    }

    private function failsafe_response_body( $status )
    {
        /**
         *         def failsafe_response_body(status)
          error_path = "#{error_file_path}/#{status.to_s[0..3]}.html"

          if File.exist?(error_path)
            File.read(error_path)
          else
            "<html><body><h1>#{status}</h1></body></html>"
          end
        end

        def log_failsafe_exception(status, exception)
          message = "/!\\ FAILSAFE /!\\  #{Time.now}\n  Status: #{status}\n"
          message << "  #{exception}\n    #{exception.backtrace.join("\n    ")}" if exception
          failsafe_logger.fatal message
          end
         */

    }

    private function failsafe_logger()
    {
        /**

        def failsafe_logger
          if defined?(::RAILS_DEFAULT_LOGGER) && !::RAILS_DEFAULT_LOGGER.nil?
            ::RAILS_DEFAULT_LOGGER
          else
            Logger.new($stderr)
          end
          end
         */
    }
     
/**
    cattr_accessor :error_file_path
    self.error_file_path = "#{::RAILS_ROOT}/public" if defined? ::RAILS_ROOT

    cattr_accessor :unprepared
    self.unprepared = true

    include ActiveSupport::Callbacks
    define_callbacks :prepare_dispatch, :before_dispatch, :after_dispatch

    before_dispatch :reload_application
    before_dispatch :prepare_application
    after_dispatch :flush_logger
    after_dispatch :cleanup_application

    if defined? ActiveRecord
      to_prepare :activerecord_instantiate_observers do
        ActiveRecord::Base.instantiate_observers
      end
      end
     */

    public function __construct( $output, $request = null, $response = null )
    {
        $this->output = $output;
        $this->request = $request;
        $this->response = $response;
    }

    public function _dispatch()
    {
        try
        {
            //$this->run_callbacks('before_dispatch');
            $m = $this->handle_request();
            //$this->run_callbacks('after_dispatch', array( 'enumerator' => 'reverse_each' ) ); // this should be in a `finally`
            return $m;
        }
        catch( \biru_controller\sake_exception $e )
        {
            throw $e;
        }
    }
        
    public function dispatch_cgi( $cgi, $session_options )
    {
        $cgi = $cgi ? $cgi : self::failsafe_response( $this->output, '400 Bad Request', function(){ return new cgi(); } );
        if( $cgi )
        {
            $this->request = new cgi_request( $cgi, $session_options );
            $this->response = new cgi_response( $cgi );
            return $this->_dispatch();
        }
    }

    public function prepare_application( $force = false )
    {
        return true;
    }

    /**
    def prepare_application(force = false)
      begin
        require_dependency 'application' unless defined?(::ApplicationController)
      rescue LoadError => error
        raise unless error.message =~ /application\.rb/
      end

      ActiveRecord::Base.verify_active_connections! if defined?(ActiveRecord)

      if unprepared || force
        run_callbacks :prepare_dispatch
        ActionView::TemplateFinder.reload! unless ActionView::Base.cache_template_loading
        self.unprepared = false
      end
    end
    

    # Cleanup the application by clearing out loaded classes so they can
    # be reloaded on the next request without restarting the server.
    def cleanup_application(force = false)
      if Dependencies.load? || force
        ActiveRecord::Base.reset_subclasses if defined?(ActiveRecord)
        Dependencies.clear
        ActiveRecord::Base.clear_reloadable_connections! if defined?(ActiveRecord)
      end
    end

    def flush_logger
      RAILS_DEFAULT_LOGGER.flush if defined?(RAILS_DEFAULT_LOGGER) && RAILS_DEFAULT_LOGGER.respond_to?(:flush)
    end
     */

    protected function handle_request()
    {
        $this->controller = routing\routes::recognize( $this->request );
        return array( $this->controller, $this->request, $this->response, $this->output );
    }

    protected function failsafe_rescue( $exception )
    {
        return;
    }
}

/**
      def failsafe_rescue(exception)
        self.class.failsafe_response(@output, '500 Internal Server Error', exception) do
          if @controller ||= defined?(::ApplicationController) ? ::ApplicationController : Base
            @controller.process_with_exception(@request, @response, exception).out(@output)
          else
            raise exception
          end
        end
      end
  end
end
 */
?>
