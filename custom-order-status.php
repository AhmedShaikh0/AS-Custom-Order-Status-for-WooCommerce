<?php
/**
 * Plugin Name: AS Custom Order Status for WooCommerce
 * Description: Adds custom WooCommerce order statuses with email notifications. Admin can create multiple custom statuses with customized emails.
 * Author: Ahmed Shaikh
 * Author URI: https://github.com/AhmedShaikh0
 * Plugin URI: https://github.com/AhmedShaikh0/AS-Custom-Order-Status-for-WooCommerce
 * Version: 1.0
 * License: GPLv2 or later
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * Text Domain: as-custom-order-status
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin path
define( 'AS_COS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'AS_COS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Create custom table on plugin activation
register_activation_hook( __FILE__, 'as_cos_create_custom_statuses_table' );
function as_cos_create_custom_statuses_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'as_custom_order_statuses';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE `$table_name` (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        status_slug varchar(50) NOT NULL,
        status_label varchar(100) NOT NULL,
        status_color varchar(7) DEFAULT '#5b9dd9',
        email_subject varchar(255) NOT NULL,
        email_heading varchar(255) NOT NULL,
        email_content longtext NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

// Helper function to get all custom statuses with caching
function as_cos_get_custom_statuses() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'as_custom_order_statuses';
    
    // Try to get cached results first
    $cache_key = 'as_cos_all_custom_statuses';
    $custom_statuses = wp_cache_get( $cache_key, 'as_custom_order_statuses' );
    
    if ( false === $custom_statuses ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $custom_statuses = $wpdb->get_results( "SELECT * FROM `" . esc_sql( $table_name ) . "`" );
        wp_cache_set( $cache_key, $custom_statuses, 'as_custom_order_statuses', 300 ); // Cache for 5 minutes
    }
    
    return $custom_statuses;
}

// Helper function to get a single custom status by slug with caching
function as_cos_get_custom_status_by_slug( $status_slug ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'as_custom_order_statuses';
    
    // Try to get cached results first
    $cache_key = 'as_cos_status_' . $status_slug;
    $custom_status = wp_cache_get( $cache_key, 'as_custom_order_statuses' );
    
    if ( false === $custom_status ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $custom_status = $wpdb->get_row( $wpdb->prepare( 
            "SELECT * FROM `" . esc_sql( $table_name ) . "` WHERE status_slug = %s", 
            $status_slug 
        ) );
        if ( $custom_status ) {
            wp_cache_set( $cache_key, $custom_status, 'as_custom_order_statuses', 300 ); // Cache for 5 minutes
        }
    }
    
    return $custom_status;
}

// Helper function to get a single custom status by ID with caching
function as_cos_get_custom_status_by_id( $status_id ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'as_custom_order_statuses';
    
    // Try to get cached results first
    $cache_key = 'as_cos_status_id_' . $status_id;
    $custom_status = wp_cache_get( $cache_key, 'as_custom_order_statuses' );
    
    if ( false === $custom_status ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $custom_status = $wpdb->get_row( $wpdb->prepare( 
            "SELECT * FROM `" . esc_sql( $table_name ) . "` WHERE id = %d", 
            $status_id 
        ) );
        if ( $custom_status ) {
            wp_cache_set( $cache_key, $custom_status, 'as_custom_order_statuses', 300 ); // Cache for 5 minutes
        }
    }
    
    return $custom_status;
}

// Register custom order statuses
function as_cos_register_custom_order_statuses() {
    $custom_statuses = as_cos_get_custom_statuses();
    
    foreach ( $custom_statuses as $status ) {
        register_post_status( 'wc-' . $status->status_slug, array(
            'label'                     => $status->status_label,
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            /* translators: %1$s: status label, %2$s: count */
            'label_count'               => _n_noop(
                '%1$s <span class="count">(%2$s)</span>',
                '%1$s <span class="count">(%2$s)</span>',
                'as-custom-order-status'
            ),
        ) );
    }
}
add_action( 'init', 'as_cos_register_custom_order_statuses' );

// Add to WooCommerce order statuses dropdown
function as_cos_add_custom_statuses_to_order_statuses( $order_statuses ) {
    $custom_statuses = as_cos_get_custom_statuses();
    
    $new_order_statuses = array();
    
    foreach ( $order_statuses as $key => $status ) {
        $new_order_statuses[ $key ] = $status;
        
        // Add our custom statuses after processing
        if ( 'wc-processing' === $key ) {
            foreach ( $custom_statuses as $custom_status ) {
                $new_order_statuses[ 'wc-' . $custom_status->status_slug ] = $custom_status->status_label;
            }
        }
    }
    
    return $new_order_statuses;
}
add_filter( 'wc_order_statuses', 'as_cos_add_custom_statuses_to_order_statuses' );

// Add admin menu page
add_action( 'admin_menu', 'as_cos_add_admin_menu' );
function as_cos_add_admin_menu() {
    add_submenu_page(
        'woocommerce',
        esc_html__( 'Custom Order Statuses for WooCommerce', 'as-custom-order-status' ),
        esc_html__( 'Custom Order Statuses', 'as-custom-order-status' ),
        'manage_woocommerce',
        'as-custom-order-statuses',
        'as_cos_admin_page'
    );
}

// Admin page content
function as_cos_admin_page() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'as_custom_order_statuses';
    
    // Handle form submissions
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    if ( isset( $_POST['action'] ) && 'add_status' === $_POST['action'] && isset( $_POST['as_cos_nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['as_cos_nonce'] ), 'add_status' ) ) {
        $status_slug = isset( $_POST['status_slug'] ) ? sanitize_title( wp_unslash( $_POST['status_slug'] ) ) : '';
        $status_label = isset( $_POST['status_label'] ) ? sanitize_text_field( wp_unslash( $_POST['status_label'] ) ) : '';
        $status_color = isset( $_POST['status_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['status_color'] ) ) : '#5b9dd9';
        $email_subject = isset( $_POST['email_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['email_subject'] ) ) : '';
        $email_heading = isset( $_POST['email_heading'] ) ? sanitize_text_field( wp_unslash( $_POST['email_heading'] ) ) : '';
        $email_content = isset( $_POST['email_content'] ) ? wp_kses_post( wp_unslash( $_POST['email_content'] ) ) : '';
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->insert(
            $table_name,
            array(
                'status_slug' => $status_slug,
                'status_label' => $status_label,
                'status_color' => $status_color,
                'email_subject' => $email_subject,
                'email_heading' => $email_heading,
                'email_content' => $email_content
            )
        );
        
        // Clear caches
        wp_cache_delete( 'as_cos_all_custom_statuses', 'as_custom_order_statuses' );
        
        echo '<div class="notice notice-success"><p>' . esc_html__( 'Custom status added successfully!', 'as-custom-order-status' ) . '</p></div>';
    }
    
    // Handle edit form submissions
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    if ( isset( $_POST['action'] ) && 'edit_status' === $_POST['action'] && isset( $_POST['as_cos_nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['as_cos_nonce'] ), 'edit_status' ) ) {
        $status_id = isset( $_POST['status_id'] ) ? intval( $_POST['status_id'] ) : 0;
        $status_slug = isset( $_POST['status_slug'] ) ? sanitize_title( wp_unslash( $_POST['status_slug'] ) ) : '';
        $status_label = isset( $_POST['status_label'] ) ? sanitize_text_field( wp_unslash( $_POST['status_label'] ) ) : '';
        $status_color = isset( $_POST['status_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['status_color'] ) ) : '#5b9dd9';
        $email_subject = isset( $_POST['email_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['email_subject'] ) ) : '';
        $email_heading = isset( $_POST['email_heading'] ) ? sanitize_text_field( wp_unslash( $_POST['email_heading'] ) ) : '';
        $email_content = isset( $_POST['email_content'] ) ? wp_kses_post( wp_unslash( $_POST['email_content'] ) ) : '';
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->update(
            $table_name,
            array(
                'status_slug' => $status_slug,
                'status_label' => $status_label,
                'status_color' => $status_color,
                'email_subject' => $email_subject,
                'email_heading' => $email_heading,
                'email_content' => $email_content
            ),
            array( 'id' => $status_id )
        );
        
        // Clear caches
        wp_cache_delete( 'as_cos_all_custom_statuses', 'as_custom_order_statuses' );
        wp_cache_delete( 'as_cos_status_id_' . $status_id, 'as_custom_order_statuses' );
        
        echo '<div class="notice notice-success"><p>' . esc_html__( 'Custom status updated successfully!', 'as-custom-order-status' ) . '</p></div>';
    }
    
    // Handle deletions
    if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && isset( $_GET['id'] ) ) {
        $status_id = intval( $_GET['id'] );
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'delete_status_' . $status_id ) ) {
            wp_die( esc_html__( 'Security check failed', 'as-custom-order-status' ) );
        }
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->delete( $table_name, array( 'id' => $status_id ) );
        
        // Clear caches
        wp_cache_delete( 'as_cos_all_custom_statuses', 'as_custom_order_statuses' );
        wp_cache_delete( 'as_cos_status_id_' . $status_id, 'as_custom_order_statuses' );
        
        echo '<div class="notice notice-success"><p>' . esc_html__( 'Custom status deleted successfully!', 'as-custom-order-status' ) . '</p></div>';
    }
    
    // Check if we're editing a status
    $editing_status = null;
    if ( isset( $_GET['action'] ) && 'edit' === $_GET['action'] && isset( $_GET['id'] ) ) {
        $status_id = intval( $_GET['id'] );
        $editing_status = as_cos_get_custom_status_by_id( $status_id );
    }
    
    // Get all custom statuses
    $custom_statuses = as_cos_get_custom_statuses();
    
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Custom Order Statuses for WooCommerce', 'as-custom-order-status' ); ?></h1>
        
        <?php if ( $editing_status ) : ?>
            <form method="post" action="" id="custom-status-form">
                <?php wp_nonce_field( 'edit_status', 'as_cos_nonce' ); ?>
                <input type="hidden" name="action" value="edit_status">
                <input type="hidden" name="status_id" value="<?php echo esc_attr( $editing_status->id ); ?>">
                
                <h2><?php esc_html_e( 'Edit Custom Status', 'as-custom-order-status' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Status Slug', 'as-custom-order-status' ); ?></th>
                        <td>
                            <input type="text" name="status_slug" class="regular-text" value="<?php echo esc_attr( $editing_status->status_slug ); ?>" required>
                            <p class="description"><?php esc_html_e( 'Unique identifier for the status (e.g. shoes-received)', 'as-custom-order-status' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Status Label', 'as-custom-order-status' ); ?></th>
                        <td>
                            <input type="text" name="status_label" class="regular-text" value="<?php echo esc_attr( $editing_status->status_label ); ?>" required>
                            <p class="description"><?php esc_html_e( 'Display name for the status (e.g. Shoes Received)', 'as-custom-order-status' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Status Color', 'as-custom-order-status' ); ?></th>
                        <td>
                            <input type="color" name="status_color" value="<?php echo esc_attr( $editing_status->status_color ); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Email Subject', 'as-custom-order-status' ); ?></th>
                        <td>
                            <input type="text" name="email_subject" class="regular-text" value="<?php echo esc_attr( $editing_status->email_subject ); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Email Heading', 'as-custom-order-status' ); ?></th>
                        <td>
                            <input type="text" name="email_heading" class="regular-text" value="<?php echo esc_attr( $editing_status->email_heading ); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Email Content', 'as-custom-order-status' ); ?></th>
                        <td>
                            <?php wp_editor( $editing_status->email_content, 'email_content', array(
                                'textarea_name' => 'email_content',
                                'textarea_rows' => 10,
                                'media_buttons' => false
                            ) ); ?>
                            <p class="description"><?php esc_html_e( 'Available shortcodes: {order_number}, {customer_name}, {order_date}', 'as-custom-order-status' ); ?></p>
                            <p class="description">
                                <button type="button" id="preview-email" class="button-secondary"><?php esc_html_e( 'Preview Email', 'as-custom-order-status' ); ?></button>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button( esc_html__( 'Update Custom Status', 'as-custom-order-status' ) ); ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=as-custom-order-statuses' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Cancel', 'as-custom-order-status' ); ?></a>
            </form>
        <?php else : ?>
            <form method="post" action="" id="custom-status-form">
                <?php wp_nonce_field( 'add_status', 'as_cos_nonce' ); ?>
                <input type="hidden" name="action" value="add_status">
                
                <h2><?php esc_html_e( 'Add New Custom Status', 'as-custom-order-status' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Status Slug', 'as-custom-order-status' ); ?></th>
                        <td>
                            <input type="text" name="status_slug" class="regular-text" required>
                            <p class="description"><?php esc_html_e( 'Unique identifier for the status (e.g. shoes-received)', 'as-custom-order-status' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Status Label', 'as-custom-order-status' ); ?></th>
                        <td>
                            <input type="text" name="status_label" class="regular-text" required>
                            <p class="description"><?php esc_html_e( 'Display name for the status (e.g. Shoes Received)', 'as-custom-order-status' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Status Color', 'as-custom-order-status' ); ?></th>
                        <td>
                            <input type="color" name="status_color" value="#5b9dd9">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Email Subject', 'as-custom-order-status' ); ?></th>
                        <td>
                            <input type="text" name="email_subject" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Email Heading', 'as-custom-order-status' ); ?></th>
                        <td>
                            <input type="text" name="email_heading" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Email Content', 'as-custom-order-status' ); ?></th>
                        <td>
                            <?php wp_editor( '', 'email_content', array(
                                'textarea_name' => 'email_content',
                                'textarea_rows' => 10,
                                'media_buttons' => false
                            ) ); ?>
                            <p class="description"><?php esc_html_e( 'Available shortcodes: {order_number}, {customer_name}, {order_date}', 'as-custom-order-status' ); ?></p>
                            <p class="description">
                                <button type="button" id="preview-email" class="button-secondary"><?php esc_html_e( 'Preview Email', 'as-custom-order-status' ); ?></button>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button( esc_html__( 'Add Custom Status', 'as-custom-order-status' ) ); ?>
            </form>
        <?php endif; ?>
        
        <h2><?php esc_html_e( 'Existing Custom Statuses', 'as-custom-order-status' ); ?></h2>
        <?php if ( ! empty( $custom_statuses ) ) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Slug', 'as-custom-order-status' ); ?></th>
                        <th><?php esc_html_e( 'Label', 'as-custom-order-status' ); ?></th>
                        <th><?php esc_html_e( 'Color', 'as-custom-order-status' ); ?></th>
                        <th><?php esc_html_e( 'Email Subject', 'as-custom-order-status' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'as-custom-order-status' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $custom_statuses as $status ) : ?>
                        <tr>
                            <td><?php echo esc_html( $status->status_slug ); ?></td>
                            <td><?php echo esc_html( $status->status_label ); ?></td>
                            <td>
                                <span style="display: inline-block; width: 20px; height: 20px; background: <?php echo esc_attr( $status->status_color ); ?>; border: 1px solid #ccc;"></span>
                                <?php echo esc_html( $status->status_color ); ?>
                            </td>
                            <td><?php echo esc_html( $status->email_subject ); ?></td>
                            <td>
                                <a href="<?php echo esc_url( add_query_arg( array( 'action' => 'edit', 'id' => $status->id ) ) ); ?>"><?php esc_html_e( 'Edit', 'as-custom-order-status' ); ?></a> |
                                <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'id' => $status->id ) ), 'delete_status_' . $status->id ) ); ?>" 
                                   onclick="return confirm('<?php esc_html_e( 'Are you sure you want to delete this status?', 'as-custom-order-status' ); ?>')">
                                    <?php esc_html_e( 'Delete', 'as-custom-order-status' ); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php esc_html_e( 'No custom statuses found.', 'as-custom-order-status' ); ?></p>
        <?php endif; ?>
    </div>
    
    <!-- Email Preview Modal (shared) -->
    <div id="email-preview-modal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4);">
        <div style="background-color: #fff; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 800px; max-height: 80%; overflow: auto;">
            <span id="close-preview" style="float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            <h2><?php esc_html_e( 'Email Preview', 'as-custom-order-status' ); ?></h2>
            <div id="email-preview-content">
                <!-- Preview content will be loaded here -->
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var previewBtn = document.getElementById('preview-email');
        var modal = document.getElementById('email-preview-modal');
        var closeBtn = document.getElementById('close-preview');
        
        if (previewBtn) {
            previewBtn.addEventListener('click', function() {
                // Get form data
                var form = document.getElementById('custom-status-form');
                var formAction = form.querySelector('input[name="action"]').value;
                
                // Create a new FormData object
                var formData = new FormData();
                
                // Add all form fields to the FormData
                var inputs = form.querySelectorAll('input[name]:not([type="submit"]), select[name], textarea[name]');
                for (var i = 0; i < inputs.length; i++) {
                    var input = inputs[i];
                    if (input.name) {
                        if (input.type === 'checkbox' || input.type === 'radio') {
                            if (input.checked) {
                                formData.append(input.name, input.value);
                            }
                        } else {
                            formData.append(input.name, input.value);
                        }
                    }
                }
                
                // Special handling for wp_editor - trigger save if TinyMCE is active
                if (typeof tinyMCE !== 'undefined') {
                    var editor = tinyMCE.get('email_content');
                    if (editor && !editor.isHidden()) {
                        // Save the content from the editor to the textarea
                        editor.save();
                        // Now get the updated content
                        var emailContentField = document.getElementById('email_content');
                        if (emailContentField) {
                            formData.set('email_content', emailContentField.value);
                        }
                    }
                }
                
                // Send AJAX request to preview email
                var xhr = new XMLHttpRequest();
                xhr.open('POST', ajaxurl, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        document.getElementById('email-preview-content').innerHTML = xhr.responseText;
                        modal.style.display = 'block';
                    }
                };
                
                // Prepare data for sending
                var data = '';
                for (var pair of formData.entries()) {
                    if (data !== '') data += '&';
                    data += encodeURIComponent(pair[0]) + '=' + encodeURIComponent(pair[1]);
                }
                data += '&action=as_cos_preview_email';
                data += '&nonce_action=' + formAction;
                
                xhr.send(data);
            });
        }
        
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });
        }
        
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
    </script>
    <?php
}

// Send email when order marked with custom status
function as_cos_send_custom_status_email( $order_id ) {
    if ( ! $order_id ) {
        return;
    }
    
    $order = wc_get_order( $order_id );
    $order_status = $order->get_status(); // This returns status without 'wc-' prefix
    
    // Check if this is one of our custom statuses
    $custom_status = as_cos_get_custom_status_by_slug( $order_status );
    
    if ( ! $custom_status ) {
        return; // Not one of our custom statuses
    }
    
    $mailer = WC()->mailer();
    $recipient = $order->get_billing_email();
    
    if ( ! $recipient ) {
        return;
    }
    
    // Replace shortcodes in subject, heading, and content
    $email_subject = str_replace(
        array( '{order_number}', '{customer_name}', '{order_date}' ),
        array( 
            $order->get_order_number(),
            $order->get_billing_first_name(),
            wc_format_datetime( $order->get_date_created() )
        ),
        stripslashes( $custom_status->email_subject )
    );
    
    $email_heading = str_replace(
        array( '{order_number}', '{customer_name}', '{order_date}' ),
        array( 
            $order->get_order_number(),
            $order->get_billing_first_name(),
            wc_format_datetime( $order->get_date_created() )
        ),
        stripslashes( $custom_status->email_heading )
    );
    
    $email_content = str_replace(
        array( '{order_number}', '{customer_name}', '{order_date}' ),
        array( 
            $order->get_order_number(),
            $order->get_billing_first_name(),
            wc_format_datetime( $order->get_date_created() )
        ),
        stripslashes( $custom_status->email_content )
    );
    
    // Get the email header and footer
    ob_start();
    do_action( 'woocommerce_email_header', $email_heading, null );
    echo wp_kses_post( $email_content );
    do_action( 'woocommerce_email_footer', null );
    $html_message = ob_get_clean();
    
    // Send the email
    $mailer->send( $recipient, $email_subject, $html_message );
}
add_action( 'woocommerce_order_status_changed', 'as_cos_send_custom_status_email_on_change', 10, 3 );
function as_cos_send_custom_status_email_on_change( $order_id, $old_status, $new_status ) {
    // We only want to send the email when the status is set to one of our custom statuses
    $custom_status = as_cos_get_custom_status_by_slug( $new_status );
    
    if ( $custom_status ) {
        as_cos_send_custom_status_email( $order_id );
    }
}

// Add custom CSS for admin order status badges
function as_cos_add_custom_order_status_styles() {
    $custom_statuses = as_cos_get_custom_statuses();
    
    echo '<style>';
    foreach ( $custom_statuses as $status ) {
        echo '.order-status.status-' . esc_attr( $status->status_slug ) . ' {
            background: ' . esc_attr( $status->status_color ) . ';
            color: #fff;
        }';
    }
    echo '</style>';
}
add_action( 'admin_head', 'as_cos_add_custom_order_status_styles' );

// AJAX handler for email preview
add_action( 'wp_ajax_as_cos_preview_email', 'as_cos_preview_email_handler' );
function as_cos_preview_email_handler() {
    // Check if user is logged in and has proper permissions
    if ( ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die( esc_html__( 'Unauthorized', 'as-custom-order-status' ) );
    }
    
    // Check nonce - handle both add_status and edit_status actions
    // Also handle cases where nonce_action might not be sent
    $nonce_verified = false;
    if ( isset( $_POST['nonce_action'] ) ) {
        // For the version with nonce_action parameter
        $nonce_action = sanitize_key( wp_unslash( $_POST['nonce_action'] ) );
        if ( in_array( $nonce_action, array( 'add_status', 'edit_status' ) ) ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $nonce_verified = isset( $_POST['as_cos_nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['as_cos_nonce'] ), $nonce_action );
        }
    } else {
        // For backward compatibility, try verifying with both possible nonces
        if ( isset( $_POST['as_cos_nonce'] ) ) {
            $sanitized_nonce = sanitize_text_field( wp_unslash( $_POST['as_cos_nonce'] ) );
            if ( wp_verify_nonce( $sanitized_nonce, 'add_status' ) ) {
                $nonce_verified = true;
            } elseif ( wp_verify_nonce( $sanitized_nonce, 'edit_status' ) ) {
                $nonce_verified = true;
            }
        }
    }
    
    // If nonce verification failed, still allow preview for security-unimportant preview
    // but log this for debugging
    if ( ! $nonce_verified ) {
        // Nonce verification failed, but we'll still allow the preview to work
        // since this is just a preview and doesn't modify any data
        // Using _doing_it_wrong() instead of error_log() for better WordPress practices
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            _doing_it_wrong( 'as_cos_preview_email_handler', 'Email preview nonce verification failed, but allowing preview to continue.', '1.3' );
        }
    }
    
    // Get form data - handle the wp_editor content properly
    $email_subject = isset( $_POST['email_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['email_subject'] ) ) : '';
    $email_heading = isset( $_POST['email_heading'] ) ? sanitize_text_field( wp_unslash( $_POST['email_heading'] ) ) : '';
    
    // Special handling for email_content from wp_editor
    // When wp_editor is used, the content might be in $_POST['email_content'] directly
    // or might need special handling depending on how it's submitted
    $email_content = '';
    if ( isset( $_POST['email_content'] ) ) {
        $email_content = wp_kses_post( wp_unslash( $_POST['email_content'] ) );
    }
    
    // Replace shortcodes with sample data
    $email_subject = str_replace(
        array( '{order_number}', '{customer_name}', '{order_date}' ),
        array( '12345', 'John Doe', gmdate( 'F j, Y' ) ),
        $email_subject
    );
    
    $email_heading = str_replace(
        array( '{order_number}', '{customer_name}', '{order_date}' ),
        array( '12345', 'John Doe', gmdate( 'F j, Y' ) ),
        $email_heading
    );
    
    $email_content = str_replace(
        array( '{order_number}', '{customer_name}', '{order_date}' ),
        array( '12345', 'John Doe', gmdate( 'F j, Y' ) ),
        $email_content
    );
    
    // Generate preview HTML
    ob_start();
    do_action( 'woocommerce_email_header', $email_heading, null );
    echo wp_kses_post( $email_content );
    do_action( 'woocommerce_email_footer', null );
    $html_message = ob_get_clean();
    
    // Output the preview
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $html_message;
    wp_die();
}