<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode to display progress bar.
 * Usage: [progress_bar] or [progress_bar course="course-key"]
 */
function ajdwp_progress_bar_shortcode( $atts ) {
    $atts = shortcode_atts(array(
         'course' => ''
    ), $atts, 'progress_bar');
    $course = sanitize_text_field($atts['course']);
    if ( empty($course) && is_singular() ) {
         global $post;
         $course = get_post_meta($post->ID, '_ajdwp_progress_bar', true);
    }
    if ( empty($course) ) {
         $course = 'default';
    }
    $progress_bars = get_option(AJDWP_OPTION, array());
    if ( empty($progress_bars[$course]) ) {
         return '';
    }
    $steps = $progress_bars[$course];
    $total = count($steps);
    $current_url = untrailingslashit(( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    $active_index = 0;
    foreach ( $steps as $i => $step ) {
         if ( untrailingslashit($step['link']) === $current_url ) {
              $active_index = $i;
              break;
         }
    }
    $overall_progress = ($total > 0) ? round((($active_index + 1) / $total) * 100) : 0;
    ob_start();
    ?>
    <div class="ajdwp-progress-wrapper" >
         <div class="ajdwp-progress-container" >
              <div class="ajdwp-progress-filled" style=" width:<?php echo $overall_progress; ?>%; "></div>
         </div>
         <ul class="ajdwp-steps">
              <?php foreach ( $steps as $step ) :
                    $display_text = ! empty($step['nickname']) ? $step['nickname'] : $step['title'];
                    $active_class = ( untrailingslashit($step['link']) === $current_url ) ? ' ajdwp-active' : '';
              ?>
                    <li class="ajdwp-step<?php echo esc_attr($active_class); ?>" >
                    <a href="<?php echo esc_url($step['link']); ?>" 
                         title="<?php echo esc_attr($step['title']); ?>" 
                         aria-label="<?php echo 'progress bar link to ' . esc_attr($step['title']); ?>">
                         <?php echo esc_html($display_text); ?>
                    </a>
                    </li>
              <?php endforeach; ?>
         </ul>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('progress_bar', 'ajdwp_progress_bar_shortcode');
