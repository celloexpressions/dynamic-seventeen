<?php
/**
 * Template Name: Dynamic Content
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @subpackage Dynamic_Seventeen
 * @since 1.0
 * @version 1.0
 */

get_header(); ?>

<div id="primary" class="content-area">
	<main id="main" class="site-main" role="main">

		<?php // Show the static page content.
		if ( have_posts() ) :
			while ( have_posts() ) : the_post();
				get_template_part( 'template-parts/content', 'dynamic-page' );
			endwhile;
		else :
			get_template_part( 'template-parts/post/content', 'none' );
		endif; ?>

		<?php dynamic_seventeen_content_menu( get_the_ID() ); ?>

	</main><!-- #main -->
</div><!-- #primary -->

<?php get_footer();
