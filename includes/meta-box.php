<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Add meta box for progress bar assignment.
function ajdwp_add_progress_meta_box() {
    $post_types = array('post', 'page');
    foreach ( $post_types as $ptype ) {
         add_meta_box('ajdwp_progress_meta', 'Progress Bar Assignment', 'ajdwp_progress_meta_box_callback', $ptype, 'side', 'default');
    }
}
add_action('add_meta_boxes', 'ajdwp_add_progress_meta_box');

function ajdwp_progress_meta_box_callback( $post ) {
    wp_nonce_field('ajdwp_save_progress_meta', 'ajdwp_progress_meta_nonce');
    $selected = get_post_meta($post->ID, '_ajdwp_progress_bar', true);
    $progress_bars = get_option(AJDWP_OPTION, array());
    ?>
    <p>
         <label for="ajdwp_progress_bar">Select Progress Bar:</label>
         <select name="ajdwp_progress_bar" id="ajdwp_progress_bar">
              <option value="">None</option>
              <?php foreach ( $progress_bars as $course_key => $steps ) : ?>
              <option value="<?php echo esc_attr($course_key); ?>" <?php selected($selected, $course_key); ?>>
                   <?php echo esc_html($course_key); ?>
              </option>
              <?php endforeach; ?>
         </select>
    </p>
    <?php
}

function ajdwp_save_progress_meta( $post_id ) {
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( ! isset($_POST['ajdwp_progress_meta_nonce']) || ! wp_verify_nonce($_POST['ajdwp_progress_meta_nonce'], 'ajdwp_save_progress_meta') ) return;
    if ( isset($_POST['ajdwp_progress_bar']) ) {
         update_post_meta($post_id, '_ajdwp_progress_bar', sanitize_text_field($_POST['ajdwp_progress_bar']));
    }
}
add_action('save_post', 'ajdwp_save_progress_meta');

// Add custom column to posts/pages list.
function ajdwp_add_progress_column( $columns ) {
    $columns['ajdwp_progress'] = 'Progress Bar';
    return $columns;
}
add_filter('manage_pages_columns', 'ajdwp_add_progress_column');
add_filter('manage_posts_columns', 'ajdwp_add_progress_column');

function ajdwp_show_progress_column( $column, $post_id ) {
    if ( $column === 'ajdwp_progress' ) {
         $assigned = get_post_meta($post_id, '_ajdwp_progress_bar', true);
         if ( empty($assigned) ) {
              echo '';
              return;
         }
         $progress_bars = get_option(AJDWP_OPTION, array());
         if ( isset($progress_bars[$assigned]) ) {
              $found = false;
              $permalink = untrailingslashit(get_permalink($post_id));
              foreach ( $progress_bars[$assigned] as $step ) {
                   if ( untrailingslashit($step['link']) === $permalink ) {
                        $found = true;
                        break;
                   }
              }
              echo $found ? esc_html($assigned) : '<em>Removed</em>';
         } else {
              echo '';
         }
    }
}
add_action('manage_pages_custom_column', 'ajdwp_show_progress_column', 10, 2);
add_action('manage_posts_custom_column', 'ajdwp_show_progress_column', 10, 2);
