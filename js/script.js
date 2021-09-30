"use strict";
var busy = false;
function save_form(_object) {
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
				if (data.hasOwnProperty('message')) {
                	global_message_show('success', data.message);
				}
				if (data.hasOwnProperty('url')) {
					location.href = data.url;
				}
			} else if (data.status == "ERROR") {
				for (var id in data["errors"]) {
					if (data["errors"].hasOwnProperty(id)) {
						jQuery(form).find("[name='"+id+"']").after("<div class='element-error'><span>"+data["errors"][id]+"</span></div>");
					}
				}
				jQuery(form).find(".element-error").fadeIn(300);
                var tab = jQuery(form).find(".element-error").first().closest(".tab-content")
                if (jQuery(tab).length > 0) {
                    jQuery(".tab[href='#"+jQuery(tab).attr("id")+"']").click();
                }
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
function google_disconnect(_object) {
	if (busy) return;
	busy = true;
	jQuery(_object).find("i").attr("class", "fas fa-spin fa-spinner");
	var post_data = {
		"action"		    : "google-disconnect",
		"hostname"		    : window.location.hostname
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
                jQuery(_object).closest(".input-box").html(data.html);
                global_message_show('success', data.message);
			} else if (data.status == "ERROR") {
                global_message_show('danger', data.message);
			} else if (data.status == "WARNING") {
                global_message_show('danger', data.message);
			} else {
                global_message_show('danger', "Internal Error.");
			}
			jQuery(_object).find("i").attr("class", "fab fa-google");
			busy = false;
		},
		error	:	function(XMLHttpRequest, textStatus, errorThrown) {
            global_message_show('danger', "Invalid server response: " + textStatus);
			jQuery(_object).find("i").attr("class", "fab fa-google");
			busy = false;
		}
	});
	return false;
}
function facebook_disconnect(_object) {
	if (busy) return;
	busy = true;
	jQuery(_object).find("i").attr("class", "fas fa-spin fa-spinner");
	var post_data = {
		"action"		    : "facebook-disconnect",
		"hostname"		    : window.location.hostname
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
                jQuery(_object).closest(".input-box").html(data.html);
                global_message_show('success', data.message);
			} else if (data.status == "ERROR") {
                global_message_show('danger', data.message);
			} else if (data.status == "WARNING") {
                global_message_show('danger', data.message);
			} else {
                global_message_show('danger', "Internal Error.");
			}
			jQuery(_object).find("i").attr("class", "fab fa-facebook-f");
			busy = false;
		},
		error	:	function(XMLHttpRequest, textStatus, errorThrown) {
            global_message_show('danger', "Invalid server response: " + textStatus);
			jQuery(_object).find("i").attr("class", "fab fa-facebook-f");
			busy = false;
		}
	});
	return false;
}
function vk_disconnect(_object) {
	if (busy) return;
	busy = true;
	jQuery(_object).find("i").attr("class", "fas fa-spin fa-spinner");
	var post_data = {
		"action"		    : "vk-disconnect",
		"hostname"		    : window.location.hostname
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
                jQuery(_object).closest(".input-box").html(data.html);
                global_message_show('success', data.message);
			} else if (data.status == "ERROR") {
                global_message_show('danger', data.message);
			} else if (data.status == "WARNING") {
                global_message_show('danger', data.message);
			} else {
                global_message_show('danger', "Internal Error.");
			}
			jQuery(_object).find("i").attr("class", "fab fa-vk");
			busy = false;
		},
		error	:	function(XMLHttpRequest, textStatus, errorThrown) {
            global_message_show('danger', "Invalid server response: " + textStatus);
			jQuery(_object).find("i").attr("class", "fab fa-vk");
			busy = false;
		}
	});
	return false;
}

var file_uploading = false;
function image_uploader_select(_object) {
	upload_select(_object, function(){
		var selected_element = jQuery(".upload-container").find(".upload-element-selected");
		if (selected_element) {
			jQuery(_object).parent().find(".image-uploader-preview img").attr("src", jQuery(selected_element).attr("data-thumbnail"));
			jQuery(_object).parent().find(".image-uploader-preview span").fadeIn(200);
			jQuery(_object).parent().find(".image-uploader-preview").slideDown(200);
			jQuery(_object).parent().parent().find("input[name='"+jQuery(_object).parent().attr("data-name")+"']").val(jQuery(selected_element).attr("data-id"));
		}
	});
	return false;
}
function image_uploader_delete(_object) {
	var default_image = jQuery(_object).parent().find("img").attr("data-default");
	var input_name = jQuery(_object).parent().parent().attr("data-name");
	jQuery(_object).fadeOut(200);
	jQuery(_object).parent().parent().parent().find("input[name='"+input_name+"']").val("");
	if (default_image == "") {
		jQuery(_object).parent().slideUp(200);
	} else {
		jQuery(_object).parent().find("img").attr("src", default_image);
	}
}

function memberships_expand_prices(_object) {
	jQuery(_object).hide();
	var panel = jQuery(_object).closest(".membership-panel");
	jQuery(panel).find(".membership-panel-footer-standard-label").hide();
	jQuery(panel).find(".membership-panel-footer-prices-label").show();
	jQuery(panel).find(".membership-panel-footer-prices").slideDown(200);
	return false;
}
/* Dialog Popup - begin */
var dialog_buttons_disable = false;
function dialog_open(_settings) {
	var settings = {
		width: 				480,
		height:				210,
		title:				esc_html__('Confirm action'),
		close_enable:		true,
		ok_enable:			true,
		cancel_enable:		true,
		ok_disabled:		false,
		cancel_disabled:	false,
		ok_label:			esc_html__('Yes'),
		cancel_label:		esc_html__('Cancel'),
		echo_html:			function() {this.html(esc_html__('Do you really want to continue?')); this.show();},
		ok_function:		function() {if (!jQuery("#dialog .dialog-button-ok").hasClass("dialog-button-disabled")) dialog_close();},
		cancel_function:	function() {if (!jQuery("#dialog .dialog-button-cancel").hasClass("dialog-button-disabled")) dialog_close();},
		html:				function(_html) {jQuery("#dialog .dialog-content-html").html(_html);},
		show:				function() {jQuery("#dialog .dialog-loading").fadeOut(100);},
		custom_button:		function(_button_html) {
			jQuery("#dialog").removeClass("dialog-no-buttons");
			jQuery("#dialog .dialog-buttons").prepend(_button_html);
		},
	}
	var objects = [settings, _settings],
    settings = objects.reduce(function (r, o) {
		Object.keys(o).forEach(function (k) {
			r[k] = o[k];
		});
		return r;
    }, {});
	
	dialog_buttons_disable = false;
	jQuery("#dialog .dialog-loading").show();
	jQuery("#dialog .dialog-title h3 label").html(settings.title);
	if (settings.close_enable) jQuery("#dialog .dialog-title a").show();
	else jQuery("#dialog .dislog-title a").hide();
	
	settings.echo_html();
	var window_height = Math.min(2*parseInt((jQuery(window).height() - 100)/2, 10), settings.height);
	var window_width = Math.min(Math.min(Math.max(2*parseInt((jQuery(window).width() - 300)/2, 10), 880), 960), settings.width);
	jQuery("#dialog").height(window_height);
	jQuery("#dialog").width(window_width);
	
	jQuery("#dialog .dialog-button").off("click");
	jQuery("#dialog .dialog-button").removeClass("dialog-button-disabled");

	if (settings.ok_enable || settings.cancel_enable) jQuery("#dialog").removeClass("dialog-no-buttons");
	else jQuery("#dialog").addClass("dialog-no-buttons");

	if (settings.ok_enable) {
		jQuery("#dialog .dialog-button-ok").find("span").html(settings.ok_label);
		if (settings.ok_disabled) jQuery("#dialog .dialog-button-ok").addClass("dialog-button-disabled");
		else jQuery("#dialog .dialog-button-ok").removeClass("dialog-button-disabled");
		jQuery("#dialog .dialog-button-ok").on("click", function(e){
			e.preventDefault();
			if (!dialog_buttons_disable && typeof settings.ok_function == "function") {
				settings.ok_function();
			}
		});
		jQuery("#dialog .dialog-button-ok").show();
	} else jQuery("#dialog .dialog-button-ok").hide();
	
	if (settings.cancel_enable) {
		jQuery("#dialog .dialog-button-cancel").find("span").html(settings.cancel_label);
		if (settings.cancel_disabled) jQuery("#dialog .dialog-button-cancel").addClass("dialog-button-disabled");
		else jQuery("#dialog .dialog-button-cancel").removeClass("dialog-button-disabled");
		jQuery("#dialog .dialog-button-cancel").on("click", function(e){
			e.preventDefault();
			if (!dialog_buttons_disable && typeof settings.cancel_function == "function") {
				settings.cancel_function();
			}
		});
		jQuery("#dialog .dialog-button-cancel").show();
	} else jQuery("#dialog .dialog-button-cancel").hide();
	
	jQuery("#dialog .dialog-button-custom").remove();

	jQuery("#dialog-overlay").fadeIn(300);
	jQuery("#dialog").css({
		'top': 					'50%',
		'transform': 			'translate(-50%, -50%) scale(1)',
		'-webkit-transform': 	'translate(-50%, -50%) scale(1)'
	});
	return settings;
}
function dialog_close() {
	jQuery("#dialog-overlay").fadeOut(300);
	jQuery("#dialog").css({
		'transform': 			'translate(-50%, -50%) scale(0)',
		'-webkit-transform': 	'translate(-50%, -50%) scale(0)'
	});
	setTimeout(function(){jQuery("#dialog").css("top", "-3000px")}, 300);
	return false;
}
/* Dialog Popup - end */

function dialog_remove_account_close() {
	jQuery("#dialog-remove-account-overlay").fadeOut(300);
	jQuery("#dialog-remove-account").css({
		'transform': 			'translate(-50%, -50%) scale(0)',
		'-webkit-transform': 	'translate(-50%, -50%) scale(0)'
	});
	setTimeout(function(){jQuery("#dialog-remove-account").css("top", "-3000px")}, 300);
	return false;
}
function dialog_remove_account_open() {
	jQuery("#dialog-remove-account").find("input[name='email']").val("");
	jQuery("#dialog-remove-account-overlay").fadeIn(300);
	jQuery("#dialog-remove-account").css({
		'top': 					'50%',
		'transform': 			'translate(-50%, -50%) scale(1)',
		'-webkit-transform': 	'translate(-50%, -50%) scale(1)'
	});
	return false;
}

function account_delete(_object) {
	if (busy) return;
	busy = true;
	jQuery(_object).find("i").attr("class", "fas fa-spin fa-spinner");
	jQuery.ajax({
		url		:	ajax_url, 
		data	:	jQuery("#dialog-remove-account").find("input, textarea, select").serialize(),
		method	:	"post",
		dataType:	"json",
		async	:	true,
		success	:	function(return_data) {
			var data;
			if (typeof return_data == 'object') data = return_data;
			else data = jQuery.parseJSON(return_data);
			if (data.status == "OK") {
				if (data.hasOwnProperty('message')) {
                	global_message_show('success', data.message);
				}
				if (data.hasOwnProperty('url')) {
					location.href = data.url;
				}
			} else if (data.status == "ERROR" || data.status == "WARNING") {
                global_message_show('danger', data.message);
			} else {
                global_message_show('danger', "Internal Error.");
			}
			dialog_remove_account_close();
			jQuery(_object).find("i").attr("class", "far fa-trash-alt");
			busy = false;
		},
		error	:	function(XMLHttpRequest, textStatus, errorThrown) {
            global_message_show('danger', "Invalid server response: " + textStatus);
			jQuery(_object).find("i").attr("class", "far fa-trash-alt");
			busy = false;
		}
	});
	return false;
}
function upload_delete(_object) {
	dialog_open({
		echo_html:		function() {
			this.html("<div class='dialog-message'>"+esc_html__("Please confirm that you want to delete the file.", "fb")+"</div>");
			this.show();
		},
		ok_label:		'Delete',
		ok_function:	function(e){
			_upload_delete(_object);
			dialog_close();
		}
	});
	return false;
}
function _upload_delete(_object) {
	if (busy) return false;
	busy = true;
	var upload_id = jQuery(_object).attr("data-id");
	jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i>");
	var post_data = {"action" : "upload-delete", "upload-id" : upload_id};
	jQuery.ajax({
		url		:	ajax_url, 
		data	: 	post_data,
		method	:	"post",
		dataType:	"json",
		async	:	true,
		success	: function(return_data) {
			try {
				var data;
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				var table = jQuery(_object).closest("table");
				if (data.status == "OK") {
					jQuery(_object).closest(".upload-element").fadeOut(300, function(){
						jQuery(_object).closest(".upload-element").remove();
					});
					global_message_show("success", data.message);
				} else if (data.status == "ERROR" || data.status == "WARNING") {
					global_message_show("danger", data.message);
				} else {
					global_message_show("danger", esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch(error) {
				global_message_show("danger", esc_html__("Something went wrong. We got unexpected server response."));
			}
			jQuery(_object).html("<i class='far fa-trash-alt'></i>");
			busy = false;
		},
		error	: function(XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_object).html("<i class='far fa-trash-alt'></i>");
			global_message_show("danger", esc_html__("Something went wrong. We got unexpected server response."));
			busy = false;
		}
	});
	return false;
}
function uploads_bulk_delete(_object) {
	dialog_open({
		echo_html:		function() {
			this.html("<div class='dialog-message'>"+esc_html__("Please confirm that you want to delete selected files.", "fb")+"</div>");
			this.show();
		},
		ok_label:		'Delete',
		ok_function:	function(e){
			uploads_bulk_action(_object);
			dialog_close();
		}
	});
	return false;
}
function uploads_bulk_action(_object) {
	if (busy) return false;
	busy = true;
	var doing_label = jQuery(_object).attr("data-doing");
	var do_label = jQuery(_object).html();
	jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i> "+doing_label);
	jQuery.ajax({
		url		:	ajax_url, 
		data	: 	jQuery(".upload-container").find("input").serialize()+"&action=uploads-"+jQuery(_object).attr("data-action"),
		method	:	"post",
		dataType:	"json",
		async	:	true,
		success	: function(return_data) {
			try {
				var data;
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				var table = jQuery(_object).closest("table");
				if (data.status == "OK") {
					global_message_show("success", data.message);
					location.reload();
				} else if (data.status == "ERROR" || data.status == "WARNING") {
					global_message_show("danger", data.message);
				} else {
					global_message_show("danger", esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch(error) {
				global_message_show("danger", esc_html__("Something went wrong. We got unexpected server response."));
			}
			jQuery(_object).html(do_label);
			busy = false;
		},
		error	: function(XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_object).html(do_label);
			global_message_show("danger", esc_html__("Something went wrong. We got unexpected server response."));
			busy = false;
		}
	});
	return false;
}
function upload_start(_object) {
	jQuery("#upload-iframe").attr("data-loading", "true");
	jQuery(".upload-element-template").clone().prependTo(".upload-container");
	jQuery(".upload-container .upload-element-template").first().removeClass("upload-element-template").addClass("upload-element-uploading");
	file_uploading = true;
}
function upload_finish(_object) {
	if (jQuery(_object).attr("data-loading") != "true") return false;
	jQuery(_object).attr("data-loading", "false");
	file_uploading = false;
	var return_data = jQuery(_object).contents().find("html").text();
	try {
		var data;
		if (typeof return_data == 'object') data = return_data;
		else data = jQuery.parseJSON(return_data);
		if (data.status == "OK") {
			jQuery(".upload-element-uploading").find(".upload-element-image img").attr("src", data.thumbnail);
			jQuery(".upload-element-uploading").attr("data-url", data.url);
			jQuery(".upload-element-uploading").attr("data-thumbnail", data.thumbnail);
			jQuery(".upload-element-uploading").attr("data-id", data.file_uid);
			jQuery(".upload-element-uploading a.upload-preview").attr("href", data.url);
			jQuery(".upload-element-uploading").find("input[type='checkbox']").attr("id", "upload-"+data.file_uid).val(data.file_uid);
			jQuery(".upload-element-uploading").find("input[type='checkbox']").next().attr("for", "upload-"+data.file_uid);
			jQuery(".upload-element-uploading").find(".upload-element-actions a").attr("data-id", data.file_uid);
			jQuery(".upload-element-uploading").removeClass("upload-element-uploading");
			global_message_show("success", data.message);
		} else if (data.status == "ERROR") {
			jQuery(".upload-element-uploading").remove();
			global_message_show("danger", data.message);
		} else {
			jQuery(".upload-element-uploading").remove();
			global_message_show("danger", esc_html__("Something went wrong. We got unexpected server response."));
		}
		jQuery(".upload-form").find("input[type='file']").val("");
	} catch(error) {
		jQuery(".upload-element-uploading").remove();
		global_message_show("danger", esc_html__("Something went wrong. We got unexpected server response."));
	}
}
function upload_preview(_object) {
	dialog_open({
		echo_html:		function() {
			this.html("<div class='upload-preview-container'><div class='upload-preview-image'><img src='"+jQuery(_object).attr("href")+"' alt=''></div><div class='upload-preview-info'><div class='upload-preview-link-container'><input readonly='readonly' type='text' value='"+jQuery(_object).attr("href")+"' onclick='this.focus();this.select();'><span title='Copy to clipboard' onclick='jQuery(this).parent().find(\"input\").select();document.execCommand(\"copy\");'><i class='far fa-copy'></i></span></div></div></div>");
			this.show();
		},
		width:			1200,
		height:			1200,
		title:			'Preview',
		ok_enable:		false,
		cancel_enable:	false
	});
	return false;
}
function upload_select(_object, _selected_handler = null) {
	dialog_open({
		echo_html:		function() {
			var dialog = this;
			var transaction_id = jQuery(_object).attr("data-id");
			var post_data = {"action" : "upload-select"};
			jQuery.ajax({
				url		:	ajax_url, 
				data	: 	post_data,
				method	:	"post",
				dataType:	"json",
				async	:	true,
				success	: function(return_data) {
					try {
						var data;
						if (typeof return_data == 'object') data = return_data;
						else data = jQuery.parseJSON(return_data);
						if (data.status == "OK") {
							dialog.custom_button(data.button_html);
							dialog.html(data.html);
							dialog.show();
						} else if (data.status == "ERROR" || data.status == "WARNING") {
							dialog_close();
							global_message_show("danger", data.message);
						} else {
							dialog_close();
							global_message_show("danger", esc_html__("Something went wrong. We got unexpected server response."));
						}
					} catch(error) {
						dialog_close();
						global_message_show("danger", esc_html__("Something went wrong. We got unexpected server response."));
					}
				},
				error	: function(XMLHttpRequest, textStatus, errorThrown) {
					dialog_close();
					global_message_show("danger", esc_html__("Something went wrong. We got unexpected server response."));
					busy = false;
				}
			});
		},
		width:			1200,
		height:			1200,
		title:			'Select Image',
		ok_enable:		true,
		ok_disabled:	true,
		ok_label:		'Select',
		cancel_enable:	false,
		ok_function:	function() {
			if (!jQuery("#dialog .dialog-button-ok").hasClass("dialog-button-disabled")) {
				if (_selected_handler) _selected_handler();
				dialog_close();
			}
		}
	});
	return false;
}
function upload_selected(_object) {
	jQuery(_object).closest(".upload-container").find(".upload-element-selected").removeClass("upload-element-selected");
	jQuery(_object).closest(".upload-element").addClass("upload-element-selected");
	jQuery("#dialog .dialog-button-ok").removeClass("dialog-button-disabled");
	return false;
}

function esc_html__(_string) {
	var string;
	if (typeof translations == typeof {} && translations.hasOwnProperty(_string)) {
		string = translations[_string];
		if (string.length == 0) string = _string;
	} else string = _string;
	return escape_html(string);
}
function escape_html(_text) {
	if (typeof _text != typeof "string") return _text;
	if (!_text) return "";
	var map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;'
	};
	return _text.replace(/[&<>"']/g, function(m) { return map[m]; });
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
function _date(_date, _format) {
	var pattern = _format.replace('yyyy', '([0-9]{4})').replace('mm', '([0-9]{2})').replace('dd', '([0-9]{2})');
	var match = _date.match(pattern);
	if (!match || _format.length != _date.length) return null;
	var year = parseInt(_date.substr(_format.indexOf('yyyy'), 4), 10);
	var month = parseInt(_date.substr(_format.indexOf('mm'), 2), 10);
	var day = parseInt(_date.substr(_format.indexOf('dd'), 2), 10);
	var date = new Date(year, month-1, day);
	if (date.getDate() == day && date.getMonth() == month-1 && date.getFullYear() == year) return date;
	return null;
}
jQuery(document).ready(function(){
	jQuery(".errorable").on("focus", function(){
		jQuery(this).parent().find('.element-error').fadeOut(300, function(){jQuery(this).remove();});		
	});
	jQuery(".form-tooltip-anchor").tooltipster({
		contentAsHTML:	true,
		maxWidth:		360,
		theme:			"tooltipster-dark",
		side:			"bottom",
		content:		"Default",
		functionFormat: function(instance, helper, content){
			return jQuery(helper.origin).parent().find('.form-tooltip-content').html();
		}
	});
	jQuery(".tooltipster").tooltipster({
		maxWidth:		360,
		theme:			"tooltipster-light",
		side:			"bottom"
	});
	jQuery("input.date").each(function(){
		var default_date = new Date();
		if (jQuery(this).attr("data-default")) default_date = new Date(jQuery(this).attr("data-default"));
		jQuery(this).airdatepicker({
			autoClose		: true,
			timepicker		: false,
			dateFormat		: date_format,
			language		: language,
			onShow			: function(inst, animationCompleted) {
				var content;
				var min_type = jQuery(inst.el).attr("data-min-type");
				var min_value = jQuery(inst.el).attr("data-min-value");
				var min_date = null;
				switch(min_type) {
					case 'today':
						min_date = new Date();
						break;
					case 'yesterday':
						min_date = new Date();
						min_date.setDate(min_date.getDate() - 1);
						break;
					case 'tomorrow':
						min_date = new Date();
						min_date.setDate(min_date.getDate() + 1);
						break;
					case 'offset':
						min_date = new Date();
						min_date.setDate(min_date.getDate() + parseInt(min_value, 10));
						break;
					case 'date':
						min_date = lepopup_date(min_value, date_format);
						break;
					case 'field':
						content = jQuery(inst.el).closest(".form");
						if (jQuery(content).find("input[name='"+min_value+"']").length > 0) min_date = _date(jQuery(content).find("input[name='"+min_value+"']").val(), date_format);
						break;
					default:
						break;
				}
				if (min_date != null) inst.update({'minDate' : min_date});
				var max_type = jQuery(inst.el).attr("data-max-type");
				var max_value = jQuery(inst.el).attr("data-max-value");
				var max_date = null;
				switch(max_type) {
					case 'today':
						max_date = new Date();
						break;
					case 'yesterday':
						max_date = new Date();
						max_date.setDate(max_date.getDate() - 1);
						break;
					case 'tomorrow':
						max_date = new Date();
						max_date.setDate(max_date.getDate() + 1);
						break;
					case 'offset':
						max_date = new Date();
						max_date.setDate(max_date.getDate() + parseInt(max_value, 10));
						break;
					case 'date':
						max_date = lepopup_date(max_value, date_format);
						break;
					case 'field':
						content = jQuery(inst.el).closest(".form");
						if (jQuery(content).find("input[name='"+max_value+"']").length > 0) max_date = _date(jQuery(content).find("input[name='"+max_value+"']").val(), date_format);
						break;
					default:
						break;
				}
				if (max_date != null) inst.update({'maxDate' : max_date});
			}
		});
		jQuery(this).airdatepicker().data('airdatepicker').selectDate(default_date);
	});
	jQuery("input.datetime").each(function(){
		var default_date = new Date();
		if (jQuery(this).attr("data-default")) default_date = new Date(jQuery(this).attr("data-default"));
		jQuery("input.datetime").airdatepicker({
			inline_popup	: true,
			autoClose		: true,
			timepicker		: true,
			dateFormat		: date_format,
			language		: language,
			timeFormat		: 'hh:ii'
		});
		jQuery(this).airdatepicker().data('airdatepicker').selectDate(default_date);
	});
	jQuery(".tabs a").on("click", function(e){
		e.preventDefault();
		if (jQuery(this).hasClass("tab-active")) return;
		var tab_set = jQuery(this).parent();
		var active_tab = jQuery(tab_set).find(".tab-active").attr("href");
		jQuery(tab_set).find(".tab-active").removeClass("tab-active");
		var tab = jQuery(this).attr("href");
		jQuery(this).addClass("tab-active");
		jQuery(active_tab).fadeOut(300, function(){
			jQuery(tab).fadeIn(300);
		});
	});
});