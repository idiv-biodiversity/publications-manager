<?php
/*
Plugin Name: Publications Manager
Plugin URI: https://github.com/idiv-biodiversity/publications-manager
Description: WordPress plugin for managing publications at <a href="https://idiv.de" target="_blank">iDiv</a>
Version: 1.0.1-alpha
Author: Christian Langer
Author URI: https://github.com/christianlanger
Text Domain: publications-manager
GitHub Plugin URI: https://github.com/idiv-biodiversity/publications-manager
*/

/* ################################################################ */
/* BASIC INIT STUFF */
/* ################################################################ */

// Includes
include_once(plugin_dir_path(__FILE__) . 'conf.php');
include_once(plugin_dir_path(__FILE__) . 'functions.php');

// Create the database table on plugin activation
register_activation_hook(__FILE__, 'publications_manager_create_table');
function publications_manager_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'idiv_publications';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id INT AUTO_INCREMENT PRIMARY KEY,
        rec_id INT,
        ref_type VARCHAR(50) NOT NULL,
        title TEXT NOT NULL,
        authors TEXT NOT NULL,
        journal VARCHAR(255),
        book_title VARCHAR(255),
        editors TEXT,
        publisher VARCHAR(255),
        year_published INT,
        doi_link VARCHAR(255),
        doi_open_access INT DEFAULT 0,
        pdf_link VARCHAR(255),
        data_link VARCHAR(255),
        code_link VARCHAR(255),
        custom_link VARCHAR(255),
        custom_link_name VARCHAR(255),
        employees TEXT,
        departments TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_updated TIMESTAMP,
        pub_status INT DEFAULT 0
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// scripts and styles for frontend
add_action('wp_enqueue_scripts', 'enqueue_publications_manager_frontend_scripts');
function enqueue_publications_manager_frontend_scripts() {

    // bootstrap
    wp_enqueue_style('bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');
    wp_enqueue_script('popper-js', 'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js', array('jquery'), null, true);
    wp_enqueue_script('bootstrap-js','https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js',array('jquery'));

    // Font Awesome CSS
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', array(), '5.15.4', 'all');

    // custom
    wp_enqueue_style('custom-frontend-css', plugin_dir_url(__FILE__) . 'css/frontend.css');

    wp_enqueue_script('custom-frontend-js', plugin_dir_url(__FILE__) . 'js/frontend.js', array('jquery'));
}

// scripts and styles for backend
add_action('admin_enqueue_scripts', 'enqueue_publications_manager_backend_scripts');
function enqueue_publications_manager_backend_scripts() {

    // editor
    wp_enqueue_script('editor');
    wp_enqueue_script('quicktags');
    wp_enqueue_style('editor-buttons');

    // bootstrap
    wp_enqueue_style('bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');
    wp_enqueue_script('popper-js', 'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js', array('jquery'), null, true);
    wp_enqueue_script('bootstrap-js','https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js',array('jquery'));

    // Selectpicker
    wp_enqueue_style('bootstrap-select-css', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/css/bootstrap-select.min.css');
    wp_enqueue_script('bootstrap-select-js', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/js/bootstrap-select.min.js', array('jquery', 'bootstrap-js'), null, true);

    // toggle switch
    wp_enqueue_style('bootstrap-toggle-css', 'https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/css/bootstrap4-toggle.min.css');
    wp_enqueue_script('bootstrap-toggle-js','https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/js/bootstrap4-toggle.min.js',array('jquery'));

    // Font Awesome CSS
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', array(), '5.15.4', 'all');

    // Custom Stuff
    wp_enqueue_style('custom-backend-css', plugin_dir_url(__FILE__) . 'css/backend.css');

    wp_enqueue_script('custom-backend-js', plugin_dir_url(__FILE__) . 'js/backend.js', array('jquery'));
    wp_localize_script('custom-backend-js', 'ajax_object', array('ajaxurl' => admin_url('admin-ajax.php'))); // Localize script data

}
    
// Create admin menu
add_action('admin_menu', 'publications_manager_menu');
function publications_manager_menu() {
    add_menu_page('Publications Manager', 'Publications Manager', 'manage_options', 'publications_manager', 'publications_manager_page','dashicons-book');

    // Submenu page
    //add_submenu_page('publications_manager', 'Edit Publication', 'Edit Publication', 'manage_options', 'edit_publication', 'edit_publication_page');

    // Register a hidden submenu page
    add_submenu_page(null, 'Edit Publication', 'Edit Publication', 'manage_options', 'edit_publication', 'edit_publication_page');
}

// Enqueue block scripts
add_action('enqueue_block_editor_assets', 'publications_manager_blocks_enqueue');
function publications_manager_blocks_enqueue() {
    wp_enqueue_script(
        'publications-manager-blocks',
        plugin_dir_url(__FILE__) . 'js/blocks.js',
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-i18n'),
        filemtime(plugin_dir_path(__FILE__) . 'js/blocks.js')
    );
    
}

/* ################################################################ */
/*                      UPDATE PROCESS */
/* ################################################################ */

// Force WordPress to check for plugin updates
//delete_site_transient('update_plugins');

// Update Check
add_filter('site_transient_update_plugins', 'plugin_update_publications_check');
function plugin_update_publications_check($transient) {
    include_once(ABSPATH . 'wp-content/custom-config.php');
    if (empty($transient->checked)) {
        return $transient;
    }

    $plugin_slug = plugin_basename(__FILE__);
    $response = wp_remote_get('https://api.github.com/repos/idiv-biodiversity/publications-manager/releases', array(
        'headers' => array(
            'Authorization' => 'token ' . GITHUB_TOKEN,
            'User-Agent'    => 'Publications Manager'
        )
    ));

    if (is_wp_error($response)) {
        error_log('Error: ' . $response->get_error_message());
        return $transient;
    }

    $github_data = json_decode(wp_remote_retrieve_body($response), true);

    // Get the first release, assuming it’s the latest
    $latest_release = $github_data[0];
    
    // Parse the version from GitHub (removing 'v' prefix if present)
    $new_version = str_replace('v', '', $latest_release['name']);

    // Compare the version from GitHub with the plugin version
    if (version_compare($transient->checked[$plugin_slug], $new_version, '<')) {
        $plugin = array(
            'slug'        => $plugin_slug,
            'new_version' => $new_version,
            'url'         => $latest_release['html_url'],
            'package'     => $latest_release['zipball_url'],
        );
        $transient->response[$plugin_slug] = (object) $plugin;
    }

    return $transient;
}

// Rename the plugin folder after the update and keep it active
add_filter('upgrader_post_install', 'rename_plugin_publications_folder', 10, 3);
function rename_plugin_publications_folder($response, $hook_extra, $result) {
    global $wp_filesystem;

    if ($hook_extra['plugin'] !== plugin_basename(__FILE__)) {
        return $response;
    }

    $proper_folder_name = 'publications-manager';
    $destination = WP_PLUGIN_DIR . '/' . $proper_folder_name;

    // Rename the plugin directory if it's different
    if ($result['destination_name'] !== $proper_folder_name) {
        $wp_filesystem->move($result['destination'], $destination);
        $result['destination'] = $destination;
        $result['destination_name'] = $proper_folder_name;
    }

    // Re-activate the plugin after renaming
    activate_plugin($proper_folder_name . '/' . plugin_basename(__FILE__));

    return $response;
}

// View details window for new release
add_filter('plugins_api', 'plugin_update_publications_details', 10, 3);
function plugin_update_publications_details($false, $action, $args) {
    include_once(ABSPATH . 'wp-content/custom-config.php');
    // Check if the action is for plugin information
    if ($action !== 'plugin_information') {
        return $false;
    }

    $plugin_slug = plugin_basename(__FILE__); 

    if ($args->slug !== $plugin_slug) {
        return $false;
    }

    // Fetch release details from GitHub
    $response = wp_remote_get('https://api.github.com/repos/idiv-biodiversity/publications-manager/releases', array(
        'headers' => array(
            'Authorization' => 'token ' . GITHUB_TOKEN,
            'User-Agent'    => 'Publications Manager'
        )
    ));

    if (is_wp_error($response)) {
        error_log('Error: ' . $response->get_error_message());
        return $false; // Return false if the request failed
    }

    $github_data = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($github_data) || !is_array($github_data)) {
        return $false; // Return false if there is no release data
    }

    // Get the first release, assuming it’s the latest
    $latest_release = $github_data[0];

    $plugin_info = new stdClass();
    $plugin_info->name = 'Publications Manager';
    $plugin_info->slug = $plugin_slug;
    $plugin_info->version = str_replace('v', '', $latest_release['name']);
    $plugin_info->author = '<a href="https://github.com/ChristianLanger">Christian Langer</a>';
    $plugin_info->homepage = 'https://github.com/idiv-biodiversity/publications-manager';
    $plugin_info->download_link = $latest_release['zipball_url'];
    $plugin_info->sections = array(
        'description' => 'WordPress plugin for managing Publications at <a href="https://idiv.de" target="_blank">iDiv</a>',
        'changelog' => '<h4>Version ' . str_replace('v', '', $latest_release['name']) . '</h4>'
    );
    $plugin_info->banners = array(
        'low' => 'https://home.uni-leipzig.de/idiv/main-page/banner-low-res.jpg',
        'high' => 'https://home.uni-leipzig.de/idiv/main-page/banner-high-res.jpg'
    );

    return $plugin_info;
}


/* ################################################################ */
/*                              FUNCTIONS */
/* ################################################################ */

// Callback function for the publication manager page
function publications_manager_page() {
    wrapper_start();
    publications_upload();
    publications_table();
    wrapper_end();
    exit();
}

// Callback function for the edit publication page
function edit_publication_page() {
    wrapper_start();
    // Include the PHP file containing the content for the edit publication page
    include_once(plugin_dir_path(__FILE__) . 'edit-publication.php');
    wrapper_end();
}


/* ################################################################ */
/* Publikationsliste (vollständig) */
/* ################################################################ */

// // shortcode for Frontend
// add_shortcode('publications_public_table', 'publications_manager_public_table_shortcode');
// function publications_manager_public_table_shortcode($atts) {
//     ob_start(); // Start output buffering to capture the HTML content
  
//     // Get publications from the database
//     global $wpdb;
//     $publications = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}idiv_publications WHERE pub_status = 1");
  
//     // Load the template file and capture its output
//     include(plugin_dir_path(__FILE__) . 'templates/publications-list.php');
  
//     return ob_get_clean(); // Return the captured HTML content
// }

add_action('init', 'register_all_publications_block');
function register_all_publications_block() {
    register_block_type('publications-manager/all-publications', array(
        'render_callback' => 'render_all_publications_block',
    ));
}
function render_all_publications_block($attributes) {

    ob_start(); // Start output buffering to capture the HTML content

    // Get publications from the database
    global $wpdb;
    $publications = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}idiv_publications WHERE pub_status = 1");
    $years = $wpdb->get_col("SELECT DISTINCT year_published FROM {$wpdb->prefix}idiv_publications WHERE pub_status = 1 ORDER BY year_published DESC");

    // Load the template file and capture its output
    include(plugin_dir_path(__FILE__) . 'templates/publications-list.php');

    return ob_get_clean(); // Return the captured HTML content
}

/* ################################################################ */
/* Publikationsliste (Arbeitsgruppe) */
/* ################################################################ */

add_action('init', 'register_group_publications_block');
function register_group_publications_block() {
    register_block_type('publications-manager/group-publications', array(
        'render_callback' => 'render_group_publications_block',
    ));
}
function render_group_publications_block($attributes) {
    $selected_group_id = isset($attributes['id']) ? intval($attributes['id']) : 0;

    if ($selected_group_id === 0) {
        return '<p>No group selected.</p>';
    }

    ob_start(); // Start output buffering to capture the HTML content

    // Get publications from the database
    global $wpdb;
    $publications = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}idiv_publications WHERE pub_status = 1 AND FIND_IN_SET({$selected_group_id}, departments) > 0;");
    $years = $wpdb->get_col("SELECT DISTINCT year_published FROM {$wpdb->prefix}idiv_publications WHERE pub_status = 1 AND FIND_IN_SET({$selected_group_id}, departments) > 0 ORDER BY year_published DESC");

    // Load the template file and capture its output
    include(plugin_dir_path(__FILE__) . 'templates/publications-list.php');

    return ob_get_clean(); // Return the captured HTML content
}

/* ################################################################ */
/* Publikationsliste (individuell) */
/* ################################################################ */

add_action('init', 'register_single_publications_block');
function register_single_publications_block() {
    register_block_type('publications-manager/single-publications', array(
        'render_callback' => 'render_single_publications_block',
    ));
}
function render_single_publications_block($attributes) {

    $selected_staff_id = isset($attributes['id']) ? intval($attributes['id']) : 0;

    if ($selected_staff_id === 0) {
        return '<p>No employee selected.</p>';
    }

    ob_start(); // Start output buffering to capture the HTML content

    // Get publications from the database
    global $wpdb;
    $publications = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}idiv_publications WHERE pub_status = 1 AND FIND_IN_SET({$selected_staff_id}, employees) > 0;");
    $years = $wpdb->get_col("SELECT DISTINCT year_published FROM {$wpdb->prefix}idiv_publications WHERE pub_status = 1 AND FIND_IN_SET({$selected_staff_id}, employees) > 0 ORDER BY year_published DESC");

    // Load the template file and capture its output
    include(plugin_dir_path(__FILE__) . 'templates/publications-list.php');

    return ob_get_clean(); // Return the captured HTML content
}
