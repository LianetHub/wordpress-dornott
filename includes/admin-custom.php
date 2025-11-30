<?php
/* Customize Admin Panel */
class Wptuts_Simple_Admin {
    function __construct() {
		// Hook onto the action 'admin_menu' for our function to remove menu items
		add_action( 'admin_menu', array( $this, 'remove_menus' ) );
		// Hook Dashboard Widgets
		add_action( 'wp_dashboard_setup', array( $this, 'remove_dashboard_widget' ) );
		// Hook onto the action 'admin_bar'
		add_action( 'wp_before_admin_bar_render', array( $this, 'my_admin_bar_render' ) );
		// CUSTOM ADMIN LOGIN HEADER LOGO
		add_action( 'login_head',  array( $this, 'my_custom_login_logo' ) );	
		// CUSTOM ADMIN LOGIN LOGO LINK		
		add_filter( 'login_headerurl', array( $this, 'change_wp_login_url' ) );
		// CUSTOM ADMIN LOGIN LOGO & ALT TEXT
		add_filter( 'login_headertext', array ( $this, 'change_wp_login_title' ) );
		// Custom css: ACF menu
		add_action( 'admin_head', array( $this, 'custom_css' ) );
    }
	
    // This function removes each menu item
    function remove_menus() {
		global $submenu;		
		unset($submenu['themes.php'][6]); // Customize
        remove_menu_page('edit-comments.php');		
    }
	
	function remove_dashboard_widget() {
		remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
		remove_action('welcome_panel', 'wp_welcome_panel');				
	}

	function my_admin_bar_render() {
		global $wp_admin_bar;
		$wp_admin_bar->remove_menu('comments');
		$wp_admin_bar->remove_menu('wp-logo');
	}
	
	function custom_css() {
		$css = "<style>";
      $css .= "#toplevel_page_theme-general-settings .wp-menu-name,#toplevel_page_theme-general-settings2 .wp-menu-name{ background: #0022aa; } #toplevel_page_theme-general-settings:hover .wp-menu-name{ background: #111; }";
			//$css .= "#adminmenu { transform: translateZ(0); }"; // fix bag Google Chrome
			//$css .= ".taxonomy-service_category #wp-description-wrap { display: none; }"; // fix bag Google Chrome
		$css .= "</style>";
		echo $css;
	}
	
	function my_custom_login_logo() {
		echo '
				<style type="text/css">
				    .login h1 a {
				        background: url('. get_bloginfo('template_directory') .'/assets/img/logo.svg) #fff center no-repeat !important;
				        background-size: 55%!important;
				        width: 312px!important;
				        height: 110px!important;
				    }
				</style>
		';
	}	

	function change_wp_login_url() {
		return home_url();
	}
	
	function change_wp_login_title() {
		return get_option('blogname');
	}
}
$wptuts_simple_admin = new Wptuts_Simple_Admin();

remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'feed_links_extra', 3);
remove_action('wp_head', 'feed_links', 2);
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'dns-prefetch', 11);
remove_action('wp_head', 'rest_output_link_wp_head');
remove_action('wp_head', 'wp_oembed_add_discovery_links');
remove_action('wp_head', 'rest_output_link_header', 11, 0);
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
add_filter('emoji_svg_url', '__return_false');


add_filter('wpcf7_autop_or_not', '__return_false'); 