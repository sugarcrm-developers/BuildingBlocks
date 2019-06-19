
function callAuth(){

data = {
    	username: jQuery('#sugarcrm_input_username').val(),
    	password: jQuery('#sugarcrm_input_password').val(),
    	url: jQuery('#sugarcrm_input_url').val(),
    	version: jQuery('#sugarcrm_input_api_version option:checked').val(),
    }
jQuery.ajax({
    url: "http://localhost:8888/sugartest/wp-json/sugarcrm-api/v1/auth",
    beforeSend: function(xhr) {
        xhr.setRequestHeader("Content-Type", "application/json");
        xhr.setRequestHeader("Accept", "application/json");
    },
    dataType: "json",
    data: JSON.stringify(data),
    type: "POST",
    success: function(response) {
        token = response.access_token;
        expiresIn = response.expires_in;
        console.log("success!!", response);
        if (true === response) {
			jQuery('#sugar_submit').prop('disabled', false);
			jQuery('#sugarcrm_verify').prop('disabled', true);
			jQuery('#alert-valid-reminder').show();
			jQuery('#alert-valid').show();
        } else {
        	jQuery('#alert-invalid').show();
        }
    },
    error: function(errorThrown) {
        console.log("no success", errorThrown.error);
    }
});
	return false;
}

/*window.onload = function () {
   document.getElementById("sugarcrm_verify").onclick=callAuth;
};
*/
jQuery(document).ready(function(){
	jQuery('#alert-valid-reminder').hide();
	jQuery('#sugarcrm-credentials-form input, #sugarcrm-credentials-form select').on('change', function() {
		jQuery('#alert-valid, #alert-invalid').hide();
		jQuery('#sugar_submit').prop('disabled', true);
		jQuery('#sugarcrm_verify').prop('disabled', false);
	});
    jQuery("#sugarcrm_verify").click(callAuth);
});