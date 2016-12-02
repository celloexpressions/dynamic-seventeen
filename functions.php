<?php
/**
 * Dynamic Seventeen theme functions.
 */

// Register additional menu locations for dynamic content menus.
// Note: this is unlikely to work seamlessly if/when it's possible to change a page template in the customizer.
function dynamic_seventeen_after_setup_theme() {
	// Get all pages with the template, and make a manu location for each.
	$pages_query = new WP_Query( array( 
		'post_type' => 'page',
		'post_status' => 'publish',
		'meta_query' => array(
			array (
				'key' => '_wp_page_template',
				'value' => 'template-page-dynamic.php',
			)
		),
	) );

	// Always add a location for the front page.
	$dynamic_pages = array(
		'front_page_content' => __( 'Front Page Content', 'dynamic-seventeen' ),
	);
	if ( $pages_query->have_posts() ) {
		while ( $pages_query->have_posts() ) {
			$pages_query->the_post();
			/* translators: %s is the post title for the menu location name */
			$dynamic_pages[get_the_ID() . '_content'] = sprintf( __( '%s Content', 'dynamic-seventeen' ), get_the_title() );
			wp_reset_postdata();
		}
	}

	register_nav_menus( $dynamic_pages );
}
add_action( 'after_setup_theme', 'dynamic_seventeen_after_setup_theme' );

/**
 * Template tag for displaying a dynamic content menu.
 *
 * @param string $location Dynamic content location, without the _content menu location suffix.
 */
function dynamic_seventeen_content_menu( $location ) {
	// Dynamic front page content.
	$walker = new Dynamic_Seventeen_Content_Menu_Walker();
	wp_nav_menu( array(
		'depth' => 0,
		'walker' => $walker,
		'theme_location' => $location . '_content',
		'items_wrap' => '<div id="%1$s" class="%2$s content-menu">%3$s</div>',
	));
}

// Customizer stuff.
function dynamic_seventeen_customize_register( $wp_customize ) {

	/**
	 * Filter number of front page sections in Twenty Seventeen.
	 *
	 * @since Twenty Seventeen 1.0
	 *
	 * @param $num_sections int
	 */
	$num_sections = apply_filters( 'twentyseventeen_front_page_sections', 4 );

	// Remove front page section options in the customizer.
	for ( $i = 1; $i < ( 1 + $num_sections ); $i++ ) {
		$wp_customize->remove_control( 'panel_' . $i );
	}
	
	// Option for number of posts in dynamic pages.
	$wp_customize->add_setting( 'dynamic_content_num_posts', array(
		'default' => 3,
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( 'dynamic_content_num_posts', array(
		'label' => __( 'Dynamic Content Number of Posts', 'dynamic-seventeen' ),
		'description' => __( 'Used for tags, categories, and post type archived on dynamic pages.', 'dynamic-seventeen' ),
		'type' => 'number',
		'section' => 'theme_options',
		'active_callback' => 'dynamic_seventeen_option_active_callback',
	) );
}
add_action( 'customize_register', 'dynamic_seventeen_customize_register', 11 ); // After Twenty Seventeen.

// Active callback for dynamic content options.
function dynamic_seventeen_option_active_callback() {
	return is_front_page() || is_page_template( 'template-page-dynamic.php' );
}

// Add child theme selectors for color schemes.
function dynamic_seventeen_custom_colors_css( $css, $hue, $saturation ) {
	$css .= '
	.colors-custom .content-menu > article:not(.has-post-thumbnail),
	.colors-custom .content-menu > section:not(.has-post-thumbnail) {
		border-top-color: hsl( ' . $hue . ', ' . $saturation . ', 87% ); /* base: #ddd; */
	}';
	return $css;
}
add_filter( 'twentyseventeen_custom_colors_css', 'dynamic_seventeen_custom_colors_css', 10, 3 );

require_once( 'class-dynamic-seventeen-content-menu-walker.php' );
