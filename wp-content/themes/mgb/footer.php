<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after
 *
 * @package Oran
 */
?>

	</div><!-- #content -->
	
	<section class="quote">
		
		<div class="container">
			
			<div class="row">
				
				<h4>Have a Project in Mind?</h4>
				<p>Oran Construction would be more than happy to sit down with you and bring your plan into reality. <br/> Fill out our consultation form below for your free construction consult.</p>
				<a href="http://dev.primomotif.com/sites/oranconstruction/contact-us/"><input type="submit" class="button" value="Schedule My Consult"/></a>
				
			</div>
			
		</div>
		
	</section>

	<footer id="colophon" class="site-footer" role="contentinfo">
		
		<div class="site-info container">
			
			<div class="col-md-4">
				Copyright 2015, Oncrete Art, Inc.
			</div>
			<div class="col-md-6">
				
				<?php

					$defaults = array(
						'theme_location'  => '',
						'menu'            => '',
						'container'       => 'div',
						'container_class' => '',
						'container_id'    => '',
						'menu_class'      => 'menu',
						'menu_id'         => '',
						'echo'            => true,
						'fallback_cb'     => 'wp_page_menu',
						'before'          => '',
						'after'           => '',
						'link_before'     => '',
						'link_after'      => '',
						'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
						'depth'           => 0,
						'walker'          => ''
					);
					
					wp_nav_menu( $defaults );
					
				?>
				
				
			</div>
			<div class="col-md-2">
				<img src="<?php echo get_bloginfo('template_directory');?>/images/san-antonio-web-design.png" title="San Antonio Website Design" alt="San Antonio Website Design Company" align="right"/>
			</div>
			
		</div><!-- .site-info -->
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
