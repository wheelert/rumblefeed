<?php

//Add stylesheet
function ww_rumblefeed_scripts() {
    wp_enqueue_style('ww_rumblefeed', plugins_url('css/rumblefeed.css', __FILE__));
    wp_enqueue_script('ww_rumblefeed', plugins_url('js/rumblefeed.js', __FILE__), array('jquery'), null, true);
}

add_action('admin_menu', 'ww_rf_options_page');
add_action('wp_enqueue_scripts', 'ww_rumblefeed_scripts');
add_shortcode( 'rumblefeed', 'rumblefeed_shortcode_fn');

function rumblefeed_shortcode_fn( $attributes ) {

    extract( shortcode_atts( array('channel' => 'false'), $attributes ) );
   
    $body .= display_feed($channel);


    return $body;
}


function display_feed($channel){

    $file = plugin_dir_path(__FILE__) ."rss.xml";
    $content = file_get_contents($file);
    $content = str_replace("media:thumbnail","thumbnail", $content); 
    $content = str_replace("itunes:image","image", $content); 

    try{
        // Instantiate XML element
        $a = new SimpleXMLElement($content);
            
        $body = '<ul class="rumblefeed">';
            
        foreach($a->channel->item as $entry) {
            $body .='<li><a href="'.$entry->guid.'" target="_blank" rel="noopener noreferrer"><img src="'.$entry->thumbnail->attributes().'" width="320" />
                    <span>'.$entry->title.'</span>
                    </a>
                </li>';  

        }
            
        $body .= "</ul>";
    }catch(Exception $ex){
           $body =  $ex; 
           $body .= $file;
    }

    return $body;

}

//
// Admin page
//

function ww_rf_options_page()
{
    register_setting( 'ww_rf', 'rf_channel' );
    register_setting( 'ww_rf', 'rf_pull' );
    //register_setting( 'ww_rf', 'option_3' );


	add_submenu_page(
		'themes.php', //apage
		'Rumble Feed settings', //page title
		'Rumble Feed', //menu tital
		'manage_options', //capability
		'ww_rf', //menu slug
		'ww_rf_options_page_html'
	);
}

function ww_rf_options_page_html() {
	// check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
    <script>
    jQuery(document).ready(function($) { 
    jQuery(".rf-pull").click(function () {
     console.log('rf-pull function hook');
     jQuery.ajax({
         type: "POST",
         url: "/wp-admin/admin-ajax.php",
         data: {
             action: 'rf_pull_feed',
             // add your parameters here
             message_id: $('.your-selector').val()
         },
         success: function (output) {
            console.log(output);
         }
         });
     });
    });
    </script>

	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <p>Feed pull will happen every hour, use the pull feed button to manually pull the feed.</p>
		<form action="options.php" method="post">
			<?php

            add_settings_section(  
                'rf_settings_section', // Section ID 
                'Rumble Feed Channel Settings', // Section Title
                'rf_section_options_callback', // Callback
                'ww_rf' // What Page?  This makes the section show up on the General Settings Page
            );

            add_settings_field( // Option 1
                'rf_channel', // Option ID
                'Rumble Channel', // Label
                'rf_textbox_callback', // !important - This is where the args go!
                'ww_rf', // Page it will be displayed (General Settings)
                'rf_settings_section', // Name of our section
                array( // The $args
                    'rf_channel',// Should match Option ID
                    'label_for' => 'rf_channel',
                    'class' => 'ww_label' 
                )  
            ); 
            
            
            add_settings_field( // Option 2
                'rf_pull', // Option ID
                'Save settings before pulling feed', // Label
                'rf_do_pull_feed', // !important - This is where the args go!
                'ww_rf', // Page it will be displayed (General Settings)
                'rf_settings_section', // Name of our section
                array( // The $args
                    'rf_pull' // Should match Option ID
                )  
            ); 
            /** 
            add_settings_field( // Option 3
                'option_3', // Option ID
                'Truth Social', // Label
                'rf_textbox_callback', // !important - This is where the args go!
                'ww_rf', // Page it will be displayed (General Settings)
                'rf_settings_section', // Name of our section
                array( // The $args
                    'option_3' // Should match Option ID
                )  
            ); 

            */
			
			settings_fields( 'ww_rf' );
			do_settings_sections( 'ww_rf' );
			
            // output save settings buttonrk_message_as_read
			submit_button( __( 'Save Settings', 'ww_rf' ) );
            
			?>
		</form>
	</div>
	<?php
}

function rf_textbox_callback($args){
    $name = $args[0];
    echo '<input name="'.$name.'" value="'. get_option($name) .'" placeholder="[channel]" size="70" />';
}

function rf_do_pull_feed($args){
    echo '<INPUT TYPE="button" class="rf-pull" VALUE="Pull Feed">';
}

//
// Pull feed rss
//
// register the ajax action for authenticated users
add_action('wp_ajax_rf_pull_feed', 'rf_pull_feed');
function rf_pull_feed(){
    $channel = get_option("rf_channel");
    $url = "http://rssgen.xyz/rumble/".$channel;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);

    $file = plugin_dir_path(__FILE__) ."rss.xml";

    $fp = fopen($file, 'w');

    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_exec ($ch);
    curl_close ($ch);
    fclose($fp);

    $response = array( 'success' => true);
    wp_send_json_success($response);

}

?>