<?php
/*

  This file is part of a child theme called Facility Flooring.
  Functions in this file will be loaded before the parent theme's functions.
  For more information, please read
  https://developer.wordpress.org/themes/advanced-topics/child-themes/

*/

// Loads the parent stylesheet — leave this in place unless you know what you're doing.
function your_theme_enqueue_styles() {

    $parent_style = 'parent-style';

    wp_enqueue_style( $parent_style,
        get_template_directory_uri() . '/style.css'
    );

    wp_enqueue_style( 'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( $parent_style ),
        wp_get_theme()->get( 'Version' )
    );
}
add_action( 'wp_enqueue_scripts', 'your_theme_enqueue_styles' );

/*  Add your own functions below this line.
    ======================================== */

