<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Auto_Posts
 * @subpackage Auto_Posts/inc
 * @author     Developer Junayed <admin@easeare.com>
 */

 class Auto_Posts{
    function __construct() {

    }

    function run(){
        add_action( "admin_menu", [$this, "auto_posts_admin_menu"] );
        add_action( "create_category", [$this, "create_new_catregory_hook"], 10, 1 );
        add_action( "delete_category", [$this, "delete_category_action_for_post"], 10, 1 );
    }

    function auto_posts_admin_menu(){
        add_submenu_page( "edit.php", "Auto Posts", "Auto Posts", "manage_options", "auto-posts", [$this, "auto_posts_menupage"], null );
        add_settings_section( 'ap_general_opt_section', '', '', 'ap_general_opt_page' );
            
        // Post title
        add_settings_field( 'ap_post_title', 'Post title', [$this, 'ap_post_title_cb'], 'ap_general_opt_page','ap_general_opt_section' );
        register_setting( 'ap_general_opt_section', 'ap_post_title' );
        // Post contents
        add_settings_field( 'ap_post_contents', 'Post contents', [$this, 'ap_post_contents_cb'], 'ap_general_opt_page','ap_general_opt_section' );
        register_setting( 'ap_general_opt_section', 'ap_post_contents' );
        // Delete related post if category deleted.
        add_settings_field( 'ap_delete_post_if_cat_deleted', 'Delete related post (Only draft) if category deleted.', [$this, 'ap_delete_post_if_cat_deleted_cb'], 'ap_general_opt_page','ap_general_opt_section' );
        register_setting( 'ap_general_opt_section', 'ap_delete_post_if_cat_deleted' );
        // Use the new category for the generated post
        add_settings_field( 'ap_use_cat_for_post', 'Use the new category for the generated post', [$this, 'ap_use_cat_for_post_cb'], 'ap_general_opt_page','ap_general_opt_section' );
        register_setting( 'ap_general_opt_section', 'ap_use_cat_for_post' );
    }
    
    function ap_post_title_cb(){
        echo '<input type="text" name="ap_post_title" placeholder="How to %%cat_name%% Fix Guide." class="widefat" value="'.get_option('ap_post_title').'">';
        echo '<p>Use <code>%%cat_name%%</code> to show category name inside texts.</p>';
    }
    function ap_post_contents_cb(){
        echo '<textarea name="ap_post_contents" placeholder="To fix the issues in %%cat_name%%, you have to do the following steps." class="widefat" rows="5">'.get_option('ap_post_contents').'</textarea>';
        echo '<p>Use <code>%%cat_name%%</code> to show category name inside texts.</p>';
    }
    function ap_delete_post_if_cat_deleted_cb(){
        echo '<input type="checkbox" name="ap_delete_post_if_cat_deleted" '.((get_option('ap_delete_post_if_cat_deleted') === 'on') ? 'checked' : '').'>';
    }
    function ap_use_cat_for_post_cb(){
        echo '<input type="checkbox" name="ap_use_cat_for_post" '.((get_option('ap_use_cat_for_post') === 'on') ? 'checked' : '').'>';
    }
    
    function auto_posts_menupage(){
        ?>
        <h3>Auto posts settings</h3>
        <hr>
    
        <form method="post" style="width: 50%" action="options.php">
            <?php
            settings_fields( 'ap_general_opt_section' );
            do_settings_sections( 'ap_general_opt_page' );
            
            submit_button(  );
            ?>
        </form>	
        <?php
    }
    
    function create_new_catregory_hook($term_id){
        global $wpdb;
        $cat_name = get_cat_name($term_id);
        $post_title = ((get_option('ap_post_title')) ? get_option('ap_post_title') : 'How to %%cat_name%% Fix Guide.');
        $post_title = str_replace("%%cat_name%%", $cat_name, $post_title);
        $post_contents = ((get_option('ap_post_contents')) ? get_option('ap_post_contents') : 'To fix the issues in %%cat_name%%, you have to do the following steps.');
        $post_contents = str_replace("%%cat_name%%", $cat_name, $post_contents);
    
        $already = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}posts WHERE post_title = '$post_title'");
        if(!$already){
            // Create post object
            $cat_post = array(
                'post_title'    => wp_strip_all_tags( $post_title ),
                'post_content'  => $post_contents,
                'post_status'   => 'draft',
                'post_author'   => 1,
                'meta_input'   => array(
                    "ap_cat_$term_id" => $term_id
                ),
            );
    
            if(get_option('ap_use_cat_for_post') === 'on'){
                $cat_post['post_category'] = array( $term_id );
            }
    
            // Insert the post into the database
            wp_insert_post( $cat_post );
        }
    }
    
    function delete_category_action_for_post($term_id){
        global $wpdb;
        if(get_option('ap_delete_post_if_cat_deleted') === 'on'){
            $post_id = $wpdb->get_var("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = 'ap_cat_$term_id' AND meta_value = $term_id");
            if($post_id){
                if(get_post_status($post_id) === 'draft'){
                    wp_delete_post( $post_id, true );
                }
            }
        }
    }
 }