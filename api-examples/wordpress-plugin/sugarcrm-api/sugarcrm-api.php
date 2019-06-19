<?php
/**
 * Plugin Name: SugarCRM Comments API Integration
 * Description: Used for testing Sugar API integration with WordPress
 * Version:     1.0.0
 * Author:      Michael Shaheen - SugarCRM
 * Author URI:  https://www.sugarcrm.com/
 * License:     GPL2
 */

// found this simple logging function at Stack Overflow - https://stackoverflow.com/a/14543498
function log_it( $msg, $label = '' ) {
    // Print the name of the calling function if $label is left empty
    $trace=debug_backtrace();
    $label = ( '' == $label ) ? $trace[1]['function'] : $label;

    // update this path as applicable
    $error_dir = '/Applications/MAMP/logs/php_error.log';
    $msg = print_r( $msg, true );
    $log = $label . "  |  " . $msg . "\n";
    error_log( $log, 3, $error_dir );
}

// BEGIN -- functions for Sugar communication

// returns the authkey token
function auth_to_sugar($usrnm, $pwrd, $url, $version) {
	$sugar_oauth_token = wp_cache_get('sugar_oauth_token_access');
	if (empty($sugar_oauth_token)) {

		// auth token empty so use refresh token and replace auth token and refresh token
		$api_path_auth = "/oauth2/token";
		$auth_url = sanitize_text_field($url) . '/rest/v' . sanitize_text_field($version) . $api_path_auth;
		log_it($auth_url, "auth_url");
		$return_auth_key = '';
		$sugar_username = sanitize_text_field($usrnm);
		$sugar_password = sanitize_text_field($pwrd);

		$oauth2_token_arguments = array(
	        'grant_type' => 'password',
	        'client_id' => 'sugar',
	        'client_secret' => '',
	        'username' => $sugar_username,
	        'password' => $sugar_password,
	        'platform' => 'wordpress_api'
	    );

		$auth_request = curl_init($auth_url);
		curl_setopt($auth_request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
		curl_setopt($auth_request, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($auth_request, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($auth_request, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($auth_request, CURLOPT_HTTPHEADER, array(
		    "Content-Type: application/json"
		));

		//convert arguments to json
		$json_arguments = json_encode($oauth2_token_arguments);
		curl_setopt($auth_request, CURLOPT_POSTFIELDS, $json_arguments);

		//execute request
		$oauth2_token_response = curl_exec($auth_request);

		//decode oauth2 response to get token
		$oauth2_token_response_obj = json_decode($oauth2_token_response);
		$sugar_oauth_token = $oauth2_token_response_obj->access_token;
		wp_cache_set('sugar_oauth_token_access', $sugar_oauth_token);
		wp_cache_set('sugar_oauth_token_refresh', $oauth2_token_response_obj->refresh_token);
    }

	return $sugar_oauth_token;
}

function get_user_id_from_sugar($wp_email_address, $sugar_oauth_token) {
	$api_path_search = "/search";

	// restrict this to leads or contacts
    $search_arguments = array(
        'q'        => $wp_email_address,
        'module_list' => 'Contacts,Leads'
    );

	// since this request is a GET we will add the arguments to the URL
	$search_url = get_option('sugarcrm_input_url') . '/rest/v' . get_option("sugarcrm_input_api_version") . $api_path_search . "?" . http_build_query($search_arguments);
	log_it($search_url, "search_url");
	$search_response = curl_it($sugar_oauth_token, $search_url);

	//decode json
	$search_response_obj = json_decode($search_response);

	wp_cache_set('sugar_user', reset($search_response_obj->records));

	// returns id of first record
	return isset(wp_cache_get('sugar_user')->id) ? wp_cache_get('sugar_user')->id : '';

}

function curl_it($sugar_oauth_token, $curl_url, $data = null, $ispost = false) {
	$the_response = null;
	$the_request = curl_init($curl_url);
	curl_setopt($the_request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
	curl_setopt($the_request, CURLOPT_SSL_VERIFYPEER, 1);
	curl_setopt($the_request, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($the_request, CURLOPT_FOLLOWLOCATION, 0);
	curl_setopt($the_request, CURLOPT_HTTPHEADER, array(
	    "Content-Type: application/json",
	    "oauth-token: {$sugar_oauth_token}"
	));
	if ($ispost) {
		curl_setopt($the_request, CURLOPT_POSTFIELDS, $data);
	}
	$the_response = curl_exec($the_request) ;
	curl_close($the_request);
	return $the_response;
}

function send_comment_to_sugar_function( $comment_ID, $comment_approved ) {
	    
	    $sugar_oauth_token = auth_to_sugar(get_option( 'sugarcrm_input_username' ), get_option( 'sugarcrm_input_password' ), get_option('sugarcrm_input_url'), get_option("sugarcrm_input_api_version"));
		$current_user = wp_get_current_user();
		$current_user_email = $current_user->user_email;
		$sugar_user_id = get_user_id_from_sugar($current_user_email, $sugar_oauth_token);
		$current_comment = get_comment($comment_ID);
		$current_post = get_post($current_comment->comment_post_ID);
		$post_title = get_the_title($current_comment->comment_post_ID);
		$post_url = get_permalink($current_comment->comment_post_ID);

		if (empty($sugar_user_id)) {
			$api_path = "/Leads";
			$url = get_option('sugarcrm_input_url') . '/rest/v' . get_option("sugarcrm_input_api_version") . $api_path;

			$record_lead = array(
			    'last_name' => $current_user->user_lastname,
			    'first_name' => $current_user->user_firstname,
			    'email1' => $current_user_email,
			    'lead_source' => 'Other',
			    'lead_source_description' => 'User posted a comment to ' . $post_url
			);
			$json_arguments = json_encode($record_lead);

			$curl_response = curl_it($sugar_oauth_token, $url, $json_arguments, true);
			//decode json
			$createdRecord = json_decode($curl_response);


// now create the note
			if (!empty($createdRecord->id)) {
				$api_path = "/Notes";
				$url = get_option('sugarcrm_input_url') . '/rest/v' . get_option("sugarcrm_input_api_version") . $api_path;
				//Set up the Record details
				$record = array(
				    'name' => 'Note posted to "' . $post_title . '"',
				    'description' => $current_comment->comment_content . "\n\n Comment added to WordPress post at " . $post_url,
				    'parent_id' => $createdRecord->id,
			    	'parent_type' => 'Leads'
				);
				$json_arguments = json_encode($record);
				$curl_response = curl_it($sugar_oauth_token, $url, $json_arguments, true);

				//decode json
				$createdRecord = json_decode($curl_response);
			}

		} else {
			$sugar_account_type = wp_cache_get('sugar_user')->_module;
			$sugar_account_id = wp_cache_get('sugar_user')->account_id;

		//Create Records - POST /<module>
			$api_path = "/Notes";
			$url = get_option('sugarcrm_input_url') . '/rest/v' . get_option("sugarcrm_input_api_version") . $api_path;
			//Set up the Record details
			$record = array(
			    'name' => 'Note posted to "' . $post_title . '"',
			    'description' => $current_comment->comment_content . "\n\n Comment added to WordPress post at " . $post_url,
			    'parent_id' => $sugar_account_type === 'Leads' ? $sugar_user_id : $sugar_account_id,
			    'parent_type' => $sugar_account_type === 'Leads' ? 'Leads' : 'Accounts'
			);
			if ($sugar_account_type !== 'Leads') {
				$record["contact_id"] = $sugar_user_id;
			}

			$json_arguments = json_encode($record);
			$curl_response = curl_it($sugar_oauth_token, $url, $json_arguments, true);

			//decode json
			$createdRecord = json_decode($curl_response);

		}
}

// END -- functions for Sugar communication

// BEGIN -- functions for admin screen

function create_plugin_settings_page() {
	wp_register_style('sugarcrm-api', plugins_url( 'sugarcrm-api'.'/css/sugarcrm-api.css'));
	wp_enqueue_style('sugarcrm-api');
    // Add the menu item and page
    $page_title = 'Sugar API Settings Page';
    $menu_title = 'Sugar API Plugin';
    $capability = 'manage_options';
    $slug = 'sugarcrm_api_fields';
    $callback = 'plugin_settings_page_content';
    $icon = 'dashicons-sugarcube';
    $position = 100;

    add_menu_page( $page_title, $menu_title, $capability, $slug, $callback, $icon, $position );
}

function plugin_settings_page_content() {
	if( isset($_POST['sugarcrm_input_username']) ){
        handle_form();
    } 
    ?>
    <div class="wrap">
        <h2>Sugar API Settings Page</h2>
        <form method="post" id="sugarcrm-credentials-form">
        	    <input type="hidden" name="updated" value="true" />

            <?php
                settings_fields( 'sugarcrm_api_fields' );
                do_settings_sections( 'sugarcrm_api_fields' );
                submit_button('Save Changes', 'submit', 'sugar_submit');
            ?>
            <button type="button" disabled="true" id="sugarcrm_verify">verify</button>
        </form>
		<p id="alert-valid" style="display: block;"><i class="fas fa-check-circle" style="color: green;"></i> These values have been checked and are valid. <span id="alert-valid-reminder">Remember to hit "Save Changes" if you wish these to be your settings going forward.</span></p>
		<p id="alert-invalid" style="display: none;"><i class="fas fa-times-circle" style="color: red;"></i> Please check your values above and verify again.</p>
    </div> 
    <?php
}

function field_callback( $arguments ) {
    $value = get_option( $arguments['uid'] ); // Get the current value, if there is one
    if( ! $value && isset($arguments['default'])) { // If no value exists
        $value = $arguments['default']; // Set to our default
    }
    $style_string = implode(';', $arguments['inline_style']);

    // Check which type of field we want
    switch( $arguments['type'] ){
        case 'text': // If it is a text field
            printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" style="%5$s" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value, $style_string );
            break;
        case 'password': // If it is a text field
            printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" style="%5$s" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value, $style_string );
            break;
        case 'select': // If it is a select dropdown
	        if( ! empty ( $arguments['options'] ) && is_array( $arguments['options'] ) ){
	            $options_markup = '';
	            foreach( $arguments['options'] as $key => $label ){
	                $options_markup .= sprintf( '<option value="%s" %s>%s</option>', $key, selected( $value, $key, false ), $label );
	            }
	            printf( '<select name="%1$s" id="%1$s" style="%3$s">%2$s</select>', $arguments['uid'], $options_markup, $style_string );
	        }
	        break;
    }

    // If there is help text
    if( $helper = $arguments['helper'] ){
        printf( '<span class="helper"> %s</span>', $helper ); // Show it
    }

    // If there is supplemental text
    if( $supplimental = $arguments['supplemental'] ){
        printf( '<p class="description">%s</p>', $supplimental ); // Show it
    }
}

function setup_sections() {
    add_settings_section( 'auth_section', 'Sugar Auth Credentials', false, 'sugarcrm_api_fields' );
}

function setup_fields() {
	$fields = array(
	        array(
	            'uid' => 'sugarcrm_input_username',
	            'label' => 'Sugar Login Username',
	            'section' => 'auth_section',
	            'type' => 'text',
	            'options' => false,
	            'placeholder' => '',
	            'helper' => '',
	            'supplemental' => '',
	            'inline_style' => array()
	        ),
	        array(
	            'uid' => 'sugarcrm_input_password',
	            'label' => 'Sugar Login Password',
	            'section' => 'auth_section',
	            'type' => 'password',
	            'options' => false,
	            'placeholder' => '',
	            'helper' => '',
	            'supplemental' => '',
	            'inline_style' => array()
	        ),
	        array(
	            'uid' => 'sugarcrm_input_url',
	            'label' => 'Sugar Instance URL',
	            'section' => 'auth_section',
	            'type' => 'text',
	            'options' => false,
	            'placeholder' => '',
	            'supplemental' => 'ex: http://localhost:8080/sugar',
	            'helper' => '',
	            'inline_style' => array('width: 75%')
	        ),
	        array(
		        'uid' => 'sugarcrm_input_api_version',
		        'label' => 'API Version',
		        'section' => 'auth_section',
		        'type' => 'select',
		        'options' => array(
		            '' => '',
		            '11_3' => 'v11.3',
		            '11_2' => 'v11.2',
		            '11_1' => 'v11.1'
		        ),
		        'placeholder' => '',
		        'helper' => '',
		        'supplemental' => '',
		        'default' => '',
	            'inline_style' => array()
		    )
	    );
    foreach( $fields as $field ){
        add_settings_field( $field['uid'], $field['label'], 'field_callback', 'sugarcrm_api_fields', $field['section'], $field );
        register_setting( 'sugarcrm_api_fields', $field['uid'] );
    }
}

// TODO: add a buton that checks the entered credentials on the admin form BEFORE saving

function handle_form() {
	foreach ($_POST as $key => $value) {
		if (strpos($key, 'sugarcrm_input_') !== false) {
			$sani = sanitize_text_field( $value );
			update_option( $key, $value );

		}
	}
	?>
    <div class="updated">
        <p>Your fields were saved!</p>
    </div> <?php
}
function init_scripts() {
    wp_enqueue_script( 'sugarcrm-api-scripts', plugins_url( '/js/scripts.js', __FILE__ ));
	wp_enqueue_script( 'wpb-fa', 'https://kit.fontawesome.com/7a94ad74dd.js' );
}

function sugarcrm_rest_auth_validate( $request ) {
	log_it($request->get_params( ), 'params');
	    $sugar_oauth_token = auth_to_sugar($request->get_param('username'), $request->get_param('password'), $request->get_param('url'), $request->get_param('version'));
	    return !empty( $sugar_oauth_token );
}

function init_rest_api () {
  register_rest_route( 'sugarcrm-api/v1', '/auth', array(
    'methods' => \WP_REST_Server::EDITABLE,
    'callback' => 'sugarcrm_rest_auth_validate',
    'args' => ['username', 'password', 'url', 'version']
  ) );
}
// END -- functions for admin screen

// BEGIN - wordpress actions

add_action( 'admin_init', 'setup_sections' );
add_action( 'admin_init', 'setup_fields' );
add_action( 'comment_post', 'send_comment_to_sugar_function', 10, 2 );
add_action( 'admin_menu', 'create_plugin_settings_page' );
add_action( 'admin_enqueue_scripts','init_scripts' );
add_action( 'rest_api_init', 'init_rest_api' );

// END - wordpress actions
