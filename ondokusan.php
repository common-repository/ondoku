<?php
/*
Plugin Name: text-to-speech Ondoku
Description: Create an audio file that automatically reads the text aloud when posting a blog, and insert it with an HTML tag at the beginning of the blog.
Author: Ondoku
Version: 1.0.21
Text Domain: ondoku3
Domain Path: /languages/
*/

if ( ! defined( 'ABSPATH' ) )
	exit;

define( 'ONDOKUSAN', __FILE__ );
define( 'ONDOKUSAN_DIR', untrailingslashit( dirname( __FILE__) ) );
define( 'ONDOKUSAN_URL', untrailingslashit( plugins_url( '', __FILE__ ) ) );
define( 'ONDOKUSAN_API', 'https://ondoku3.com/ja/text_to_speech_api/' );

require_once( ONDOKUSAN_DIR . '/classes/core.php' );
require_once( ONDOKUSAN_DIR . '/classes/setting.php' );
require_once( ONDOKUSAN_DIR . '/classes/hooks.php' );

new ONDOKUSAN();
