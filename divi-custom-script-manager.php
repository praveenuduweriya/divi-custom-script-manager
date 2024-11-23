<?php
/*
Plugin Name: Divi Custom Script Manager
Description: Manages custom scripts for different pages in Divi
Version: 1.2
Author: Praveen Uduweriya
*/

// Ensure WordPress is loaded
if (!defined('ABSPATH')) {
    exit;
}

class DiviCustomScriptManager {
    private $js_directory;
    
    public function __construct() {
        $this->js_directory = plugin_dir_path(__FILE__) . 'js';
        $this->init();
    }
    
    private function init() {
        // Create js directory if it doesn't exist
        if (!file_exists($this->js_directory)) {
            wp_mkdir_p($this->js_directory);
        }
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function register_settings() {
        register_setting('dcm_options', 'dcm_page_scripts');
    }
    
    public function enqueue_scripts() {
        global $post;
        if (is_page() && $post) {
            $page_scripts = get_option('dcm_page_scripts', array());
            if (isset($page_scripts[$post->ID])) {
                $file_path = $this->js_directory . '/page-' . $post->ID . '.js';
                if (file_exists($file_path)) {
                    wp_enqueue_script(
                        'dcm-custom-script-' . $post->ID,
                        plugins_url('js/page-' . $post->ID . '.js', __FILE__),
                        array(),
                        filemtime($file_path),
                        true
                    );
                }
            }
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Script Manager',
            'Script Manager',
            'manage_options',
            'dcm-script-manager',
            array($this, 'render_admin_page'),
            'dashicons-editor-code'
        );
    }
    
    public function render_admin_page() {
        // Verify nonce and user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $page_scripts = get_option('dcm_page_scripts', array());
        $selected_page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
        $script_content = '';
        $message = '';
        $message_type = 'info';
        
        // Handle form submission
        if (isset($_POST['dcm_save']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'dcm_save_script')) {
            $script_content = isset($_POST['script_content']) ? wp_unslash($_POST['script_content']) : '';
            
            if ($selected_page_id > 0) {
                if (!empty($script_content)) {
                    // Ensure js directory exists
                    if (!file_exists($this->js_directory)) {
                        wp_mkdir_p($this->js_directory);
                    }
                    
                    $file_path = $this->js_directory . '/page-' . $selected_page_id . '.js';
                    
                    // Try to save the file
                    $saved = file_put_contents($file_path, $script_content);
                    
                    if ($saved !== false) {
                        $page_scripts[$selected_page_id] = true;
                        update_option('dcm_page_scripts', $page_scripts);
                        $message = 'Script saved successfully!';
                        $message_type = 'updated';
                    } else {
                        $message = 'Error: Could not save the script file. Please check directory permissions.';
                        $message_type = 'error';
                    }
                } else {
                    // Remove script if content is empty
                    $file_path = $this->js_directory . '/page-' . $selected_page_id . '.js';
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                    unset($page_scripts[$selected_page_id]);
                    update_option('dcm_page_scripts', $page_scripts);
                    $message = 'Script removed successfully!';
                    $message_type = 'updated';
                }
            } else {
                $message = 'Please select a page first.';
                $message_type = 'error';
            }
        }
        
        // Load existing script content
        if ($selected_page_id > 0 && isset($page_scripts[$selected_page_id])) {
            $file_path = $this->js_directory . '/page-' . $selected_page_id . '.js';
            if (file_exists($file_path)) {
                $script_content = file_get_contents($file_path);
            }
        }
        
        // Display admin interface
        ?>
        <div class="wrap">
            <h1>Custom Script Manager</h1>
            
            <?php if ($message): ?>
            <div class="<?php echo esc_attr($message_type); ?>">
                <p><?php echo esc_html($message); ?></p>
            </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <?php wp_nonce_field('dcm_save_script'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="page_id">Select Page:</label>
                        </th>
                        <td>
                            <?php 
                            wp_dropdown_pages(array(
                                'selected' => $selected_page_id,
                                'name' => 'page_id',
                                'show_option_none' => 'Select a page',
                                'option_none_value' => '0'
                            )); 
                            ?>
                            <input type="submit" name="dcm_load" value="Load Script" class="button">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="script_content">Script Content:</label>
                        </th>
                        <td>
                            <textarea name="script_content" id="script_content" rows="20" class="large-text code"><?php echo esc_textarea($script_content); ?></textarea>
                            <p class="description">Enter your custom JavaScript code here. The code will be loaded on the selected page only.</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="dcm_save" value="Save Script" class="button button-primary">
                </p>
            </form>
        </div>
        <?php
    }
}

// Initialize the plugin
new DiviCustomScriptManager();