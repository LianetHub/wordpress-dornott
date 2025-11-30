<?php 

//add option page
if( function_exists('acf_add_options_page') ) {
	
	acf_add_options_page(array(
		'page_title' 	=> 'Настройки темы',
		'menu_title'	=> 'Настройки темы',
		'menu_slug' 	=> 'theme-general-settings',
		'capability'	=> 'edit_posts',
		'redirect'		=> false
	));
	
	// acf_add_options_page(array(
	// 	'page_title' 	=> 'Глобальные секции',
	// 	'menu_title'	=> 'Глобальные секции',
	// 	'menu_slug' 	=> 'theme-general-settings2',
	// 	'capability'	=> 'edit_posts',
	// 	'redirect'		=> false
	// ));
	
	// acf_add_options_sub_page(array(
	// 	'page_title' 	=> 'dde',
	// 	'menu_title'	=> 'Хедdedер',
	// 	'parent_slug'	=> 'theme-general-settings2',
	// ));
}


function my_acf_admin_head() {
?>
		<style type="text/css">

	h2.hndle.ui-sortable-handle {
			background: #cfa144;
			color: #fff!important;
			-webkit-transition: all 0.25s;
			-o-transition: all 0.25s;
			transition: all 0.25s;
	}

	.acf-field.acf-accordion .acf-label.acf-accordion-title {
    background: #EBE9F5;
    transition: all 0.25s;
	}
	.acf-accordion .acf-accordion-title label {
	    text-transform: uppercase;
	    color: #000;
	}
	.acf-field p.description{
		color: #ffa500;
	}

	.acf-field-group {
       border: 1px solid #282D41!important;
	}


</style>
<?php
}

add_action('acf/input/admin_head', 'my_acf_admin_head');