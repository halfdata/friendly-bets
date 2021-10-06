"use strict";
var busy = false;
function switch_form(_object) {
    var current_form_id = jQuery(_object).closest(".form-wrapper").attr("id");
    var new_form_id = "reset-form";
    if (current_form_id == "reset-form") new_form_id = "login-form";
    jQuery("#"+current_form_id).fadeOut(200, function() {
        jQuery("#"+new_form_id).fadeIn(200);
    });
    return false;
}
function login(_object) {
	if (busy) return;
	busy = true;
	jQuery(_object).find("i").attr("class", "fas fa-spin fa-spinner");
	jQuery(_object).closest(".form").find(".inline-error").slideUp(200);
	var form = jQuery(_object).closest(".form");
	jQuery(form).find(".element-error").fadeOut(300, function(){
		jQuery(this).remove();
	});
	var post_data = {
		"action"		    : "login",
		"email"		        : jQuery(form).find("[name='email']").val(),
		"password"	        : jQuery(form).find("[name='password']").val(),
		"redirect"	        : jQuery(form).find("[name='redirect']").val(),
		"hostname"		    : window.location.hostname,
		"_token" 			: jQuery("input[name='_token']").val()
	};
	jQuery.ajax({
		url		:	ajax_url, 
		data	:	post_data,
		method	:	"post",
		dataType:	"json",
		async	:	true,
		success	:	function(return_data) {
			var data;
			if (typeof return_data == 'object') data = return_data;
			else data = jQuery.parseJSON(return_data);
			if (data.status == "OK") {
                global_message_show('success', data.message);
                location.href = data.url;
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
function reset(_object) {
	if (busy) return;
	busy = true;
	jQuery(_object).find("i").attr("class", "fas fa-spin fa-spinner");
	jQuery(_object).closest(".form").find(".inline-error").slideUp(200);
	var form = jQuery(_object).closest(".form");
	jQuery(form).find(".element-error").fadeOut(300, function(){
		jQuery(this).remove();
	});
	var post_data = {
		"action"		    : "reset-password",
		"email"		        : jQuery(form).find("[name='email']").val(),
		"redirect"	        : jQuery(form).find("[name='redirect']").val(),
		"hostname"		    : window.location.hostname,
		"_token" 			: jQuery("input[name='_token']").val()
	};
	jQuery.ajax({
		url		:	ajax_url, 
		data	:	post_data,
		method	:	"post",
		dataType:	"json",
		async	:	true,
		success	:	function(return_data) {
			var data;
			if (typeof return_data == 'object') data = return_data;
			else data = jQuery.parseJSON(return_data);
			if (data.status == "OK") {
                global_message_show('success', data.message);
                jQuery(form).find("[name='email']").val("");
			} else if (data.status == "ERROR") {
				for (var id in data["errors"]) {
					if (data["errors"].hasOwnProperty(id)) {
						jQuery(form).find("[name='"+id+"']").parent().append("<div class='element-error'><span>"+data["errors"][id]+"</span></div>");
					}
				}
				jQuery(form).find(".element-error").fadeIn(300);
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

jQuery(document).keyup(function(e) {
	if (e.keyCode == 13) {
		if (jQuery(document.activeElement).parent().hasClass("input-box")) {
			if (jQuery(document.activeElement).prop("tagName").toLowerCase() == "textarea" && !e.ctrlKey) {
				return;
			}
			form = jQuery(document.activeElement).closest(".form").find(".button").click();
		}
	}
});
