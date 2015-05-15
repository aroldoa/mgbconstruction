<?php
/**
 * Handles all admin ajax interactions for the Envira Gallery plugin.
 *
 * @since 1.0.0
 *
 * @package Envira_Gallery
 * @author  Thomas Griffin
 */

add_action( 'wp_ajax_envira_gallery_change_type', 'envira_gallery_ajax_change_type' );
/**
 * Changes the type of gallery to the user selection.
 *
 * @since 1.0.0
 */
function envira_gallery_ajax_change_type() {

    // Run a security check first.
    check_ajax_referer( 'envira-gallery-change-type', 'nonce' );

    // Prepare variables.
    $post_id = absint( $_POST['post_id'] );
    $post    = get_post( $post_id );
    $type    = stripslashes( $_POST['type'] );

    // Retrieve the data for the type selected.
    ob_start();
    $instance = Envira_Gallery_Metaboxes::get_instance();
    $instance->images_display( $type, $post );
    $html = ob_get_clean();

    // Send back the response.
    echo json_encode( array( 'type' => $type, 'html' => $html ) );
    die;

}

add_action( 'wp_ajax_envira_gallery_load_image', 'envira_gallery_ajax_load_image' );
/**
 * Loads an image into a gallery.
 *
 * @since 1.0.0
 */
function envira_gallery_ajax_load_image() {

    // Run a security check first.
    check_ajax_referer( 'envira-gallery-load-image', 'nonce' );

    // Prepare variables.
    $id      = absint( $_POST['id'] );
    $post_id = absint( $_POST['post_id'] );

    // Set post meta to show that this image is attached to one or more Envira galleries.
    $has_gallery = get_post_meta( $id, '_eg_has_gallery', true );
    if ( empty( $has_gallery ) ) {
        $has_gallery = array();
    }

    $has_gallery[] = $post_id;
    update_post_meta( $id, '_eg_has_gallery', $has_gallery );

    // Set post meta to show that this image is attached to a gallery on this page.
    $in_gallery = get_post_meta( $post_id, '_eg_in_gallery', true );
    if ( empty( $in_gallery ) ) {
        $in_gallery = array();
    }

    $in_gallery[] = $id;
    update_post_meta( $post_id, '_eg_in_gallery', $in_gallery );

    // Set data and order of image in gallery.
    $gallery_data = get_post_meta( $post_id, '_eg_gallery_data', true );
    if ( empty( $gallery_data ) ) {
        $gallery_data = array();
    }

    // If no gallery ID has been set, set it now.
    if ( empty( $gallery_data['id'] ) ) {
        $gallery_data['id'] = $post_id;
    }

    // Set data and update the meta information.
    $gallery_data = envira_gallery_ajax_prepare_gallery_data( $gallery_data, $id );
    update_post_meta( $post_id, '_eg_gallery_data', $gallery_data );

    // Run hook before building out the item.
    do_action( 'envira_gallery_ajax_load_image', $id, $post_id );

    // Build out the individual HTML output for the gallery image that has just been uploaded.
    $html = Envira_Gallery_Metaboxes::get_instance()->get_gallery_item( $id, $gallery_data['gallery'][$id], $post_id );

    // Allow addons to filter the HTML output
    $html = apply_filters( 'envira_gallery_ajax_get_gallery_item_html', $html, $gallery_data, $id, $post_id );

    // Flush the gallery cache.
    Envira_Gallery_Common::get_instance()->flush_gallery_caches( $post_id );

    echo json_encode( $html );
    die;

}

add_action( 'wp_ajax_envira_gallery_load_library', 'envira_gallery_ajax_load_library' );
/**
 * Loads the Media Library images into the media modal window for selection.
 *
 * @since 1.0.0
 */
function envira_gallery_ajax_load_library() {

    // Run a security check first.
    check_ajax_referer( 'envira-gallery-load-gallery', 'nonce' );

    // Prepare variables.
    $offset  = (int) $_POST['offset'];
    $search  = trim( stripslashes( $_POST['search'] ) );
    $post_id = absint( $_POST['post_id'] );
    $html    = '';

    // Build args
    $args = array(
        'post_type'     => 'attachment', 
        'post_mime_type'=> 'image', 
        'post_status'   => 'any', 
        'posts_per_page'=> 20, 
        'offset'        => $offset,
    );

    // Add search term to args if not empty
    if ( ! empty( $search ) ) {
        $args['s'] = $search;
    }

    // Grab the library contents
    $library = get_posts( $args );
    if ( $library ) {
        foreach ( (array) $library as $image ) {
            $has_gallery = get_post_meta( $image->ID, '_eg_has_gallery', true );
            $class       = $has_gallery && in_array( $post_id, (array) $has_gallery ) ? ' selected envira-gallery-in-gallery' : '';

            $html .= '<li class="attachment' . $class . '" data-attachment-id="' . absint( $image->ID ) . '">';
                $html .= '<div class="attachment-preview landscape">';
                    $html .= '<div class="thumbnail">';
                        $html .= '<div class="centered">';
                            $src = wp_get_attachment_image_src( $image->ID, 'thumbnail' );
                            $html .= '<img src="' . esc_url( $src[0] ) . '" />';
                        $html .= '</div>';
                    $html .= '</div>';
                    $html .= '<a class="check" href="#"><div class="media-modal-icon"></div></a>';
                $html .= '</div>';
            $html .= '</li>';
        }
    }

    echo json_encode( array( 'html' => stripslashes( $html ) ) );
    die;

}

add_action( 'wp_ajax_envira_gallery_insert_images', 'envira_gallery_ajax_insert_images' );
/**
 * Inserts one or more images from the Media Library into a gallery.
 *
 * @since 1.0.0
 */
function envira_gallery_ajax_insert_images() {

    // Run a security check first.
    check_ajax_referer( 'envira-gallery-insert-images', 'nonce' );

    // Prepare variables.
    $images = array();
    if ( isset( $_POST['images'] ) ) {
        $images  = stripslashes_deep( (array) $_POST['images'] );
    }
    
    $post_id = absint( $_POST['post_id'] );

    // Grab and update any gallery data if necessary.
    $in_gallery = get_post_meta( $post_id, '_eg_in_gallery', true );
    if ( empty( $in_gallery ) ) {
        $in_gallery = array();
    }

    // Set data and order of image in gallery.
    $gallery_data = get_post_meta( $post_id, '_eg_gallery_data', true );
    if ( empty( $gallery_data ) ) {
        $gallery_data = array();
    }

    // If no gallery ID has been set, set it now.
    if ( empty( $gallery_data['id'] ) ) {
        $gallery_data['id'] = $post_id;
    }

    // Loop through the images and add them to the gallery.
    foreach ( (array) $images as $i => $id ) {
        // Update the attachment image post meta first.
        $has_gallery = get_post_meta( $id, '_eg_has_gallery', true );
        if ( empty( $has_gallery ) ) {
            $has_gallery = array();
        }

        $has_gallery[] = $post_id;
        update_post_meta( $id, '_eg_has_gallery', $has_gallery );

        // Now add the image to the gallery for this particular post.
        $in_gallery[] = $id;
        $gallery_data = envira_gallery_ajax_prepare_gallery_data( $gallery_data, $id );
    }

    // Update the gallery data.
    update_post_meta( $post_id, '_eg_in_gallery', $in_gallery );
    update_post_meta( $post_id, '_eg_gallery_data', $gallery_data );

    // Run hook before finishing.
    do_action( 'envira_gallery_ajax_insert_images', $images, $post_id );

    // Flush the gallery cache.
    Envira_Gallery_Common::get_instance()->flush_gallery_caches( $post_id );

    echo json_encode( true );
    die;

}

add_action( 'wp_ajax_envira_gallery_sort_images', 'envira_gallery_ajax_sort_images' );
/**
 * Sorts images based on user-dragged position in the gallery.
 *
 * @since 1.0.0
 */
function envira_gallery_ajax_sort_images() {

    // Run a security check first.
    check_ajax_referer( 'envira-gallery-sort', 'nonce' );

    // Prepare variables.
    $order        = explode( ',', $_POST['order'] );
    $post_id      = absint( $_POST['post_id'] );
    $gallery_data = get_post_meta( $post_id, '_eg_gallery_data', true );
    $new_order    = array();

    // Loop through the order and generate a new array based on order received.
    foreach ( $order as $id ) {
        $new_order['gallery'][$id] = $gallery_data['gallery'][$id];
    }

    // Update the gallery data.
    update_post_meta( $post_id, '_eg_gallery_data', $new_order );

    // Flush the gallery cache.
    Envira_Gallery_Common::get_instance()->flush_gallery_caches( $post_id );

    echo json_encode( true );
    die;

}

add_action( 'wp_ajax_envira_gallery_remove_image', 'envira_gallery_ajax_remove_image' );
/**
 * Removes an image from a gallery.
 *
 * @since 1.0.0
 */
function envira_gallery_ajax_remove_image() {

    // Run a security check first.
    check_ajax_referer( 'envira-gallery-remove-image', 'nonce' );

    // Prepare variables.
    $post_id      = absint( $_POST['post_id'] );
    $attach_id    = absint( $_POST['attachment_id'] );
    $gallery_data = get_post_meta( $post_id, '_eg_gallery_data', true );
    $in_gallery   = get_post_meta( $post_id, '_eg_in_gallery', true );
    $has_gallery  = get_post_meta( $attach_id, '_eg_has_gallery', true );

    // Unset the image from the gallery, in_gallery and has_gallery checkers.
    unset( $gallery_data['gallery'][$attach_id] );

    if ( ( $key = array_search( $attach_id, (array) $in_gallery ) ) !== false ) {
        unset( $in_gallery[$key] );
    }

    if ( ( $key = array_search( $post_id, (array) $has_gallery ) ) !== false ) {
        unset( $has_gallery[$key] );
    }

    // Update the gallery data.
    update_post_meta( $post_id, '_eg_gallery_data', $gallery_data );
    update_post_meta( $post_id, '_eg_in_gallery', $in_gallery );
    update_post_meta( $attach_id, '_eg_has_gallery', $has_gallery );

    // Run hook before finishing the reponse.
    do_action( 'envira_gallery_ajax_remove_image', $attach_id, $post_id );

    // Flush the gallery cache.
    Envira_Gallery_Common::get_instance()->flush_gallery_caches( $post_id );

    echo json_encode( true );
    die;

}

add_action( 'wp_ajax_envira_gallery_remove_images', 'envira_gallery_ajax_remove_images' );
/**
 * Removes multiple images from a gallery.
 *
 * @since 1.3.2.4
 */
function envira_gallery_ajax_remove_images() {

    // Run a security check first.
    check_ajax_referer( 'envira-gallery-remove-image', 'nonce' );

    // Prepare variables.
    $post_id      = absint( $_POST['post_id'] );
    $attach_ids   = (array) $_POST['attachment_ids'];
    $gallery_data = get_post_meta( $post_id, '_eg_gallery_data', true );
    $in_gallery   = get_post_meta( $post_id, '_eg_in_gallery', true );

    foreach ( (array) $attach_ids as $attach_id ) {
        $has_gallery  = get_post_meta( $attach_id, '_eg_has_gallery', true );

        // Unset the image from the gallery, in_gallery and has_gallery checkers.
        unset( $gallery_data['gallery'][$attach_id] );

        if ( ( $key = array_search( $attach_id, (array) $in_gallery ) ) !== false ) {
            unset( $in_gallery[$key] );
        }

        if ( ( $key = array_search( $post_id, (array) $has_gallery ) ) !== false ) {
            unset( $has_gallery[$key] );
        }

        // Update the attachment data.
        update_post_meta( $attach_id, '_eg_has_gallery', $has_gallery );
    }

    // Update the gallery data
    update_post_meta( $post_id, '_eg_gallery_data', $gallery_data );
    update_post_meta( $post_id, '_eg_in_gallery', $in_gallery );

    // Run hook before finishing the reponse.
    do_action( 'envira_gallery_ajax_remove_images', $attach_id, $post_id );

    // Flush the gallery cache.
    Envira_Gallery_Common::get_instance()->flush_gallery_caches( $post_id );

    echo json_encode( true );
    die;

}

add_action( 'wp_ajax_envira_gallery_save_meta', 'envira_gallery_ajax_save_meta' );
/**
 * Saves the metadata for an image in a gallery.
 *
 * @since 1.0.0
 */
function envira_gallery_ajax_save_meta() {

    // Run a security check first.
    check_ajax_referer( 'envira-gallery-save-meta', 'nonce' );

    // Prepare variables.
    $post_id      = absint( $_POST['post_id'] );
    $attach_id    = absint( $_POST['attach_id'] );
    $meta         = $_POST['meta'];
    $gallery_data = get_post_meta( $post_id, '_eg_gallery_data', true );

    if ( isset( $meta['title'] ) ) {
        $gallery_data['gallery'][$attach_id]['title'] = trim( $meta['title'] );
    }

    if ( isset( $meta['alt'] ) ) {
        $gallery_data['gallery'][$attach_id]['alt'] = trim( esc_html( $meta['alt'] ) );
    }

    if ( isset( $meta['link'] ) ) {
        $gallery_data['gallery'][$attach_id]['link'] = esc_url( $meta['link'] );
    }

    if ( isset( $meta['link_new_window'] ) ) {
        $gallery_data['gallery'][$attach_id]['link_new_window'] = trim( $meta['link_new_window'] );
    }
    
    if ( isset( $meta['caption'] ) ) {
        $gallery_data['gallery'][$attach_id]['caption'] = trim( $meta['caption'] );
    }

    // Allow filtering of meta before saving.
    $gallery_data = apply_filters( 'envira_gallery_ajax_save_meta', $gallery_data, $meta, $attach_id, $post_id );

    // Update the gallery data.
    update_post_meta( $post_id, '_eg_gallery_data', $gallery_data );

    // Flush the gallery cache.
    Envira_Gallery_Common::get_instance()->flush_gallery_caches( $post_id );

    echo json_encode( true );
    die;

}

add_action( 'wp_ajax_envira_gallery_refresh', 'envira_gallery_ajax_refresh' );
/**
 * Refreshes the DOM view for a gallery.
 *
 * @since 1.0.0
 */
function envira_gallery_ajax_refresh() {

    // Run a security check first.
    check_ajax_referer( 'envira-gallery-refresh', 'nonce' );

    // Prepare variables.
    $post_id = absint( $_POST['post_id'] );
    $gallery = '';

    // Grab all gallery data.
    $gallery_data = get_post_meta( $post_id, '_eg_gallery_data', true );

    // If there are no gallery items, don't do anything.
    if ( empty( $gallery_data ) || empty( $gallery_data['gallery'] ) ) {
        echo json_encode( array( 'error' => true ) );
        die;
    }

    // Loop through the data and build out the gallery view.
    foreach ( (array) $gallery_data['gallery'] as $id => $data ) {
        $gallery .= Envira_Gallery_Metaboxes::get_instance()->get_gallery_item( $id, $data, $post_id );
    }

    echo json_encode( array( 'success' => $gallery ) );
    die;

}

add_action( 'wp_ajax_envira_gallery_load_gallery_data', 'envira_gallery_ajax_load_gallery_data' );
/**
 * Retrieves and return gallery data for the specified ID.
 *
 * @since 1.0.0
 */
function envira_gallery_ajax_load_gallery_data() {

    // Prepare variables and grab the gallery data.
    $gallery_id   = absint( $_POST['post_id'] );
    $gallery_data = get_post_meta( $gallery_id, '_eg_gallery_data', true );

    // Send back the gallery data.
    echo json_encode( $gallery_data );
    die;

}

add_action( 'wp_ajax_envira_gallery_install_addon', 'envira_gallery_ajax_install_addon' );
/**
 * Installs an Envira addon.
 *
 * @since 1.0.0
 */
function envira_gallery_ajax_install_addon() {

    // Run a security check first.
    check_ajax_referer( 'envira-gallery-install', 'nonce' );

    // Install the addon.
    if ( isset( $_POST['plugin'] ) ) {
        $download_url = $_POST['plugin'];
        global $hook_suffix;

        // Set the current screen to avoid undefined notices.
        set_current_screen();

        // Prepare variables.
        $method = '';
        $url    = add_query_arg(
            array(
                'page' => 'envira-gallery-settings'
            ),
            admin_url( 'admin.php' )
        );
        $url = esc_url( $url );

        // Start output bufferring to catch the filesystem form if credentials are needed.
        ob_start();
        if ( false === ( $creds = request_filesystem_credentials( $url, $method, false, false, null ) ) ) {
            $form = ob_get_clean();
            echo json_encode( array( 'form' => $form ) );
            die;
        }

        // If we are not authenticated, make it happen now.
        if ( ! WP_Filesystem( $creds ) ) {
            ob_start();
            request_filesystem_credentials( $url, $method, true, false, null );
            $form = ob_get_clean();
            echo json_encode( array( 'form' => $form ) );
            die;
        }

        // We do not need any extra credentials if we have gotten this far, so let's install the plugin.
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once plugin_dir_path( Envira_Gallery::get_instance()->file ) . 'includes/admin/skin.php';

        // Create the plugin upgrader with our custom skin.
        $installer = new Plugin_Upgrader( $skin = new Envira_Gallery_Skin() );
        $installer->install( $download_url );

        // Flush the cache and return the newly installed plugin basename.
        wp_cache_flush();
        if ( $installer->plugin_info() ) {
            $plugin_basename = $installer->plugin_info();
            echo json_encode( array( 'plugin' => $plugin_basename ) );
            die;
        }
    }

    // Send back a response.
    echo json_encode( true );
    die;

}

add_action( 'wp_ajax_envira_gallery_activate_addon', 'envira_gallery_ajax_activate_addon' );
/**
 * Activates an Envira addon.
 *
 * @since 1.0.0
 */
function envira_gallery_ajax_activate_addon() {

    // Run a security check first.
    check_ajax_referer( 'envira-gallery-activate', 'nonce' );

    // Activate the addon.
    if ( isset( $_POST['plugin'] ) ) {
        $activate = activate_plugin( $_POST['plugin'] );

        if ( is_wp_error( $activate ) ) {
            echo json_encode( array( 'error' => $activate->get_error_message() ) );
            die;
        }
    }

    echo json_encode( true );
    die;

}

add_action( 'wp_ajax_envira_gallery_deactivate_addon', 'envira_gallery_ajax_deactivate_addon' );
/**
 * Deactivates an Envira addon.
 *
 * @since 1.0.0
 */
function envira_gallery_ajax_deactivate_addon() {

    // Run a security check first.
    check_ajax_referer( 'envira-gallery-deactivate', 'nonce' );

    // Deactivate the addon.
    if ( isset( $_POST['plugin'] ) ) {
        $deactivate = deactivate_plugins( $_POST['plugin'] );
    }

    echo json_encode( true );
    die;

}

/**
 * Helper function to prepare the metadata for an image in a gallery.
 *
 * @since 1.0.0
 *
 * @param array $gallery_data  Array of data for the gallery.
 * @param int $id              The attachment ID to prepare data for.
 * @return array $gallery_data Amended gallery data with updated image metadata.
 */
function envira_gallery_ajax_prepare_gallery_data( $gallery_data, $id ) {

    $attachment = get_post( $id );
    $url        = wp_get_attachment_image_src( $id, 'full' );
    $alt_text   = get_post_meta( $id, '_wp_attachment_image_alt', true );
    $gallery_data['gallery'][$id] = array(
        'status'  => 'pending',
        'src'     => isset( $url[0] ) ? esc_url( $url[0] ) : '',
        'title'   => get_the_title( $id ),
        'link'    => isset( $url[0] ) ? esc_url( $url[0] ) : '',
        'alt'     => ! empty( $alt_text ) ? $alt_text : '',
        'caption' => ! empty( $attachment->post_excerpt ) ? $attachment->post_excerpt : '',
        'thumb'   => ''
    );

    $gallery_data = apply_filters( 'envira_gallery_ajax_item_data', $gallery_data, $attachment, $id );

    return $gallery_data;

}