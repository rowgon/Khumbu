<?php
/**
 * The template for displaying all single posts.
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

get_header();
?>
<main class="site-main post-single" role="main" style="padding-top: 140px !important; background-color: #0a0916;">
	<?php
	while ( have_posts() ) :
		the_post();
		
		// Get categories
		$category_name = 'Artículo';
		$post_cats = get_the_category();
		if ( ! empty( $post_cats ) ) {
			$category_name = $post_cats[0]->name;
		}
		?>
		
		<!-- Post Hero -->
		<div class="post-single__hero">
			<div class="container">
				<div class="post-single__meta reveal">
					<span class="chip"><i class="i-accent"></i> <?php echo esc_html($category_name); ?></span>
					<span class="post-single__date"><?php echo get_the_date(); ?></span>
				</div>
				<h1 class="display reveal" style="margin-top: 20px;"><?php the_title(); ?></h1>
				
				<?php 
				$author = get_the_author();
				if ($author) : ?>
					<p class="post-single__author reveal">Escrito por <strong><?php echo esc_html($author); ?></strong></p>
				<?php endif; ?>
			</div>
		</div>

		<!-- Featured Image -->
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="post-single__thumbnail reveal">
				<?php the_post_thumbnail('full'); ?>
			</div>
		<?php endif; ?>

		<!-- Post Content -->
		<div class="container container--narrow">
			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?> style="display: block !important; width: 100%;">
				<div class="khumbu-post-content reveal" style="display: block !important; width: 100%;">
					<?php the_content(); ?>
				</div>
				
				<div class="post-single__footer">
					<a href="/blog/" class="ghost-cta"><span class="arrow" aria-hidden="true">←</span> Volver al Blog</a>
				</div>
			</article>
		</div>

	<?php endwhile; ?>
</main>

<?php
get_footer();
