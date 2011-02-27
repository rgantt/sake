<?php
namespace biru_controller;

require_once dirname(__FILE__).'/reader.php';

class lazy_session_reader extends reader
{
	public function delete( $name )
	{
		unset( $this->heap[ $name ] );
		unset( $_SESSION[ $name ] );
	}
	
	public function __construct( $session = array() )
	{
		parent::__construct( $session );
		$this->heap = isset( $_SESSION ) ? $_SESSION : $session;
	}

    public function __destruct()
    {
        $_SESSION = $this->heap;
    }
}
?>
