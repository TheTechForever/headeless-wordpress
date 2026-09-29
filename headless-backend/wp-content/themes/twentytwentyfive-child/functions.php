<?php
// Enqueue parent + child stylesheets.
add_action( 'wp_enqueue_scripts', function () {
	$parent = 'parent-style';
	wp_enqueue_style( $parent, get_template_directory_uri() . '/style.css' );
	wp_enqueue_style( 'child-style', get_stylesheet_uri(), array( $parent ), wp_get_theme()->get( 'Version' ) );
} );
