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
        add_action( "create_category", [$this, "create_new_category_action"], 10, 1 );
        add_action( "create_post_tag", [$this, "create_new_tag_action"], 10, 1 );
        add_action( "delete_category", [$this, "delete_category_action_for_post"], 10, 1 );
        add_action( "delete_post_tag", [$this, "delete_post_tag_action_for_post"], 10, 1 );
        add_action( "admin_enqueue_scripts", [$this, "__admin_enqueue_scripts"], 10, 1 );
        add_action( "init", [$this, "ap_form_data_save"] );

        add_action( "wp_ajax_get_additional_template", [$this, "get_additional_template"] );
        add_action( "wp_ajax_nopriv_get_additional_template", [$this, "get_additional_template"] );
    }

    function __admin_enqueue_scripts(){
        if(isset($_GET['page']) && $_GET['page'] === 'auto-posts'){
            // Styles
            wp_enqueue_style( 'selectize', plugin_dir_url( __FILE__ )."css/selectize.min.css",array(), AUTO_POSTS_VERSION, 'all' );
            wp_enqueue_style( 'auto-posts', plugin_dir_url( __FILE__ )."css/auto-posts.css",array(), AUTO_POSTS_VERSION, 'all' );

            // Scripts
            wp_enqueue_media();
            wp_enqueue_script( 'selectize', plugin_dir_url( __FILE__ ).'js/selectize.min.js', array('jquery'), AUTO_POSTS_VERSION, false );
            wp_enqueue_script( 'auto-posts', plugin_dir_url( __FILE__ ).'js/auto-post.js', array('jquery','selectize'), AUTO_POSTS_VERSION, true );
            wp_localize_script( 'auto-posts', 'autopost', array(
                'ajaxurl' => admin_url("admin-ajax.php")
            ) );
        }
    }

    function auto_posts_admin_menu(){
        add_submenu_page( "edit.php", "Auto Posts", "Auto Posts", "manage_options", "auto-posts", [$this, "auto_posts_menupage"], null );

        add_settings_section( 'ap_general_opt_section', '', '', 'ap_general_opt_page' );
        // Delete related post if category deleted.
        add_settings_field( 'ap_delete_post_if_cat_deleted', 'Delete related post (Only draft) if category deleted.', [$this, 'ap_delete_post_if_cat_deleted_cb'], 'ap_general_opt_page','ap_general_opt_section' );
        register_setting( 'ap_general_opt_section', 'ap_delete_post_if_cat_deleted' );
        // Generate posts based on category creation
        add_settings_field( 'ap_use_cat_for_post', 'Generate posts based on category creation.', [$this, 'ap_use_cat_for_post_cb'], 'ap_general_opt_page','ap_general_opt_section' );
        register_setting( 'ap_general_opt_section', 'ap_use_cat_for_post' );
        // Generate posts based on tag creation
        add_settings_field( 'ap_use_tag_for_post', 'Generate posts based on tag creation.', [$this, 'ap_use_tag_for_post_cb'], 'ap_general_opt_page','ap_general_opt_section' );
        register_setting( 'ap_general_opt_section', 'ap_use_tag_for_post' );
        // Auto publish
        add_settings_field( 'ap_auto_published', 'Auto published.', [$this, 'ap_auto_published_cb'], 'ap_general_opt_page','ap_general_opt_section' );
        register_setting( 'ap_general_opt_section', 'ap_auto_published' );
    }

    function ap_delete_post_if_cat_deleted_cb(){
        echo '<input type="checkbox" name="ap_delete_post_if_cat_deleted" '.((get_option('ap_delete_post_if_cat_deleted') === 'on') ? 'checked' : '').'>';
    }
    function ap_use_cat_for_post_cb(){
        echo '<input type="checkbox" name="ap_use_cat_for_post" '.((get_option('ap_use_cat_for_post') === 'on') ? 'checked' : '').'>';
    }
    function ap_use_tag_for_post_cb(){
        echo '<input type="checkbox" name="ap_use_tag_for_post" '.((get_option('ap_use_tag_for_post') === 'on') ? 'checked' : '').'>';
    }
    function ap_auto_published_cb(){
        echo '<input type="checkbox" name="ap_auto_published" '.((get_option('ap_auto_published') === 'on') ? 'checked' : '').'>';
    }
    
    function auto_posts_menupage(){
       require_once plugin_dir_path( __FILE__ )."partials/template.php";
    }
    
    function template_component( $type = '', $field_name, $data, $index = null ){
        $ap_author =  null;
        $ap_titles =  [];
        $ap_contents =  [];
        $ap_thumbnail =  '';
        $ap_tags =  [];
        $ap_cats =  [];

        if(is_array($data)){
            if(array_key_exists("ap_author", $data)) $ap_author = $data['ap_author'];
            if(array_key_exists("ap_titles", $data)) $ap_titles = $data['ap_titles'];
            if(array_key_exists("ap_contents", $data)) $ap_contents = $data['ap_contents'];
            if(array_key_exists("ap_thumbnail", $data)) $ap_thumbnail = $data['ap_thumbnail'];
            if(array_key_exists("ap_tags", $data)) $ap_tags = $data['ap_tags'];
            if(array_key_exists("ap_cats", $data)) $ap_cats = $data['ap_cats'];
        }

        $output = '';

        if($type === 'additional'){
            $output .= '<div class="__template">';
        }

        $output .= '<table>';
        $output .= '<tr>';
        $output .= '<th>Post author</th>';
        $output .= '<td>';
        $output .= '<select id="user-'.$index.'" name="'.$field_name.'[ap_author]" class="ap_post_author">';
        $output .= '<option value="">Select</option>';
        
        $users = get_users();
        if(is_array($users)){
            foreach($users as $user){
                $output .= '<option '.((intval($ap_author) === $user->ID) ? 'selected' : '').' value="'.$user->ID.'">'.$user->display_name.'</option>';
            }
        }
        
        $output .= '</select>';
        $output .= '</td>';
        $output .= '</tr>';
        $output .= '<tr>';
        $output .= '<th>Default title</th>';
        $output .= '<td>';
        $output .= '<div class="_default_titles">';

        if(is_array($ap_titles)){
            foreach($ap_titles as $key => $ap_title){
                $output .= '<div class="title_content">';
                $output .= '<input type="text" name="'.$field_name.'[ap_titles][]" placeholder="How to %%term_name%% Fix Guide." class="widefat" value="'.$ap_title.'">';
                $output .= '<p>Use <code>%%term_name%%</code> to show term name inside texts</p>';
                if($key > 0){
                    $output .= '<span class="remove_title">+</span>';
                }
                $output .= '</div>';
            }
        }

        if(empty($ap_titles)){
            $output .= '<div class="title_content">';
            $output .= '<input type="text" name="'.$field_name.'[ap_titles][]" placeholder="How to %%term_name%% Fix Guide." class="widefat" value="">';
            $output .= '<p>Use <code>%%term_name%%</code> to show term name inside texts</p>';
            $output .= '</div>';
        }

        $output .= '</div>';

        $output .= '<button data-name="'.$field_name.'" class="button-secondary add_new_title">Add title</button>';

        $output .= '</td>';
        $output .= '</tr>';
        $output .= '<tr>';
        $output .= '<th>Default contents</th>';
        $output .= '<td>';

        $output .= '<div class="__default_templates">';

        if(is_array($ap_contents)){
            foreach($ap_contents as $key => $contents){
                $output .= '<div class="template_content">';
                $output .= '<textarea name="'.$field_name.'[ap_contents][]" placeholder="To fix the issues in %%term_name%%, you have to do the following steps." class="widefat" rows="5">'.$contents.'</textarea>';
                $output .= '<p>Use <code>%%term_name%%</code> to show term name inside texts.</p>';
                if($key > 0){
                    $output .= '<span class="remove_template">+</span>';
                }
                $output .= '</div>';
            }
        }

        if(empty($ap_contents)){
            $output .= '<div class="template_content">';
            $output .= '<textarea name="'.$field_name.'[ap_contents][]" placeholder="To fix the issues in %%term_name%%, you have to do the following steps." class="widefat" rows="5"></textarea>';
            $output .= '<p>Use <code>%%term_name%%</code> to show term name inside texts.</p>';
            $output .= '</div>';
        }

        $output .= '</div>';

        $output .= '<button data-name="'.$field_name.'" class="button-secondary add_new_template">Add template</button>';
        $output .= '</td>';
        $output .= '</tr>';
        $output .= '<tr>';
        $output .= '<th>Default thumbnail</th>';
        $output .= '<td>';
        
        $output .= '<div class="ap_thumbnail_preview">';
        if($ap_thumbnail){
            $thumbUrl = wp_get_attachment_url( $ap_thumbnail );
            $output .= '<img class="preview_thumbnail_img" src="'.$thumbUrl.'">';
        }
        $output .= '</div>';
        
        $output .= '<input type="hidden" name="'.$field_name.'[ap_thumbnail]" class="widefat ap_default_thumbnail" value="'.$ap_thumbnail.'">';

        $output .= '<button class="button-secondary ap_upload_thumbnail">Upload a thumbnail</button>';
        if($ap_thumbnail){
            $output .= '&nbsp;&nbsp;<button class="ap_remove_thumbnail button-secondary" class="button-secondary">Remove</button>'; 
        }
        
        $output .= '</td>';
        $output .= '</tr>';
        $output .= '<tr>';
        $output .= '<th>Default Tags</th>';
        $output .= '<td>';
        $output .= '<select id="tags-'.$index.'" name="'.$field_name.'[ap_tags][]" multiple class="ap_default_tag">';
        $output .= '<option value="">Select</option>';
        
        $tags = get_tags(array(
            'hide_empty' => false
        ));

        foreach ($tags as $tag) {
            $output .= '<option '.((is_array($ap_tags) && in_array($tag->name, $ap_tags)) ? 'selected' : '').' value="'.$tag->name.'">'.$tag->name.'</option>';
        }
        
        $output .= '</select>';
        $output .= '</td>';
        $output .= '</tr>';

        $output .= '<tr>';
        $output .= '<th>Default Categories</th>';
        $output .= '<td>';
        $output .= '<select id="ap_cats-'.$index.'" name="'.$field_name.'[ap_cats][]" multiple class="ap_default_cats">';
        $output .= '<option value="">Select</option>';
        
        $cats = get_terms(array(
            'taxonomy'   => 'category',
            'hide_empty' => false
        ));

        foreach ($cats as $cat) {
            $output .= '<option '.((is_array($ap_cats) && in_array($cat->term_id, $ap_cats)) ? 'selected' : '').' value="'.$cat->term_id.'">'.$cat->name.'</option>';
        }
        
        $output .= '</select>';
        $output .= '</td>';
        $output .= '</tr>';
        $output .= '</table>';
        
        if($type === 'additional'){
            $output .= '<button class="button-secondary removeTemplate">Remove template</button>';
            $output .= '</div>';
        }

        return $output;
    }

    function create_new_tag_action($tag_id){
        global $wpdb;

        if(get_option('ap_use_tag_for_post') !== 'on'){
            return;
        }
        $tag_name = get_term( $tag_id )->name;

        // Default data
        $ap_default = get_option( '_ap_default_template' );
        if(is_array($ap_default) && sizeof($ap_default) > 0){
            $ap_author =  1;
            $ap_titles =  [];
            $ap_contents =  [];
            $ap_thumbnail =  '';
            $ap_tags =  [];
            
            if(array_key_exists("ap_author", $ap_default)) $ap_author = $ap_default['ap_author'];
            if(array_key_exists("ap_titles", $ap_default)) $ap_titles = $ap_default['ap_titles'];
            if(array_key_exists("ap_contents", $ap_default)) $ap_contents = $ap_default['ap_contents'];
            if(array_key_exists("ap_thumbnail", $ap_default)) $ap_thumbnail = $ap_default['ap_thumbnail'];
            if(array_key_exists("ap_tags", $ap_default)) $ap_tags = $ap_default['ap_tags'];
            if(!is_array($ap_titles)) $ap_titles = [];
            if(!is_array($ap_contents)) $ap_contents = [];
            if(!is_array($ap_tags)) $ap_tags = [];
            $ap_tags[] = $tag_name;

            // Title start
            $title = '';
            if(is_array($ap_titles) && sizeof($ap_titles) > 0){
                shuffle($ap_titles);
                $contentIndex = array_rand($ap_titles, 1);
                $title = $ap_titles[$contentIndex];
            }
            if(empty($title)){
                $title = 'How to %%term_name%% Fix Guide.';
            }
            $title = str_replace("%%term_name%%", $tag_name, $title);
            $title = str_replace("%%term_name%%", $tag_name, $title);
            // Title end
            
            // contents start
            $contents = '';
            if(is_array($ap_contents) && sizeof($ap_contents) > 0){
                shuffle($ap_contents);
                $contentIndex = array_rand($ap_contents, 1);
                $contents = $ap_contents[$contentIndex];
            }

            if(empty($contents)){
                $contents = 'To fix the issues in %%term_name%%, you have to do the following steps.';
            }
            $contents = str_replace("%%term_name%%", $tag_name, $contents);
            // contents end

            $status = 'draft';
            if(get_option('ap_auto_published') === 'on'){
                $status = 'publish';
            }

            // Create post object
            $tag_post = array(
                'post_title'    => wp_strip_all_tags( $title ),
                'post_content'  => $contents,
                'post_status'   => $status,
                'post_author'   => $ap_author,
                'meta_input'   => array(
                    "ap_tags_$tag_id" => $tag_id
                ),
            );

            // Insert the post into the database
            $post_id = wp_insert_post( $tag_post );

            if($ap_thumbnail && !is_wp_error( $post_id )){
                set_post_thumbnail( $post_id, $ap_thumbnail );
            }

            if(is_array($ap_tags) && !is_wp_error( $post_id )){
                wp_set_post_tags( $post_id, $ap_tags );
            }
        }

        $additional_templates = get_option("_ap_additional_template");
        if(is_array($additional_templates) && sizeof($additional_templates) > 0){
            foreach($additional_templates as $add_temp){
                if(is_array($add_temp)){
                    $ap_author =  1;
                    $ap_titles =  [];
                    $ap_contents =  [];
                    $ap_thumbnail =  '';
                    $ap_tags =  [];
                    
                    if(array_key_exists("ap_author", $add_temp)) $ap_author = $add_temp['ap_author'];
                    if(array_key_exists("ap_titles", $add_temp)) $ap_titles = $add_temp['ap_titles'];
                    if(array_key_exists("ap_contents", $add_temp)) $ap_contents = $add_temp['ap_contents'];
                    if(array_key_exists("ap_thumbnail", $add_temp)) $ap_thumbnail = $add_temp['ap_thumbnail'];
                    if(array_key_exists("ap_tags", $add_temp)) $ap_tags = $add_temp['ap_tags'];
                    if(!is_array($ap_titles)) $ap_titles = [];
                    if(!is_array($ap_contents)) $ap_contents = [];
                    if(!is_array($ap_tags)) $ap_tags = [];
                    $ap_tags[] = $tag_name;
        
                    // Title start
                    $title = '';
                    if(is_array($ap_titles) && sizeof($ap_titles) > 0){
                        shuffle($ap_titles);
                        $contentIndex = array_rand($ap_titles, 1);
                        $title = $ap_titles[$contentIndex];
                    }
                    if(empty($title)){
                        $title = 'How to %%term_name%% Fix Guide.';
                    }
                    $title = str_replace("%%term_name%%", $tag_name, $title);
                    // Title end
                    
                    // Contents start
                    $contents = '';
                    if(is_array($ap_contents) && sizeof($ap_contents) > 0){
                        shuffle($ap_contents);
                        $contentIndex = array_rand($ap_contents, 1);
                        $contents = $ap_contents[$contentIndex];
                    }
                    if(empty($contents)){
                        $contents = 'To fix the issues in %%term_name%%, you have to do the following steps.';
                    }
                    $contents = str_replace("%%term_name%%", $tag_name, $contents);
                    // Content end
        
                    $status = 'draft';
                    if(get_option('ap_auto_published') === 'on'){
                        $status = 'publish';
                    }
        
                    // Create post object
                    $tag_post = array(
                        'post_title'    => wp_strip_all_tags( $title ),
                        'post_content'  => $contents,
                        'post_status'   => $status,
                        'post_author'   => $ap_author,
                        'meta_input'   => array(
                            "ap_tags_$tag_id" => $tag_id
                        ),
                    );
                    
                    // Insert the post into the database
                    $post_id = wp_insert_post( $tag_post );
        
                    if($ap_thumbnail && !is_wp_error( $post_id )){
                        set_post_thumbnail( $post_id, $ap_thumbnail );
                    }
        
                    if(is_array($ap_tags) && !is_wp_error( $post_id )){
                        wp_set_post_tags( $post_id, $ap_tags );
                    }
                }
            }
        }
    }

    function create_new_category_action($cat_id){
        global $wpdb;

        if(get_option('ap_use_cat_for_post') !== 'on'){
            return;
        }

        $cat_name = get_cat_name($cat_id);

        // Default data
        $ap_default = get_option( '_ap_default_template' );
        if(is_array($ap_default) && sizeof($ap_default) > 0){
            $ap_author =  1;
            $ap_titles =  [];
            $ap_contents =  [];
            $ap_thumbnail =  '';
            $ap_cats =  [];
            
            if(array_key_exists("ap_author", $ap_default)) $ap_author = $ap_default['ap_author'];
            if(array_key_exists("ap_titles", $ap_default)) $ap_titles = $ap_default['ap_titles'];
            if(array_key_exists("ap_contents", $ap_default)) $ap_contents = $ap_default['ap_contents'];
            if(array_key_exists("ap_thumbnail", $ap_default)) $ap_thumbnail = $ap_default['ap_thumbnail'];
            if(array_key_exists("ap_cats", $ap_default)) $ap_cats = $ap_default['ap_cats'];
            if(!is_array($ap_titles)) $ap_titles = [];
            if(!is_array($ap_contents)) $ap_contents = [];
            if(!is_array($ap_cats)) $ap_cats = [];
            $ap_cats[] = $cat_id;

            // Title start
            $title = '';
            if(is_array($ap_titles) && sizeof($ap_titles) > 0){
                shuffle($ap_titles);
                $contentIndex = array_rand($ap_titles, 1);
                $title = $ap_titles[$contentIndex];
            }
            if(empty($title)){
                $title = 'How to %%term_name%% Fix Guide.';
            }
            $title = str_replace("%%term_name%%", $cat_name, $title);
            $title = str_replace("%%term_name%%", $cat_name, $title);
            // Title end
            
            // contents start
            $contents = '';
            if(is_array($ap_contents) && sizeof($ap_contents) > 0){
                shuffle($ap_contents);
                $contentIndex = array_rand($ap_contents, 1);
                $contents = $ap_contents[$contentIndex];
            }

            if(empty($contents)){
                $contents = 'To fix the issues in %%term_name%%, you have to do the following steps.';
            }
            $contents = str_replace("%%term_name%%", $cat_name, $contents);
            // contents end

            $status = 'draft';
            if(get_option('ap_auto_published') === 'on'){
                $status = 'publish';
            }

            // Create post object
            $cat_post = array(
                'post_title'    => wp_strip_all_tags( $title ),
                'post_content'  => $contents,
                'post_status'   => $status,
                'post_author'   => $ap_author,
                'meta_input'   => array(
                    "ap_cat_$cat_id" => $cat_id
                )
            );

            // Insert the post into the database
            $post_id = wp_insert_post( $cat_post );

            if($ap_thumbnail && !is_wp_error( $post_id )){
                set_post_thumbnail( $post_id, $ap_thumbnail );
            }

            if(is_array($ap_cats) && !is_wp_error( $post_id )){
                wp_set_post_categories( $post_id, $ap_cats );
            }
        }

        $additional_templates = get_option("_ap_additional_template");
        if(is_array($additional_templates) && sizeof($additional_templates) > 0){
            foreach($additional_templates as $add_temp){
                if(is_array($add_temp)){
                    $ap_author =  1;
                    $ap_titles =  [];
                    $ap_contents =  [];
                    $ap_thumbnail =  '';
                    $ap_cats =  [];
                    
                    if(array_key_exists("ap_author", $add_temp)) $ap_author = $add_temp['ap_author'];
                    if(array_key_exists("ap_titles", $add_temp)) $ap_titles = $add_temp['ap_titles'];
                    if(array_key_exists("ap_contents", $add_temp)) $ap_contents = $add_temp['ap_contents'];
                    if(array_key_exists("ap_thumbnail", $add_temp)) $ap_thumbnail = $add_temp['ap_thumbnail'];
                    if(array_key_exists("ap_cats", $add_temp)) $ap_cats = $add_temp['ap_cats'];
                    if(!is_array($ap_titles)) $ap_titles = [];
                    if(!is_array($ap_contents)) $ap_contents = [];
                    if(!is_array($ap_cats)) $ap_cats = [];
                    $ap_cats[] = $cat_id;
        
                    // Title start
                    $title = '';
                    if(is_array($ap_titles) && sizeof($ap_titles) > 0){
                        shuffle($ap_titles);
                        $contentIndex = array_rand($ap_titles, 1);
                        $title = $ap_titles[$contentIndex];
                    }
                    if(empty($title)){
                        $title = 'How to %%term_name%% Fix Guide.';
                    }
                    $title = str_replace("%%term_name%%", $cat_name, $title);
                    // Title end
                    
                    // Contents start
                    $contents = '';
                    if(is_array($ap_contents) && sizeof($ap_contents) > 0){
                        shuffle($ap_contents);
                        $contentIndex = array_rand($ap_contents, 1);
                        $contents = $ap_contents[$contentIndex];
                    }
                    if(empty($contents)){
                        $contents = 'To fix the issues in %%term_name%%, you have to do the following steps.';
                    }
                    $contents = str_replace("%%term_name%%", $cat_name, $contents);
                    // Content end
        
                    $status = 'draft';
                    if(get_option('ap_auto_published') === 'on'){
                        $status = 'publish';
                    }
        
                    // Create post object
                    $cat_post = array(
                        'post_title'    => wp_strip_all_tags( $title ),
                        'post_content'  => $contents,
                        'post_status'   => $status,
                        'post_author'   => $ap_author,
                        'meta_input'   => array(
                            "ap_cat_$cat_id" => $cat_id
                        )
                    );
                    
                    // Insert the post into the database
                    $post_id = wp_insert_post( $cat_post );
            
                    if($ap_thumbnail && !is_wp_error( $post_id )){
                        set_post_thumbnail( $post_id, $ap_thumbnail );
                    }
        
                    if(is_array($ap_cats) && !is_wp_error( $post_id )){
                        wp_set_post_categories( $post_id, $ap_cats );
                    }
                }
            }
        }
    }
    
    function delete_category_action_for_post($term_id){
        global $wpdb;
        if(get_option('ap_delete_post_if_cat_deleted') === 'on'){
            $post_ids = $wpdb->get_results("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = 'ap_cat_$term_id' AND meta_value = '$term_id'");
            if($post_ids){
                foreach($post_ids as $post){
                    if(get_post_status($post->post_id) === 'draft'){
                        wp_delete_post( $post->post_id, true );
                    }
                }
            }
        }
    }

    function delete_post_tag_action_for_post($term_id){
        global $wpdb;
        if(get_option('ap_delete_post_if_cat_deleted') === 'on'){
            $post_ids = $wpdb->get_results("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = 'ap_tags_$term_id' AND meta_value = '$term_id'");
            if($post_ids){
                foreach($post_ids as $post){
                    if(get_post_status($post->post_id) === 'draft'){
                        wp_delete_post( $post->post_id, true );
                    }
                }
            }
        }
    }

    function ap_form_data_save(){
        // Default fields
        if(isset($_POST['ap__default_template'])){
            $data = $_POST['ap_default_values'];
            $form_data = array(
                'ap_author' => null,
                'ap_titles' => [],
                'ap_contents' => [],
                'ap_thumbnail' => '',
                'ap_tags' => '',
                'ap_cats' => ''
            );
            
            if(array_key_exists("ap_author", $data)) $form_data['ap_author'] = intval($data['ap_author']);
            if(array_key_exists("ap_titles", $data)) $form_data['ap_titles'] = $data['ap_titles'];
            if(array_key_exists("ap_titles", $form_data)) {
                $titles_arr = [];
                foreach($form_data['ap_titles'] as $titles){
                    $titles_arr[] = stripcslashes( $titles );
                }
                $form_data['ap_titles'] = $titles_arr;
            }

            if(array_key_exists("ap_contents", $data)) $form_data['ap_contents'] = $data['ap_contents'];
            if(array_key_exists("ap_contents", $form_data)) {
                $contents_arr = [];
                foreach($form_data['ap_contents'] as $contents){
                    $contents_arr[] = stripcslashes( $contents );
                }
                $form_data['ap_contents'] = $contents_arr;
            }

            if(array_key_exists("ap_thumbnail", $data)) $form_data['ap_thumbnail'] = intval($data['ap_thumbnail']);
            if(array_key_exists("ap_tags", $data)) $form_data['ap_tags'] = $data['ap_tags'];
            if(array_key_exists("ap_cats", $data)) $form_data['ap_cats'] = $data['ap_cats'];

            update_option( '_ap_default_template', $form_data );
        }

        // Additional fields
        if(isset($_POST['ap__additional_template'])){
            $templatesArr = $_POST['ap_additional_values'];
            $templates = [];
            if(is_array( $templatesArr )){
                foreach($templatesArr as $data){
                    $form_data = array(
                        'ap_author' => null,
                        'ap_titles' => [],
                        'ap_contents' => [],
                        'ap_thumbnail' => '',
                        'ap_tags' => '',
                        'ap_cats' => ''
                    );
                    
                    if(array_key_exists("ap_author", $data)) $form_data['ap_author'] = intval($data['ap_author']);
                    if(array_key_exists("ap_titles", $data)) $form_data['ap_titles'] = $data['ap_titles'];
                    if(array_key_exists("ap_titles", $form_data)) {
                        $titles_arr = [];
                        foreach($form_data['ap_titles'] as $titles){
                            $titles_arr[] = stripcslashes( $titles );
                        }
                        $form_data['ap_titles'] = $titles_arr;
                    }

                    if(array_key_exists("ap_contents", $data)) $form_data['ap_contents'] = $data['ap_contents'];
                    if(array_key_exists("ap_contents", $form_data)) {
                        $contents_arr = [];
                        foreach($form_data['ap_contents'] as $contents){
                            $contents_arr[] = stripcslashes( $contents );
                        }
                        $form_data['ap_contents'] = $contents_arr;
                    }

                    if(array_key_exists("ap_thumbnail", $data)) $form_data['ap_thumbnail'] = intval($data['ap_thumbnail']);
                    if(array_key_exists("ap_tags", $data)) $form_data['ap_tags'] = $data['ap_tags'];
                    if(array_key_exists("ap_cats", $data)) $form_data['ap_cats'] = $data['ap_cats'];

                    $templates[] = $form_data;
                }
            }

            update_option( '_ap_additional_template', $templates );
        }
    }

    // Ajax - get additional template
    function get_additional_template(){
        if(isset($_GET['index'])){
            $idex = $_GET['index'];

            $template = $this->template_component('additional', 'ap_additional_values['.time().']', [], $idex);
            echo json_encode(array("template" => $template));
        }
        
        die;
    }
 }