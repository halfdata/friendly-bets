"use strict";
var busy = false;
function register(_object) {
	if (busy) return;
	busy = true;
	jQuery(_object).find("i").attr("class", "fas fa-spin fa-spinner");
	jQuery(_object).closest(".form").find(".inline-error").slideUp(200);
	var form = jQuery(_object).closest(".form");
	jQuery(form).find(".element-error").fadeOut(300, function(){
		jQuery(this).remove();
	});
	var post_data = {
		"action"		    : "register",
		"timezone"		    : jQuery(form).find("[name='timezone']").val(),
		"name"		        : jQuery(form).find("[name='name']").val(),
		"email"		        : jQuery(form).find("[name='email']").val(),
		"password"	        : jQuery(form).find("[name='password']").val(),
		"repeat-password"   : jQuery(form).find("[name='repeat-password']").val(),
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
                location.href = data.url;
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
function set_password(_object) {
	if (busy) return;
	busy = true;
	jQuery(_object).find("i").attr("class", "fas fa-spin fa-spinner");
	jQuery(_object).closest(".form").find(".inline-error").slideUp(200);
	var form = jQuery(_object).closest(".form");
	jQuery(form).find(".element-error").fadeOut(300, function(){
		jQuery(this).remove();
	});
	var post_data = {
		"action"		    : "set-password",
		"password"	        : jQuery(form).find("[name='password']").val(),
		"repeat-password"   : jQuery(form).find("[name='repeat-password']").val(),
		"reset-id"	        : jQuery(form).find("[name='reset-id']").val(),
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
                location.href = data.url;
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
