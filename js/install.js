"use strict";
var busy = false;
function submit_form(_object) {
	if (busy) return;
	busy = true;
	jQuery(_object).find("i").attr("class", "fas fa-spin fa-spinner");
	jQuery(_object).closest(".form").find(".inline-error").slideUp(200);
	var form = jQuery(_object).closest(".form");
	jQuery(form).find(".element-error").fadeOut(300, function(){
		jQuery(this).remove();
	});
	jQuery.ajax({
		url		:	ajax_url, 
		data	:	jQuery(_object).closest(".form").find("input, textarea, select").serialize(),
		method	:	"post",
		dataType:	"json",
		async	:	true,
		success	:	function(return_data) {
			var data;
			if (typeof return_data == 'object') data = return_data;
			else data = jQuery.parseJSON(return_data);
			if (data.status == "OK") {
				jQuery("#installation-form").fadeOut(250, function(){
					jQuery("#installation-form").html(data.html);
					jQuery("#installation-form").fadeIn(250);
				});
			} else if (data.status == "ERROR") {
                global_message_show('danger', data.message);
			} else if (data.status == "WARNING") {
                global_message_show('danger', data.message);
			} else {
                global_message_show('danger', "Internal Error.");
			}
			jQuery(_object).find("i").attr("class", "fas fa-angle-right");
			busy = false;
		},
		error	:	function(XMLHttpRequest, textStatus, errorThrown) {
            global_message_show('danger', "Invalid server response: " + textStatus);
			jQuery(_object).find("i").attr("class", "fas fa-angle-right");
			busy = false;
		}
	});
	return false;
}
var global_message_timer;
function global_message_show(_type, _message) {
	clearTimeout(global_message_timer);
	jQuery("#global-message").fadeOut(300, function() {
		jQuery("#global-message").attr("class", "");
		jQuery("#global-message").addClass("global-message-"+_type).html(_message);
		jQuery("#global-message").fadeIn(300);
		global_message_timer = setTimeout(function(){jQuery("#global-message").fadeOut(300);}, 5000);
	});
}
