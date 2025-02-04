<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Register a new bulk action.
function ajdwp_register_bulk_actions( $bulk_actions ) {
    $bulk_actions['assign_progress_bar'] = 'Assign Progress Bar';
    return $bulk_actions;
}
add_filter('bulk_actions-edit-page', 'ajdwp_register_bulk_actions');
add_filter('bulk_actions-edit-post', 'ajdwp_register_bulk_actions');

// Process the bulk action.
function ajdwp_handle_bulk_assign( $redirect_to, $doaction, $post_ids ) {
    if ( $doaction !== 'assign_progress_bar' ) {
         return $redirect_to;
    }
    // Ensure a progress bar is selected.
    if ( ! isset($_REQUEST['selected_progress_bar']) || empty($_REQUEST['selected_progress_bar']) ) {
         return $redirect_to;
    }
    $progress_bar = sanitize_text_field($_REQUEST['selected_progress_bar']);
    $progress_bars = get_option(AJDWP_OPTION, array());
    if ( ! isset($progress_bars[$progress_bar]) ) {
         return $redirect_to;
    }
    // Loop through each selected post, assign the progress bar, and append a new step.
    foreach ( $post_ids as $post_id ) {
         $post_title = get_the_title($post_id);
         $post_link  = get_permalink($post_id);
         update_post_meta($post_id, '_ajdwp_progress_bar', $progress_bar);
         $step = array(
              'title'    => $post_title,
              'nickname' => '',
              'link'     => $post_link,
         );
         $progress_bars[$progress_bar][] = $step;
    }
    update_option(AJDWP_OPTION, $progress_bars);
    $redirect_to = add_query_arg('bulk_assigned', count($post_ids), $redirect_to);
    return $redirect_to;
}
add_filter('handle_bulk_actions-edit-page', 'ajdwp_handle_bulk_assign', 10, 3);
add_filter('handle_bulk_actions-edit-post', 'ajdwp_handle_bulk_assign', 10, 3);

// Show admin notice when bulk action completes.
function ajdwp_bulk_admin_notice() {
    if ( ! empty($_REQUEST['bulk_assigned']) ) {
         $count = intval($_REQUEST['bulk_assigned']);
         echo '<div class="updated"><p>Assigned progress bar to ' . $count . ' item(s).</p></div>';
    }
}
add_action('admin_notices', 'ajdwp_bulk_admin_notice');

// Output the inline dropdown in the posts/pages list.
function ajdwp_progress_bulk_action_dropdown() {
    // Retrieve all progress bars saved in the options.
    $progress_bars = get_option(AJDWP_OPTION, array());
    
    // If no progress bars exist, output a message for debugging.
    if ( empty($progress_bars) ) {
        echo '<p style="color:red; margin-left:10px;">No progress bars available. Please create one in the Progress Bar Manager.</p>';
        return;
    }
    
    echo '<select name="selected_progress_bar" id="selected_progress_bar" style="margin-left:10px;">';
    echo '<option value="">Select Progress Bar</option>';
    foreach ( $progress_bars as $key => $steps ) {
        echo '<option value="' . esc_attr($key) . '">' . esc_html($key) . '</option>';
    }
    echo '</select>';
}
add_action('restrict_manage_posts', 'ajdwp_progress_bulk_action_dropdown');
