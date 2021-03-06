<?php
/**
 * Metabox class.
 *
 * @since 1.0.0
 *
 * @package Envira_Gallery
 * @author  Thomas Griffin
 */
class Envira_Gallery_Metaboxes {

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
     * Primary class constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {

        // Load the base class object.
        $this->base = Envira_Gallery::get_instance();

        // Scripts and styles
        add_action( 'admin_enqueue_scripts', array( $this, 'meta_box_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'meta_box_scripts' ) );

        // Load the metabox hooks and filters.
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 100 );

        // Load all tabs.
        add_action( 'envira_gallery_tab_images', array( $this, 'images_tab' ) );
        add_action( 'envira_gallery_tab_config', array( $this, 'config_tab' ) );
        add_action( 'envira_gallery_tab_lightbox', array( $this, 'lightbox_tab' ) );
        add_action( 'envira_gallery_tab_thumbnails', array( $this, 'thumbnails_tab' ) );
        add_action( 'envira_gallery_tab_mobile', array( $this, 'mobile_tab' ) );
        add_action( 'envira_gallery_tab_misc', array( $this, 'misc_tab' ) );

        // Add action to save metabox config options.
        add_action( 'save_post', array( $this, 'save_meta_boxes' ), 10, 2 );

    }

    /**
     * Loads styles for our metaboxes.
     *
     * @since 1.0.0
     *
     * @return null Return early if not on the proper screen.
     */
    public function meta_box_styles() {

        // Only load if we are adding or editing an Envira Post Type
        $screen = get_current_screen();
        if ( $screen->base !== 'post' ) {
            return;
        }
        if ( $screen->post_type !== 'envira' ) {
            return;
        }

        // Load necessary metabox styles.
        wp_register_style( $this->base->plugin_slug . '-metabox-style', plugins_url( 'assets/css/metabox.css', $this->base->file ), array(), $this->base->version );
        wp_enqueue_style( $this->base->plugin_slug . '-metabox-style' );
        
        // If WordPress version < 4.0, add attachment-details-modal-support.css
        // This contains the 4.0 CSS to make the attachment window display correctly
        $version = (float) get_bloginfo( 'version' );
		if ( $version < 4 ) {
			wp_register_style( $this->base->plugin_slug . '-attachment-details-modal-support', plugins_url( 'assets/css/attachment-details-modal-support.css', $this->base->file ), array(), $this->base->version );
			wp_enqueue_style( $this->base->plugin_slug . '-attachment-details-modal-support' );
		}

        // Fire a hook to load in custom metabox styles.
        do_action( 'envira_gallery_metabox_styles' );

    }

    /**
     * Loads scripts for our metaboxes.
     *
     * @since 1.0.0
     *
     * @global int $id      The current post ID.
     * @global object $post The current post object.
     * @return null         Return early if not on the proper screen.
     */
    public function meta_box_scripts( $hook ) {

        global $id, $post;

        // Only load if we are adding or editing an Envira Post Type
        $screen = get_current_screen();
        if ( $screen->base !== 'post' ) {
            return;
        }
        if ( $screen->post_type !== 'envira' ) {
            return;
        }

        // Set the post_id for localization.
        $post_id = isset( $post->ID ) ? $post->ID : (int) $id;

        // Sortables
        wp_enqueue_script( 'jquery-ui-sortable' );
        
        // Image Uploader
        wp_enqueue_media( array( 
            'post' => $post_id, 
        ) );
        add_filter( 'plupload_init', array( $this, 'plupload_init' ) );
        wp_register_script( $this->base->plugin_slug . '-media-uploader', plugins_url( 'assets/js/media-uploader.js', $this->base->file ), array( 'jquery' ), $this->base->version, true );
        wp_enqueue_script( $this->base->plugin_slug . '-media-uploader' );
        wp_localize_script( 
            $this->base->plugin_slug . '-media-uploader',
            'envira_gallery_media_uploader',
            array(
                'ajax'           => admin_url( 'admin-ajax.php' ),
                'id'             => $post_id,
                'load_image'     => wp_create_nonce( 'envira-gallery-load-image' ),
            )
        );

        // Tabs
        wp_register_script( $this->base->plugin_slug . '-tabs-script', plugins_url( 'assets/js/tabs.js', $this->base->file ), array( 'jquery' ), $this->base->version, true );
        wp_enqueue_script( $this->base->plugin_slug . '-tabs-script' );
        
        // Metaboxes
        wp_register_script( $this->base->plugin_slug . '-metabox-script', plugins_url( 'assets/js/metabox.js', $this->base->file ), array( 'jquery', 'plupload-handlers', 'quicktags', 'jquery-ui-sortable' ), $this->base->version, true );
        wp_enqueue_script( $this->base->plugin_slug . '-metabox-script' );
        wp_localize_script(
            $this->base->plugin_slug . '-metabox-script',
            'envira_gallery_metabox',
            array(
                'ajax'           => admin_url( 'admin-ajax.php' ),
                'change_nonce'   => wp_create_nonce( 'envira-gallery-change-type' ),
                'id'             => $post_id,
                'import'         => __( 'You must select a file to import before continuing.', 'envira-gallery' ),
                'insert_nonce'   => wp_create_nonce( 'envira-gallery-insert-images' ),
                'inserting'      => __( 'Inserting...', 'envira-gallery' ),
                'library_search' => wp_create_nonce( 'envira-gallery-library-search' ),
                'load_gallery'   => wp_create_nonce( 'envira-gallery-load-gallery' ),
                'refresh_nonce'  => wp_create_nonce( 'envira-gallery-refresh' ),
                'remove'         => __( 'Are you sure you want to remove this image from the gallery?', 'envira-gallery' ),
                'remove_multiple'=> __( 'Are you sure you want to remove these images from the gallery?', 'envira-gallery' ),
                'remove_nonce'   => wp_create_nonce( 'envira-gallery-remove-image' ),
                'save_nonce'     => wp_create_nonce( 'envira-gallery-save-meta' ),
                'saving'         => __( 'Saving...', 'envira-gallery' ),
                'saved'          => __( 'Saved!', 'envira-gallery' ),
                'sort'           => wp_create_nonce( 'envira-gallery-sort' )
            )
        );

        // Add custom CSS for hiding specific things.
        add_action( 'admin_head', array( $this, 'meta_box_css' ) );

        // Fire a hook to load custom metabox scripts.
        do_action( 'envira_gallery_metabox_scripts' );

    }

    /**
    * Amends the default Plupload parameters for initialising the Media Uploader, to ensure
    * the uploaded image is attached to our Envira CPT
    *
    * @since 1.0.0
    *
    * @param array $params Params
    * @return array Params
    */
    public function plupload_init( $params ) {

        global $post_ID;

        // Define the Envira Gallery Post ID, so Plupload attaches the uploaded images
        // to this Envira Gallery
        $params['multipart_params']['post_id'] = $post_ID;

        // Build an array of supported file types for Plupload
        $supported_file_types = Envira_Gallery_Common::get_instance()->get_supported_filetypes();

        // Assign supported file types and return
        $params['filters']['mime_types'] = $supported_file_types;

        // Return and apply a custom filter to our init data.
        $params = apply_filters( 'envira_gallery_plupload_init', $params, $post_ID );
        return $params;

    }
    
    /**
     * Hides unnecessary meta box items on Envira post type screens.
     *
     * @since 1.0.0
     */
    public function meta_box_css() {

        ?>
        <style type="text/css">.misc-pub-section:not(.misc-pub-post-status) { display: none; }</style>
        <?php

        // Fire action for CSS on Envira post type screens.
        do_action( 'envira_gallery_admin_css' );

    }

    /**
     * Creates metaboxes for handling and managing galleries.
     *
     * @since 1.0.0
     */
    public function add_meta_boxes() {

        // Let's remove all of those dumb metaboxes from our post type screen to control the experience.
        $this->remove_all_the_metaboxes();
        
        // Add metabox to Envira CPT
        add_meta_box( 'envira-gallery', __( 'Envira Gallery Settings', 'envira-gallery' ), array( $this, 'meta_box_callback' ), 'envira', 'normal', 'high' );

    }

    /**
     * Removes all the metaboxes except the ones I want on MY POST TYPE. RAGE.
     *
     * @since 1.0.0
     *
     * @global array $wp_meta_boxes Array of registered metaboxes.
     * @return smile $for_my_buyers Happy customers with no spammy metaboxes!
     */
    public function remove_all_the_metaboxes() {

        global $wp_meta_boxes;

        // This is the post type you want to target. Adjust it to match yours.
        $post_type  = 'envira';

        // These are the metabox IDs you want to pass over. They don't have to match exactly. preg_match will be run on them.
        $pass_over_defaults = array( 'submitdiv', 'envira' );
        $pass_over  = apply_filters( 'envira_gallery_metabox_ids', $pass_over_defaults );

        // All the metabox contexts you want to check.
        $contexts_defaults = array( 'normal', 'advanced', 'side' );
        $contexts   = apply_filters( 'envira_gallery_metabox_contexts', $contexts_defaults );

        // All the priorities you want to check.
        $priorities_defaults = array( 'high', 'core', 'default', 'low' );
        $priorities = apply_filters( 'envira_gallery_metabox_priorities', $priorities_defaults );

        // Loop through and target each context.
        foreach ( $contexts as $context ) {
            // Now loop through each priority and start the purging process.
            foreach ( $priorities as $priority ) {
                if ( isset( $wp_meta_boxes[$post_type][$context][$priority] ) ) {
                    foreach ( (array) $wp_meta_boxes[$post_type][$context][$priority] as $id => $metabox_data ) {
                        // If the metabox ID to pass over matches the ID given, remove it from the array and continue.
                        if ( in_array( $id, $pass_over ) ) {
                            unset( $pass_over[$id] );
                            continue;
                        }

                        // Otherwise, loop through the pass_over IDs and if we have a match, continue.
                        foreach ( $pass_over as $to_pass ) {
                            if ( preg_match( '#^' . $id . '#i', $to_pass ) ) {
                                continue;
                            }
                        }

                        // If we reach this point, remove the metabox completely.
                        unset( $wp_meta_boxes[$post_type][$context][$priority][$id] );
                    }
                }
            }
        }

    }

    /**
     * Callback for displaying content in the registered metabox.
     *
     * @since 1.0.0
     *
     * @param object $post The current post object.
     */
    public function meta_box_callback( $post ) {

        // Keep security first.
        wp_nonce_field( 'envira-gallery', 'envira-gallery' );

        // Check for our meta overlay helper.
        $gallery_data = get_post_meta( $post->ID, '_eg_gallery_data', true );
        $helper       = get_post_meta( $post->ID, '_eg_just_published', true );
        $class        = '';
        if ( $helper ) {
            $class = 'envira-helper-needed';
        }

        ?>
        <div id="envira-tabs" class="envira-clear <?php echo $class; ?>">
            <?php $this->meta_helper( $post, $gallery_data ); ?>
            <ul id="envira-tabs-nav" class="envira-clear">
                <?php $i = 0; foreach ( (array) $this->get_envira_tab_nav() as $id => $title ) : $class = 0 === $i ? 'envira-active' : ''; ?>
                    <li class="<?php echo $class; ?>"><a href="#envira-tab-<?php echo $id; ?>" title="<?php echo $title; ?>"><?php echo $title; ?></a></li>
                <?php $i++; endforeach; ?>
            </ul>
            <?php $i = 0; foreach ( (array) $this->get_envira_tab_nav() as $id => $title ) : $class = 0 === $i ? 'envira-active' : ''; ?>
                <div id="envira-tab-<?php echo $id; ?>" class="envira-tab envira-clear <?php echo $class; ?>">
                    <?php do_action( 'envira_gallery_tab_' . $id, $post ); ?>
                </div>
            <?php $i++; endforeach; ?>
        </div>
        <?php

    }

    /**
     * Callback for getting all of the tabs for Envira galleries.
     *
     * @since 1.0.0
     *
     * @return array Array of tab information.
     */
    public function get_envira_tab_nav() {

        $tabs = array(
            'images'     => __( 'Images', 'envira-gallery' ),
            'config'     => __( 'Config', 'envira-gallery' ),
            'lightbox'   => __( 'Lightbox', 'envira-gallery' ),
            'thumbnails' => __( 'Thumbnails', 'envira-gallery' ),
            'mobile'     => __( 'Mobile', 'envira-gallery' ),
        );
        $tabs = apply_filters( 'envira_gallery_tab_nav', $tabs );

        // "Misc" tab is required.
        $tabs['misc'] = __( 'Misc', 'envira-gallery' );

        return $tabs;

    }

    /**
     * Callback for displaying the UI for main images tab.
     *
     * @since 1.0.0
     *
     * @param object $post The current post object.
     */
    public function images_tab( $post ) {

        // Output a notice if missing cropping extensions because Soliloquy needs them.
        if ( ! $this->has_gd_extension() && ! $this->has_imagick_extension() ) {
            ?>
            <div class="error below-h2">
                <p><strong><?php _e( 'The GD or Imagick libraries are not installed on your server. Envira Gallery requires at least one (preferably Imagick) in order to crop images and may not work properly without it. Please contact your webhost and ask them to compile GD or Imagick for your PHP install.', 'envira-gallery' ); ?></strong></p>
            </div>
            <?php
        }

        // Output the gallery type selection items.
        ?>
        <ul id="envira-gallery-types-nav" class="envira-clear">
            <li class="envira-gallery-type-label"><span><?php _e( 'Gallery Type', 'envira-gallery' ); ?></span></li>
            <?php $i = 0; foreach ( (array) $this->get_envira_types( $post ) as $id => $title ) : ?>
                <li><label for="envira-gallery-type-<?php echo $id; ?>"><input id="envira-gallery-type-<?php echo sanitize_html_class( $id ); ?>" type="radio" name="_envira_gallery[type]" value="<?php echo $id; ?>" <?php checked( $this->get_config( 'type', $this->get_config_default( 'type' ) ), $id ); ?> /> <?php echo $title; ?></label></li>
            <?php $i++; endforeach; ?>
            <li class="envira-gallery-type-spinner"><span class="spinner envira-gallery-spinner"></span></li>
        </ul>
        <?php

        // Output the display based on the type of slider being created.
        echo '<div id="envira-gallery-main" class="envira-clear">';
            $this->images_display( $this->get_config( 'type', $this->get_config_default( 'type' ) ), $post );
        echo '</div>';

    }

    /**
     * Returns the types of galleris available.
     *
     * @since 1.0.0
     *
     * @param object $post The current post object.
     * @return array       Array of gallery types to choose.
     */
    public function get_envira_types( $post ) {

        $types = array(
            'default' => __( 'Default', 'envira-gallery' )
        );

        return apply_filters( 'envira_gallery_types', $types, $post );

    }

    /**
     * Determines the Images tab display based on the type of gallery selected.
     *
     * @since 1.0.0
     *
     * @param string $type The type of display to output.
     * @param object $post The current post object.
     */
    public function images_display( $type = 'default', $post ) {

        // Output a unique hidden field for settings save testing for each type of slider.
        echo '<input type="hidden" name="_envira_gallery[type_' . $type . ']" value="1" />';

        // Output the display based on the type of slider available.
        switch ( $type ) {
            case 'default' :
                $this->do_default_display( $post );
                break;
            default:
                do_action( 'envira_gallery_display_' . $type, $post );
                break;
        }

    }

    /**
     * Callback for displaying the default gallery UI.
     *
     * @since 1.0.0
     *
     * @param object $post The current post object.
     */
    public function do_default_display( $post ) {

        // Output the media upload form
        Envira_Gallery_Media::get_instance()->media_upload_form( $post->ID );

        // Prepare output data.
        $gallery_data = get_post_meta( $post->ID, '_eg_gallery_data', true );

        ?>
        <a href="#" class="button button-red envira-gallery-images-delete"><?php _e( 'Delete Selected Images from Gallery', 'envira-gallery' ); ?></a>

        <ul id="envira-gallery-output" class="envira-clear">
            <?php if ( ! empty( $gallery_data['gallery'] ) ) : ?>
                <?php foreach ( $gallery_data['gallery'] as $id => $data ) : ?>
                    <?php echo $this->get_gallery_item( $id, $data, $post->ID ); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
        <?php $this->media_library( $post );

    }

    /**
     * Inserts the meta icon for displaying useful gallery meta like shortcode and template tag.
     *
     * @since 1.0.0
     *
     * @param object $post        The current post object.
     * @param array $gallery_data Array of gallery data for the current post.
     * @return null               Return early if this is an auto-draft.
     */
    public function meta_helper( $post, $gallery_data ) {

        if ( isset( $post->post_status ) && 'auto-draft' == $post->post_status ) {
            return;
        }

        // Check for our meta overlay helper.
        $helper = get_post_meta( $post->ID, '_eg_just_published', true );
        $class  = '';
        if ( $helper ) {
            $class = 'envira-helper-active';
            delete_post_meta( $post->ID, '_eg_just_published' );
        }

        ?>
        <div class="envira-meta-helper <?php echo $class; ?>">
            <span class="envira-meta-close-text"><?php _e( '(click the icon to open and close the overlay dialog)', 'envira-gallery' ); ?></span>
            <a href="#" class="envira-meta-icon" title="<?php esc_attr__( 'Click here to view meta information about this gallery.', 'envira-gallery' ); ?>"></a>
            <div class="envira-meta-information">
                <p><?php _e( 'You can place this gallery anywhere into your posts, pages, custom post types or widgets by using <strong>one</strong> the shortcode(s) below:', 'envira-gallery' ); ?></p>
                <code><?php echo '[envira-gallery id="' . $post->ID . '"]'; ?></code>
                <?php if ( ! empty( $gallery_data['config']['slug'] ) ) : ?>
                    <br><code><?php echo '[envira-gallery slug="' . $gallery_data['config']['slug'] . '"]'; ?></code>
                <?php endif; ?>
                <p><?php _e( 'You can also place this gallery into your template files by using <strong>one</strong> the template tag(s) below:', 'envira-gallery' ); ?></p>
                <code><?php echo 'if ( function_exists( \'envira_gallery\' ) ) { envira_gallery( \'' . $post->ID . '\' ); }'; ?></code>
                <?php if ( ! empty( $gallery_data['config']['slug'] ) ) : ?>
                    <br><code><?php echo 'if ( function_exists( \'envira_gallery\' ) ) { envira_gallery( \'' . $gallery_data['config']['slug'] . '\', \'slug\' ); }'; ?></code>
                <?php endif; ?>
            </div>
        </div>
        <?php

    }

    /**
     * Callback for displaying the UI for selecting images from the media library to insert.
     *
     * @since 1.0.0
     *
     * @param object $post The current post object.
     */
    public function media_library( $post ) {

        ?>
        <div id="envira-gallery-upload-ui-wrapper">
            <div id="envira-gallery-upload-ui" class="envira-gallery-image-meta" style="display: none;">
                <div class="media-modal wp-core-ui">
                    <a class="media-modal-close" href="#"><span class="media-modal-icon"></span></a>
                    <div class="media-modal-content">
                        <div class="media-frame envira-gallery-media-frame wp-core-ui hide-menu envira-gallery-meta-wrap">
                            <div class="media-frame-title">
                                <h1><?php _e( 'Insert Media into Gallery', 'envira-gallery' ); ?></h1>
                            </div>

                            <!-- Tabs -->
                            <div class="media-frame-router">
                                <div class="media-router">
                                    <a href="#" class="media-menu-item active" data-envira-gallery-content="select-images"><?php _e( 'Library Images', 'envira-gallery' ); ?></a>
                                    <?php do_action( 'envira_gallery_modal_router', $post ); ?>
                                </div><!-- end .media-router -->
                            </div><!-- end .media-frame-router -->

                            <!-- Content Sections -->
                            <?php $this->images_content( $post ); ?>
                            <?php do_action( 'envira_gallery_modal_content', $post ); ?>

                            <!-- Button -->
                            <div class="media-frame-toolbar">
                                <div class="media-toolbar">
                                    <div class="media-toolbar-secondary">
                                        <div class="media-selection one">
                                            <div class="selection-view">
                                                <ul tabindex="-1" class="attachments" id="__attachments-view-<?php echo $post->ID; ?>">
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="media-toolbar-primary">
                                        <a href="#" class="envira-gallery-media-insert button media-button button-large button-primary media-button-insert" title="<?php esc_attr_e( 'Insert Media into Gallery', 'envira-gallery' ); ?>"><?php _e( 'Insert Media into Gallery', 'envira-gallery' ); ?></a>
                                    </div><!-- end .media-toolbar-primary -->
                                </div><!-- end .media-toolbar -->
                            </div><!-- end .media-frame-toolbar -->
                        </div><!-- end .media-frame -->
                    </div><!-- end .media-modal-content -->
                </div><!-- end .media-modal -->
                <div class="media-modal-backdrop"></div>
            </div><!-- end .envira-gallery-image-meta -->
        </div><!-- end #envira-gallery-upload-ui-wrapper-->
        <?php

    }

    /**
     * Outputs the image content in the modal selection window.
     *
     * @since 1.3.2.5
     *
     * @param object $post The current post object.
     */
    public function images_content( $post ) {

        ?>
        <!-- begin content for inserting slides from media library -->
        <div id="envira-gallery-select-images">
            <div class="media-frame-content">
                <div class="attachments-browser">
                    <div class="media-toolbar envira-gallery-library-toolbar">
                        <div class="media-toolbar-primary">
                            <input type="search" placeholder="<?php esc_attr_e( 'Search', 'envira-gallery' ); ?>" id="envira-gallery-gallery-search" class="search" value="" />
                        </div>
                        <div class="media-toolbar-secondary">
                            <span class="spinner envira-gallery-spinner"></span>
                        </div>
                    </div>
                    <?php $library = get_posts( array( 'post_type' => 'attachment', 'post_mime_type' => 'image', 'post_status' => 'inherit', 'posts_per_page' => 20 ) ); ?>
                    <?php if ( $library ) : ?>
                    <ul class="attachments envira-gallery-gallery" data-envira-gallery-offset="20">
                    <?php foreach ( (array) $library as $image ) :
                        $has_gallery = get_post_meta( $image->ID, '_eg_has_gallery', true );
                        $class       = $has_gallery && in_array( $post->ID, (array) $has_gallery ) ? ' selected envira-gallery-in-gallery' : ''; ?>
                        <li class="attachment<?php echo $class; ?>" data-attachment-id="<?php echo absint( $image->ID ); ?>">
                            <div class="attachment-preview landscape">
                                <div class="thumbnail">
                                    <div class="centered">
                                        <?php $src = wp_get_attachment_image_src( $image->ID, 'thumbnail' ); ?>
                                        <img src="<?php echo esc_url( $src[0] ); ?>" />
                                    </div>
                                </div>
                                <a class="check" href="#"><div class="media-modal-icon"></div></a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    </ul><!-- end .envira-gallery-meta -->
                    <?php endif; ?>
                    <div class="media-sidebar">
                        <div class="envira-gallery-meta-sidebar">
                            <h3><?php _e( 'Helpful Tips', 'envira-gallery' ); ?></h3>
                            <strong><?php _e( 'Selecting Images', 'envira-gallery' ); ?></strong>
                            <p><?php _e( 'You can insert any image from your Media Library into your gallery. If the image you want to insert is not shown on the screen, you can either click on the "Load More Images from Library" button to load more images or use the search box to find the images you are looking for.', 'envira-gallery' ); ?></p>
                        </div><!-- end .envira-gallery-meta-sidebar -->
                    </div><!-- end .media-sidebar -->
                </div><!-- end .attachments-browser -->
            </div><!-- end .media-frame-content -->
        </div><!-- end #envira-gallery-image-slides -->
        <!-- end content for inserting slides from media library -->
        <?php

    }

    /**
     * Callback for displaying the UI for setting gallery config options.
     *
     * @since 1.0.0
     *
     * @param object $post The current post object.
     */
    public function config_tab( $post ) {

        ?>
        <div id="envira-config">
            <p class="envira-intro"><?php _e( 'The settings below adjust the basic configuration options for the gallery.', 'envira-gallery' ); ?></p>
            <table class="form-table">
                <tbody>
                    <tr id="envira-config-columns-box">
                        <th scope="row">
                            <label for="envira-config-columns"><?php _e( 'Number of Gallery Columns', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <select id="envira-config-columns" name="_envira_gallery[columns]">
                                <?php foreach ( (array) $this->get_columns() as $i => $data ) : ?>
                                    <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'columns', $this->get_config_default( 'columns' ) ) ); ?>><?php echo $data['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e( 'Determines the number of columns in the gallery.', 'envira-gallery' ); ?></p>
                        </td>
                    </tr>
                    <tr id="envira-config-gallery-theme-box">
                        <th scope="row">
                            <label for="envira-config-gallery-theme"><?php _e( 'Gallery Theme', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <select id="envira-config-gallery-theme" name="_envira_gallery[gallery_theme]">
                                <?php foreach ( (array) $this->get_gallery_themes() as $i => $data ) : ?>
                                    <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'gallery_theme', $this->get_config_default( 'gallery_theme' ) ) ); ?>><?php echo $data['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e( 'Sets the theme for the gallery display.', 'envira-gallery' ); ?></p>
                        </td>
                    </tr>
                    
                    <!-- Display Description -->
                    <tr id="envira-config-display-description-box">
                        <th scope="row">
                            <label for="envira-config-display-description"><?php _e( 'Display Gallery Description?', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <select id="envira-config-display-description" name="_envira_gallery[description_position]" data-envira-conditional="envira-config-description-box">
                                <?php 
	                            foreach ( (array) $this->get_display_description_options() as $i => $data ) {
		                            ?>
                                    <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'description_position', $this->get_config_default( 'description_position' ) ) ); ?>><?php echo $data['name']; ?></option>
									<?php
	                            }
                                ?>
                            </select>
                            <p class="description"><?php _e( 'Choose to display a description above or below this gallery\'s images.', 'envira-gallery' ); ?></p>
                        </td>
                    </tr>

                    <!-- Description -->
                    <tr id="envira-config-description-box">
                        <th scope="row">
                            <label for="envira-config-gallery-description"><?php _e( 'Gallery Description', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
	                        <?php
	                        $description = $this->get_config( 'description' );
	                        if ( empty( $description ) ) {
		                        $description = $this->get_config_default( 'description' );
		                    }
	                        wp_editor( $description, 'envira-gallery-description', array(
	                        	'media_buttons' => false,
	                        	'wpautop' 		=> true,
	                        	'tinymce' 		=> true,
	                        	'textarea_name' => '_envira_gallery[description]',
	                        ) );
	                        ?>
                            <p class="description"><?php _e( 'The description to display for this gallery.', 'envira-gallery' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr id="envira-config-gutter-box">
                        <th scope="row">
                            <label for="envira-config-gutter"><?php _e( 'Column Gutter Width', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-gutter" type="number" name="_envira_gallery[gutter]" value="<?php echo $this->get_config( 'gutter', $this->get_config_default( 'gutter' ) ); ?>" /> <span class="envira-unit"><?php _e( 'px', 'envira-gallery' ); ?></span>
                            <p class="description"><?php _e( 'Sets the space between the columns (defaults to 10).', 'envira-gallery' ); ?></p>
                        </td>
                    </tr>
                    <tr id="envira-config-margin-box">
                        <th scope="row">
                            <label for="envira-config-margin"><?php _e( 'Margin Below Each Image', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-margin" type="number" name="_envira_gallery[margin]" value="<?php echo $this->get_config( 'margin', $this->get_config_default( 'margin' ) ); ?>" /> <span class="envira-unit"><?php _e( 'px', 'envira-gallery' ); ?></span>
                            <p class="description"><?php _e( 'Sets the space below each item in the gallery.', 'envira-gallery' ); ?></p>
                        </td>
                    </tr>
                    <tr id="envira-config-isotope-box">
                        <th scope="row">
                            <label for="envira-config-random"><?php _e( 'Randomize Gallery Order?', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-random" type="checkbox" name="_envira_gallery[random]" value="<?php echo $this->get_config( 'random', $this->get_config_default( 'random' ) ); ?>" <?php checked( $this->get_config( 'random', $this->get_config_default( 'random' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'If enabled, the gallery output will be randomized on each page load.', 'envira-gallery' ); ?></span>
                        </td>
                    </tr>
                    
                    <tr id="envira-config-crop-size-box">
                        <th scope="row">
                            <label for="envira-config-crop-width"><?php _e( 'Image Dimensions', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-crop-width" type="number" name="_envira_gallery[crop_width]" value="<?php echo $this->get_config( 'crop_width', $this->get_config_default( 'crop_width' ) ); ?>" /> &#215; <input id="envira-config-crop-height" type="number" name="_envira_gallery[crop_height]" value="<?php echo $this->get_config( 'crop_height', $this->get_config_default( 'crop_height' ) ); ?>" /> <span class="envira-unit"><?php _e( 'px', 'envira-gallery' ); ?></span>
                            <p class="description"><?php _e( 'You should adjust these dimensions based on the number of columns in your gallery. This does not affect the full size lightbox images.', 'envira-gallery' ); ?></p>
                        </td>
                    </tr>
                    <tr id="envira-config-crop-box">
                        <th scope="row">
                            <label for="envira-config-crop"><?php _e( 'Crop Images?', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-crop" type="checkbox" name="_envira_gallery[crop]" value="<?php echo $this->get_config( 'crop', $this->get_config_default( 'crop' ) ); ?>" <?php checked( $this->get_config( 'crop', $this->get_config_default( 'crop' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'If enabled, forces images to exactly match the sizes defined above for Image Dimensions and Mobile Dimensions.', 'envira-gallery' ); ?></span>
                            <span class="description"><?php _e( 'If disabled, images will be resized to maintain their aspect ratio.', 'envira-gallery' ); ?></span>
                            
                        </td>
                    </tr>
                    <tr id="envira-config-dimensions-box">
                        <th scope="row">
                            <label for="envira-config-dimensions"><?php _e( 'Set Dimensions on Images?', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-dimensions" type="checkbox" name="_envira_gallery[dimensions]" value="<?php echo $this->get_config( 'dimensions', $this->get_config_default( 'dimensions' ) ); ?>" <?php checked( $this->get_config( 'dimensions', $this->get_config_default( 'dimensions' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables the width and height attributes on the img element. Only needs to be enabled if you need to meet Google Pagespeeds requirements.', 'envira-gallery' ); ?></span>
                        </td>
                    </tr>
                    <tr id="envira-config-isotope-box">
                        <th scope="row">
                            <label for="envira-config-isotope"><?php _e( 'Enable Isotope?', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-isotope" type="checkbox" name="_envira_gallery[isotope]" value="<?php echo $this->get_config( 'isotope', $this->get_config_default( 'isotope' ) ); ?>" <?php checked( $this->get_config( 'isotope', $this->get_config_default( 'isotope' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables isotope/masonry layout support for the main gallery images.', 'envira-gallery' ); ?></span>
                        </td>
                    </tr>
                    
                    <tr id="envira-config-css-animations-box">
                        <th scope="row">
                            <label for="envira-config-css-animations"><?php _e( 'Enable CSS Animations?', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-css-animations" type="checkbox" name="_envira_gallery[css_animations]" value="<?php echo $this->get_config( 'css_animations', $this->get_config_default( 'css_animations' ) ); ?>" <?php checked( $this->get_config( 'css_animations', $this->get_config_default( 'css_animations' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables CSS animations when loading the main gallery images.', 'envira-gallery' ); ?></span>
                        </td>
                    </tr>
                    <?php do_action( 'envira_gallery_config_box', $post ); ?>
                </tbody>
            </table>
        </div>
        <?php

    }

    /**
     * Callback for displaying the UI for setting gallery lightbox options.
     *
     * @since 1.0.0
     *
     * @param object $post The current post object.
     */
    public function lightbox_tab( $post ) {

        ?>
        <div id="envira-lightbox">
            <p class="envira-intro"><?php _e( 'The settings below adjust the lightbox outputs and displays.', 'envira-gallery' ); ?></p>
            <table class="form-table">
                <tbody>
                    <tr id="envira-config-lightbox-enabled-box">
                        <th scope="row">
                            <label for="envira-config-lightbox-enabled"><?php _e( 'Enable Lightbox?', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-lightbox-enabled" type="checkbox" name="_envira_gallery[lightbox_enabled]" value="<?php echo $this->get_config( 'lightbox_enabled', $this->get_config_default( 'lightbox_enabled' ) ); ?>" <?php checked( $this->get_config( 'lightbox_enabled', $this->get_config_default( 'lightbox_enabled' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables the gallery lightbox.', 'envira-gallery' ); ?></span>
                        </td>
                    </tr>
                    <tr id="envira-config-lightbox-theme-box">
                        <th scope="row">
                            <label for="envira-config-lightbox-theme"><?php _e( 'Gallery Lightbox Theme', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <select id="envira-config-lightbox-theme" name="_envira_gallery[lightbox_theme]">
                                <?php foreach ( (array) $this->get_lightbox_themes() as $i => $data ) : ?>
                                    <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'lightbox_theme', $this->get_config_default( 'lightbox_theme' ) ) ); ?>><?php echo $data['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e( 'Sets the theme for the gallery lightbox display.', 'envira-gallery' ); ?></p>
                        </td>
                    </tr>
                    <tr id="envira-config-lightbox-title-display-box">
                        <th scope="row">
                            <label for="envira-config-lightbox-title-display"><?php _e( 'Caption Position', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <select id="envira-config-lightbox-title-display" name="_envira_gallery[title_display]">
                                <?php foreach ( (array) $this->get_title_displays() as $i => $data ) : ?>
                                    <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'title_display', $this->get_config_default( 'title_display' ) ) ); ?>><?php echo $data['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e( 'Sets the display of the lightbox image\'s caption.', 'envira-gallery' ); ?></p>
                        </td>
                    </tr>
                    <tr id="envira-config-lightbox-arrows-box">
                        <th scope="row">
                            <label for="envira-config-lightbox-arrows"><?php _e( 'Enable Gallery Arrows?', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-lightbox-arrows" type="checkbox" name="_envira_gallery[arrows]" value="<?php echo $this->get_config( 'arrows', $this->get_config_default( 'arrows' ) ); ?>" <?php checked( $this->get_config( 'arrows', $this->get_config_default( 'arrows' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables the gallery lightbox navigation arrows.', 'envira-gallery' ); ?></span>
                        </td>
                    </tr>
                    <tr id="envira-config-lightbox-keyboard-box">
                        <th scope="row">
                            <label for="envira-config-lightbox-keyboard"><?php _e( 'Enable Keyboard Navigation?', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-lightbox-keyboard" type="checkbox" name="_envira_gallery[keyboard]" value="<?php echo $this->get_config( 'keyboard', $this->get_config_default( 'keyboard' ) ); ?>" <?php checked( $this->get_config( 'keyboard', $this->get_config_default( 'keyboard' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables keyboard navigation in the gallery lightbox.', 'envira-gallery' ); ?></span>
                        </td>
                    </tr>
                    <tr id="envira-config-lightbox-mousewheel-box">
                        <th scope="row">
                            <label for="envira-config-lightbox-mousewheel"><?php _e( 'Enable Mousewheel Navigation?', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-lightbox-mousewheel" type="checkbox" name="_envira_gallery[mousewheel]" value="<?php echo $this->get_config( 'mousewheel', $this->get_config_default( 'mousewheel' ) ); ?>" <?php checked( $this->get_config( 'mousewheel', $this->get_config_default( 'mousewheel' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables mousewheel navigation in the gallery.', 'envira-gallery' ); ?></span>
                        </td>
                    </tr>
                    <tr id="envira-config-lightbox-toolbar-box">
                        <th scope="row">
                            <label for="envira-config-lightbox-toolbar"><?php _e( 'Enable Gallery Toolbar?', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-lightbox-toolbar" type="checkbox" name="_envira_gallery[toolbar]" value="<?php echo $this->get_config( 'toolbar', $this->get_config_default( 'toolbar' ) ); ?>" <?php checked( $this->get_config( 'toolbar', $this->get_config_default( 'toolbar' ) ), 1 ); ?> data-envira-conditional="envira-config-lightbox-toolbar-title-box,envira-config-lightbox-toolbar-position-box" />
                            <span class="description"><?php _e( 'Enables or disables the gallery lightbox toolbar.', 'envira-gallery' ); ?></span>
                        </td>
                    </tr>
                    <tr id="envira-config-lightbox-toolbar-title-box">
                        <th scope="row">
                            <label for="envira-config-lightbox-toolbar-title"><?php _e( 'Display Gallery Title in Toolbar?', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-lightbox-toolbar-title" type="checkbox" name="_envira_gallery[toolbar_title]" value="<?php echo $this->get_config( 'toolbar_title', $this->get_config_default( 'toolbar_title' ) ); ?>" <?php checked( $this->get_config( 'toolbar_title', $this->get_config_default( 'toolbar_title' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Display the gallery title in the lightbox toolbar.', 'envira-gallery' ); ?></span>
                        </td>
                    </tr>
                    <tr id="envira-config-lightbox-toolbar-position-box">
                        <th scope="row">
                            <label for="envira-config-lightbox-toolbar-position"><?php _e( 'Gallery Toolbar Position', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <select id="envira-config-lightbox-toolbar-position" name="_envira_gallery[toolbar_position]">
                                <?php foreach ( (array) $this->get_toolbar_positions() as $i => $data ) : ?>
                                    <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'toolbar_position', $this->get_config_default( 'toolbar_position' ) ) ); ?>><?php echo $data['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e( 'Sets the position of the lightbox toolbar.', 'envira-gallery' ); ?></p>
                        </td>
                    </tr>
                    <tr id="envira-config-lightbox-aspect-box">
                        <th scope="row">
                            <label for="envira-config-lightbox-aspect"><?php _e( 'Keep Aspect Ratio?', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-lightbox-toolbar" type="checkbox" name="_envira_gallery[aspect]" value="<?php echo $this->get_config( 'aspect', $this->get_config_default( 'aspect' ) ); ?>" <?php checked( $this->get_config( 'aspect', $this->get_config_default( 'aspect' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'If enabled, images will always resize based on the original aspect ratio.', 'envira-gallery' ); ?></span>
                        </td>
                    </tr>
                    <tr id="envira-config-lightbox-loop-box">
                        <th scope="row">
                            <label for="envira-config-lightbox-loop"><?php _e( 'Loop Gallery Navigation?', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-lightbox-loop" type="checkbox" name="_envira_gallery[loop]" value="<?php echo $this->get_config( 'loop', $this->get_config_default( 'loop' ) ); ?>" <?php checked( $this->get_config( 'loop', $this->get_config_default( 'loop' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables infinite navigation cycling of the lightbox gallery.', 'envira-gallery' ); ?></span>
                        </td>
                    </tr>
                    <tr id="envira-config-lightbox-effect-box">
                        <th scope="row">
                            <label for="envira-config-lightbox-effect"><?php _e( 'Lightbox Transition Effect', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <select id="envira-config-lightbox-effect" name="_envira_gallery[effect]">
                                <?php foreach ( (array) $this->get_transition_effects() as $i => $data ) : ?>
                                    <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'effect', $this->get_config_default( 'effect' ) ) ); ?>><?php echo $data['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e( 'Type of transition between images in the lightbox view.', 'envira-gallery' ); ?></p>
                        </td>
                    </tr>
                    
                    <?php do_action( 'envira_gallery_lightbox_box', $post ); ?>
                </tbody>
            </table>
        </div>
        <?php

    }

    /**
     * Callback for displaying the UI for setting gallery thumbnail options.
     *
     * @since 1.0.0
     *
     * @param object $post The current post object.
     */
    public function thumbnails_tab( $post ) {

        ?>
        <div id="envira-thumbnails">
            <p class="envira-intro"><?php _e( 'If enabled, thumbnails are generated automatically inside the lightbox. The settings below adjust the thumbnail views for the gallery lightbox display.', 'envira-gallery' ); ?></p>
            <table class="form-table">
                <tbody>
                    <tr id="envira-config-thumbnails-box">
                        <th scope="row">
                            <label for="envira-config-thumbnails"><?php _e( 'Enable Gallery Thumbnails?', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-thumbnails" type="checkbox" name="_envira_gallery[thumbnails]" value="<?php echo $this->get_config( 'thumbnails', $this->get_config_default( 'thumbnails' ) ); ?>" <?php checked( $this->get_config( 'thumbnails', $this->get_config_default( 'thumbnails' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables the gallery lightbox thumbnails.', 'envira-gallery' ); ?></span>
                        </td>
                    </tr>
                    <tr id="envira-config-thumbnails-width-box">
                        <th scope="row">
                            <label for="envira-config-thumbnails-width"><?php _e( 'Gallery Thumbnails Width', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-thumbnails-width" type="number" name="_envira_gallery[thumbnails_width]" value="<?php echo $this->get_config( 'thumbnails_width', $this->get_config_default( 'thumbnails_width' ) ); ?>" /> <span class="envira-unit"><?php _e( 'px', 'envira-gallery' ); ?></span>
                            <p class="description"><?php _e( 'Sets the width of each lightbox thumbnail.', 'envira-gallery' ); ?></p>
                        </td>
                    </tr>
                    <tr id="envira-config-thumbnails-height-box">
                        <th scope="row">
                            <label for="envira-config-thumbnails-height"><?php _e( 'Gallery Thumbnails Height', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-thumbnails-height" type="number" name="_envira_gallery[thumbnails_height]" value="<?php echo $this->get_config( 'thumbnails_height', $this->get_config_default( 'thumbnails_height' ) ); ?>" /> <span class="envira-unit"><?php _e( 'px', 'envira-gallery' ); ?></span>
                            <p class="description"><?php _e( 'Sets the height of each lightbox thumbnail.', 'envira-gallery' ); ?></p>
                        </td>
                    </tr>
                    <tr id="envira-config-thumbnails-position-box">
                        <th scope="row">
                            <label for="envira-config-thumbnails-position"><?php _e( 'Gallery Thumbnails Position', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <select id="envira-config-thumbnails-position" name="_envira_gallery[thumbnails_position]">
                                <?php foreach ( (array) $this->get_thumbnail_positions() as $i => $data ) : ?>
                                    <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'thumbnails_position', $this->get_config_default( 'thumbnails_position' ) ) ); ?>><?php echo $data['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e( 'Sets the position of the lightbox thumbnails.', 'envira-gallery' ); ?></p>
                        </td>
                    </tr>
                    <?php do_action( 'envira_gallery_thumbnails_box', $post ); ?>
                </tbody>
            </table>
        </div>
        <?php

    }

     /**
     * Callback for displaying the UI for setting gallery mobile options.
     *
     * @since 1.3.2
     *
     * @param object $post The current post object.
     */
    public function mobile_tab( $post ) {

        ?>
        <div id="envira-mobile">
            <p class="envira-intro"><?php _e( 'The settings below adjust configuration options for the gallery and lightbox when viewed on a mobile device.', 'envira-gallery' ); ?></p>
            <table class="form-table">
                <tbody>
                    <!-- Mobile Images -->
                    <tr id="envira-config-mobile-box">
                        <th scope="row">
                            <label for="envira-config-mobile"><?php _e( 'Create Mobile Gallery Images?', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-mobile" type="checkbox" name="_envira_gallery[mobile]" value="<?php echo $this->get_config( 'mobile', $this->get_config_default( 'mobile' ) ); ?>" <?php checked( $this->get_config( 'mobile', $this->get_config_default( 'mobile' ) ), 1 ); ?> data-envira-conditional="envira-config-mobile-size-box" />
                            <span class="description"><?php _e( 'Enables or disables creating specific images for mobile devices.', 'envira-gallery' ); ?></span>
                        </td>
                    </tr>
                    <tr id="envira-config-mobile-size-box">
                        <th scope="row">
                            <label for="envira-config-mobile-width"><?php _e( 'Mobile Dimensions', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-mobile-width" type="number" name="_envira_gallery[mobile_width]" value="<?php echo $this->get_config( 'mobile_width', $this->get_config_default( 'mobile_width' ) ); ?>" /> &#215; <input id="envira-config-mobile-height" type="number" name="_envira_gallery[mobile_height]" value="<?php echo $this->get_config( 'mobile_height', $this->get_config_default( 'mobile_height' ) ); ?>" /> <span class="envira-unit"><?php _e( 'px', 'envira-gallery' ); ?></span>
                            <p class="description"><?php _e( 'These will be the sizes used for images displayed on mobile devices.', 'envira-gallery' ); ?></p>
                        </td>
                    </tr>

                    <!-- Lightbox -->
                    <tr id="envira-config-mobile-lightbox-box">
                        <th scope="row">
                            <label for="envira-config-mobile-lightbox"><?php _e( 'Enable Lightbox?', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-mobile-lightbox" type="checkbox" name="_envira_gallery[mobile_lightbox]" value="<?php echo $this->get_config( 'mobile_lightbox', $this->get_config_default( 'mobile_lightbox' ) ); ?>" <?php checked( $this->get_config( 'mobile_lightbox', $this->get_config_default( 'mobile_lightbox' ) ), 1 ); ?> data-envira-conditional="envira-config-mobile-touchwipe-box,envira-config-mobile-arrows-box,envira-config-mobile-toolbar-box,envira-config-mobile-thumbnails-box" />
                            <span class="description"><?php _e( 'Enables or disables the gallery lightbox on mobile devices.', 'envira-gallery' ); ?></span>
                        </td>
                    </tr>
                    <tr id="envira-config-mobile-touchwipe-box">
                        <th scope="row">
                            <label for="envira-config-mobile-touchwipe"><?php _e( 'Enable Gallery Touchwipe?', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-mobile-touchwipe" type="checkbox" name="_envira_gallery[mobile_touchwipe]" value="<?php echo $this->get_config( 'mobile_touchwipe', $this->get_config_default( 'mobile_touchwipe' ) ); ?>" <?php checked( $this->get_config( 'mobile_touchwipe', $this->get_config_default( 'mobile_touchwipe' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables touchwipe support for the gallery lightbox on mobile devices.', 'envira-gallery' ); ?></span>
                        </td>
                    </tr>
                    <tr id="envira-config-mobile-arrows-box">
                        <th scope="row">
                            <label for="envira-config-mobile-arrows"><?php _e( 'Enable Gallery Arrows?', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-mobile-arrows" type="checkbox" name="_envira_gallery[mobile_arrows]" value="<?php echo $this->get_config( 'mobile_arrows', $this->get_config_default( 'mobile_arrows' ) ); ?>" <?php checked( $this->get_config( 'mobile_arrows', $this->get_config_default( 'mobile_arrows' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables the gallery lightbox navigation arrows on mobile devices.', 'envira-gallery' ); ?></span>
                        </td>
                    </tr>
                    <tr id="envira-config-mobile-toolbar-box">
                        <th scope="row">
                            <label for="envira-config-mobile-toolbar"><?php _e( 'Enable Gallery Toolbar?', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-mobile-toolbar" type="checkbox" name="_envira_gallery[mobile_toolbar]" value="<?php echo $this->get_config( 'mobile_toolbar', $this->get_config_default( 'mobile_toolbar' ) ); ?>" <?php checked( $this->get_config( 'mobile_toolbar', $this->get_config_default( 'mobile_toolbar' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables the gallery lightbox toolbar on mobile devices.', 'envira-gallery' ); ?></span>
                        </td>
                    </tr>
                    <tr id="envira-config-mobile-thumbnails-box">
                        <th scope="row">
                            <label for="envira-config-mobile-thumbnails"><?php _e( 'Enable Gallery Thumbnails?', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-mobile-thumbnails" type="checkbox" name="_envira_gallery[mobile_thumbnails]" value="<?php echo $this->get_config( 'mobile_thumbnails', $this->get_config_default( 'mobile_toolbar' ) ); ?>" <?php checked( $this->get_config( 'mobile_thumbnails', $this->get_config_default( 'mobile_thumbnails' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables the gallery lightbox thumbnails on mobile devices.', 'envira-gallery' ); ?></span>
                        </td>
                    </tr>
                    
                    <?php do_action( 'envira_gallery_mobile_box', $post ); ?>
                </tbody>
            </table>
        </div>
        <?php

    }

    /**
     * Callback for displaying the UI for setting gallery miscellaneous options.
     *
     * @since 1.0.0
     *
     * @param object $post The current post object.
     */
    public function misc_tab( $post ) {

        ?>
        <div id="envira-misc">
            <p class="envira-intro"><?php _e( 'The settings below adjust the miscellaneous settings for the gallery lightbox display.', 'envira-gallery' ); ?></p>
            <table class="form-table">
                <tbody>
                    <tr id="envira-config-title-box">
                        <th scope="row">
                            <label for="envira-config-title"><?php _e( 'Gallery Title', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-title" type="text" name="_envira_gallery[title]" value="<?php echo $this->get_config( 'title', $this->get_config_default( 'title' ) ); ?>" />
                            <p class="description"><?php _e( 'Internal gallery title for identification in the admin.', 'envira-gallery' ); ?></p>
                        </td>
                    </tr>
                    <tr id="envira-config-slug-box">
                        <th scope="row">
                            <label for="envira-config-slug"><?php _e( 'Gallery Slug', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-slug" type="text" name="_envira_gallery[slug]" value="<?php echo $this->get_config( 'slug', $this->get_config_default( 'slug' ) ); ?>" />
                            <p class="description"><?php _e( '<strong>Unique</strong> internal gallery slug for identification and advanced gallery queries.', 'envira-gallery' ); ?></p>
                        </td>
                    </tr>
                    <tr id="envira-config-classes-box">
                        <th scope="row">
                            <label for="envira-config-classes"><?php _e( 'Custom Gallery Classes', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <textarea id="envira-config-classes" rows="5" cols="75" name="_envira_gallery[classes]" placeholder="<?php _e( 'Enter custom gallery CSS classes here, one per line.', 'envira-gallery' ); ?>"><?php echo implode( "\n", (array) $this->get_config( 'classes', $this->get_config_default( 'classes' ) ) ); ?></textarea>
                            <p class="description"><?php _e( 'Adds custom CSS classes to this gallery. Enter one class per line.', 'envira-gallery' ); ?></p>
                        </td>
                    </tr>
                    <tr id="envira-config-import-export-box">
                        <th scope="row">
                            <label for="envira-config-import-gallery"><?php _e( 'Import/Export Gallery', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <form></form>
                            <?php 
                            $import_url = 'auto-draft' == $post->post_status ? add_query_arg( array( 'post' => $post->ID, 'action' => 'edit', 'envira-gallery-imported' => true ), admin_url( 'post.php' ) ) : add_query_arg( 'envira-gallery-imported', true ); 
                            $import_url = esc_url( $import_url );
                            ?>
                            <form action="<?php echo $import_url; ?>" id="envira-config-import-gallery-form" class="envira-gallery-import-form" method="post" enctype="multipart/form-data">
                                <input id="envira-config-import-gallery" type="file" name="envira_import_gallery" />
                                <input type="hidden" name="envira_import" value="1" />
                                <input type="hidden" name="envira_post_id" value="<?php echo $post->ID; ?>" />
                                <?php wp_nonce_field( 'envira-gallery-import', 'envira-gallery-import' ); ?>
                                <?php submit_button( __( 'Import Gallery', 'envira-gallery' ), 'secondary', 'envira-gallery-import-submit', false ); ?>
                                <span class="spinner envira-gallery-spinner"></span>
                            </form>
                            <form id="envira-config-export-gallery-form" method="post">
                                <input type="hidden" name="envira_export" value="1" />
                                <input type="hidden" name="envira_post_id" value="<?php echo $post->ID; ?>" />
                                <?php wp_nonce_field( 'envira-gallery-export', 'envira-gallery-export' ); ?>
                                <?php submit_button( __( 'Export Gallery', 'envira-gallery' ), 'secondary', 'envira-gallery-export-submit', false ); ?>
                            </form>
                        </td>
                    </tr>
                    <tr id="envira-config-rtl-box">
                        <th scope="row">
                            <label for="envira-config-rtl"><?php _e( 'Enable RTL Support?', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-rtl" type="checkbox" name="_envira_gallery[rtl]" value="<?php echo $this->get_config( 'rtl', $this->get_config_default( 'rtl' ) ); ?>" <?php checked( $this->get_config( 'rtl', $this->get_config_default( 'rtl' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables RTL support in Envira for right-to-left languages.', 'envira-gallery' ); ?></span>
                        </td>
                    </tr>
                    <?php do_action( 'envira_gallery_misc_box', $post ); ?>
                </tbody>
            </table>
        </div>
        <?php

    }

    /**
     * Callback for saving values from Envira metaboxes.
     *
     * @since 1.0.0
     *
     * @param int $post_id The current post ID.
     * @param object $post The current post object.
     */
    public function save_meta_boxes( $post_id, $post ) {
	    
        // Bail out if we fail a security check.
        if ( ! isset( $_POST['envira-gallery'] ) || ! wp_verify_nonce( $_POST['envira-gallery'], 'envira-gallery' ) || ! isset( $_POST['_envira_gallery'] ) ) {
            return;
        }

        // Bail out if running an autosave, ajax, cron or revision.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
	        // Check if this is a Quick Edit request
	        if ( isset( $_POST['_inline_edit'] ) ) {
		        
		        // Just update specific fields in the Quick Edit screen
		        
		        // Get settings
		        $settings = get_post_meta( $post_id, '_eg_gallery_data', true );
		        if ( empty( $settings ) ) {
			        return;
		        }
        
				// Update Settings
	        	$settings['config']['columns']             = preg_replace( '#[^a-z0-9-_]#', '', $_POST['_envira_gallery']['columns'] );
                $settings['config']['gallery_theme']       = preg_replace( '#[^a-z0-9-_]#', '', $_POST['_envira_gallery']['gallery_theme'] );
                $settings['config']['gutter']              = absint( $_POST['_envira_gallery']['gutter'] );
                $settings['config']['margin']              = absint( $_POST['_envira_gallery']['margin'] );
                $settings['config']['crop_width']          = absint( $_POST['_envira_gallery']['crop_width'] );
                $settings['config']['crop_height']         = absint( $_POST['_envira_gallery']['crop_height'] );
	        
		        // Provide a filter to override settings.
				$settings = apply_filters( 'envira_gallery_quick_edit_save_settings', $settings, $post_id, $post );
				
				// Update the post meta.
				update_post_meta( $post_id, '_eg_gallery_data', $settings );
				
				// Finally, flush all gallery caches to ensure everything is up to date.
				$this->flush_gallery_caches( $post_id, $settings['config']['slug'] );
				
	        } 
        
            return;
        }

        if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        // Bail out if the user doesn't have the correct permissions to update the slider.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        
        // If the post has just been published for the first time, set meta field for the gallery meta overlay helper.
        if ( isset( $post->post_date ) && isset( $post->post_modified ) && $post->post_date === $post->post_modified ) {
            update_post_meta( $post_id, '_eg_just_published', true );
        }

        // Sanitize all user inputs.
        $settings = get_post_meta( $post_id, '_eg_gallery_data', true );
        if ( empty( $settings ) ) {
            $settings = array();
        }

        // If the ID of the gallery is not set or is lost, replace it now.
        if ( empty( $settings['id'] ) || ! $settings['id'] ) {
            $settings['id'] = $post_id;
        }
        
        // Config
        $settings['config']['type']                = isset( $_POST['_envira_gallery']['type'] ) ? $_POST['_envira_gallery']['type'] : $this->get_config_default( 'type' );
        $settings['config']['columns']             = preg_replace( '#[^a-z0-9-_]#', '', $_POST['_envira_gallery']['columns'] );
        $settings['config']['gallery_theme']       = preg_replace( '#[^a-z0-9-_]#', '', $_POST['_envira_gallery']['gallery_theme'] );
        $settings['config']['description_position'] = preg_replace( '#[^a-z0-9-_]#', '', $_POST['_envira_gallery']['description_position'] );
        $settings['config']['description'] 		   = trim( $_POST['_envira_gallery']['description'] );
        $settings['config']['gutter']              = absint( $_POST['_envira_gallery']['gutter'] );
        $settings['config']['margin']              = absint( $_POST['_envira_gallery']['margin'] );
        $settings['config']['random']              = isset( $_POST['_envira_gallery']['random'] ) ? 1 : 0;
        $settings['config']['crop']                = isset( $_POST['_envira_gallery']['crop'] ) ? 1 : 0;
        $settings['config']['dimensions']          = isset( $_POST['_envira_gallery']['dimensions'] ) ? 1 : 0;
        $settings['config']['crop_width']          = absint( $_POST['_envira_gallery']['crop_width'] );
        $settings['config']['crop_height']         = absint( $_POST['_envira_gallery']['crop_height'] );
        $settings['config']['isotope']             = isset( $_POST['_envira_gallery']['isotope'] ) ? 1 : 0;
        $settings['config']['css_animations']	   = isset( $_POST['_envira_gallery']['css_animations'] ) ? 1 : 0;
        
        // Lightbox
        $settings['config']['lightbox_enabled']    = isset( $_POST['_envira_gallery']['lightbox_enabled'] ) ? 1 : 0;
        $settings['config']['lightbox_theme']      = preg_replace( '#[^a-z0-9-_]#', '', $_POST['_envira_gallery']['lightbox_theme'] );
        $settings['config']['title_display']       = preg_replace( '#[^a-z0-9-_]#', '', $_POST['_envira_gallery']['title_display'] );
        $settings['config']['arrows']              = isset( $_POST['_envira_gallery']['arrows'] ) ? 1 : 0;
        $settings['config']['keyboard']            = isset( $_POST['_envira_gallery']['keyboard'] ) ? 1 : 0;
        $settings['config']['mousewheel']          = isset( $_POST['_envira_gallery']['mousewheel'] ) ? 1 : 0;
        $settings['config']['aspect']              = isset( $_POST['_envira_gallery']['aspect'] ) ? 1 : 0;
        $settings['config']['toolbar']             = isset( $_POST['_envira_gallery']['toolbar'] ) ? 1 : 0;
        $settings['config']['toolbar_title']       = isset( $_POST['_envira_gallery']['toolbar_title'] ) ? 1 : 0;
        $settings['config']['toolbar_position']    = preg_replace( '#[^a-z0-9-_]#', '', $_POST['_envira_gallery']['toolbar_position'] );
        $settings['config']['loop']                = isset( $_POST['_envira_gallery']['loop'] ) ? 1 : 0;
        $settings['config']['effect']              = preg_replace( '#[^a-z0-9-_]#', '', $_POST['_envira_gallery']['effect'] );
        
        // Thumbnails
        $settings['config']['thumbnails']          = isset( $_POST['_envira_gallery']['thumbnails'] ) ? 1 : 0;
        $settings['config']['thumbnails_width']    = absint( $_POST['_envira_gallery']['thumbnails_width'] );
        $settings['config']['thumbnails_height']   = absint( $_POST['_envira_gallery']['thumbnails_height'] );
        $settings['config']['thumbnails_position'] = preg_replace( '#[^a-z0-9-_]#', '', $_POST['_envira_gallery']['thumbnails_position'] );
        
        // Mobile
        $settings['config']['mobile']              = isset( $_POST['_envira_gallery']['mobile'] ) ? 1 : 0;
        $settings['config']['mobile_width']        = absint( $_POST['_envira_gallery']['mobile_width'] );
        $settings['config']['mobile_height']       = absint( $_POST['_envira_gallery']['mobile_height'] );
        $settings['config']['mobile_lightbox']     = isset( $_POST['_envira_gallery']['mobile_lightbox'] ) ? 1 : 0;
        $settings['config']['mobile_touchwipe']    = isset( $_POST['_envira_gallery']['mobile_touchwipe'] ) ? 1 : 0;
        $settings['config']['mobile_arrows']       = isset( $_POST['_envira_gallery']['mobile_arrows'] ) ? 1 : 0;
        $settings['config']['mobile_toolbar']      = isset( $_POST['_envira_gallery']['mobile_toolbar'] ) ? 1 : 0;
        $settings['config']['mobile_thumbnails']   = isset( $_POST['_envira_gallery']['mobile_thumbnails'] ) ? 1 : 0;

        // Misc
        $settings['config']['classes']             = explode( "\n", $_POST['_envira_gallery']['classes'] );
        $settings['config']['rtl']                 = isset( $_POST['_envira_gallery']['rtl'] ) ? 1 : 0;
        $settings['config']['title']               = trim( strip_tags( $_POST['_envira_gallery']['title'] ) );
        $settings['config']['slug']                = sanitize_text_field( $_POST['_envira_gallery']['slug'] );
    
        // If on an envira post type, map the title and slug of the post object to the custom fields if no value exists yet.
        if ( isset( $post->post_type ) && 'envira' == $post->post_type ) {
            $settings['config']['title'] = trim( strip_tags( $post->post_title ) );
            $settings['config']['slug']  = sanitize_text_field( $post->post_name );
        }

        // Provide a filter to override settings.
        $settings = apply_filters( 'envira_gallery_save_settings', $settings, $post_id, $post );

        // Update the post meta.
        update_post_meta( $post_id, '_eg_gallery_data', $settings );

        // Change states of images in gallery from pending to active.
        $this->change_gallery_states( $post_id );

        // If the thumbnails option is checked, crop images accordingly.
        if ( isset( $settings['config']['thumbnails'] ) && $settings['config']['thumbnails'] ) {
            $args = array(
                'position' => 'c',
                'width'    => $this->get_config( 'thumbnails_width', $this->get_config_default( 'thumbnails_width' ) ),
                'height'   => $this->get_config( 'thumbnails_height', $this->get_config_default( 'thumbnails_height' ) ),
                'quality'  => 100,
                'retina'   => false
            );
            $args = apply_filters( 'envira_gallery_crop_image_args', $args );
            $this->crop_thumbnails( $args, $post_id );
        }

        // If the crop option is checked, crop images accordingly.
        if ( isset( $settings['config']['crop'] ) && $settings['config']['crop'] ) {
            $args = array(
                'position' => 'c',
                'width'    => $this->get_config( 'crop_width', $this->get_config_default( 'crop_width' ) ),
                'height'   => $this->get_config( 'crop_height', $this->get_config_default( 'crop_height' ) ),
                'quality'  => 100,
                'retina'   => false
            );
            $args = apply_filters( 'envira_gallery_crop_image_args', $args );
            $this->crop_images( $args, $post_id );
        }

        // If the mobile option is checked, crop images accordingly.
        if ( isset( $settings['config']['mobile'] ) && $settings['config']['mobile'] ) {
            $args = array(
                'position' => 'c',
                'width'    => $this->get_config( 'mobile_width', $this->get_config_default( 'mobile_width' ) ),
                'height'   => $this->get_config( 'mobile_height', $this->get_config_default( 'mobile_height' ) ),
                'quality'  => 100,
                'retina'   => false
            );
            $args = apply_filters( 'envira_gallery_crop_image_args', $args );
            $this->crop_images( $args, $post_id );
        }

        // Fire a hook for addons that need to utilize the cropping feature.
        do_action( 'envira_gallery_saved_settings', $settings, $post_id, $post );

        // Finally, flush all gallery caches to ensure everything is up to date.
        $this->flush_gallery_caches( $post_id, $settings['config']['slug'] );

    }

    /**
     * Helper method for retrieving the gallery layout for an item in the admin.
     *
     * @since 1.0.0
     *
     * @param int $id The  ID of the item to retrieve.
     * @param array $data  Array of data for the item.
     * @param int $post_id The current post ID.
     * @return string The  HTML output for the gallery item.
     */
    public function get_gallery_item( $id, $data, $post_id = 0 ) {

        $thumbnail = wp_get_attachment_image_src( $id, 'thumbnail' ); ob_start(); ?>
        <li id="<?php echo $id; ?>" class="envira-gallery-image envira-gallery-status-<?php echo $data['status']; ?>" data-envira-gallery-image="<?php echo $id; ?>">
            <img src="<?php echo esc_url( $thumbnail[0] ); ?>" alt="<?php esc_attr_e( $data['alt'] ); ?>" />
            <a href="#" class="check"><div class="media-modal-icon"></div></a>
            <a href="#" class="envira-gallery-remove-image" title="<?php esc_attr_e( 'Remove Image from Gallery?', 'envira-gallery' ); ?>"></a>
            <a href="#" class="envira-gallery-modify-image" title="<?php esc_attr_e( 'Modify Image', 'envira-gallery' ); ?>"></a>
            <?php echo $this->get_gallery_item_meta( $id, $data, $post_id ); ?>
        </li>
        <?php
        return ob_get_clean();

    }

    /**
     * Helper method for retrieving the gallery metadata editing modal.
     *
     * @since 1.0.0
     *
     * @param int $id      The ID of the item to retrieve.
     * @param array $data  Array of data for the item.
     * @param int $post_id The current post ID.
     * @return string      The HTML output for the gallery item.
     */
    public function get_gallery_item_meta( $id, $data, $post_id ) {
	    
	    ob_start(); ?>
        <div id="envira-gallery-meta-<?php echo $id; ?>" class="envira-gallery-meta-container" style="display:none;">
            <div class="media-modal wp-core-ui">
                <!-- Close -->
                <a class="media-modal-close" href="#"><span class="media-modal-icon"></span></a>
                
                <div class="media-modal-content">
                    <div class="edit-attachment-frame mode-select hide-menu hide-router envira-gallery-media-frame envira-gallery-meta-wrap">
	                    
	                    <!-- Back / Next Buttons -->
	                    <div class="edit-media-header">
		                    <button class="left dashicons" data-attachment-id="">
			                    <span class="screen-reader-text"><?php _e( 'Edit previous media item', 'envira-gallery' ); ?></span>
		                    </button>
		                    <button class="right dashicons" data-attachment-id="">
			                    <span class="screen-reader-text"><?php _e( 'Edit next media item', 'envira-gallery' ); ?></span>
		                    </button>
	                    </div>
	                    
	                    <!-- Title -->
                        <div class="media-frame-title">
                            <h1><?php _e( 'Edit Metadata', 'envira-gallery' ); ?></h1>
                        </div>
                        
                        <!-- Content -->
                        <div class="media-frame-content" id="envira-gallery-meta-table-<?php echo $id; ?>">
	                        <div tabindex="0" role="checkbox" class="attachment-details save-ready">
		                        <!-- Left -->
		                        <div class="attachment-media-view portrait">
			                        <div class="thumbnail thumbnail-image">
                                        <?php do_action( 'envira_gallery_before_preview', $id, $data, $post_id ); ?>
				                        <img class="details-image" src="<?php echo $data['src']; ?>" draggable="false" />
                                        <?php do_action( 'envira_gallery_after_preview', $id, $data, $post_id ); ?>
			                        </div>
			                    </div>
		                        
		                        <!-- Right -->
		                        <div class="attachment-info">
			                        <?php do_action( 'envira_gallery_before_meta_help', $id, $data, $post_id ); ?>
			                        <!-- Details -->
			                        <div class="details">
				                        <?php do_action( 'envira_gallery_before_meta_help_items', $id, $data, $post_id ); ?>
				                         
				                        <div class="filename">
											<strong><?php _e( 'Title', 'envira-gallery' ); ?></strong>
											<?php _e( 'Image titles can take any type of HTML. You can adjust the position of the titles in the main Lightbox settings.', 'envira-gallery' ); ?>
											<br /><br />
				                        </div>
				                        
				                        <div class="filename">
											<strong><?php _e( 'Caption', 'envira-gallery' ); ?></strong>
											<?php _e( 'Caption can take any type of HTML, and are displayed when an image is clicked.', 'envira-gallery' ); ?>
											<br /><br />
				                        </div>
				                        
				                        <div class="filename">
											<strong><?php _e( 'Alt Text', 'envira-gallery' ); ?></strong>
											<?php _e( 'Very important for SEO, the Alt Text describes the image.', 'envira-gallery' ); ?>
											<br /><br />
				                        </div>
				                        
				                        <div class="filename">
											<strong><?php _e( 'URL', 'envira-gallery' ); ?></strong>
											<?php _e( 'Enter a hyperlink if you wish to link this image to somewhere other than its full size image.', 'envira-gallery' ); ?>
											<br /><br />
				                        </div>
				                        
				                        <?php do_action( 'envira_gallery_after_meta_help_items', $id, $data, $post_id ); ?>
			                        </div>
			                        <?php do_action( 'envira_gallery_after_meta_help', $id, $data, $post_id ); ?>
			                        
			                        <?php do_action( 'envira_gallery_before_meta_table', $id, $data, $post_id ); ?>
			                        <!-- Settings -->
			                        <div class="settings">
			                        	<?php do_action( 'envira_gallery_before_meta_settings', $id, $data, $post_id ); ?>
                                        
                                        <!-- Image Title -->
                                        <label class="setting" for="envira-gallery-title-<?php echo $id; ?>">
	                                        <span class="name"><?php _e( 'Title', 'envira-gallery' ); ?></span>
	                                        <input id="envira-gallery-title-<?php echo $id; ?>" class="envira-gallery-title" type="text" name="_envira_gallery[meta_title]" value="<?php echo ( ! empty( $data['title'] ) ? esc_attr( $data['title'] ) : '' ); ?>" data-envira-meta="title" />
                                        </label>
                                        
                                        <!-- Caption -->
                                        <div class="setting">
	                                        <span class="name"><?php _e( 'Caption', 'envira-gallery' ); ?></span>	                                           
	                                        <?php 
		                                    $caption = ( ! empty( $data['caption'] ) ? $data['caption'] : '' );
		                                    wp_editor( $caption, 'envira-gallery-caption-' . $id, array( 
		                                    	'media_buttons' => false, 
		                                    	'wpautop' 		=> false, 
		                                    	'tinymce' 		=> false, 
		                                    	'textarea_name' => '_envira_gallery[meta_caption]', 
		                                    	'quicktags' => array( 
		                                    		'buttons' => 'strong,em,link,ul,ol,li,close' 
		                                    	),
		                                    ) ); 
		                                    ?>   
                                        </div>
                                        
                                        <!-- Alt Text -->
                                        <label class="setting" for="envira-gallery-alt-<?php echo $id; ?>">
	                                        <span class="name"><?php _e( 'Alt Text', 'envira-gallery' ); ?></span>
	                                        <input id="envira-gallery-alt-<?php echo $id; ?>" class="envira-gallery-alt" type="text" name="_envira_gallery[meta_alt]" value="<?php echo ( ! empty( $data['alt'] ) ? esc_attr( $data['alt'] ) : '' ); ?>" data-envira-meta="alt" />
                                        </label>
                                        
                                        <!-- Link -->
                                        <label class="setting" for="envira-gallery-link-<?php echo $id; ?>">
	                                        <span class="name"><?php _e( 'URL', 'envira-gallery' ); ?></span>
	                                        <input id="envira-gallery-link-<?php echo $id; ?>" class="envira-gallery-link" type="text" name="_envira_gallery[meta_link]" value="<?php echo ( ! empty( $data['link'] ) ? esc_attr( $data['link'] ) : '' ); ?>" data-envira-meta="link" />
										</label>
										
										<div id="wp-link">
											<div id="search-panel">
												<div class="link-search-wrapper">
													<label class="setting" for="search-field">
														<input type="search" id="search-field" class="link-search-field" autocomplete="off" placeholder="<?php _e( 'Search WordPress Content for URL', 'envira-gallery' ); ?>" />
														<span class="spinner"></span>
													</label>
												</div>
												<div id="search-results" class="query-results" tabindex="0">
													<ul></ul>
												</div>
											</div>
										</div>
										
										<!-- Link in New Window -->
                                        <label class="setting" for="envira-gallery-link-new-window-<?php echo $id; ?>">
                                        	<span class="name"><?php _e( 'Open URL in New Window?', 'envira-gallery' ); ?></span>
											<input id="envira-gallery-link-new-window-<?php echo $id; ?>" class="envira-gallery-link-new-window" type="checkbox" name="_envira_gallery[meta_link_new_window]" value="1" data-envira-meta="link_new_window"<?php echo ( ( isset( $data['link_new_window'] ) && !empty( $data['link_new_window'] ) ) ? ' checked' : '' ); ?> />
                                        </label>
                                        
                                        <?php do_action( 'envira_gallery_after_meta_settings', $id, $data, $post_id ); ?>
			                        </div>
			                        <!-- /.settings -->     
                                   
                                    <?php do_action( 'envira_gallery_after_meta_table', $id, $data, $post_id ); ?>	
			                        
			                        <!-- Actions -->
			                        <div class="actions">
			                            <a href="#" class="envira-gallery-meta-submit button media-button button-large button-primary media-button-insert" title="<?php esc_attr_e( 'Save Metadata', 'envira-gallery' ); ?>" data-envira-gallery-item="<?php echo $id; ?>"><?php _e( 'Save Metadata', 'envira-gallery' ); ?></a>

										<!-- Save Spinner -->
				                        <span class="settings-save-status">
					                        <span class="spinner"></span>
					                        <span class="saved"><?php _e( 'Saved.', 'envira-gallery' ); ?></span>
				                        </span>
                                    </div>
			                        <!-- /.actions -->
		                        </div>
	                        </div>
                        </div>
                    </div><!-- end .media-frame -->
                </div><!-- end .media-modal-content -->
            </div><!-- end .media-modal -->
            
            <div class="media-modal-backdrop"></div>
        </div>
        <?php
        return ob_get_clean();

    }

    /**
     * Helper method to change a gallery state from pending to active. This is done
     * automatically on post save. For previewing galleries before publishing,
     * simply click the "Preview" button and Envira will load all the images present
     * in the gallery at that time.
     *
     * @since 1.0.0
     *
     * @param int $id The current post ID.
     */
    public function change_gallery_states( $post_id ) {

        $gallery_data = get_post_meta( $post_id, '_eg_gallery_data', true );
        if ( ! empty( $gallery_data['gallery'] ) ) {
            foreach ( (array) $gallery_data['gallery'] as $id => $item ) {
                $gallery_data['gallery'][$id]['status'] = 'active';
            }
        }

        update_post_meta( $post_id, '_eg_gallery_data', $gallery_data );

    }

    /**
     * Helper method to crop gallery thumbnails to the specified sizes.
     *
     * @since 1.0.0
     *
     * @param array $args  Array of args used when cropping the images.
     * @param int $post_id The current post ID.
     */
    public function crop_thumbnails( $args, $post_id ) {

        // Gather all available images to crop.
        $gallery_data = get_post_meta( $post_id, '_eg_gallery_data', true );
        $images       = ! empty( $gallery_data['gallery'] ) ? $gallery_data['gallery'] : false;
        $common       = Envira_Gallery_Common::get_instance();

        // Loop through the images and crop them.
        if ( $images ) {
            // Increase the time limit to account for large image sets and suspend cache invalidations.
            set_time_limit( Envira_Gallery_Common::get_instance()->get_max_execution_time() );
            wp_suspend_cache_invalidation( true );

            foreach ( $images as $id => $item ) {
                // Get the full image attachment. If it does not return the data we need, skip over it.
                $image = wp_get_attachment_image_src( $id, 'full' );
                if ( ! is_array( $image ) ) {
                    continue;
                }

                // Generate the cropped image.
                $cropped_image = $common->resize_image( $image[0], $args['width'], $args['height'], true, $args['position'], $args['quality'], $args['retina'] );

                // If there is an error, possibly output error message, otherwise woot!
                if ( is_wp_error( $cropped_image ) ) {
                    // If debugging is defined, print out the error.
                    if ( defined( 'ENVIRA_GALLERY_CROP_DEBUG' ) && ENVIRA_GALLERY_CROP_DEBUG ) {
                        echo '<pre>' . var_export( $cropped_image->get_error_message(), true ) . '</pre>';
                    }
                } else {
                    $gallery_data['gallery'][$id]['thumb'] = $cropped_image;
                }
            }

            // Turn off cache suspension and flush the cache to remove any cache inconsistencies.
            wp_suspend_cache_invalidation( false );
            wp_cache_flush();

            // Update the gallery data.
            update_post_meta( $post_id, '_eg_gallery_data', $gallery_data );
        }

    }

    /**
     * Helper method to crop gallery images to the specified sizes.
     *
     * @since 1.0.0
     *
     * @param array $args  Array of args used when cropping the images.
     * @param int $post_id The current post ID.
     */
    public function crop_images( $args, $post_id ) {

        // Gather all available images to crop.
        $gallery_data = get_post_meta( $post_id, '_eg_gallery_data', true );
        $images       = ! empty( $gallery_data['gallery'] ) ? $gallery_data['gallery'] : false;
        $common       = Envira_Gallery_Common::get_instance();

        // Loop through the images and crop them.
        if ( $images ) {
            // Increase the time limit to account for large image sets and suspend cache invalidations.
            set_time_limit( Envira_Gallery_Common::get_instance()->get_max_execution_time() );
            wp_suspend_cache_invalidation( true );

            foreach ( $images as $id => $item ) {
                // Get the full image attachment. If it does not return the data we need, skip over it.
                $image = wp_get_attachment_image_src( $id, 'full' );
                if ( ! is_array( $image ) ) {
                    continue;
                }

                // Generate the cropped image.
                $cropped_image = $common->resize_image( $image[0], $args['width'], $args['height'], true, $args['position'], $args['quality'], $args['retina'] );

                // If there is an error, possibly output error message, otherwise woot!
                if ( is_wp_error( $cropped_image ) ) {
                    // If debugging is defined, print out the error.
                    if ( defined( 'ENVIRA_GALLERY_CROP_DEBUG' ) && ENVIRA_GALLERY_CROP_DEBUG ) {
                        echo '<pre>' . var_export( $cropped_image->get_error_message(), true ) . '</pre>';
                    }
                }
            }

            // Turn off cache suspension and flush the cache to remove any cache inconsistencies.
            wp_suspend_cache_invalidation( false );
            wp_cache_flush();
        }

    }

    /**
     * Helper method to flush gallery caches once a gallery is updated.
     *
     * @since 1.0.0
     *
     * @param int $post_id The current post ID.
     * @param string $slug The unique gallery slug.
     */
    public function flush_gallery_caches( $post_id, $slug ) {

        Envira_Gallery_Common::get_instance()->flush_gallery_caches( $post_id, $slug );

    }

    /**
     * Helper method for retrieving config values.
     *
     * @since 1.0.0
     *
     * @global int $id        The current post ID.
     * @global object $post   The current post object.
     * @param string $key     The config key to retrieve.
     * @param string $default A default value to use.
     * @return string         Key value on success, empty string on failure.
     */
    public function get_config( $key, $default = false ) {

        global $id, $post;

        // Get the current post ID. If ajax, grab it from the $_POST variable.
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX && array_key_exists( 'post_id', $_POST ) ) {
            $post_id = absint( $_POST['post_id'] );
        } else {
            $post_id = isset( $post->ID ) ? $post->ID : (int) $id;
        }

        $settings = get_post_meta( $post_id, '_eg_gallery_data', true );
        if ( isset( $settings['config'][$key] ) ) {
            return $settings['config'][$key];
        } else {
            return $default ? $default : '';
        }

    }

    /**
     * Helper method for setting default config values.
     *
     * @since 1.0.0
     *
     * @param string $key The default config key to retrieve.
     * @return string Key value on success, false on failure.
     */
    public function get_config_default( $key ) {

        $instance = Envira_Gallery_Common::get_instance();
        return $instance->get_config_default( $key );

    }

    /**
     * Helper method for retrieving columns.
     *
     * @since 1.0.0
     *
     * @return array Array of column data.
     */
    public function get_columns() {

        $instance = Envira_Gallery_Common::get_instance();
        return $instance->get_columns();

    }

    /**
     * Helper method for retrieving gallery themes.
     *
     * @since 1.0.0
     *
     * @return array Array of gallery theme data.
     */
    public function get_gallery_themes() {

        $instance = Envira_Gallery_Common::get_instance();
        return $instance->get_gallery_themes();

    }
    
    /**
     * Helper method for retrieving description options.
     *
     * @since 1.0.0
     *
     * @return array Array of description options.
     */
    public function get_display_description_options() {

        return array(
	    	array(
		    	'name' 	=> __( 'Do not display', 'envira-gallery' ),
		    	'value' => 0,
			),
			array(
		    	'name' 	=> __( 'Display above galleries', 'envira-gallery' ),
		    	'value' => 'above',
			),
			array(
		    	'name' 	=> __( 'Display below galleries', 'envira-gallery' ),
		    	'value' => 'below',
			),
	    );

    }

    /**
     * Helper method for retrieving lightbox themes.
     *
     * @since 1.0.0
     *
     * @return array Array of lightbox theme data.
     */
    public function get_lightbox_themes() {

        $instance = Envira_Gallery_Common::get_instance();
        return $instance->get_lightbox_themes();

    }

    /**
     * Helper method for retrieving title displays.
     *
     * @since 1.0.0
     *
     * @return array Array of title display data.
     */
    public function get_title_displays() {

        $instance = Envira_Gallery_Common::get_instance();
        return $instance->get_title_displays();

    }

    /**
     * Helper method for retrieving toolbar positions.
     *
     * @since 1.0.0
     *
     * @return array Array of toolbar position data.
     */
    public function get_toolbar_positions() {

        $instance = Envira_Gallery_Common::get_instance();
        return $instance->get_toolbar_positions();

    }

    /**
     * Helper method for retrieving lightbox transition effects.
     *
     * @since 1.0.0
     *
     * @return array Array of transition effect data.
     */
    public function get_transition_effects() {

        $instance = Envira_Gallery_Common::get_instance();
        return $instance->get_transition_effects();

    }

    /**
     * Helper method for retrieving thumbnail positions.
     *
     * @since 1.0.0
     *
     * @return array Array of thumbnail position data.
     */
    public function get_thumbnail_positions() {

        $instance = Envira_Gallery_Common::get_instance();
        return $instance->get_thumbnail_positions();

    }

    /**
     * Returns the post types to skip for loading Envira metaboxes.
     *
     * @since 1.0.7
     *
     * @return array Array of skipped posttypes.
     */
    public function get_skipped_posttypes() {

        $skipped_posttypes = array( 'attachment', 'revision', 'nav_menu_item', 'soliloquy', 'soliloquyv2', 'envira_album' );
        return apply_filters( 'envira_gallery_skipped_posttypes', $skipped_posttypes );

    }

    /**
     * Flag to determine if the GD library has been compiled.
     *
     * @since 1.0.0
     *
     * @return bool True if has proper extension, false otherwise.
     */
    public function has_gd_extension() {

        return extension_loaded( 'gd' ) && function_exists( 'gd_info' );

    }

    /**
     * Flag to determine if the Imagick library has been compiled.
     *
     * @since 1.0.0
     *
     * @return bool True if has proper extension, false otherwise.
     */
    public function has_imagick_extension() {

        return extension_loaded( 'imagick' );

    }

    /**
     * Returns the singleton instance of the class.
     *
     * @since 1.0.0
     *
     * @return object The Envira_Gallery_Metaboxes object.
     */
    public static function get_instance() {

        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Envira_Gallery_Metaboxes ) ) {
            self::$instance = new Envira_Gallery_Metaboxes();
        }

        return self::$instance;

    }

}

// Load the metabox class.
$envira_gallery_metaboxes = Envira_Gallery_Metaboxes::get_instance();