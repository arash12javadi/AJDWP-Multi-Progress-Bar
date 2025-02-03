<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action('admin_menu', 'ajdwp_progress_add_admin_menu');
function ajdwp_progress_add_admin_menu() {
    add_menu_page(
        'Progress Bar Manager',
        'Progress Bars',
        'manage_options',
        'course-progress',
        'ajdwp_progress_admin_page',
        'dashicons-schedule',
        80
    );
}

add_action('admin_init', 'ajdwp_progress_register_settings');
function ajdwp_progress_register_settings() {
    register_setting('ajdwp_progress_group', AJDWP_OPTION);
}

// Enqueue scripts and styles for our admin page.
add_action('admin_enqueue_scripts', 'ajdwp_progress_admin_enqueue');
function ajdwp_progress_admin_enqueue($hook) {
    if ( isset($_GET['page']) && $_GET['page'] === 'course-progress' ) {
         wp_enqueue_script('jquery-ui-sortable');
         wp_enqueue_style('ajdwp_admin_css', AJDWP_PLUGIN_URL . 'assets/css/admin.css');
         wp_enqueue_script('ajdwp_admin_js', AJDWP_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'jquery-ui-sortable'), false, true);
    }
}

function ajdwp_progress_admin_page() {
    if ( ! current_user_can('manage_options') ) {
         return;
    }
    
    // Retrieve current progress bars.
    $progress_bars = get_option(AJDWP_OPTION, array());
    
    // --- Handle deletion of a progress bar ---
    if ( isset($_GET['action']) && $_GET['action'] === 'delete' && ! empty($_GET['course']) ) {
         $course_to_delete = sanitize_text_field($_GET['course']);
         if ( isset($progress_bars[$course_to_delete]) ) {
              unset($progress_bars[$course_to_delete]);
              update_option(AJDWP_OPTION, $progress_bars);
              echo '<div class="updated"><p>Progress bar <strong>' . esc_html($course_to_delete) . '</strong> deleted.</p></div>';
         }
    }
    
    // --- Handle adding a new progress bar ---
    if ( isset($_POST['new_course']) && ! empty($_POST['new_course']) ) {
         check_admin_referer('ajdwp_add_progress_bar', 'ajdwp_progress_nonce');
         $new_course = sanitize_text_field($_POST['new_course']);
         if ( ! isset($progress_bars[$new_course]) ) {
              $progress_bars[$new_course] = array();
              update_option(AJDWP_OPTION, $progress_bars);
              echo '<div class="updated"><p>Progress bar <strong>' . esc_html($new_course) . '</strong> added.</p></div>';
         } else {
              echo '<div class="error"><p>A progress bar with that identifier already exists.</p></div>';
         }
    }
    
    // --- Handle saving edited steps for a progress bar ---
    if ( isset($_POST['course_key']) && isset($_POST['save_steps']) ) {
         check_admin_referer('ajdwp_save_steps', 'ajdwp_progress_nonce');
         $course_key = sanitize_text_field($_POST['course_key']);
         $steps = array();
         if ( isset($_POST['step_title']) && is_array($_POST['step_title']) ) {
              foreach ( $_POST['step_title'] as $index => $title ) {
                   $link = isset($_POST['step_link'][$index]) ? esc_url_raw($_POST['step_link'][$index]) : '';
                   $nickname = isset($_POST['step_nickname'][$index]) ? sanitize_text_field($_POST['step_nickname'][$index]) : '';
                   $title = sanitize_text_field($title);
                   // Only save if title and link are provided.
                   if ( ! empty($title) && ! empty($link) ) {
                        $steps[] = array(
                             'title'    => $title,
                             'nickname' => $nickname,
                             'link'     => $link,
                        );
                   }
              }
         }
         $progress_bars[$course_key] = $steps;
         update_option(AJDWP_OPTION, $progress_bars);
         echo '<div class="updated"><p>Progress bar <strong>' . esc_html($course_key) . '</strong> updated.</p></div>';
    }
    
    // --- Display the admin page ---
    ?>
    <div class="wrap">
         <h1>Progress Bar Manager</h1>
         
         <!-- Form to add a new progress bar -->
         <h2>Add New Progress Bar</h2>
         <form method="post">
              <?php wp_nonce_field('ajdwp_add_progress_bar', 'ajdwp_progress_nonce'); ?>
              <input type="text" name="new_course" placeholder="e.g., wordpress, seo, marketing" required />
              <?php submit_button('Add Progress Bar'); ?>
         </form>
         
         <!-- List of existing progress bars with their shortcodes -->
         <h2>Existing Progress Bars</h2>
         <table class="wp-list-table widefat fixed striped">
              <thead>
                   <tr>
                        <th>Identifier</th>
                        <th>Shortcode</th>
                        <th>Actions</th>
                   </tr>
              </thead>
              <tbody>
                   <?php foreach ( $progress_bars as $course_key => $steps ) : ?>
                   <tr>
                        <td><?php echo esc_html($course_key); ?></td>
                        <td><code>[progress_bar course="<?php echo esc_attr($course_key); ?>"]</code></td>
                        <td>
                             <a href="<?php echo admin_url('admin.php?page=course-progress&course=' . urlencode($course_key)); ?>">Edit</a> | 
                             <a href="<?php echo admin_url('admin.php?page=course-progress&action=delete&course=' . urlencode($course_key)); ?>" onclick="return confirm('Are you sure you want to delete this progress bar?');">Delete</a>
                        </td>
                   </tr>
                   <?php endforeach; ?>
              </tbody>
         </table>
         
         <?php
         // If a progress bar is selected for editing, show its steps editing form.
         if ( isset($_GET['course']) ) :
              $course_key = sanitize_text_field($_GET['course']);
              $steps = isset($progress_bars[$course_key]) ? $progress_bars[$course_key] : array();
         ?>
         <h2>Edit Progress Bar: <?php echo esc_html($course_key); ?></h2>
         <form method="post">
              <?php wp_nonce_field('ajdwp_save_steps', 'ajdwp_progress_nonce'); ?>
              <input type="hidden" name="course_key" value="<?php echo esc_attr($course_key); ?>" />
              <table class="wp-list-table widefat fixed striped" id="ajdwp_steps_table">
                   <thead>
                        <tr>
                             <th>Full Title</th>
                             <th>Nickname (Displayed)</th>
                             <th>Link</th>
                             <th>Action</th>
                        </tr>
                   </thead>
                   <tbody id="ajdwp_steps_body">
                        <?php if ( ! empty($steps) ) : ?>
                             <?php foreach ( $steps as $step ) : ?>
                             <tr class="ajdwp_step">
                                  <td><input type="text" name="step_title[]" value="<?php echo esc_attr($step['title']); ?>" /></td>
                                  <td><input type="text" name="step_nickname[]" value="<?php echo esc_attr($step['nickname']); ?>" placeholder="Optional (will use full title if empty)" /></td>
                                  <td><input type="text" name="step_link[]" value="<?php echo esc_attr($step['link']); ?>" /></td>
                                  <td><button class="button ajdwp-delete-step" type="button">Delete</button></td>
                             </tr>
                             <?php endforeach; ?>
                        <?php endif; ?>
                   </tbody>
              </table>
              <p>
                   <button id="ajdwp-add-step" class="button" type="button">+ Add Step</button>
              </p>
              <?php submit_button('Save Progress Bar', 'primary', 'save_steps'); ?>
         </form>
         <?php endif; ?>
    </div>
    <?php
}
