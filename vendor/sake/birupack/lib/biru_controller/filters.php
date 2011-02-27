<?
function apply_filters( &$controller, $action, $before_filters, $after_filters )
{
	do_filter( $controller, $action, $before_filters );
	$controller->$action();
	do_filter( $controller, $action, $after_filters );
}

function do_filter( &$controller, $action, $filters )
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
						$controller->$key();
						next;
					}
				break;
			}
		}
		else
		{
			$controller->$key();
		}
	}
}
?>
