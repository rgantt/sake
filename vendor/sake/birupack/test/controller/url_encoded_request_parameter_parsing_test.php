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
}
/**
  def test_parse_params
    input = {
      "customers[boston][first][name]" => [ "David" ],
      "customers[boston][first][url]" => [ "http://David" ],
      "customers[boston][second][name]" => [ "Allan" ],
      "customers[boston][second][url]" => [ "http://Allan" ],
      "something_else" => [ "blah" ],
      "something_nil" => [ nil ],
      "something_empty" => [ "" ],
      "products[first]" => [ "Apple Computer" ],
      "products[second]" => [ "Pc" ],
      "" => [ 'Save' ]
    }

    expected_output =  {
      "customers" => {
        "boston" => {
          "first" => {
            "name" => "David",
            "url" => "http://David"
          },
          "second" => {
            "name" => "Allan",
            "url" => "http://Allan"
          }
        }
      },
      "something_else" => "blah",
      "something_empty" => "",
      "something_nil" => "",
      "products" => {
        "first" => "Apple Computer",
        "second" => "Pc"
      }
    }

    assert_equal expected_output, ActionController::AbstractRequest.parse_request_parameters(input)
  end

  UploadedStringIO = ActionController::UploadedStringIO
  class MockUpload < UploadedStringIO
    def initialize(content_type, original_path, *args)
      self.content_type = content_type
      self.original_path = original_path
      super *args
    end
  end

  def test_parse_params_from_multipart_upload
    file = MockUpload.new('img/jpeg', 'foo.jpg')
    ie_file = MockUpload.new('img/jpeg', 'c:\\Documents and Settings\\foo\\Desktop\\bar.jpg')
    non_file_text_part = MockUpload.new('text/plain', '', 'abc')

    input = {
      "something" => [ UploadedStringIO.new("") ],
      "array_of_stringios" => [[ UploadedStringIO.new("One"), UploadedStringIO.new("Two") ]],
      "mixed_types_array" => [[ UploadedStringIO.new("Three"), "NotStringIO" ]],
      "mixed_types_as_checkboxes[strings][nested]" => [[ file, "String", UploadedStringIO.new("StringIO")]],
      "ie_mixed_types_as_checkboxes[strings][nested]" => [[ ie_file, "String", UploadedStringIO.new("StringIO")]],
      "products[string]" => [ UploadedStringIO.new("Apple Computer") ],
      "products[file]" => [ file ],
      "ie_products[string]" => [ UploadedStringIO.new("Microsoft") ],
      "ie_products[file]" => [ ie_file ],
      "text_part" => [non_file_text_part]
    }

    expected_output =  {
      "something" => "",
      "array_of_stringios" => ["One", "Two"],
      "mixed_types_array" => [ "Three", "NotStringIO" ],
      "mixed_types_as_checkboxes" => {
         "strings" => {
            "nested" => [ file, "String", "StringIO" ]
         },
      },
      "ie_mixed_types_as_checkboxes" => {
         "strings" => {
            "nested" => [ ie_file, "String", "StringIO" ]
         },
      },
      "products" => {
        "string" => "Apple Computer",
        "file" => file
      },
      "ie_products" => {
        "string" => "Microsoft",
        "file" => ie_file
      },
      "text_part" => "abc"
    }

    params = ActionController::AbstractRequest.parse_request_parameters(input)
    assert_equal expected_output, params

    # Lone filenames are preserved.
    assert_equal 'foo.jpg', params['mixed_types_as_checkboxes']['strings']['nested'].first.original_filename
    assert_equal 'foo.jpg', params['products']['file'].original_filename

    # But full Windows paths are reduced to their basename.
    assert_equal 'bar.jpg', params['ie_mixed_types_as_checkboxes']['strings']['nested'].first.original_filename
    assert_equal 'bar.jpg', params['ie_products']['file'].original_filename
  end

  def test_parse_params_with_file
    input = {
      "customers[boston][first][name]" => [ "David" ],
      "something_else" => [ "blah" ],
      "logo" => [ File.new(File.dirname(__FILE__) + "/cgi_test.rb").path ]
    }

    expected_output = {
      "customers" => {
        "boston" => {
          "first" => {
            "name" => "David"
          }
        }
      },
      "something_else" => "blah",
      "logo" => File.new(File.dirname(__FILE__) + "/cgi_test.rb").path,
    }

    assert_equal expected_output, ActionController::AbstractRequest.parse_request_parameters(input)
  end

  def test_parse_params_with_array
    input = { "selected[]" =>  [ "1", "2", "3" ] }

    expected_output = { "selected" => [ "1", "2", "3" ] }

    assert_equal expected_output, ActionController::AbstractRequest.parse_request_parameters(input)
  end

  def test_parse_params_with_non_alphanumeric_name
    input     = { "a/b[c]" =>  %w(d) }
    expected  = { "a/b" => { "c" => "d" }}
    assert_equal expected, ActionController::AbstractRequest.parse_request_parameters(input)
  end

  def test_parse_params_with_single_brackets_in_middle
    input     = { "a/b[c]d" =>  %w(e) }
    expected  = { "a/b" => {} }
    assert_equal expected, ActionController::AbstractRequest.parse_request_parameters(input)
  end

  def test_parse_params_with_separated_brackets
    input     = { "a/b@[c]d[e]" =>  %w(f) }
    expected  = { "a/b@" => { }}
    assert_equal expected, ActionController::AbstractRequest.parse_request_parameters(input)
  end

  def test_parse_params_with_separated_brackets_and_array
    input     = { "a/b@[c]d[e][]" =>  %w(f) }
    expected  = { "a/b@" => { }}
    assert_equal expected , ActionController::AbstractRequest.parse_request_parameters(input)
  end

  def test_parse_params_with_unmatched_brackets_and_array
    input     = { "a/b@[c][d[e][]" =>  %w(f) }
    expected  = { "a/b@" => { "c" => { }}}
    assert_equal expected, ActionController::AbstractRequest.parse_request_parameters(input)
  end

  def test_parse_params_with_nil_key
    input = { nil => nil, "test2" => %w(value1) }
    expected = { "test2" => "value1" }
    assert_equal expected, ActionController::AbstractRequest.parse_request_parameters(input)
  end
end


class MultipartRequestParameterParsingTest < Test::Unit::TestCase
  FIXTURE_PATH = File.dirname(__FILE__) + '/../fixtures/multipart'

  def test_single_parameter
    params = process('single_parameter')
    assert_equal({ 'foo' => 'bar' }, params)
  end

  def test_bracketed_param
    assert_equal({ 'foo' => { 'baz' => 'bar'}}, process('bracketed_param'))
  end

  def test_text_file
    params = process('text_file')
    assert_equal %w(file foo), params.keys.sort
    assert_equal 'bar', params['foo']

    file = params['file']
    assert_kind_of StringIO, file
    assert_equal 'file.txt', file.original_filename
    assert_equal "text/plain", file.content_type
    assert_equal 'contents', file.read
  end

  def test_large_text_file
    params = process('large_text_file')
    assert_equal %w(file foo), params.keys.sort
    assert_equal 'bar', params['foo']

    file = params['file']
    assert_kind_of Tempfile, file
    assert_equal 'file.txt', file.original_filename
    assert_equal "text/plain", file.content_type
    assert ('a' * 20480) == file.read
  end

  uses_mocha "test_no_rewind_stream" do
    def test_no_rewind_stream
      # Ensures that parse_multipart_form_parameters works with streams that cannot be rewound
      file = File.open(File.join(FIXTURE_PATH, 'large_text_file'), 'rb')
      file.expects(:rewind).raises(Errno::ESPIPE)
      params = ActionController::AbstractRequest.parse_multipart_form_parameters(file, 'AaB03x', file.stat.size, {})
      assert_not_equal 0, file.pos  # file was not rewound after reading
    end
  end

  def test_binary_file
    params = process('binary_file')
    assert_equal %w(file flowers foo), params.keys.sort
    assert_equal 'bar', params['foo']

    file = params['file']
    assert_kind_of StringIO, file
    assert_equal 'file.csv', file.original_filename
    assert_nil file.content_type
    assert_equal 'contents', file.read

    file = params['flowers']
    assert_kind_of StringIO, file
    assert_equal 'flowers.jpg', file.original_filename
    assert_equal "image/jpeg", file.content_type
    assert_equal 19512, file.size
    #assert_equal File.read(File.dirname(__FILE__) + '/../../../activerecord/test/fixtures/flowers.jpg'), file.read
  end

  def test_mixed_files
    params = process('mixed_files')
    assert_equal %w(files foo), params.keys.sort
    assert_equal 'bar', params['foo']

    # Ruby CGI doesn't handle multipart/mixed for us.
    assert_kind_of String, params['files']
    assert_equal 19756, params['files'].size
  end

  private
    def process(name)
      File.open(File.join(FIXTURE_PATH, name), 'rb') do |file|
        params = ActionController::AbstractRequest.parse_multipart_form_parameters(file, 'AaB03x', file.stat.size, {})
        assert_equal 0, file.pos  # file was rewound after reading
        params
      end
    end
end


class XmlParamsParsingTest < Test::Unit::TestCase
  def test_single_file
    person = parse_body("<person><name>David</name><avatar type='file' name='me.jpg' content_type='image/jpg'>#{Base64.encode64('ABC')}</avatar></person>")

    assert_equal "image/jpg", person['person']['avatar'].content_type
    assert_equal "me.jpg", person['person']['avatar'].original_filename
    assert_equal "ABC", person['person']['avatar'].read
  end

  def test_multiple_files
    person = parse_body(<<-end_body)
      <person>
        <name>David</name>
        <avatars>
          <avatar type='file' name='me.jpg' content_type='image/jpg'>#{Base64.encode64('ABC')}</avatar>
          <avatar type='file' name='you.gif' content_type='image/gif'>#{Base64.encode64('DEF')}</avatar>
        </avatars>
      </person>
    end_body

    assert_equal "image/jpg", person['person']['avatars']['avatar'].first.content_type
    assert_equal "me.jpg", person['person']['avatars']['avatar'].first.original_filename
    assert_equal "ABC", person['person']['avatars']['avatar'].first.read

    assert_equal "image/gif", person['person']['avatars']['avatar'].last.content_type
    assert_equal "you.gif", person['person']['avatars']['avatar'].last.original_filename
    assert_equal "DEF", person['person']['avatars']['avatar'].last.read
  end

  private
    def parse_body(body)
      env = { 'CONTENT_TYPE'   => 'application/xml',
              'CONTENT_LENGTH' => body.size.to_s }
      cgi = ActionController::Integration::Session::StubCGI.new(env, body)
      ActionController::CgiRequest.new(cgi).request_parameters
    end
end

class LegacyXmlParamsParsingTest < XmlParamsParsingTest
  private
    def parse_body(body)
      env = { 'HTTP_X_POST_DATA_FORMAT' => 'xml',
              'CONTENT_LENGTH' => body.size.to_s }
      cgi = ActionController::Integration::Session::StubCGI.new(env, body)
      ActionController::CgiRequest.new(cgi).request_parameters
    end
end
*/
