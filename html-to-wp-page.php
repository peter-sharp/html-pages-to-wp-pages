<?php
/*
Plugin Name: HTML To Wordpress Page
 */
namespace Html_WpPage;

require_once( 'vendor/autoload.php' );
require('includes/utils.php');

use Sunra\PhpSimple\HtmlDomParser as HtmlDomParser;



add_action( 'init', __NAMESPACE__."\init" );
add_action( 'admin_menu', __NAMESPACE__.'\create_menu_page' );
define('INPUT_DIR', '/uploads/html_to_wp_pages');

function init() {

  convert_to_wp_pages ( );
}



function create_menu_page() {
  \add_menu_page( 'Html to Wp Page Options',
   'Html to Wp Page Options',
    'manage_options'
    , 'html-to-wp',
     __NAMESPACE__.'\render_page',
      '',
       null );
}

function render_page() {
  include('templates/menu_page.php');
}

function convert_to_wp_pages ( ) {
  if( $_POST['start_converting_html'] === "TRUE" ) {
    $input_dir = plugin_dir_path(__FILE__).'../..'.INPUT_DIR;


    $htmlToWpPagesConverter = new HtmlToWpPagesConverter( $input_dir );
    $htmlToWpPagesConverter->convert();
  }
}

class HtmlToWpPagesConverter {
  public    $input_dir = "./";

  protected $file_names;
  protected $ignore_exts = array('.deleted');
  protected $target_exts = array( '.htm', '.html');
  protected $space_placeholders = array( '+');

  protected $content_selector = '.document';

  function __construct ( $input_dir = NULL){
      $this->input_dir = ( $input_dir ) ? $input_dir : $this->input_dir;

  }

  public function convert() {
    $this->file_names = $this->get_file_list( $this->input_dir);
    $this->conversion_loop( );
  }

  protected function conversion_loop( ){

    foreach( $this->file_names as $file_name ) {
      if( $this->is_accepted_file_type( $file_name ) ) {
        $file_data = $this->read_file( $file_name );

        $wp_page_name = $this->get_page_name( $file_name );
        $page_content = $this->get_page_content( $file_data );
        $this->add_page( $wp_page_name, $page_content );
      }
    }
  }

  protected function is_accepted_file_type( $file_name ) {
    return \strposa( $file_name, $this->target_exts );
  }

  protected function read_file( $file_name ) {
    return file_get_contents( $this->input_dir.'/'.$file_name);
  }

  protected function get_page_content( $file_data ) {
    $dom = HtmlDomParser::str_get_html( $file_data );
    $page_content = $dom->find( $this->content_selector, 0 )->innertext;
    return $page_content;
  }

  protected function get_page_name( $file_name ) {


    $wp_page_name = pathinfo( $file_name, PATHINFO_FILENAME);
    $wp_page_name = $this->replace_placeholders_with_spaces( $wp_page_name );
    return $wp_page_name;
  }

  protected function replace_placeholders_with_spaces( $file_name ) {
    return str_replace( $this->space_placeholders, ' ', $file_name );
  }

  protected function get_file_list( $input_dir ) {
    return scandir( $input_dir );
  }

  protected function add_page( $title, $content ) {
    $page = array(
      'post_type' => 'page',
      'post_title' => $title,
      'post_content' => $content,
      'post_status' => 'publish'
    );

    wp_insert_post ( $page, TRUE);
  }

}
