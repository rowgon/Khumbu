<?php
/**
 * Hello Elementor Child Theme functions and definitions
 */

add_action( 'wp_enqueue_scripts', function() {
    // 1. Google Fonts: Poppins (Display), Montserrat (Body), Playfair Display (Serif Italic)
    wp_enqueue_style( 'khumbu-google-fonts', 'https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Montserrat:wght@300;400;500;600&family=Playfair+Display:ital,wght@1,400&display=swap', array(), null );
    
    // 2. Child Theme Stylesheets
    wp_enqueue_style( 'hello-elementor-child-style', get_stylesheet_uri(), array( 'hello-elementor' ) );
    wp_enqueue_style( 'khumbu-tokens', get_stylesheet_directory_uri() . '/css/tokens.css', array( 'khumbu-google-fonts' ), '1.0.0' );
    wp_enqueue_style( 'khumbu-styles', get_stylesheet_directory_uri() . '/css/styles.css', array( 'khumbu-tokens' ), '1.0.0' );
} );
