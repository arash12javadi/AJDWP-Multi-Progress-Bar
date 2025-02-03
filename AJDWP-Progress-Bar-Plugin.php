<?php
/*
Plugin Name: AJDWP-Progress-Bar-Plugin
Description: Creates an admin menu for managing course steps and displays a clickable progress bar via shortcode. Supports adding, deleting, drag‐and‐drop reordering, and a nickname option that shows the full title on hover.
Version: 250203
Author: Arash Javadi
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * ------------------------------
 * ADMIN: Create and Manage Steps
 * ------------------------------
 */

// Add a new admin menu page.
add_action('admin_menu', 'cpb_add_admin_menu');
function cpb_add_admin_menu() {
    add_menu_page(
        'Course Progress Steps',         // Page title
        'AJDWP Course Progress',         // Menu title
        'manage_options',                // Capability
        'course-progress',               // Menu slug
        'cpb_admin_page',                // Callback function
        'dashicons-schedule',            // Icon
        80                               // Position
    );
}

// Register our setting.
add_action('admin_init', 'cpb_register_settings');
function cpb_register_settings() {
    register_setting('cpb_options_group', 'cpb_steps');
}

// Enqueue jQuery UI Sortable (adjusted to load on our admin page)
add_action('admin_enqueue_scripts', 'cpb_admin_enqueue');
function cpb_admin_enqueue($hook) {
    if ( isset($_GET['page']) && in_array($_GET['page'], array('course-progress', 'progress-bar')) ) {
         wp_enqueue_script('jquery-ui-sortable');
    }
}

function cpb_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Save form submission
    if (isset($_POST['cpb_nonce']) && wp_verify_nonce($_POST['cpb_nonce'], 'cpb_save_steps')) {
        $steps = array();
        if (isset($_POST['step_title']) && is_array($_POST['step_title'])) {
            // Loop through the submitted arrays (titles, nicknames, and links are assumed to be aligned by index)
            foreach ($_POST['step_title'] as $index => $title) {
                $link = isset($_POST['step_link'][$index]) ? esc_url_raw($_POST['step_link'][$index]) : '';
                $nickname = isset($_POST['step_nickname'][$index]) ? sanitize_text_field($_POST['step_nickname'][$index]) : '';
                $title = sanitize_text_field($title);
                // Only save the step if both the title and link are not empty.
                // If you want to save steps even if one field is empty, remove or adjust this condition.
                if (!empty($title) && !empty($link)) {
                    $steps[] = array(
                        'title'    => $title,
                        'nickname' => $nickname,
                        'link'     => $link,
                    );
                }
            }
        }
        update_option('cpb_steps', $steps);
        echo '<div class="updated"><p>Steps saved.</p></div>';
    }
    
    // Retrieve saved steps (if any)
    $steps = get_option('cpb_steps', array());
    ?>
    <div class="wrap">
        <h1>Course Progress Steps</h1>
        <form method="post" action="">
            <?php wp_nonce_field('cpb_save_steps', 'cpb_nonce'); ?>
            <table class="wp-list-table widefat fixed striped" id="cpb_steps_table">
                <thead>
                    <tr>
                        <th>Full Title</th>
                        <th>Nickname (Displayed)</th>
                        <th>Link</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="cpb_steps_body">
                    <?php if (!empty($steps)) : ?>
                        <?php foreach ($steps as $step) : ?>
                            <tr class="cpb_step">
                                <td>
                                    <input type="text" name="step_title[]" value="<?php echo esc_attr($step['title']); ?>" />
                                </td>
                                <td>
                                    <input type="text" name="step_nickname[]" value="<?php echo esc_attr($step['nickname']); ?>" placeholder="e.g., Step 1" />
                                </td>
                                <td>
                                    <input type="text" name="step_link[]" value="<?php echo esc_attr($step['link']); ?>" />
                                </td>
                                <td>
                                    <button class="button cpb-delete-step" type="button">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <p>
                <button id="cpb-add-step" class="button" type="button">+ Add Step</button>
            </p>
            <?php submit_button(); ?>
        </form>
    </div>
    <script type="text/javascript">
    jQuery(document).ready(function($){
        // Enable drag-and-drop sorting on the table body.
        $("#cpb_steps_body").sortable({
            placeholder: "cpb-sortable-placeholder",
            forcePlaceholderSize: true
        });
        
        // When clicking the "Add Step" button, append a new row.
        $("#cpb-add-step").on('click', function(){
            var newRow = '<tr class="cpb_step">'+
                '<td><input type="text" name="step_title[]" value="" /></td>'+
                '<td><input type="text" name="step_nickname[]" value="" placeholder="e.g., Step 1" /></td>'+
                '<td><input type="text" name="step_link[]" value="" /></td>'+
                '<td><button class="button cpb-delete-step" type="button">Delete</button></td>'+
                '</tr>';
            $("#cpb_steps_body").append(newRow);
        });
        
        // Delete a step row when clicking the "Delete" button.
        $(document).on('click', '.cpb-delete-step', function(){
            $(this).closest('tr').remove();
        });
        
        // Change cursor to move when hovering over a table row.
        $(document).on('mouseover', '.cpb_step', function(){
            $(this).css('cursor', 'move');
        });
    });
    </script>
    <style>
    .cpb-sortable-placeholder {
        background: #f0f0f0;
        height: 40px;
    }
    </style>
    <?php
}

/**
 * ------------------------------
 * FRONT-END: Progress Bar Shortcode
 * ------------------------------
 *
 * Use the [progress_bar] shortcode in any page.
 * The progress bar will:
 * - Display a horizontal bar with a filled portion based on which step is active.
 * - List each step as a clickable link.
 * - The visible text for each step is its nickname (if provided) and on hover the full title is shown.
 *
 * Note on progress calculation:
 * In this refined example, if you have 4 steps then:
 *   Step 1 (index 0) = ((0+1)/4)*100 = 25%,
 *   Step 2 (index 1) = ((1+1)/4)*100 = 50%,
 *   Step 3 (index 2) = ((2+1)/4)*100 = 75%,
 *   Step 4 (index 3) = ((3+1)/4)*100 = 100%.
 */

add_shortcode('progress_bar', 'cpb_progress_bar_shortcode');
function cpb_progress_bar_shortcode() {
    $steps = get_option('cpb_steps', array());
    if (empty($steps)) {
        return '';
    }
    $total = count($steps);
    
    // Determine current page URL
    $current_url = trailingslashit(( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    
    // Find active step index (default to 0 if no match)
    $active_index = 0;
    foreach ($steps as $i => $step) {
        if ($current_url == trailingslashit($step['link'])) {
            $active_index = $i;
            break;
        }
    }
    // Calculate overall progress.
    $overall_progress = ($total > 0) ? round((($active_index + 1) / $total) * 100) : 0;
    
    ob_start();
    ?>
    <div class="cpb-progress-wrapper">
      <!-- Progress Bar Container -->
      <div class="cpb-progress-container">
         <div class="cpb-progress-filled" style="width: <?php echo $overall_progress; ?>%;"></div>
      </div>
      <!-- Steps List -->
      <ul class="cpb-steps">
         <?php foreach ($steps as $step) : 
                // Use nickname for display if available, otherwise use full title.
                $display_text = !empty($step['nickname']) ? $step['nickname'] : $step['title'];
                $is_active = ($current_url == trailingslashit($step['link'])) ? ' cpb-active' : '';
         ?>
         <li class="cpb-step<?php echo $is_active; ?>">
            <a href="<?php echo esc_url($step['link']); ?>" title="<?php echo esc_attr($step['title']); ?>">
                <?php echo esc_html($display_text); ?>
            </a>
         </li>
         <?php endforeach; ?>
      </ul>
    </div>
    <style>
    .cpb-progress-wrapper {
         margin: 20px 0;
    }
    .cpb-progress-container {
         background: #e0e0e0;
         width: 100%;
         height: 10px;
         position: relative;
         border-radius: 5px;
         overflow: hidden;
    }
    .cpb-progress-filled {
         background: #0073aa;
         height: 100%;
         width: 0;
         transition: width 0.5s ease-in-out;
    }
    .cpb-steps {
         list-style: none;
         padding: 0;
         margin: 10px 0 0 0;
         display: flex;
         justify-content: space-between;
    }
    .cpb-steps li {
         flex: 1;
         text-align: center;
    }
    .cpb-steps li a {
         text-decoration: none;
         color: #0073aa;
         font-weight: bold;
    }
    .cpb-steps li.cpb-active a {
         color: red;
    }
    </style>
    <?php
    return ob_get_clean();
}
