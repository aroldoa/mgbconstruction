<?php
/**
 *
 	Template Name: Full Width
 *
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site will use a
 * different template.
 *
 * @package Oran
 */

get_header(); ?>

<section class="pre-intro">

	<div class="container">
		<div class="row">
			<div class="col-sm-8 col-md-offset-2">
				<h1><?php the_title(); ?></h1>
				<p><?php the_excerpt();?></p>
			</div>
			<div class="col-sm-4"></div>
		</div>
	<div>

</section>


<div class="container">

	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">
					<?php while ( have_posts() ) : the_post(); ?>
		
						<?php get_template_part( 'content', 'page' ); ?>
		
						<?php
							// If comments are open or we have at least one comment, load up the comment template
							if ( comments_open() || get_comments_number() ) :
								comments_template();
							endif;
						?>
		
					<?php endwhile; // end of the loop. ?>
		</main><!-- #main -->
	</div><!-- #primary -->
</div>	

<?php get_footer(); ?>
