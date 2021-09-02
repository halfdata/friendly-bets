"use strict";
var totalizator_busy = false;
function totalizator_campaign_delete(_object) {
	dialog_open({
		echo_html:		function() {
			this.html("<div class='dialog-message'>"+esc_html__("Please confirm that you want to delete totalizator.", "t")+"</div>");
			this.show();
		},
		ok_label:		'Delete',
		ok_function:	function(e){
			_totalizator_campaign_delete(_object);
			dialog_close();
		}
	});
	return false;
}
function _totalizator_campaign_delete(_object) {
	if (totalizator_busy) return false;
	totalizator_busy = true;
	var campaign_id = jQuery(_object).attr("data-id");
	var doing_label = jQuery(_object).attr("data-doing");
	var do_label = jQuery(_object).html();
	jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i> "+doing_label);
	var post_data = {"action" : "totalizator-campaign-delete", "campaign-id" : campaign_id};
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
			totalizator_busy = false;
		},
		error	: function(XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_object).html(do_label);
			global_message_show("danger", esc_html__("Something went wrong. We got unexpected server response."));
			totalizator_busy = false;
		}
	});
	return false;
}
function totalizator_campaign_status_toggle(_object) {
	if (totalizator_busy) return false;
	totalizator_busy = true;
	var campaign_id = jQuery(_object).attr("data-id");
	var campaign_status = jQuery(_object).attr("data-status");
	var doing_label = jQuery(_object).attr("data-doing");
	var do_label = jQuery(_object).html();
	jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i> "+doing_label);
	var post_data = {"action" : "totalizator-campaign-status-toggle", "campaign-id" : campaign_id, "status" : campaign_status};
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
					jQuery(_object).html(data.campaign_action);
					jQuery(_object).attr("data-status", data.campaign_status);
					jQuery(_object).attr("data-doing", data.campaign_action_doing);
					if (data.campaign_status == "active") jQuery(_object).closest(".totalizator-panel").find(".totalizator-badge-status").html("");
					else jQuery(_object).closest(".totalizator-panel").find(".totalizator-badge-status").html("<span class='totalizator-panel-badge totalizator-panel-badge-danger'>"+data.campaign_status_label+"</span>");
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
			totalizator_busy = false;
		},
		error	: function(XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_object).html(do_label);
			global_message_show("danger", esc_html__("Something went wrong. We got unexpected server response."));
			totalizator_busy = false;
		}
	});
	return false;
}

function totalizator_campaign_quit(_object) {
	dialog_open({
		echo_html:		function() {
			this.html("<div class='dialog-message'>"+esc_html__("Please confirm that you want to quit totalizator. You lose existing bets, but can join later.", "t")+"</div>");
			this.show();
		},
		ok_label:		'Quit',
		ok_function:	function(e){
			_totalizator_campaign_quit(_object);
			dialog_close();
		}
	});
	return false;
}
function _totalizator_campaign_quit(_object) {
	if (totalizator_busy) return false;
	totalizator_busy = true;
	var campaign_id = jQuery(_object).attr("data-id");
	var doing_label = jQuery(_object).attr("data-doing");
	var do_label = jQuery(_object).html();
	jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i> "+doing_label);
	var post_data = {"action" : "totalizator-campaign-quit", "cid" : campaign_id};
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
			totalizator_busy = false;
		},
		error	: function(XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_object).html(do_label);
			global_message_show("danger", esc_html__("Something went wrong. We got unexpected server response."));
			totalizator_busy = false;
		}
	});
	return false;
}
function totalizator_game_delete(_object) {
	dialog_open({
		echo_html:		function() {
			this.html("<div class='dialog-message'>"+esc_html__("Please confirm that you want to delete the game.", "t")+"</div>");
			this.show();
		},
		ok_label:		'Delete',
		ok_function:	function(e){
			_totalizator_game_delete(_object);
			dialog_close();
		}
	});
	return false;
}
function _totalizator_game_delete(_object) {
	if (totalizator_busy) return false;
	totalizator_busy = true;
	var game_uid = jQuery(_object).attr("data-id");
	var campaign_uid = jQuery(_object).attr("data-cid");
	var doing_label = jQuery(_object).attr("data-doing");
	var do_label = jQuery(_object).html();
	jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i> "+doing_label);
	var post_data = {"action" : "totalizator-game-delete", "game-id" : game_uid, "cid" : campaign_uid};
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
			totalizator_busy = false;
		},
		error	: function(XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_object).html(do_label);
			global_message_show("danger", esc_html__("Something went wrong. We got unexpected server response."));
			totalizator_busy = false;
		}
	});
	return false;
}

function totalizator_participant_delete(_object) {
	dialog_open({
		echo_html:		function() {
			this.html("<div class='dialog-message'>"+esc_html__("Please confirm that you want to delete the participant.", "t")+"</div>");
			this.show();
		},
		ok_label:		'Delete',
		ok_function:	function(e){
			_totalizator_participant_delete(_object);
			dialog_close();
		}
	});
	return false;
}
function _totalizator_participant_delete(_object) {
	if (totalizator_busy) return false;
	totalizator_busy = true;
	var participant_uid = jQuery(_object).attr("data-id");
	var campaign_uid = jQuery(_object).attr("data-cid");
	jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i>");
	var post_data = {"action" : "totalizator-participant-delete", "participant-id" : participant_uid, "cid" : campaign_uid};
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
					location.reload();
				} else if (data.status == "ERROR" || data.status == "WARNING") {
					global_message_show("danger", data.message);
				} else {
					global_message_show("danger", esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch(error) {
				global_message_show("danger", esc_html__("Something went wrong. We got unexpected server response."));
			}
			jQuery(_object).html("<i class='far fa-trash-alt'></i>");
			totalizator_busy = false;
		},
		error	: function(XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_object).html("<i class='far fa-trash-alt'></i>");
			global_message_show("danger", esc_html__("Something went wrong. We got unexpected server response."));
			totalizator_busy = false;
		}
	});
	return false;
}
function totalizator_guessing_results(_object) {
	dialog_open({
		echo_html:		function() {
			this.html("<div class='dialog-message'>"+esc_html__("Are you sure that you want to watch guessing results before disclosure date?", "t")+"</div>");
			this.show();
		},
		ok_label:		'I am sure!',
		ok_function:	function(e){
			location.href = jQuery(_object).attr("href");
		}
	});
	return false;
}
