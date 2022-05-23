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
        // 
    }

    function run(){
        add_action( "admin_menu", [$this, "auto_posts_admin_menu"] );
        add_action( "create_category", [$this, "create_new_catregory_hook"], 10, 1 );
        add_action( "delete_category", [$this, "delete_category_action_for_post"], 10, 1 );
        add_action( "admin_enqueue_scripts", [$this, "__admin_enqueue_scripts"], 10, 1 );
    }

    function __admin_enqueue_scripts(){
        if(isset($_GET['page']) && $_GET['page'] === 'auto-posts'){
            wp_enqueue_media();
            wp_enqueue_script( 'auto-post', plugin_dir_url( __FILE__ ).'js/auto-post.js', array('jquery'), AUTO_POSTS_VERSION, true );
        }
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
        // Default thumbnail
        add_settings_field( 'ap_default_thumbnail', 'Default thumbnail', [$this, 'ap_default_thumbnail_cb'], 'ap_general_opt_page','ap_general_opt_section' );
        register_setting( 'ap_general_opt_section', 'ap_default_thumbnail' );
        // Delete related post if category deleted.
        add_settings_field( 'ap_delete_post_if_cat_deleted', 'Delete related post (Only draft) if category deleted.', [$this, 'ap_delete_post_if_cat_deleted_cb'], 'ap_general_opt_page','ap_general_opt_section' );
        register_setting( 'ap_general_opt_section', 'ap_delete_post_if_cat_deleted' );
        // Use the new category for the generated post
        add_settings_field( 'ap_use_cat_for_post', 'Use the new category for the generated post', [$this, 'ap_use_cat_for_post_cb'], 'ap_general_opt_page','ap_general_opt_section' );
        register_setting( 'ap_general_opt_section', 'ap_use_cat_for_post' );
        // Auto publish
        add_settings_field( 'ap_auto_published', 'Auto published', [$this, 'ap_auto_published_cb'], 'ap_general_opt_page','ap_general_opt_section' );
        register_setting( 'ap_general_opt_section', 'ap_auto_published' );
    }
    
    function ap_post_title_cb(){
        echo '<input type="text" name="ap_post_title" placeholder="How to %%cat_name%% Fix Guide." class="widefat" value="'.get_option('ap_post_title').'">';
        echo '<p>Use <code>%%cat_name%%</code> to show category name inside texts.</p>';
    }
    function ap_post_contents_cb(){
        echo '<textarea name="ap_post_contents" placeholder="To fix the issues in %%cat_name%%, you have to do the following steps." class="widefat" rows="5">'.get_option('ap_post_contents').'</textarea>';
        echo '<p>Use <code>%%cat_name%%</code> to show category name inside texts.</p>';
    }
    function ap_default_thumbnail_cb(){
        $attachment_url = '';
        if(get_option('ap_default_thumbnail')){
            $attachment_url = wp_get_attachment_url( get_option('ap_default_thumbnail') );
        }
        
        echo '<div class="ap_thumbnail_preview">';
        if($attachment_url){
            echo '<img style="width: 190px; margin: 10px 0; border: 2px solid #ddd; border-radius: 5px;" src="'.$attachment_url.'">';
        }
        echo '</div>';
        echo '<button id="ap_upload_thumbnail" class="button-secondary">Upload a thumbnail</button>';
        if($attachment_url){
            echo '&nbsp;&nbsp;<button style="border-color: red; color: red; box-shadow:none;" id="ap_remove_thumbnail" class="button-secondary">Remove</button>'; 
        }
        echo '<input type="hidden" name="ap_default_thumbnail" id="ap_default_thumbnail" class="widefat" value="'.get_option('ap_default_thumbnail').'">';
    }
    function ap_delete_post_if_cat_deleted_cb(){
        echo '<input type="checkbox" name="ap_delete_post_if_cat_deleted" '.((get_option('ap_delete_post_if_cat_deleted') === 'on') ? 'checked' : '').'>';
    }
    function ap_use_cat_for_post_cb(){
        echo '<input type="checkbox" name="ap_use_cat_for_post" '.((get_option('ap_use_cat_for_post') === 'on') ? 'checked' : '').'>';
    }
    function ap_auto_published_cb(){
        echo '<input type="checkbox" name="ap_auto_published" '.((get_option('ap_auto_published') === 'on') ? 'checked' : '').'>';
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
            $status = 'draft';
            if(get_option('ap_auto_published') === 'on'){
                $status = 'publish';
            }

            // Create post object
            $cat_post = array(
                'post_title'    => wp_strip_all_tags( $post_title ),
                'post_content'  => $post_contents,
                'post_status'   => $status,
                'post_author'   => 1,
                'meta_input'   => array(
                    "ap_cat_$term_id" => $term_id
                ),
            );

            if(get_option('ap_use_cat_for_post') === 'on'){
                $cat_post['post_category'] = array( $term_id );
            }
    
            // Insert the post into the database
            $post_id = wp_insert_post( $cat_post );

            $thumbnail_id = get_option('ap_default_thumbnail');
            if($thumbnail_id && !is_wp_error( $post_id )){
                set_post_thumbnail( $post_id, $thumbnail_id );
            }
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