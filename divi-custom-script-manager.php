<?php
/*
Plugin Name: Divi Custom Script Manager
Description: Manages custom scripts for different pages in Divi
Version: 1.1
Author: Praveen Uduweriya
*/

// Enqueue scripts
function dcm_enqueue_scripts() {
    global $post;
    if (is_page()) {
        $page_scripts = get_option('dcm_page_scripts', array());
        if (isset($page_scripts[$post->ID])) {
            wp_enqueue_script('dcm-custom-script-' . $post->ID, plugins_url('js/page-' . $post->ID . '.js', __FILE__), array(), '1.0', true);
        }
    }
}
add_action('wp_enqueue_scripts', 'dcm_enqueue_scripts');

// Add admin menu
function dcm_admin_menu() {
    add_menu_page('Script Manager', 'Script Manager', 'manage_options', 'dcm-script-manager', 'dcm_admin_page');
}
add_action('admin_menu', 'dcm_admin_menu');

// Admin page
function dcm_admin_page() {
    $page_scripts = get_option('dcm_page_scripts', array());
    $selected_page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
    $script_content = '';

    if (isset($_POST['dcm_save'])) {
        $script_content = wp_unslash($_POST['script_content']);
        
        if (!empty($script_content)) {
            $page_scripts[$selected_page_id] = true;
            update_option('dcm_page_scripts', $page_scripts);
            
            $file_path = plugin_dir_path(__FILE__) . 'js/page-' . $selected_page_id . '.js';
            file_put_contents($file_path, $script_content);
            
            echo '<div class="updated"><p>Script saved successfully!</p></div>';
        }
    } elseif ($selected_page_id > 0 && isset($page_scripts[$selected_page_id])) {
        $file_path = plugin_dir_path(__FILE__) . 'js/page-' . $selected_page_id . '.js';
        if (file_exists($file_path)) {
            $script_content = file_get_contents($file_path);
        }
    }
    
    ?>
    <div class="wrap">
        <h1>Custom Script Manager</h1>
        <form method="post" action="">
            <label for="page_id">Select Page:</label>
            <?php 
            wp_dropdown_pages(array(
                'selected' => $selected_page_id,
                'name' => 'page_id',
                'show_option_none' => 'Select a page',
                'option_none_value' => '0'
            )); 
            ?>
            <input type="submit" name="dcm_load" value="Load Script" class="button">
            <br><br>
            <label for="script_content">Script Content:</label>
            <textarea name="script_content" rows="10" cols="50"><?php echo esc_textarea($script_content); ?></textarea>
            <br><br>
            <input type="submit" name="dcm_save" value="Save Script" class="button button-primary">
        </form>
    </div>
    <?php
}