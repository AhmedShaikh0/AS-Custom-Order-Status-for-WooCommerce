// Admin page content
function as_cos_admin_page() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'as_custom_order_statuses';
    
    // Handle form submissions
    if ( isset( $_POST['action'] ) && $_POST['action'] === 'add_status' && wp_verify_nonce( $_POST['as_cos_nonce'], 'add_status' ) ) {
        $status_slug = sanitize_title( $_POST['status_slug'] );
        $status_label = sanitize_text_field( $_POST['status_label'] );
        $status_color = sanitize_hex_color( $_POST['status_color'] );
        $email_subject = sanitize_text_field( $_POST['email_subject'] );
        $email_heading = sanitize_text_field( $_POST['email_heading'] );
        $email_content = wp_kses_post( $_POST['email_content'] );
        
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
        
        echo '<div class="notice notice-success"><p>' . esc_html__( 'Custom status added successfully!', 'as-custom-order-status' ) . '</p></div>';
    }
    
    // Handle edit form submissions
    if ( isset( $_POST['action'] ) && $_POST['action'] === 'edit_status' && wp_verify_nonce( $_POST['as_cos_nonce'], 'edit_status' ) ) {
        $status_id = intval( $_POST['status_id'] );
        $status_slug = sanitize_title( $_POST['status_slug'] );
        $status_label = sanitize_text_field( $_POST['status_label'] );
        $status_color = sanitize_hex_color( $_POST['status_color'] );
        $email_subject = sanitize_text_field( $_POST['email_subject'] );
        $email_heading = sanitize_text_field( $_POST['email_heading'] );
        $email_content = wp_kses_post( $_POST['email_content'] );
        
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
        
        echo '<div class="notice notice-success"><p>' . esc_html__( 'Custom status updated successfully!', 'as-custom-order-status' ) . '</p></div>';
    }
    
    // Handle deletions
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) ) {
        if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'delete_status_' . $_GET['id'] ) ) {
            wp_die( esc_html__( 'Security check failed', 'as-custom-order-status' ) );
        }
        
        $wpdb->delete( $table_name, array( 'id' => intval( $_GET['id'] ) ) );
        
        echo '<div class="notice notice-success"><p>' . esc_html__( 'Custom status deleted successfully!', 'as-custom-order-status' ) . '</p></div>';
    }
    
    // Check if we're editing a status
    $editing_status = null;
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' && isset( $_GET['id'] ) ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $editing_status = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . esc_sql( $table_name ) . "` WHERE id = %d", intval( $_GET['id'] ) ) );
    }
    
    // Get all custom statuses
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $custom_statuses = $wpdb->get_results( "SELECT * FROM `" . esc_sql( $table_name ) . "`" );
    
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Custom Order Statuses', 'as-custom-order-status' ); ?></h1>
        
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