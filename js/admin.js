"use strict";
function toggle_mail_method(_object) {
	if (jQuery(_object).val() == "smtp") {
		jQuery("#mail-method-mail-content").fadeOut(250, function() {
			jQuery("#mail-method-smtp-content").fadeIn(250);
		});
	} else {
		jQuery("#mail-method-smtp-content").fadeOut(250, function() {
			jQuery("#mail-method-mail-content").fadeIn(250);
		});
	}
}
function test_mailing(_object) {
	if (busy) return;
	busy = true;
	jQuery(_object).find("i").attr("class", "fas fa-spin fa-spinner");
	jQuery("#test-mailing-message").slideUp(200);
	var form = jQuery(_object).closest(".sender-details");
	jQuery(form).find(".element-error").fadeOut(300, function(){
		jQuery(this).remove();
	});
	jQuery.ajax({
		url		:	ajax_url, 
		data	:	jQuery(".sender-details").find("input, textarea, select").serialize()+"&action=test-mailing&_token="+jQuery("input[name='_token']").val(),
		method	:	"post",
		//dataType:	"json",
		async	:	true,
		success	:	function(return_data) {
			var data;
			if (typeof return_data == 'object') data = return_data;
			else {
				var temp = /<fb-debug>(.*?)<\/fb-debug>/g.exec(return_data);
				if (temp) return_data = temp[1];
                data = jQuery.parseJSON(return_data);
            }
			if (data.status == "OK") {
                global_message_show('success', data.message);
			} else if (data.status == "ERROR") {
                jQuery("#test-mailing-message").html(data.message);
                jQuery("#test-mailing-message").slideDown(200);
			} else if (data.status == "WARNING") {
                global_message_show('danger', data.message);
			} else {
                global_message_show('danger', "Internal Error.");
			}
			jQuery(_object).find("i").attr("class", "far fa-envelope");
			busy = false;
		},
		error	:	function(XMLHttpRequest, textStatus, errorThrown) {
            global_message_show('danger', "Invalid server response: " + textStatus);
			jQuery(_object).find("i").attr("class", "far fa-envelope");
			busy = false;
		}
	});
	return false;
}
function user_delete(_object) {
	dialog_open({
		echo_html:		function() {
			this.html("<div class='dialog-message'>"+esc_html__("Please confirm that you want to delete the user.", "fb")+"</div>");
			this.show();
		},
		ok_label:		'Delete',
		ok_function:	function(e){
			_user_delete(_object);
			dialog_close();
		}
	});
	return false;
}
function _user_delete(_object) {
	if (busy) return false;
	busy = true;
	var user_id = jQuery(_object).attr("data-id");
	var doing_label = jQuery(_object).attr("data-doing");
	var do_label = jQuery(_object).html();
	jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i> "+doing_label);
	var post_data = {"action" : "user-delete", "user-id" : user_id, "_token" : jQuery("input[name='_token']").val()};
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
					jQuery(_object).closest("tr").fadeOut(300, function(){
						jQuery(_object).closest("tr").remove();
						if (jQuery(table).find("tr").length <= 2) {
							jQuery(table).find(".table-empty").fadeIn(300);
						}
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
function user_status_toggle(_object) {
	if (busy) return false;
	busy = true;
	var user_id = jQuery(_object).attr("data-id");
	var user_status = jQuery(_object).attr("data-status");
	var doing_label = jQuery(_object).attr("data-doing");
	var do_label = jQuery(_object).html();
	jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i> "+doing_label);
	var post_data = {"action" : "user-status-toggle", "user-id" : user_id, "status" : user_status, "_token" : jQuery("input[name='_token']").val()};
	jQuery.ajax({
		url		:	ajax_url, 
		data	: 	post_data,
		method	:	"post",
		dataType:	"json",
		async	:	true,
		success	: function(return_data) {
			jQuery(_object).html(do_label);
			try {
				var data;
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					jQuery(_object).html(data.user_action);
					jQuery(_object).attr("data-status", data.user_status);
					jQuery(_object).attr("data-doing", data.user_action_doing);
					if (data.user_status == "active") jQuery(_object).closest("tr").find(".table-badge-status").html("");
					else jQuery(_object).closest("tr").find(".table-badge-status").html("<span class='badge badge-danger'>"+data.user_status_label+"</span>");
					global_message_show("success", data.message);
				} else if (data.status == "ERROR" || data.status == "WARNING") {
					jQuery(_object).html(do_label);
					global_message_show("danger", data.message);
				} else {
					jQuery(_object).html(do_label);
					global_message_show("danger", esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch(error) {
				jQuery(_object).html(do_label);
				global_message_show("danger", esc_html__("Something went wrong. We got unexpected server response."));
			}
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
function users_bulk_delete(_object) {
	dialog_open({
		echo_html:		function() {
			this.html("<div class='dialog-message'>"+esc_html__("Please confirm that you want to delete users.", "fb")+"</div>");
			this.show();
		},
		ok_label:		'Delete',
		ok_function:	function(e){
			users_bulk_action(_object);
			dialog_close();
		}
	});
	return false;
}
function users_bulk_action(_object) {
	if (busy) return false;
	busy = true;
	var doing_label = jQuery(_object).attr("data-doing");
	var do_label = jQuery(_object).html();
	jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i> "+doing_label);
	jQuery.ajax({
		url		:	ajax_url, 
		data	: 	jQuery(".table").find("input").serialize()+"&action=users-"+jQuery(_object).attr("data-action")+"&_token="+jQuery("input[name='_token']").val(),
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
function session_delete(_object) {
	dialog_open({
		echo_html:		function() {
			this.html("<div class='dialog-message'>"+esc_html__("Please confirm that you want to close this session.", "fb")+"</div>");
			this.show();
		},
		ok_label:		'Close',
		ok_function:	function(e){
			_session_delete(_object);
			dialog_close();
		}
	});
	return false;
}
function _session_delete(_object) {
	if (busy) return false;
	busy = true;
	var session_id = jQuery(_object).attr("data-id");
	var doing_label = jQuery(_object).attr("data-doing");
	var do_label = jQuery(_object).html();
	jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i> "+doing_label);
	var post_data = {"action" : "session-delete", "session-id" : session_id, "_token" : jQuery("input[name='_token']").val()};
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
					jQuery(_object).closest("tr").fadeOut(300, function(){
						jQuery(_object).closest("tr").remove();
						if (jQuery(table).find("tr").length <= 2) {
							jQuery(table).find(".table-empty").fadeIn(300);
						}
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
function sessions_bulk_delete(_object) {
	dialog_open({
		echo_html:		function() {
			this.html("<div class='dialog-message'>"+esc_html__("Please confirm that you want to close selected sessions.", "fb")+"</div>");
			this.show();
		},
		ok_label:		'Close',
		ok_function:	function(e){
			_sessions_bulk_delete(_object);
			dialog_close();
		}
	});
	return false;
}
function _sessions_bulk_delete(_object) {
	if (busy) return false;
	busy = true;
	var doing_label = jQuery(_object).attr("data-doing");
	var do_label = jQuery(_object).html();
	jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i> "+doing_label);
	jQuery.ajax({
		url		:	ajax_url, 
		data	: 	jQuery(".table").find("input").serialize()+"&action=sessions-delete&_token="+jQuery("input[name='_token']").val(),
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
function membership_feature_add(_object) {
	jQuery(_object).prev().find(".membership-feature-template").clone().appendTo(jQuery(_object).prev());
	var i = 0;
	jQuery(".membership-feature-template").first().removeClass("membership-feature-template").find("input[type='radio']").each(function(){
		var id = "bullet-"+jQuery(".membership-feature").length+"-"+i;
		jQuery(this).attr("id", id);
		jQuery(this).next().attr("for", id);
		i++;
	});
	return false;
}
function membership_feature_delete(_object) {
	dialog_open({
		echo_html:		function() {
			this.html("<div class='dialog-message'>"+esc_html__("Please confirm that you want to delete this feature.", "fb")+"</div>");
			this.show();
		},
		ok_label:		'Delete',
		ok_function:	function(e){
			jQuery(_object).closest(".membership-feature").remove();
			dialog_close();
		}
	});
	return false;
}
function membership_price_add(_object) {
	jQuery(_object).prev().find(".membership-price-template").clone().appendTo(jQuery(_object).prev());
	jQuery(".membership-price-template").first().removeClass(("membership-price-template"));
	return false;
}
function membership_price_delete(_object) {
	dialog_open({
		echo_html:		function() {
			this.html("<div class='dialog-message'>"+esc_html__("Please confirm that you want to delete price option.", "fb")+"</div>");
			this.show();
		},
		ok_label:		'Delete',
		ok_function:	function(e){
			jQuery(_object).closest(".membership-price").remove();
			dialog_close();
		}
	});
	return false;
}
function membership_price_archive(_object) {
	dialog_open({
		echo_html:		function() {
			this.html("<div class='dialog-message'>"+esc_html__("Please confirm that you want to archive price option.", "fb")+"</div>");
			this.show();
		},
		ok_label:		'Archive',
		ok_function:	function(e){
			var item = jQuery(_object).closest(".membership-price");
			jQuery(item).removeClass("membership-price-active").addClass("membership-price-archive");
			jQuery(item).find("input[name='status']").val("archive");
			jQuery(item).find(".membership-status span").text(jQuery(item).attr("data-status-label-archive"));
			dialog_close();
		}
	});
	return false;
}
function membership_price_activate(_object) {
	dialog_open({
		echo_html:		function() {
			this.html("<div class='dialog-message'>"+esc_html__("Please confirm that you want to activate price option.", "fb")+"</div>");
			this.show();
		},
		ok_label:		'Activate',
		ok_function:	function(e){
			var item = jQuery(_object).closest(".membership-price");
			jQuery(item).removeClass("membership-price-archive").addClass("membership-price-active");
			jQuery(item).find("input[name='status']").val("active");
			jQuery(item).find(".membership-status span").text(jQuery(item).attr("data-status-label-active"));
			dialog_close();
		}
	});
	return false;
}
function membership_save(_object) {
	if (busy) return;
	busy = true;
	jQuery(_object).find("i").attr("class", "fas fa-spin fa-spinner");
	var form = jQuery(_object).closest(".form");
	jQuery(form).find(".inline-error").slideUp(200);
	jQuery(form).find(".element-error").fadeOut(300, function(){
		jQuery(this).remove();
	});
	var post_data = {
		"action" 		: "membership-save", 
		"id" 			: jQuery(form).find("input[name='id']").val(),
		"color" 		: jQuery(form).find("input[name='color']:checked").val(),
		"title" 		: {}, 
		"description" 	: {}, 
		"footer" 		: {}, 
		"price-options" : new Array(),
		"features"		: new Array(),
		"options"		: {},
		"_token" 		: jQuery("input[name='_token']").val()
	};
	var i = 0;
	jQuery(".membership-price").each(function() {
		if (jQuery(this).hasClass("membership-price-template")) return true;
		var price_option = {
			"id" 				: jQuery(this).attr("data-id"),
			"dom-id"			: "o-"+i,
			"price" 			: jQuery(this).find("input[name='price']").val(),
			"currency" 			: jQuery(this).find("select[name='currency']").val(),
			"billing-period" 	: jQuery(this).find("select[name='billing-period']").val(),
			"status" 			: jQuery(this).find("input[name='status']").val()
		}
		var price_title = {};
		jQuery(this).find("input[name^='price-title']").each(function() {
			price_title[jQuery(this).attr("name").match(/\[([^)]+)\]/)[1]] = jQuery(this).val();
		});
		price_option['title'] = price_title;
		post_data["price-options"].push(price_option);
		jQuery(this).attr("data-dom-id", "o-"+i);
		i++;
	});
	var i = 0;
	jQuery(".membership-feature").each(function() {
		if (jQuery(this).hasClass("membership-feature-template")) return true;
		var feature = {
			"dom-id"			: "f-"+i,
			"bullet"			: jQuery(this).find("input[name='bullet']:checked").val()
		}
		var feature_label = {};
		jQuery(this).find("input[name^='feature-label']").each(function() {
			feature_label[jQuery(this).attr("name").match(/\[([^)]+)\]/)[1]] = jQuery(this).val();
		});
		feature['label'] = feature_label;
		post_data["features"].push(feature);
		jQuery(this).attr("data-dom-id", "f-"+i);
		i++;
	});
	jQuery(form).find("input[name^='title']").each(function() {
		post_data["title"][jQuery(this).attr("name").match(/\[([^)]+)\]/)[1]] = jQuery(this).val();
	});
	jQuery(form).find("textarea[name^='description']").each(function() {	
		post_data["description"][jQuery(this).attr("name").match(/\[([^)]+)\]/)[1]] = jQuery(this).val();
	});
	jQuery(form).find("input[name^='footer']").each(function() {
		post_data["footer"][jQuery(this).attr("name").match(/\[([^)]+)\]/)[1]] = jQuery(this).val();
	});
	jQuery(".membership-options").find("input[type='text'], input[type='checkbox']:checked, input[type='radio']:checked, select, textarea").each(function(){
		var name = jQuery(this).attr("name");
		if (name) post_data["options"][name] = jQuery(this).val();
	});

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
				if (data.hasOwnProperty('message')) {
                	global_message_show('success', data.message);
				}
				if (data.hasOwnProperty('url')) {
					location.href = data.url;
				}
			} else if (data.status == "ERROR") {
				for (var id in data["errors"]) {
					if (data["errors"].hasOwnProperty(id)) {
						if (typeof data["errors"][id] == typeof {}) {
							for (var sub_id in data["errors"][id]) {
								if (data["errors"][id].hasOwnProperty(sub_id)) {
									jQuery(form).find("[data-dom-id='"+id+"']").find("[name='"+sub_id+"']").after("<div class='element-error'><span>"+data["errors"][id][sub_id]+"</span></div>");
								}
							}
						} else {
							jQuery(form).find("[name='"+id+"']").after("<div class='element-error'><span>"+data["errors"][id]+"</span></div>");
						}
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
function membership_free_save(_object) {
	if (busy) return;
	busy = true;
	jQuery(_object).find("i").attr("class", "fas fa-spin fa-spinner");
	var form = jQuery(_object).closest(".form");
	jQuery(form).find(".inline-error").slideUp(200);
	jQuery(form).find(".element-error").fadeOut(300, function(){
		jQuery(this).remove();
	});
	var post_data = {
		"action" 		: "membership-free-save", 
		"title" 		: {}, 
		"description" 	: {}, 
		"footer" 		: {}, 
		"features"		: new Array(),
		"options"		: {},
		"_token" 		: jQuery("input[name='_token']").val()
	};
	var i = 0;
	jQuery(".membership-feature").each(function() {
		if (jQuery(this).hasClass("membership-feature-template")) return true;
		var feature = {
			"dom-id"			: "f-"+i,
			"bullet"			: jQuery(this).find("input[name='bullet']:checked").val()
		}
		var feature_label = {};
		jQuery(this).find("input[name^='feature-label']").each(function() {
			feature_label[jQuery(this).attr("name").match(/\[([^)]+)\]/)[1]] = jQuery(this).val();
		});
		feature['label'] = feature_label;
		post_data["features"].push(feature);
		jQuery(this).attr("data-dom-id", "f-"+i);
		i++;
	});
	jQuery(form).find("input[name^='title']").each(function() {
		post_data["title"][jQuery(this).attr("name").match(/\[([^)]+)\]/)[1]] = jQuery(this).val();
	});
	jQuery(form).find("textarea[name^='description']").each(function() {	
		post_data["description"][jQuery(this).attr("name").match(/\[([^)]+)\]/)[1]] = jQuery(this).val();
	});
	jQuery(form).find("input[name^='footer']").each(function() {
		post_data["footer"][jQuery(this).attr("name").match(/\[([^)]+)\]/)[1]] = jQuery(this).val();
	});
	jQuery(".membership-options").find("input[type='text'], input[type='checkbox']:checked, input[type='radio']:checked, select, textarea").each(function(){
		var name = jQuery(this).attr("name");
		if (name) post_data["options"][name] = jQuery(this).val();
	});

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
				if (data.hasOwnProperty('message')) {
                	global_message_show('success', data.message);
				}
				if (data.hasOwnProperty('url')) {
					location.href = data.url;
				}
			} else if (data.status == "ERROR") {
				for (var id in data["errors"]) {
					if (data["errors"].hasOwnProperty(id)) {
						if (typeof data["errors"][id] == typeof {}) {
							for (var sub_id in data["errors"][id]) {
								if (data["errors"][id].hasOwnProperty(sub_id)) {
									jQuery(form).find("[data-dom-id='"+id+"']").find("[name='"+sub_id+"']").after("<div class='element-error'><span>"+data["errors"][id][sub_id]+"</span></div>");
								}
							}
						} else {
							jQuery(form).find("[name='"+id+"']").after("<div class='element-error'><span>"+data["errors"][id]+"</span></div>");
						}
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
function memberships_save_list() {
	global_message_show('info', "Saving...");
	var post_data = {
		"action" 		: "memberships-save-list", 
		"memberships"	: new Array(),
		"_token" 		: jQuery("input[name='_token']").val()
	};
	jQuery(".membership-panel").each(function() {
		post_data["memberships"].push(jQuery(this).attr("data-id"));
	});

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
			} else if (data.status == "ERROR" || data.status == "WARNING") {
                global_message_show('danger', data.message);
			} else {
                global_message_show('danger', "Internal Error.");
			}
		},
		error	:	function(XMLHttpRequest, textStatus, errorThrown) {
            global_message_show('danger', "Invalid server response: " + textStatus);
		}
	});
	return false;
}
function membership_delete(_object) {
	dialog_open({
		echo_html:		function() {
			this.html("<div class='dialog-message'>"+esc_html__("Please confirm that you want to delete membership.", "fb")+"</div>");
			this.show();
		},
		ok_label:		'Delete',
		ok_function:	function(e){
			_membership_delete(_object);
			dialog_close();
		}
	});
	return false;
}
function _membership_delete(_object) {
	if (busy) return false;
	busy = true;
	var membership_id = jQuery(_object).attr("data-id");
	jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i>");
	var post_data = {"action" : "membership-delete", "membership-id" : membership_id, "_token" : jQuery("input[name='_token']").val()};
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
					jQuery(_object).closest(".membership-panel").fadeOut(300, function(){
						jQuery(_object).closest(".membership-panel").remove();
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
function membership_archive(_object) {
	dialog_open({
		echo_html:		function() {
			this.html("<div class='dialog-message'>"+esc_html__("Please confirm that you want to archive membership.", "fb")+"</div>");
			this.show();
		},
		ok_label:		'Archive',
		ok_function:	function(e){
			_membership_archive(_object);
			dialog_close();
		}
	});
	return false;
}
function _membership_archive(_object) {
	if (busy) return false;
	busy = true;
	var membership_id = jQuery(_object).attr("data-id");
	jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i>");
	var post_data = {"action" : "membership-archive", "membership-id" : membership_id, "_token" : jQuery("input[name='_token']").val()};
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
					jQuery(_object).closest(".membership-panel").removeClass("membership-panel-active").addClass("membership-panel-archive");
					global_message_show("success", data.message);
				} else if (data.status == "ERROR" || data.status == "WARNING") {
					global_message_show("danger", data.message);
				} else {
					global_message_show("danger", esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch(error) {
				global_message_show("danger", esc_html__("Something went wrong. We got unexpected server response."));
			}
			jQuery(_object).html("<i class='fas fa-file-import'></i>");
			busy = false;
		},
		error	: function(XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_object).html("<i class='fas fa-file-import'></i>");
			global_message_show("danger", esc_html__("Something went wrong. We got unexpected server response."));
			busy = false;
		}
	});
	return false;
}
function membership_activate(_object) {
	dialog_open({
		echo_html:		function() {
			this.html("<div class='dialog-message'>"+esc_html__("Please confirm that you want to activate membership.", "fb")+"</div>");
			this.show();
		},
		ok_label:		'Activate',
		ok_function:	function(e){
			_membership_activate(_object);
			dialog_close();
		}
	});
	return false;
}
function _membership_activate(_object) {
	if (busy) return false;
	busy = true;
	var membership_id = jQuery(_object).attr("data-id");
	jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i>");
	var post_data = {"action" : "membership-activate", "membership-id" : membership_id, "_token" : jQuery("input[name='_token']").val()};
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
					jQuery(_object).closest(".membership-panel").removeClass("membership-panel-archive").addClass("membership-panel-active");
					global_message_show("success", data.message);
				} else if (data.status == "ERROR" || data.status == "WARNING") {
					global_message_show("danger", data.message);
				} else {
					global_message_show("danger", esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch(error) {
				global_message_show("danger", esc_html__("Something went wrong. We got unexpected server response."));
			}
			jQuery(_object).html("<i class='fas fa-file-export'></i>");
			busy = false;
		},
		error	: function(XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_object).html("<i class='fas fa-file-export'></i>");
			global_message_show("danger", esc_html__("Something went wrong. We got unexpected server response."));
			busy = false;
		}
	});
	return false;
}
function membership_expiration_handle(_object) {
	if (jQuery("select[name='membership']").val() == 0) {
		jQuery(".membership-never-expires").hide();
		jQuery(".membership-expires").hide();
	} else {
		jQuery(".membership-never-expires").show();
		if (jQuery("input[name='membership-never-expires']").is(":checked")) jQuery(".membership-expires").hide();
		else jQuery(".membership-expires").show();
	}
}
function transaction_delete(_object) {
	dialog_open({
		echo_html:		function() {
			this.html("<div class='dialog-message'>"+esc_html__("Please confirm that you want to delete this transaction.", "fb")+"</div>");
			this.show();
		},
		ok_label:		'Delete',
		ok_function:	function(e){
			_transaction_delete(_object);
			dialog_close();
		}
	});
	return false;
}
function _transaction_delete(_object) {
	if (busy) return false;
	busy = true;
	var transaction_id = jQuery(_object).attr("data-id");
	jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i>");
	var post_data = {"action" : "transaction-delete", "transaction-id" : transaction_id, "_token" : jQuery("input[name='_token']").val()};
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
					jQuery(_object).closest("tr").fadeOut(300, function(){
						jQuery(_object).closest("tr").remove();
						if (jQuery(table).find("tr").length <= 2) {
							jQuery(table).find(".table-empty").fadeIn(300);
						}
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
function transactions_bulk_delete(_object) {
	dialog_open({
		echo_html:		function() {
			this.html("<div class='dialog-message'>"+esc_html__("Please confirm that you want to delete transactions.", "fb")+"</div>");
			this.show();
		},
		ok_label:		'Delete',
		ok_function:	function(e){
			transactions_bulk_action(_object);
			dialog_close();
		}
	});
	return false;
}
function transactions_bulk_action(_object) {
	if (busy) return false;
	busy = true;
	var doing_label = jQuery(_object).attr("data-doing");
	var do_label = jQuery(_object).html();
	jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i> "+doing_label);
	jQuery.ajax({
		url		:	ajax_url, 
		data	: 	jQuery(".table").find("input").serialize()+"&action=transactions-"+jQuery(_object).attr("data-action")+"&_token="+jQuery("input[name='_token']").val(),
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
function transaction_details(_object) {
	dialog_open({
		echo_html:		function() {
			var dialog = this;
			var transaction_id = jQuery(_object).attr("data-id");
			var post_data = {"action" : "transaction-details", "transaction-id" : transaction_id, "_token" : jQuery("input[name='_token']").val()};
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
		title:			'Transaction details',
		ok_enable:		false,
		cancel_enable:	false
	});
	return false;
}

jQuery(document).ready(function(){
	jQuery(".memberships-sortable").sortable({
		items: ".membership-panel:not(.membership-panel-free)",
		forcePlaceholderSize: true,
		dropOnEmpty: true,
		placeholder: "membership-panel-placeholder",
		stop: function(event, ui) {
			memberships_save_list();
		}
	});
	jQuery(".membership-panel").disableSelection();
	jQuery(".membership-features").sortable({
		items: ".membership-feature",
		forcePlaceholderSize: true,
		dropOnEmpty: true,
		placeholder: "membership-feature-placeholder"
	});
	jQuery(".membership-feature").disableSelection();
	jQuery(".membership-prices").sortable({
		items: ".membership-price",
		forcePlaceholderSize: true,
		dropOnEmpty: true,
		placeholder: "membership-price-placeholder"
	});
	jQuery(".membership-price").disableSelection();
});