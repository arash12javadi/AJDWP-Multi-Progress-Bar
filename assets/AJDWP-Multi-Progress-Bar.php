<?php
/*
Plugin Name: AJDWP-Multi-Progress-Bar-Plugin
Description: Manage multiple progress bars on your site. Create/edit/delete progress bars (each with multiple steps), assign one to posts/pages via a meta box and custom column, and use a bulk action with an inline dropdown to add posts to a progress bar (which also appends the post title and view link as a new step). Use the [progress_bar] shortcode (or [progress_bar course="course-key"]) to display the progress bar.
Version: 250203
Author: Arash Javadi
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Enqueue CSS for frontend display.
function ajdwp_enqueue_frontend_styles() {
    wp_enqueue_style('ajdwp_admin_css', AJDWP_PLUGIN_URL . 'assets/css/admin.css');
}
add_action('wp_enqueue_scripts', 'ajdwp_enqueue_frontend_styles');

define('AJDWP_OPTION', 'ajdwp_progress_bars');
define('AJDWP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AJDWP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files.
require_once AJDWP_PLUGIN_DIR . 'includes/admin-page.php';
require_once AJDWP_PLUGIN_DIR . 'includes/meta-box.php';
require_once AJDWP_PLUGIN_DIR . 'includes/bulk-actions.php';
require_once AJDWP_PLUGIN_DIR . 'includes/shortcode.php';
