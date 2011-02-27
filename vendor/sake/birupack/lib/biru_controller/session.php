<?
namespace biru_controller;
require_once 'reader.php';

class lazy_session_reader extends reader
{
	public function delete( $name )
	{
		unset( $this->heap[ $name ] );
		unset( $_SESSION[ $name ] );
	}
	
	public function __construct( $session = array() )
	{
		parent::__construct();
		$this->heap = $_SESSION;
	}

    public function __destruct()
    {
        $_SESSION = $this->heap;
    }
}
?>
