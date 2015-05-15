<?php
/**
 * The template used for displaying page content in page.php
 *
 * @package Oran
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		
	</header><!-- .entry-header -->

	<div class="entry-content">
		<?php the_content(); ?>
		<?php
			wp_link_pages( array(
				'before' => '<div class="page-links">' . __( 'Pages:', 'oran' ),
				'after'  => '</div>',
			) );
		?>
	</div><!-- .entry-content -->

	<section class="pre-footer">
		<div class="container">
			<div class="row">
				<div class="col-md-6">
					<h3>We Are Oran Construction Company</h3>
					
					<div class="col-md-5 nopadding">
						<img class="img-repsonsive thumbnail" src="<?php echo get_bloginfo('template_directory');?>/images/oran-construction.jpg" title="Oran Construction Company" alt="Oran Construction Company"/>

					</div>
					<div class="col-md-7">
						<p>Residential and Commercial construction services by Oran Construction Company located here in San Antonio Texas. We specialize in General Construction.</p>
						
						<p><a href="#">Learn More About Us</a></p>
					</div>
				</div>
				<div class="col-md-5 col-sm-offset-1">
					
					<h3>News &amp; Events</h3>
					
						<?php 
							// the query
							
							$args = array(
								'post_type' => 'post'
							);
							
							$the_query = new WP_Query( $args ); ?>
							
							<?php if ( $the_query->have_posts() ) : ?>
							
								<!-- pagination here -->
							
								<!-- the loop -->
								<?php while ( $the_query->have_posts() ) : $the_query->the_post(); ?>
									
									<div class="news">
										<span class="date"><?php the_date(); ?></span>
										<h2><a href="<?php the_permalink();?>"><?php the_title(); ?></a></h2>
									</div>
									
								<?php endwhile; ?>
								<!-- end of the loop -->
							
								<!-- pagination here -->
							
								<?php wp_reset_postdata(); ?>
							
							<?php else : ?>
								<p><?php _e( 'Sorry, no posts matched your criteria.' ); ?></p>
							<?php endif; ?>
					
				</div>
			</div>
			
		</div>
	</section>

	<footer class="entry-footer">
	</footer><!-- .entry-footer -->
</article><!-- #post-## -->