<?php
namespace biru_controller;

require dirname(__FILE__)."/../sake_unit.php";

class url_encoded_request_parameter_parsing_test extends SAKE_test_case
{
	public function setUp()
	{
		$this->request = new \biru_controller\test_request();

		$this->query_string = "action=create_customer&full_name=Ryan%20Preston%20Gantt&customerId=1";
		$this->query_string_with_empty = "action=create_customer&full_name=";
		$this->query_string_with_array = "action=create_customer&selected[]=1&selected[]=2&selected[]=3";
		$this->query_string_with_amps  = "action=create_customer&name=Don%27t+%26+Does";
		$this->query_string_with_multiple_of_same_name = "action=update_order&full_name=Lau%20Taarnskov&products=4&products=2&products=3";
		$this->query_string_with_many_equal = "action=create_customer&full_name=abc=def=ghi";
		$this->query_string_without_equal = "action";
		$this->query_string_with_many_ampersands = "&action=create_customer&&&full_name=Ryan%20Preston%20Gantt";
		$this->query_string_with_empty_key = "action=create_customer&full_name=Ryan%20Preston%20Gantt&=Save";
	}

	public function test_query_string()
	{
		$this->assertEquals(
			array( 'action' => 'create_customer', 'full_name' => 'Ryan Preston Gantt', 'customerId' => '1' ),
			\biru_controller\abstract_request::parse_query_parameters( $this->query_string )
		);
	}

	public function test_deep_query_string()
	{
		$this->assertEquals(
			array( 'x' => array( 'y' => array( 'z' => '10' ) ) ),
			\biru_controller\abstract_request::parse_query_parameters( 'x[y][z]=10' )
		);
	}

	public function test_deep_query_string_with_array()
	{
		$this->assertEquals(
			array( 'x' => array( 'y' => array( 'z' => array( '10' ) ) ) ),
			\biru_controller\abstract_request::parse_query_parameters( 'x[y][z][]=10' )
		);
		$this->assertEquals(
			array( 'x' => array( 'y' => array( 'z' => array( '10', '5' ) ) ) ),
			\biru_controller\abstract_request::parse_query_parameters( 'x[y][z][]=10&x[y][z][]=5' )
		);
	}

	public function test_deep_query_string_with_array_of_hash()
	{
		$this->assertEquals(
			array( 'x' => array( 'y' => array( array( 'z' => '10' ) ) ) ),
			\biru_controller\abstract_request::parse_query_parameters( 'x[y][][z]=10' )
		);
		$this->assertEquals(
			array( 'x' => array( 'y' => array( array( 'z' => '10'), array( 'w' => '5' ) ) ) ),
			\biru_controller\abstract_request::parse_query_parameters( 'x[y][][z]=10&x[y][][w]=5' )
		);
	}

	public function test_deep_query_string_with_array_of_hashes_with_multiple_pairs()
	{
		$this->assertEquals(
			array( 'x' => array( 'y' => array( array( 'z' => '10' ), array( 'w' => 'a' ), array( 'z' => '20' ), array( 'w' => 'b' ) ) ) ),
			\biru_controller\abstract_request::parse_query_parameters( 'x[y][][z]=10&x[y][][w]=a&x[y][][z]=20&x[y][][w]=b' )
		);
	}

	public function test_query_string_with_null()
	{
		$this->assertEquals(
			array( 'action' => 'create_customer', 'full_name' => '' ),
			\biru_controller\abstract_request::parse_query_parameters( $this->query_string_with_empty )
		);
	}

	public function test_query_string_with_array()
	{
		$this->assertEquals(
			array( 'action' => 'create_customer', 'selected' => array( '1', '2', '3' ) ),
			\biru_controller\abstract_request::parse_query_parameters( $this->query_string_with_array )
		);
	}

	public function test_query_string_with_amps()
	{
		$this->assertEquals(
			array( 'action' => 'create_customer', 'name' => "Don't & Does" ),
			\biru_controller\abstract_request::parse_query_parameters( $this->query_string_with_amps )
		);
	}

	public function test_query_string_with_many_equal()
	{
		$this->assertEquals(
			array( 'action' => 'create_customer', 'full_name' => 'abc=def=ghi' ),
			\biru_controller\abstract_request::parse_query_parameters( $this->query_string_with_many_equal )
		);
	}

	public function test_query_string_without_equal()
	{
		$this->assertEquals(
			array( 'action' => null ),
			\biru_controller\abstract_request::parse_query_parameters( $this->query_string_without_equal )
		);
	}

	public function test_query_string_with_empty_key()
	{
		$this->assertEquals(
			array( 'action' => 'create_customer', 'full_name' => "Ryan Preston Gantt" ),
			\biru_controller\abstract_request::parse_query_parameters( $this->query_string_with_empty_key )
		);
	}

	public function test_query_string_with_many_ampersands()
	{
		$this->assertEquals(
			array( 'action' => 'create_customer', 'full_name' => "Ryan Preston Gantt" ),
			\biru_controller\abstract_request::parse_query_parameters( $this->query_string_with_many_ampersands )
		);
	}

	public function test_unbalanced_query_string_with_array()
	{
		$this->assertEquals(
			array( 'location' => array( '1', '2' ), 'age_group' => array( '2' ) ),
			\biru_controller\abstract_request::parse_query_parameters( 'location[]=1&location[]=2&age_group[]=2' )
		);
	}
	
	public function test_parse_params()
	{
		$input = array(
			"customers[boston][first][name]" => array( "Ryan" ),
			"customers[boston][first][url]" => array( "http://Ryan" ),
			"customers[boston][second][name]" => array( "David" ),
			"customers[boston][second][url]" => array( "http://David" ),
			"something_else" => array( "blah" ),
			"something_null" => array( null ),
			"something_empty" => array(),
			"products[first]" => array( "Apple Computer" ),
			"products[second]" => array( "Pc" ),
			"" => array( "Save" )
		);
		
		$expected_output = array(
			"customers" => array(
				"boston" => array( 
					"first" => array(
						"name" => "Ryan",
						"url" => "http://Ryan"
					),
					"second" => array(
						"name" => "David",
						"url" => "http://David"
					)
				)
			),
			"something_else" => "blah",
			"something_empty" => "",
			"something_null" => "",
			"products" => array(
				"first" => "Apple Computer",
				"second" => "Pc"
			)
		);
		
		$this->assertEquals( 
			$expected_output, 
			\biru_controller\abstract_request::parse_request_parameters( $input )
		);
	}
}
