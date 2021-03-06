<?php
/**
 * Shortcode class.
 *
 * @since 1.0.0
 *
 * @package Envira_Gallery
 * @author  Thomas Griffin
 */
class Envira_Gallery_Shortcode {

    /**
     * Holds the class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public static $instance;

    /**
     * Path to the file.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $file = __FILE__;

    /**
     * Holds the base class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public $base;

    /**
     * Holds the gallery data.
     *
     * @since 1.0.0
     *
     * @var array
     */
    public $data;

    /**
     * Holds gallery IDs for init firing checks.
     *
     * @since 1.0.0
     *
     * @var array
     */
    public $done = array();

    /**
     * Iterator for galleries on the page.
     *
     * @since 1.0.0
     *
     * @var int
     */
    public $counter = 1;

    /**
     * Holds image URLs for indexing.
     *
     * @since 1.0.0
     *
     * @var array
     */
    public $index = array();

    /**
     * Primary class constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {

        // Load the base class object.
        $this->base = Envira_Gallery::get_instance();

        // Register main gallery style.
        wp_register_style( $this->base->plugin_slug . '-style', plugins_url( 'assets/css/envira.css', $this->base->file ), array(), $this->base->version );

        // Register main gallery script.
		wp_register_script( $this->base->plugin_slug . '-script', plugins_url( 'assets/js/min/envira-min.js', $this->base->file ), array( 'jquery' ), $this->base->version, true );

        // Load hooks and filters.
        add_shortcode( 'envira-gallery', array( $this, 'shortcode' ) );
        add_filter( 'widget_text', 'do_shortcode' );

    }

    /**
     * Creates the shortcode for the plugin.
     *
     * @since 1.0.0
     *
     * @global object $post The current post object.
     *
     * @param array $atts Array of shortcode attributes.
     * @return string     The gallery output.
     */
    public function shortcode( $atts ) {

        global $post;
        
        // If no attributes have been passed, the gallery should be pulled from the current post.
        $gallery_id = false;
        if ( empty( $atts ) ) {
            $gallery_id = $post->ID;
            $data       = is_preview() ? $this->base->_get_gallery( $gallery_id ) : $this->base->get_gallery( $gallery_id );
        } else if ( isset( $atts['id'] ) ) {
            $gallery_id = (int) $atts['id'];
            $data       = is_preview() ? $this->base->_get_gallery( $gallery_id ) : $this->base->get_gallery( $gallery_id );
        } else if ( isset( $atts['slug'] ) ) {
            $gallery_id = $atts['slug'];
            $data       = is_preview() ? $this->base->_get_gallery_by_slug( $gallery_id ) : $this->base->get_gallery_by_slug( $gallery_id );
        } else {
            // A custom attribute must have been passed. Allow it to be filtered to grab data from a custom source.
            $data = apply_filters( 'envira_gallery_custom_gallery_data', false, $atts, $post );
        }
        
        // Randomize gallery order if specified
        if ( $this->get_config( 'random', $data ) ) {
		    // Shuffle keys
			$keys = array_keys($data['gallery']);
			shuffle($keys);
		
			// Rebuild array in new order
			$new = array();
		    foreach($keys as $key) {
		        $new[$key] = $data['gallery'][$key];
		    }
	
			// Assign back to gallery
	    	$data['gallery'] = $new;    
        }

        // Allow the data to be filtered before it is stored and used to create the gallery output.
        $data = apply_filters( 'envira_gallery_pre_data', $data, $gallery_id );

        // If there is no data to output or the gallery is inactive, do nothing.
        if ( ! $data || empty( $data['gallery'] ) || isset( $data['status'] ) && 'inactive' == $data['status'] && ! is_preview() ) {
            return;
        }
        
        // Get rid of any external plugins trying to jack up our stuff where a gallery is present.
        $this->plugin_humility();

        // Prepare variables.
        $this->data[$data['id']]  = $data;
        $this->index[$data['id']] = array();
        $gallery                  = '';
        $i                        = 1;

        // If this is a feed view, customize the output and return early.
        if ( is_feed() ) {
            return $this->do_feed_output( $data );
        }

        // Load scripts and styles.
        wp_enqueue_style( $this->base->plugin_slug . '-style' );
        wp_enqueue_script( $this->base->plugin_slug . '-script' );
        
        // Load custom gallery themes if necessary.
        if ( 'base' !== $this->get_config( 'gallery_theme', $data ) ) {
            $this->load_gallery_theme( $this->get_config( 'gallery_theme', $data ) );
        }

        // Load custom lightbox themes if necessary.
        if ( 'base' !== $this->get_config( 'lightbox_theme', $data ) ) {
            $this->load_lightbox_theme( $this->get_config( 'lightbox_theme', $data ) );
        }

        // Load gallery init code in the footer.
        add_action( 'wp_footer', array( $this, 'gallery_init' ), 1000 );

        // Run a hook before the gallery output begins but after scripts and inits have been set.
        do_action( 'envira_gallery_before_output', $data );

        // Apply a filter before starting the gallery HTML.
        $gallery = apply_filters( 'envira_gallery_output_start', $gallery, $data );

        // Build out the gallery HTML.
        $gallery .= '<div id="envira-gallery-wrap-' . sanitize_html_class( $data['id'] ) . '" class="' . $this->get_gallery_classes( $data ) . '">';
            $gallery  = apply_filters( 'envira_gallery_output_before_container', $gallery, $data );

            // Description
            if ( isset( $data['config']['description_position'] ) && $data['config']['description_position'] == 'above' ) {
                $gallery = $this->description( $gallery, $data );  
            }

            $gallery .= '<div id="envira-gallery-' . sanitize_html_class( $data['id'] ) . '" class="envira-gallery-public envira-gallery-' . sanitize_html_class( $this->get_config( 'columns', $data ) ) . '-columns envira-clear' . ( $this->get_config( 'isotope', $data ) ? ' enviratope' : '' ) . ( $this->get_config( 'css_animations', $data ) ? ' envira-gallery-css-animations' : '' ) . '" data-envira-columns="' . $this->get_config( 'columns', $data ) . '">';
                foreach ( $data['gallery'] as $id => $item ) {
                    // Skip over images that are pending (ignore if in Preview mode).
                    if ( isset( $item['status'] ) && 'pending' == $item['status'] && ! is_preview() ) {
                        continue;
                    }

                    $item     = apply_filters( 'envira_gallery_output_item_data', $item, $id, $data, $i );
                    $imagesrc = $this->get_image_src( $id, $item, $data );
                    $gallery  = apply_filters( 'envira_gallery_output_before_item', $gallery, $id, $item, $data, $i );
                    
                    $padding = absint( round( $this->get_config( 'gutter', $data ) / 2 ) );
                   
                    $output   = '<div id="envira-gallery-item-' . sanitize_html_class( $id ) . '" class="' . $this->get_gallery_item_classes( $item, $i, $data ) . '" style="padding-left: ' . $padding . 'px; padding-bottom: ' . $this->get_config( 'margin', $data ) . 'px; padding-right: ' . $padding . 'px;" ' . apply_filters( 'envira_gallery_output_item_attr', '', $id, $item, $data, $i ) . '>';
                        
                        $output .= '<div class="envira-gallery-item-inner">';
                        $output  = apply_filters( 'envira_gallery_output_before_link', $output, $id, $item, $data, $i );

                        if ( ! empty( $item['link'] ) ) {
                            $output .= '<a href="' . esc_url( $item['link'] ) . '" class="envira-gallery-' . sanitize_html_class( $data['id'] ) . ' envira-gallery-link" rel="enviragallery' . sanitize_html_class( $data['id'] ) . '" title="' . strip_tags( html_entity_decode( $item['title'] ) ) . '" data-envira-caption="' . str_replace( "\n", '<br />', esc_attr( $item['caption'] ) ) . '" data-thumbnail="' . esc_url( $item['thumb'] ) . '"' . ( ( isset($item['link_new_window']) && $item['link_new_window'] == 1 ) ? ' target="_blank"' : '' ) . ' ' . apply_filters( 'envira_gallery_output_link_attr', '', $id, $item, $data, $i ) . '>';
                        }

                            $output  = apply_filters( 'envira_gallery_output_before_image', $output, $id, $item, $data, $i );
							$output .= '<img id="envira-gallery-image-' . sanitize_html_class( $id ) . '" class="envira-gallery-image envira-gallery-image-' . $i . '" data-envira-index="' . $i . '" src="' . esc_url( $imagesrc ) . '"' . ( $this->get_config( 'dimensions', $data ) ? ' width="' . $this->get_config( 'crop_width', $data ) . '" height="' . $this->get_config( 'crop_height', $data ) . '"' : '' ) . ' data-envira-src="' . esc_url( $imagesrc ) . '" alt="' . esc_attr( $item['alt'] ) . '" title="' . strip_tags( html_entity_decode( $item['title'] ) ) . '" ' . apply_filters( 'envira_gallery_output_image_attr', '', $id, $item, $data, $i ) . ' />';
                            $output  = apply_filters( 'envira_gallery_output_after_image', $output, $id, $item, $data, $i );

                        if ( ! empty( $item['link'] ) ) {
                            $output .= '</a>';
                        }

                        $output  = apply_filters( 'envira_gallery_output_after_link', $output, $id, $item, $data, $i );
                        $output .= '</div>';
                        
                    $output .= '</div>';
                    $output  = apply_filters( 'envira_gallery_output_single_item', $output, $id, $item, $data, $i );
                    $gallery .= $output;
                    $gallery  = apply_filters( 'envira_gallery_output_after_item', $gallery, $id, $item, $data, $i );

                    // Increment the iterator.
                    $i++;
                }
            $gallery .= '</div>';
            // Description
            if ( isset( $data['config']['description_position'] ) && $data['config']['description_position'] == 'below' ) {
                $gallery = $this->description( $gallery, $data );  
            }

            $gallery  = apply_filters( 'envira_gallery_output_after_container', $gallery, $data );

        $gallery .= '</div>';
        $gallery  = apply_filters( 'envira_gallery_output_end', $gallery, $data );

        // Increment the counter.
        $this->counter++;

        // Remove any contextual filters so they don't affect other galleries on the page.
        if ( $this->get_config( 'mobile', $data ) ) {
            remove_filter( 'envira_gallery_output_image_attr', array( $this, 'mobile_image' ), 999, 4 );
        }

        // Add no JS fallback support.
        $no_js    = '<noscript>';
        $no_js   .= $this->get_indexable_images( $data['id'] );
        $no_js   .= '</noscript>';
        $gallery .= $no_js;

        // Return the gallery HTML.
        return apply_filters( 'envira_gallery_output', $gallery, $data );

    }

    /**
    * Builds HTML for the Gallery Description
    *
    * @since 1.3.0.2
    *
    * @param string $gallery Gallery HTML
    * @param array $data Data
    * @return HTML
    */
    public function description( $gallery, $data ) {

        $gallery .= '<div class="envira-gallery-description envira-gallery-description-above" style="padding-bottom: ' . $this->get_config( 'margin', $data ) . 'px;">';
            $gallery  = apply_filters( 'envira_gallery_output_before_description', $gallery, $data ); 
            $gallery .= wpautop( $data['config']['description'] );
            $gallery  = apply_filters( 'envira_gallery_output_after_description', $gallery, $data );
        $gallery .= '</div>'; 
        
        return $gallery;
    }

    /**
     * Outputs the gallery init script in the footer.
     *
     * @since 1.0.0
     */
    public function gallery_init() {

        ?>
        <script type="text/javascript">jQuery(document).ready(function($){<?php ob_start();
            do_action( 'envira_gallery_api_start_global' );
            foreach ( $this->data as $data ) {
                // Prevent multiple init scripts for the same gallery ID.
                if ( in_array( $data['id'], $this->done ) ) {
                    continue;
                }
                $this->done[] = $data['id'];

                do_action( 'envira_gallery_api_start', $data ); 
                
                // Define container
                ?>
                var envira_container_<?php echo $data['id']; ?> = '';
                
                <?php
                // Isotope: Start
                if ( $this->get_config( 'isotope', $data ) ) {
	                ?>
	                envira_container_<?php echo $data['id']; ?> = $('#envira-gallery-<?php echo $data['id']; ?>').imagesLoaded( function() {
		                envira_container_<?php echo $data['id']; ?>.enviratope( {
			                <?php do_action( 'envira_gallery_api_enviratope_config', $data ); ?>
	                    	itemSelector: '.envira-gallery-item',
							masonry: {
	                        	columnWidth: '.envira-gallery-item'
	                    	}
	                	});
					});
	                <?php 
		            do_action( 'envira_gallery_api_enviratope', $data ); 
		        }
		        // Isotope: End
		        
		        // CSS Animations: Start
		        if ( $this->get_config( 'css_animations', $data ) ) {
	                ?>
	                envira_container_<?php echo $data['id']; ?> = $('#envira-gallery-<?php echo $data['id']; ?>').imagesLoaded( function() {
		                $('.envira-gallery-item img').fadeTo('slow', 1);
					});
	                <?php
		        }
		        // CSS Animations: End
		        
		        // Fancybox: Start
                if ( $this->get_config( 'lightbox_enabled', $data ) ) {
    		        ?>
    				$('.envira-gallery-<?php echo $data['id']; ?>').envirabox({
                        <?php do_action( 'envira_gallery_api_config', $data ); // Depreciated ?>
                        <?php do_action( 'envira_gallery_api_envirabox_config', $data ); ?>
                        <?php if ( ! $this->get_config( 'keyboard', $data ) ) : ?>
                        keys: 0,
                        <?php endif; ?>
                        arrows: <?php echo $this->get_config( 'arrows', $data ); ?>,
                        aspectRatio: <?php echo $this->get_config( 'aspect', $data ); ?>,
                        loop: <?php echo $this->get_config( 'loop', $data ); ?>,
                        mouseWheel: <?php echo $this->get_config( 'mousewheel', $data ); ?>,
                        preload: 1,
                        nextEffect: '<?php echo $this->get_config( 'effect', $data ); ?>',
                        prevEffect: '<?php echo $this->get_config( 'effect', $data ); ?>',
                        tpl: {
                            wrap     : '<?php echo $this->get_lightbox_template( $data ); ?>',
                            image    : '<img class="envirabox-image" src="{href}" alt="" data-envira-title="" data-envira-caption="" data-envira-index="" data-envira-data="" />',
                            iframe   : '<iframe id="envirabox-frame{rnd}" name="envirabox-frame{rnd}" class="envirabox-iframe" frameborder="0" vspace="0" hspace="0" allowtransparency="true"\></iframe>',
                            error    : '<p class="envirabox-error"><?php echo __( 'The requested content cannot be loaded.<br/>Please try again later.</p>', 'envira-gallery' ); ?>',
                            closeBtn : '<a title="<?php echo __( 'Close', 'envira-gallery' ); ?>" class="envirabox-item envirabox-close" href="javascript:;"></a>',
                            next     : '<a title="<?php echo __( 'Next', 'envira-gallery' ); ?>" class="envirabox-nav envirabox-next" href="javascript:;"><span></span></a>',
                            prev     : '<a title="<?php echo __( 'Previous', 'envira-gallery' ); ?>" class="envirabox-nav envirabox-prev" href="javascript:;"><span></span></a>'
                            <?php do_action( 'envira_gallery_api_templates', $data ); ?>
                        },
                        helpers: {
                            <?php do_action( 'envira_gallery_api_helper_config', $data ); ?>
                            title: {
                                <?php do_action( 'envira_gallery_api_title_config', $data ); ?>
                                type: '<?php echo $this->get_config( 'title_display', $data ); ?>'
                            },
                            <?php if ( $this->get_config( 'thumbnails', $data ) ) : ?>
                            thumbs: {
                                width: <?php echo $this->get_config( 'thumbnails_width', $data ); ?>,
                                height: <?php echo $this->get_config( 'thumbnails_height', $data ); ?>,
                                source: function(current) {
                                    return $(current.element).data('thumbnail');
                                },
                                position: '<?php echo $this->get_config( 'thumbnails_position', $data ); ?>'
                            },
                            <?php endif; ?>
                            <?php if ( $this->get_config( 'toolbar', $data ) ) : ?>
                            buttons: {
                                tpl: '<?php echo $this->get_toolbar_template( $data ); ?>',
                                position: '<?php echo $this->get_config( 'toolbar_position', $data ); ?>',
                                padding: '<?php echo ( ( $this->get_config( 'toolbar_position', $data ) == 'bottom' && $this->get_config( 'thumbnails', $data ) && $this->get_config( 'thumbnails_position', $data ) == 'bottom' ) ? true : false ); ?>'
                            },
                            <?php endif; ?>
                        },
                        <?php do_action( 'envira_gallery_api_config_callback', $data ); ?>
                        beforeLoad: function(){
    	                    this.title = $(this.element).data('envira-caption');
    	                    <?php do_action( 'envira_gallery_api_before_load', $data ); ?>
                        },
                        afterLoad: function(){
                            <?php do_action( 'envira_gallery_api_after_load', $data ); ?>
                        },
                        beforeShow: function(){
                            $(window).on({
                                'resize.envirabox' : function(){
                                    $.envirabox.update();
                                }
                            });

                            // Get alt, data-envira-title and data-envira-caption attributes from clicked image link
                            var alt = this.element.find('img').attr('alt');
                            var title = this.element.find('img').parent().attr('title');
                            var caption = this.element.find('img').parent().data('envira-caption');
                            var index = this.element.find('img').data('envira-index');
                           
                            // Set alt, data-envira-title, data-envira-caption and data-envira-index attributes on Lightbox image
                            this.inner.find('img').attr('alt', alt).attr('data-envira-title', title).attr('data-envira-caption', caption).attr('data-envira-index', index);
                            
                            <?php do_action( 'envira_gallery_api_before_show', $data ); ?>
                        },
                        afterShow: function(){
                            <?php
                            if ( $this->get_config( 'mobile_touchwipe', $data ) ) {
                                ?>
                              	$('.envirabox-inner').swipe( {
        		                    swipe: function(event, direction, distance, duration, fingerCount, fingerData) {
        			                    if (direction === 'left') {
        				                    $.envirabox.next(direction);
        			                    } else if (direction === 'right') {
        				                    $.envirabox.prev(direction);
        			                    }
        		                    }
        	                    } );
                                <?php
                            }
                            ?>
    				                    
                            <?php do_action( 'envira_gallery_api_after_show', $data ); ?>
                        },
                        beforeClose: function(){
                            <?php do_action( 'envira_gallery_api_before_close', $data ); ?>
                        },
                        afterClose: function(){
                            $(window).off('resize.envirabox');
                            <?php do_action( 'envira_gallery_api_after_close', $data ); ?>
                        },
                        onUpdate: function(){
                            <?php 
                            if ( $this->get_config( 'toolbar', $data ) ) : ?>
                            var envira_buttons_<?php echo $data['id']; ?> = $('#envirabox-buttons li').map(function(){
                                return $(this).width();
                            }).get(),
                                envira_buttons_total_<?php echo $data['id']; ?> = 0;
                            $.each(envira_buttons_<?php echo $data['id']; ?>, function(i, val){
                                envira_buttons_total_<?php echo $data['id']; ?> += parseInt(val, 10);
                            });
                            $('#envirabox-buttons ul').width(envira_buttons_total_<?php echo $data['id']; ?>);
                            $('#envirabox-buttons').width(envira_buttons_total_<?php echo $data['id']; ?>).css('left', ($(window).width() - envira_buttons_total_<?php echo $data['id']; ?>)/2);
                            <?php endif; ?>

                            <?php do_action( 'envira_gallery_api_on_update', $data ); ?>
                        },
                        onCancel: function(){
                            <?php do_action( 'envira_gallery_api_on_cancel', $data ); ?>
                        },
                        onPlayStart: function(){
                            <?php do_action( 'envira_gallery_api_on_play_start', $data ); ?>
                        },
                        onPlayEnd: function(){
                            <?php do_action( 'envira_gallery_api_on_play_end', $data ); ?>
                        }
                    });

                    <?php 
    	            do_action( 'envira_gallery_api_lightbox', $data ); 
    	            // Fancybox: End
                }
	            
                do_action( 'envira_gallery_api_end', $data );
            } // foreach

            // Minify before outputting to improve page load time.
            do_action( 'envira_gallery_api_end_global' );
            echo $this->minify( ob_get_clean() ); ?>});</script>
        <?php

    }

    /**
     * Loads a custom gallery display theme.
     *
     * @since 1.0.0
     *
     * @param string $theme The custom theme slug to load.
     */
    public function load_gallery_theme( $theme ) {

        // Loop through the available themes and enqueue the one called.
        foreach ( Envira_Gallery_Common::get_instance()->get_gallery_themes() as $array => $data ) {
            if ( $theme !== $data['value'] ) {
                continue;
            }

            wp_enqueue_style( $this->base->plugin_slug . $theme . '-theme', plugins_url( 'themes/' . $theme . '/style.css', $data['file'] ), array( $this->base->plugin_slug . '-style' ) );
            break;
        }

    }

    /**
     * Loads a custom gallery lightbox theme.
     *
     * @since 1.0.0
     *
     * @param string $theme The custom theme slug to load.
     */
    public function load_lightbox_theme( $theme ) {

        // Loop through the available themes and enqueue the one called.
        foreach ( Envira_Gallery_Common::get_instance()->get_lightbox_themes() as $array => $data ) {
            if ( $theme !== $data['value'] ) {
                continue;
            }

            wp_enqueue_style( $this->base->plugin_slug . $theme . '-theme', plugins_url( 'themes/' . $theme . '/style.css', $data['file'] ), array( $this->base->plugin_slug . '-style' ) );
            break;
        }

    }

    /**
     * Helper method for adding custom gallery classes.
     *
     * @since 1.0.0
     *
     * @param array $data The gallery data to use for retrieval.
     * @return string     String of space separated gallery classes.
     */
    public function get_gallery_classes( $data ) {

        // Set default class.
        $classes   = array();
        $classes[] = 'envira-gallery-wrap';

        // Add custom class based on data provided.
        $classes[] = 'envira-gallery-theme-' . $this->get_config( 'gallery_theme', $data );
        $classes[] = 'envira-lightbox-theme-' . $this->get_config( 'lightbox_theme', $data );

        // If we have custom classes defined for this gallery, output them now.
        foreach ( (array) $this->get_config( 'classes', $data ) as $class ) {
            $classes[] = $class;
        }

        // If the gallery has RTL support, add a class for it.
        if ( $this->get_config( 'rtl', $data ) ) {
            $classes[] = 'envira-gallery-rtl';
        }

        // Allow filtering of classes and then return what's left.
        $classes = apply_filters( 'envira_gallery_output_classes', $classes, $data );
        return trim( implode( ' ', array_map( 'trim', array_map( 'sanitize_html_class', array_unique( $classes ) ) ) ) );

    }

    /**
     * Helper method for adding custom gallery classes.
     *
     * @since 1.0.4
     *
     * @param array $item Array of item data.
     * @param int $i      The current position in the gallery.
     * @param array $data The gallery data to use for retrieval.
     * @return string     String of space separated gallery item classes.
     */
    public function get_gallery_item_classes( $item, $i, $data ) {

        // Set default class.
        $classes   = array();
        $classes[] = 'envira-gallery-item';
        $classes[] = 'enviratope-item';
        $classes[] = 'envira-gallery-item-' . $i;

        // Allow filtering of classes and then return what's left.
        $classes = apply_filters( 'envira_gallery_output_item_classes', $classes, $item, $i, $data );
        return trim( implode( ' ', array_map( 'trim', array_map( 'sanitize_html_class', array_unique( $classes ) ) ) ) );

    }

    /**
     * Helper method to retrieve the proper image src attribute based on gallery settings.
     *
     * @since 1.0.0
     *
     * @param int $id      The image attachment ID to use.
     * @param array $item  Gallery item data.
     * @param array $data  The gallery data to use for retrieval.
     * @param bool $mobile Whether or not to retrieve the mobile image.
     * @return string      The proper image src attribute for the image.
     */
    public function get_image_src( $id, $item, $data, $mobile = false ) {
	    
	    // Detect if user is on a mobile device - if so, override $mobile flag which may be manually set
	    // by out of date addons or plugins
	    if ( $this->get_config( 'mobile', $data ) ) {
	    	$mobile = wp_is_mobile();
	    }
	    
	    // Get the full image src. If it does not return the data we need, return the image link instead.
        $src   = wp_get_attachment_image_src( $id, 'full' );
        $image = ! empty( $src[0] ) ? $src[0] : false;
        if ( ! $image ) {
            $image = ! empty( $item['src'] ) ? $item['src'] : false;
            if ( ! $image ) {
                return apply_filters( 'envira_gallery_no_image_src', $item['link'], $id, $item, $data );
            }
        };

        // Prep our indexable images.
        if ( $image && ! $mobile ) {
            $this->index[$data['id']][$id] = array(
                'src' => $image,
                'alt' => ! empty( $item['alt'] ) ? $item['alt'] : ''
            );
        }
        
        // Resize or crop image
        // This is safe to call every time, as resize_image() will check if the image already exists, preventing thumbnails
        // from being generated every single time.
        $type = $mobile ? 'mobile' : 'crop'; // 'crop' is misleading here - it's the key that stores the thumbnail width + height      
        $common = Envira_Gallery_Common::get_instance();
        $args = array(
            'position' => 'c',
            'width'    => $this->get_config( $type . '_width', $data ),
            'height'   => $this->get_config( $type . '_height', $data ),
            'quality'  => 100,
            'retina'   => false
        );
        $args   = apply_filters( 'envira_gallery_crop_image_args', $args);
        $resized_image = $common->resize_image( $image, $args['width'], $args['height'], $this->get_config( 'crop', $data ), $args['position'], $args['quality'], $args['retina'], $data );
		
        // If there is an error, possibly output error message and return the default image src.
        if ( is_wp_error( $resized_image ) ) {
            // If debugging is defined, print out the error.
            if ( defined( 'ENVIRA_GALLERY_CROP_DEBUG' ) && ENVIRA_GALLERY_CROP_DEBUG ) {
                echo '<pre>' . var_export( $resized_image->get_error_message(), true ) . '</pre>';
            }

            // Return the non-cropped image as a fallback.
            return apply_filters( 'envira_gallery_image_src', $image, $id, $item, $data );
        } else {
            return apply_filters( 'envira_gallery_image_src', $resized_image, $id, $item, $data );
        }

    }

    /**
     * Helper method to retrieve the proper gallery toolbar template.
     *
     * @since 1.0.0
     *
     * @param array $data Array of gallery data.
     * @return string     String template for the gallery toolbar.
     */
    public function get_toolbar_template( $data ) {

        // Build out the custom template based on options chosen.
        $template  = '<div id="envirabox-buttons">';
            $template .= '<ul>';
                $template  = apply_filters( 'envira_gallery_toolbar_start', $template, $data );
                
                // Prev
                $template .= '<li><a class="btnPrev" title="' . __( 'Previous', 'envira-gallery' ) . '" href="javascript:;"></a></li>';
                $template  = apply_filters( 'envira_gallery_toolbar_after_prev', $template, $data );
                
                // Next
                $template .= '<li><a class="btnNext" title="' . __( 'Next', 'envira-gallery' ) . '" href="javascript:;"></a></li>';
                $template  = apply_filters( 'envira_gallery_toolbar_after_next', $template, $data );
                
                // Title
                if ( $this->get_config( 'toolbar_title', $data ) ) {
	            	$template .= '<li id="envirabox-buttons-title"><span>' . $this->get_config( 'title', $data ) . '</span></li>';
					$template  = apply_filters( 'envira_gallery_toolbar_after_title', $template, $data );   
                }
                
                // Close
                $template .= '<li><a class="btnClose" title="' . __( 'Close', 'envira-gallery' ) . '" href="javascript:;"></a></li>';
                $template  = apply_filters( 'envira_gallery_toolbar_after_close', $template, $data );
                
                $template  = apply_filters( 'envira_gallery_toolbar_end', $template, $data );
            $template .= '</ul>';
        $template .= '</div>';

        // Return the template, filters applied and all.
        return apply_filters( 'envira_gallery_toolbar', $template, $data );

    }
    
    /**
	* Helper method to retrieve the gallery lightbox template
	*
	* @since 1.3.1.4
	*
	* @param array $data Array of gallery data
	* @return string String template for the gallery lightbox
	*/
    public function get_lightbox_template( $data ) {
	   
		// Build out the lightbox template
	    $template = '<div class="envirabox-wrap" tabIndex="-1"><div class="envirabox-skin"><div class="envirabox-outer"><div class="envirabox-inner"></div></div></div></div>';
    
		// Return the template, filters applied
		return apply_filters( 'envira_gallery_lightbox_template', $template, $data );
    
    }

    /**
     * Helper method for retrieving config values.
     *
     * @since 1.0.0
     *
     * @param string $key The config key to retrieve.
     * @param array $data The gallery data to use for retrieval.
     * @return string     Key value on success, default if not set.
     */
    public function get_config( $key, $data ) {

        $instance = Envira_Gallery_Common::get_instance();

        // If we are on a mobile device, some config keys have mobile equivalents, which we need to check instead
        if ( wp_is_mobile() ) {
            $mobile_keys = array(
                'lightbox_enabled'  => 'mobile_lightbox',
                'arrows'            => 'mobile_arrows',
                'toolbar'           => 'mobile_toolbar',
                'thumbnails'        => 'mobile_thumbnails',
            );
            $mobile_keys = apply_filters( 'envira_gallery_get_config_mobile_keys', $mobile_keys );

            if ( array_key_exists( $key, $mobile_keys ) ) {
                // Use the mobile array key to get the config value
                $key = $mobile_keys[ $key ];
            }
        }

        return isset( $data['config'][$key] ) ? $data['config'][$key] : $instance->get_config_default( $key );

    }

    /**
     * Helper method to minify a string of data.
     *
     * @since 1.0.4
     *
     * @param string $string  String of data to minify.
     * @return string $string Minified string of data.
     */
    public function minify( $string ) {

        $clean = preg_replace( '/((?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:\/\/.*))/', '', $string );
        $clean = str_replace( array( "\r\n", "\r", "\t", "\n", '  ', '    ', '     ' ), '', $clean );
        return apply_filters( 'envira_gallery_minified_string', $clean, $string );

    }

    /**
     * I'm sure some plugins mean well, but they go a bit too far trying to reduce
     * conflicts without thinking of the consequences.
     *
     * 1. Prevents Foobox from completely borking envirabox as if Foobox rules the world.
     *
     * @since 1.0.0
     */
    public function plugin_humility() {

        if ( class_exists( 'fooboxV2' ) ) {
            remove_action( 'wp_footer', array( $GLOBALS['foobox'], 'disable_other_lightboxes' ), 200 );
        }

    }

    /**
     * Outputs only the first image of the gallery inside a regular <div> tag
     * to avoid styling issues with feeds.
     *
     * @since 1.0.5
     *
     * @param array $data      Array of gallery data.
     * @return string $gallery Custom gallery output for feeds.
     */
    public function do_feed_output( $data ) {

        $gallery = '<div class="envira-gallery-feed-output">';
            foreach ( $data['gallery'] as $id => $item ) {
                // Skip over images that are pending (ignore if in Preview mode).
                if ( isset( $item['status'] ) && 'pending' == $item['status'] && ! is_preview() ) {
                    continue;
                }

                $imagesrc = $this->get_image_src( $id, $item, $data );
                $gallery .= '<img class="envira-gallery-feed-image" src="' . esc_url( $imagesrc ) . '" title="' . trim( esc_html( $item['title'] ) ) . '" alt="' .trim( esc_html( $item['alt'] ) ) . '" />';
                break;
             }
        $gallery .= '</div>';

        return apply_filters( 'envira_gallery_feed_output', $gallery, $data );

    }

    /**
     * Returns a set of indexable image links to allow SEO indexing for preloaded images.
     *
     * @since 1.0.0
     *
     * @param mixed $id       The slider ID to target.
     * @return string $images String of indexable image HTML.
     */
    public function get_indexable_images( $id ) {

        // If there are no images, don't do anything.
        $images = '';
        $i      = 1;
        if ( empty( $this->index[$id] ) ) {
            return $images;
        }

        foreach ( (array) $this->index[$id] as $attach_id => $data ) {
            $images .= '<img src="' . esc_url( $data['src'] ) . '" alt="' . esc_attr( $data['alt'] ) . '" />';
            $i++;
        }

        return apply_filters( 'envira_gallery_indexable_images', $images, $this->index, $id );

    }

    /**
     * Returns the singleton instance of the class.
     *
     * @since 1.0.0
     *
     * @return object The Envira_Gallery_Shortcode object.
     */
    public static function get_instance() {

        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Envira_Gallery_Shortcode ) ) {
            self::$instance = new Envira_Gallery_Shortcode();
        }

        return self::$instance;

    }

}

// Load the shortcode class.
$envira_gallery_shortcode = Envira_Gallery_Shortcode::get_instance();