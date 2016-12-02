<?php
/**
 * Create HTML list of nav menu items.
 *
 * This custom walker displays the associated post content for each post menu item, 
 * and posts within terms for term items. The markup is specific to Twenty Seventeen, 
 * but the functionality can be easily adapted for use in other themes.
 *
 * @since Dynamic Seventeen 1.0
 * @uses Walker
 */
class Dynamic_Seventeen_Content_Menu_Walker extends Walker_Nav_Menu {
	
	function start_lvl( &$output, $depth = 0, $args = array() ) {
		$output .= '<div class="dynamic-content-container">';
	}
	
	function end_lvl( &$output, $depth = 0, $args = array() ) {
		$output .= '</div>';
	}

	/**
	 * Start the element output.
	 *
	 * @see Nav_Menu_Walker::start_el()
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $item   Menu item data object.
	 * @param int    $depth  Depth of menu item. Used for padding.
	 * @param array  $args   An array of arguments. @see wp_nav_menu()
	 * @param int    $id     Current item ID.
	 */
	 public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {

		if ( 0 !== $depth ) {
			return; // Only top-level items are supported.
		}

		if ( 'post_type' === $item->type ) {
			$output .= $this->single_post_content( $item );
		} elseif ( 'taxonomy' === $item->type ) {
			$output .= $this->taxonomy_term_content( $item );
		} elseif ( 'post_type_archive' === $item->type ) {
			$output .= $this->post_type_archive_content( $item );
		} elseif ( 'custom' === $item->type ) {
			$output .= $this->custom_link_content( $item );
		} else {
			// Do an action so that plugins can hook in here if they support custom menu item object types.
			do_action( 'dynamic_content_menu_item_' . $item->type );
		}
	}

	/**
	 * Ends the element output.
	 *
	 * @see Walker::end_el()
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $item   Page data object. Not used.
	 * @param int    $depth  Depth of page. Not Used.
	 * @param array  $args   An array of arguments. @see wp_nav_menu()
	 */
	public function end_el( &$output, $item, $depth = 0, $args = array() ) {
		// This walker doesn't support nesting, so the end is included with start_el.
		return;
	}
	

	/**
	 * Return the markup for a custom link item.
	 *
	 * @param WP_Nav_Menu_Item $item The nav menu item to display the content for.
	 */
	public function custom_link_content( $item ) {

		/** This filter is documented in wp-includes/post-template.php */
		$title = apply_filters( 'the_title', $item->title, $item->ID );

		ob_start(); ?>

		<article id="<?php echo 'nav-menu-item-' . $item->id; ?>">

			<div class="panel-content">
				<div class="wrap">
					<header class="entry-header">
						<h2 class="entry-title"><a href="<?php echo esc_url( $item->url ); ?>"><?php echo $title; ?></a></h2>
					</header><!-- .entry-header -->

					<div class="entry-content">
						<?php if ( '' !== $item->description ) {
							echo apply_filters( 'the_content', $item->description );
						} ?>
					</div><!-- .entry-content -->
				</div>
			</div>

		</article>
		<?php
		return ob_get_clean();
	}

	/**
	 * Return the markup for a post's content.
	 *
	 * @param WP_Nav_Menu_Item $item The nav menu item to display the content for.
	 */
	public function single_post_content( $item ) {
		$post = get_post( $item->object_id );
		if ( $post ) {

			/** This filter is documented in wp-includes/post-template.php */
			$title = apply_filters( 'the_title', $item->title, $item->ID );

			setup_postdata( $post );
			ob_start(); ?>

			<article id="<?php echo 'nav-menu-item-' . $item->ID; ?>" <?php post_class('', $post ); ?>>

				<?php
				if ( has_post_thumbnail( $post ) ) :
					$thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'twentyseventeen-featured-image' );

					// Calculate aspect ratio: h / w * 100%.
					$ratio = $thumbnail[2] / $thumbnail[1] * 100;
					?>

					<div class="panel-image" style="background-image: url(<?php echo esc_url( $thumbnail[0] ); ?>);">
						<div class="panel-image-prop" style="padding-top: <?php echo esc_attr( $ratio ); ?>%"></div>
					</div><!-- .panel-image -->

				<?php endif; ?>

				<div class="panel-content">
					<div class="wrap">
						<header class="entry-header">
							<h2 class="entry-title"><a href="<?php echo esc_url( $item->url ); ?>"><?php echo $title; ?></a></h2>
							<?php if ( '' !== $item->description ) {
								echo '<p class="dynamic-content-item-description">' . $item->description . '</p>';
							} ?>
						</header><!-- .entry-header -->

						<div class="entry-content">
							<?php
								/* translators: %s: Name of current post */
								the_content( sprintf(
									__( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'twentyseventeen' ), // Identical to parent theme string.
									get_the_title()
								) );
							?>
						</div><!-- .entry-content -->

						<?php wp_reset_postdata();

						// This part is straight from Twenty Seventeen.
						// Show recent blog posts if is blog posts page (Note that get_option returns a string, so we're casting the result as an int).
						if ( get_the_ID() === (int) get_option( 'page_for_posts' )  ) : ?>

							<?php // Show four most recent posts.
							$recent_posts = new WP_Query( array(
								'posts_per_page'      => absint( get_theme_mod( 'dynamic_content_num_posts', 3 ) ),
								'post_status'         => 'publish',
								'ignore_sticky_posts' => true,
								'no_found_rows'       => true,
							) );
							?>

					 		<?php if ( $recent_posts->have_posts() ) : ?>

								<div class="recent-posts">

									<?php
									while ( $recent_posts->have_posts() ) : $recent_posts->the_post();
										get_template_part( 'template-parts/post/content', 'excerpt' );
									endwhile;
									wp_reset_postdata();
									?>
								</div><!-- .recent-posts -->
							<?php endif; ?>
						<?php endif;?>
					</div>
				</div>
			</article>
			<?php
			return ob_get_clean();
		}
		return false;
	}

	/**
	 * Return the markup for a term's content.
	 *
	 * @param WP_Nav_Menu_Item $item The nav menu item to display the content for.
	 */
	public function taxonomy_term_content( $item ) {
		$term_id = $item->object_id;
		$taxonomy = $item->object;

		$term = get_term( $term_id, $taxonomy );
		if ( $term ) {
			// Query for tutorials in this tag.
			$posts = get_posts( array(
				'numberposts'      => absint( get_theme_mod( 'dynamic_content_num_posts', 3 ) ),
				'suppress_filters' => false,
				'post_type'        => 'any', // Important to ensure maximum compatibility with custom post types & taxononmies.
				'tax_query'        => array(
					array(
						'field'    => 'term_id',
						'taxonomy' => $taxonomy,
						'terms'    => $term->term_id,
					),
				),
			) );
			if ( ! empty ( $posts ) ) {
				return $this->dynamic_archive_content( $posts, $item );
			}
		}
		return false;
	}

	/**
	 * Return the markup for a post type archive's content.
	 *
	 * @param WP_Nav_Menu_Item $item The nav menu item to display the content for.
	 */
	public function post_type_archive_content( $item ) {
		if ( post_type_exists( $item->object ) ) {
			// Query for tutorials in this tag.
			$posts = get_posts( array(
				'numberposts'      => absint( get_theme_mod( 'dynamic_content_num_posts', 3 ) ),
				'suppress_filters' => false,
				'post_type'        => $item->object,
			) );
			if ( ! empty ( $posts ) ) {
				$this->dynamic_archive_content( $posts, $item );
			}
		}
		return false;
	}

	/**
	 * Return the markup for an archive's content.
	 *
	 * @param Array            $posts Array of WP_Post objects to display.
	 * @param WP_Nav_Menu_Item $item  Nav Menu Item associated with the archive being displayed.
	 */
	public function dynamic_archive_content( $posts, $item ) {
		global $post;

		/** This filter is documented in wp-includes/post-template.php */
		$title = apply_filters( 'the_title', $item->title, $item->ID );
		ob_start(); ?>

		<section id="<?php echo 'nav-menu-item-' . $item->ID; ?>" class="archive object-<?php echo $item->object . ' type-' . $item->type; ?>">

			<?php // Find the first post with a featured image in this query, and show the image.
			$img_post = $posts[0];
			$i = 0;
			while ( ! has_post_thumbnail( $img_post ) && $i < count( $posts ) ) {
				$img_post = $posts[$i];
				$i++;
			}
			if ( has_post_thumbnail( $img_post ) ) :
				$thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $img_post->ID ), 'twentyseventeen-featured-image' );

				// Calculate aspect ratio: h / w * 100%.
				$ratio = $thumbnail[2] / $thumbnail[1] * 100;
				?>

				<div class="panel-image" style="background-image: url(<?php echo esc_url( $thumbnail[0] ); ?>);">
					<div class="panel-image-prop" style="padding-top: <?php echo esc_attr( $ratio ); ?>%"></div>
				</div><!-- .panel-image -->

			<?php endif; ?>

			<div class="panel-content">
				<div class="wrap">
					<header class="entry-header">
						<h2 class="entry-title"><a href="<?php echo esc_url( $item->url ); ?>"><?php echo $title; ?></a></h2>
						<?php if ( '' !== $item->description ) {
							echo '<p class="dynamic-content-item-description">' . $item->description . '</p>';
						} elseif ( 'taxonomy' === $item->type && '' !== term_description( $item->object_id, $item->object ) ) {
							echo '<p class="dynamic-content-item-description">' . term_description( $item->object_id, $item->object ) . '</p>';
						} ?>
					</header><!-- .entry-header -->

					<div class="recent-posts">
						<?php foreach( $posts as $post ) {
							setup_postdata( $post ); // Allows the_* functions to work without passing an ID.

							get_template_part( 'template-parts/post/content', 'excerpt' ); // Use the base theme template part here since we can.

							wp_reset_postdata();
						} ?>
					</div><!-- .recent-posts -->
				</div>
			</div>
		</section>
		<?php
		return ob_get_clean();
	} // Function.
} // Class.
