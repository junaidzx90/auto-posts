<h3>Auto posts settings</h3>
<hr>
<div class="ap_settings__tab">
<?php
    $current_tab = 'general';

    if(isset($_GET['page']) && $_GET['page'] === 'auto-posts' && isset($_GET['tab'])){
        $tab = sanitize_text_field( $_GET['tab'] );

        switch ($tab) {
            case 'general':
                $current_tab = 'general';
                break;
            case 'default':
                $current_tab = 'default';
                break;
            case 'additional':
                $current_tab = 'additional';
                break;
            
            default:
                $current_tab = 'default';
                break;
        }
    }
    ?>

    <a href="?page=auto-posts&tab=general" class="ap_tablinks <?php echo (($current_tab === 'general') ? 'active': '') ?>">General setting</a>
    <a href="?page=auto-posts&tab=default" class="ap_tablinks <?php echo (($current_tab === 'default') ? 'active': '') ?>">Default template</a>
    <a href="?page=auto-posts&tab=additional" class="ap_tablinks <?php echo (($current_tab === 'additional') ? 'active': '') ?>">Additional templates</a>
</div>

<!-- Genral contents -->
<div id="general" class="ap_tabcontent <?php echo (($current_tab === 'general') ? 'active': '') ?>">
    <form method="post" style="width: 50%" action="options.php">
        <?php
        settings_fields( 'ap_general_opt_section' );
        do_settings_sections( 'ap_general_opt_page' );
        
        submit_button(  );
        ?>
    </form>	
</div>

<!-- Default contents -->
<div id="default" class="ap_tabcontent <?php echo (($current_tab === 'default') ? 'active': '') ?>">
    <form class="__default_template" method="post" action="">
        <?php 
        $data = get_option( '_ap_default_template' );
        echo $this->template_component('default', 'ap_default_values', $data); 
        ?>
        <input type="submit" class="button-primary" value="Save changes" name="ap__default_template">
    </form>
</div>

<!-- Additional contents -->
<div id="additional" class="ap_tabcontent <?php echo (($current_tab === 'additional') ? 'active': '') ?>">
    <form class="__default_template" method="post" action="">
        <div id="additional_templates">
            <?php 
            $additional_template = get_option( '_ap_additional_template' );
            if(is_array($additional_template)){
                foreach($additional_template as $key => $template){
                    echo $this->template_component('additional', 'ap_additional_values['.$key.']', $template);
                }
            }
            ?>
        </div>
        <input type="submit" class="button-primary" value="Save changes" name="ap__additional_template">
        <button class="button-secondary" id="add_new_template">Add new template</button>
    </form>
</div>