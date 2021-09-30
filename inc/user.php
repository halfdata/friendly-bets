<?php
if (!defined('INTEGRITY')) exit;

class user_class {

	function __construct() {
		global $options, $language;
		
		add_action('admin_menu', array(&$this, 'admin_menu'));
		add_action('ajax-profile-save', array(&$this, "ajax_profile_save"));
		add_action('ajax-google-disconnect', array(&$this, "ajax_google_disconnect"));
		add_action('ajax-facebook-disconnect', array(&$this, "ajax_facebook_disconnect"));
		add_action('ajax-vk-disconnect', array(&$this, "ajax_vk_disconnect"));
		add_action('ajax-account-remove', array(&$this, "ajax_account_remove"));
		add_action('ajax-upload-delete', array(&$this, "ajax_upload_delete"));
		add_action('ajax-uploads-delete', array(&$this, "ajax_uploads_delete"));
		add_action('ajax-upload-select', array(&$this, "ajax_upload_select"));
	}

	function admin_menu() {
		global $user_details, $wpdb;
		add_menu_page(
			esc_html__('User', 'fb')
			, "user"
			, array()
			, 'user'
			, ''
			, array()
			, 'none'
		);
		add_submenu_page(
			'user'
			, esc_html__('Profile', 'fb')
			, 'profile'
			, array()
			, 'user'
			, array(&$this, 'page_profile')
		);
		add_submenu_page(
			'user'
			, esc_html__('My media', 'fb')
			, 'media'
			, array()
			, 'user'
			, array(&$this, 'page_uploads')
		);
		add_submenu_page(
			'user'
			, ''
			, 'user-data'
			, array()
			, 'user'
			, array(&$this, 'page_user_data')
		);
		if (MEMBERSHIP_ENABLE && $user_details['role'] != 'admin') {
			if ($user_details['membership_id'] == 0) {
				$memberships = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."memberships WHERE deleted != '1' AND status != 'archive'", ARRAY_A);
				if (sizeof($memberships) > 0) {
					add_menu_page(
						esc_html__('Upgrade', 'fb')
						, "upgrade"
						, array()
						, 'user'
						, array(&$this, 'page_upgrade')
						, array()
						, 'none'
					);
					add_menu_page(
						''
						, "upgrading"
						, array()
						, 'user'
						, array(&$this, 'page_upgrading')
						, array()
						, 'none'
					);
				}
			} else {
				add_menu_page(
					''
					, "cancel-membership"
					, array()
					, 'user'
					, array(&$this, 'page_cancel_membership')
					, array()
					, 'none'
				);
			}
		}
	}

	function page_profile() {
		global $wpdb, $options, $user_details, $language, $free_membership, $languages;

		if (empty($user_details)) {
			header("Location: ".url('login.php').'?redirect='.urlencode(url('?page=profile')));
			exit;
		}

		$content = '';
		$page_title = esc_html__('My profile', 'fb');
		$content .= '
		<h1>'.$page_title.'</h1>
		<div class="form" id="settings-form">
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Email', 'fb').'</label>
				</div>
				<div class="form-tooltip">
				</div>
				<div class="form-content">
					<strong>'.esc_html($user_details['email']).'</strong>
				</div>
			</div>
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Full name', 'fb').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Specify your full name.', 'fb').'
					</div>
				</div>
				<div class="form-content">
					<div class="input-box">
						<input class="errorable" type="text" name="name" placeholder="'.esc_html__('Full name', 'fb').'" value="'.esc_html($user_details['name']).'">
					</div>
				</div>
			</div>
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Timezone', 'fb').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Select your timezone', 'fb').'
					</div>
				</div>
				<div class="form-content">
					<div class="input-box">
						<select class="errorable" name="timezone">
							'.timezone_choice($user_details['timezone']).'
						</select>
					</div>
				</div>
			</div>';
		if (MEMBERSHIP_ENABLE && $user_details['role'] != 'admin') {
			$memberships = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."memberships WHERE deleted != '1' AND status != 'archive'", ARRAY_A);
			if ($memberships > 0) {
				$content .= '
			<h2>'.esc_html__('Membership', 'fb').'</h2>
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Level', 'fb').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Your current membership.', 'fb').'
					</div>
				</div>
				<div class="form-content">';
					if ($user_details['membership_id'] == 0) {
						$title = translatable_parse($free_membership['title']);
						$free_title = (array_key_exists($language, $title) && !empty($title[$language]) ? $title[$language] : $title['default']);
						$content .= '
						<strong>'.esc_html($free_title).'</strong>
						<div><a class="button2" href="'.esc_html(url('?page=upgrade')).'">'.esc_html__('Upgrade', 'fb').'</a></div>';
					} else {
						$membership = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."memberships WHERE id = '".esc_sql($user_details['membership_id'])."' AND deleted != '1'", ARRAY_A);
						$title = translatable_parse($membership['title']);
						$membership_title = (array_key_exists($language, $title) && !empty($title[$language]) ? $title[$language] : $title['default']);
						$content .= '
						<strong>'.esc_html($membership_title).'</strong>
						'.($user_details['membership_txn_id'] > 0 ? '<a class="tooltipster single-action single-action-red" href="'.esc_html(url('?page=cancel-membership')).'" title="'.esc_html__('Cancel Membership', 'fb').'"><i class="fas fa-unlink"></i></a>' : '');
					}
					$content .= '
				</div>
			</div>';
			}
		}
		if ($options['google-enable'] == 'on' || $options['facebook-enable'] == 'on' || $options['vk-enable'] == 'on') {
			$content .= '
			<h2>'.esc_html__('Connections', 'fb').'</h2>';
			if ($options['google-enable'] == 'on') {
				$content .= '
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Google', 'fb').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Sign in using your Google Account.', 'fb').'
					</div>
				</div>
				<div class="form-content">
					<div class="input-box">';
				$connection_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."user_connections WHERE user_id = '".esc_sql($user_details['id'])."' AND source = 'google' AND deleted != '1'", ARRAY_A);
				if (empty($connection_details)) {
					$content .= '
						<a class="social-button social-button-google" href="https://accounts.google.com/o/oauth2/auth?client_id='.urlencode($options['google-client-id']).'&scope=profile%20email&response_type=code&redirect_uri='.urlencode(url('auth.php').'?google=auth').'">
							<i class="fab fa-google"></i> '.esc_html__('Connect to Google', 'fb').'
						</a>';
				} else {
					$content .= '
						<strong>'.esc_html($connection_details['source_id']).'</strong>
						<a class="tooltipster single-action single-action-red" href="#" onclick="return google_disconnect(this);" title="'.esc_html__('Disconnect', 'fb').'"><i class="fas fa-unlink"></i></a>';
				}
				$content .= '
					</div>
				</div>
			</div>';
			}
			if ($options['facebook-enable'] == 'on') {
				$content .= '
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Facebook', 'fb').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Sign in using your Facebook Account.', 'fb').'
					</div>
				</div>
				<div class="form-content">
					<div class="input-box">';
				$connection_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."user_connections WHERE user_id = '".esc_sql($user_details['id'])."' AND source = 'facebook' AND deleted != '1'", ARRAY_A);
				if (empty($connection_details)) {
					$content .= '
						<a class="social-button social-button-facebook" href="https://www.facebook.com/dialog/oauth?client_id='.urlencode($options['facebook-client-id']).'&scope=public_profile,email&redirect_uri='.urlencode(url('auth.php').'?facebook=auth').'">
							<i class="fab fa-facebook-f"></i> '.esc_html__('Connect to Facebook', 'fb').'
						</a>';
				} else {
					$content .= '
						<strong>'.esc_html($connection_details['source_id']).'</strong>
						<a class="tooltipster single-action single-action-red" href="#" onclick="return facebook_disconnect(this);" title="'.esc_html__('Disconnect', 'fb').'"><i class="fas fa-unlink"></i></a>';
				}
				$content .= '
					</div>
				</div>
			</div>';
			}
			if ($options['vk-enable'] == 'on') {
				$content .= '
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('VK', 'fb').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Sign in using your VK Account.', 'fb').'
					</div>
				</div>
				<div class="form-content">
					<div class="input-box">';
				$connection_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."user_connections WHERE user_id = '".esc_sql($user_details['id'])."' AND source = 'vk' AND deleted != '1'", ARRAY_A);
				if (empty($connection_details)) {
					$content .= '
						<a class="social-button social-button-vk" href="https://oauth.vk.com/authorize?client_id='.urlencode($options['vk-client-id']).'&display=page&redirect_uri='.urlencode(url('auth-vk.php')).'&scope=email&response_type=code&v=6.00">
							<i class="fab fa-vk"></i> '.esc_html__('Connect to VK', 'fb').'
						</a>';
				} else {
					$content .= '
						<strong>'.esc_html($connection_details['source_id']).'</strong>
						<a class="tooltipster single-action single-action-red" href="#" onclick="return vk_disconnect(this);" title="'.esc_html__('Disconnect', 'fb').'"><i class="fas fa-unlink"></i></a>';
				}
				$content .= '
					</div>
				</div>
			</div>';
			}
		}
		$content .= '
			<h2>'.esc_html__('Security Settings', 'fb').'</h2>
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Current password', 'fb').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Current password. Type it if you want to change the password.', 'fb').'
					</div>
				</div>
				<div class="form-content">
					<div class="input-box">
						<input class="errorable" type="password" name="current-password" placeholder="'.esc_html__('Current password', 'fb').'" value="">
					</div>
				</div>
			</div>
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('New password', 'fb').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Set new password. Leave this field blank if you do not want to change password.', 'fb').'
					</div>
				</div>
				<div class="form-content">
					<div class="columns">
						<div class="column column-50">
							<div class="input-box">
								<input class="errorable" type="password" name="password" placeholder="'.esc_html__('New password', 'fb').'" value="">
							</div>
						</div>
						<div class="column column-50">
							<div class="input-box">
								<input class="errorable" type="password" name="repeat-password" placeholder="'.esc_html__('Repeat password', 'fb').'" value="">
							</div>
						</div>
					</div>
				</div>
			</div>
			<h2>'.esc_html__('Privacy', 'fb').'</h2>
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Manage privacy', 'fb').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Download all info that we know about you or completely delete your account.', 'fb').'
					</div>
				</div>
				<div class="form-content">
					<div class="buttons-container">
						<a class="button2" href="'.url('?page=user-data').'" target="_blank">
							<i class="far fa-list-alt"></i>
							<span>'.esc_html__('Download my info', 'fb').'</span>
						</a>
						<a class="button2 button-red" href="#" onclick="return dialog_remove_account_open();">
							<i class="far fa-trash-alt"></i>
							<span>'.esc_html__('Delete account', 'fb').'</span>
						</a>
					</div>
				</div>
			</div>
			<div class="form-row right-align">
				<input type="hidden" name="action" value="profile-save">
				<a class="button" href="#" onclick="return save_form(this);" data-label="'.esc_html__('Save Details', 'fb').'">
					<span>'.esc_html__('Save Details', 'fb').'</span>
					<i class="fas fa-angle-right"></i>
				</a>
			</div>
		</div>
		<div class="dialog-danger-overlay" id="dialog-remove-account-overlay" onclick="return dialog_remove_account_close();"></div>
		<div class="dialog-danger" id="dialog-remove-account">
			<div class="dialog-danger-inner">
				<span class="dialog-danger-close" onclick="return dialog_remove_account_close();"><i class="fas fa-times"></i></span>
				<div class="dialog-danger-content">
					<div class="dialog-danger-content-html">
						<div class="dialog-danger-message">
							'.esc_html__('By entering and submitting my email address in the box below, I confirm that I want to remove my account (including all data created by me and associated with my account) from this website. I understand that removed data can not be recovered.', 'fb').'
						</div>
						<input type="email" name="email" placeholder="'.esc_html__('Type your email address...', 'fb').'" value="" />
						<input type="hidden" name="action" value="account-remove" />
					</div>
				</div>
				<div class="dialog-danger-buttons">
					<a class="button2 button-red" href="#" onclick="account_delete(this); return false;"><i class="far fa-trash-alt"></i>'.esc_html__('Delete account', 'fb').'</a>
				</div>
			</div>
		</div>';		
		return array('title' => $page_title, 'content' => $content);
	}

	function ajax_profile_save() {
		global $wpdb, $options, $user_details, $language, $languages, $mail_methods, $smtp_secures;

		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Please enter your account to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
		$timezone = trim(stripslashes($_REQUEST['timezone']));
		$name = trim(stripslashes($_REQUEST['name']));
		$current_password = trim(stripslashes($_REQUEST['current-password']));
		$password = trim(stripslashes($_REQUEST['password']));
		$repeat_password = trim(stripslashes($_REQUEST['repeat-password']));
		
		$timezone_offset = timezone_offset($timezone);
		if ($timezone_offset === false) $errors['timezone'] = esc_html__('Invalid timezone.', 'fb');
	
		if (mb_strlen($name) < 2) $errors['name'] = esc_html__('The full name is too short.', 'fb');
		else if (mb_strlen($name) > 127) $errors['name'] = esc_html__('The full name is too long.', 'fb');
		
		if (!empty($password)) {
			if (password_verify($current_password, $user_details['password'])) {
				if (mb_strlen($password) < 6) $errors['password'] = esc_html__('The password must be at least 6 characters long.', 'fb');
				else if ($password != $repeat_password) $errors['repeat-password'] = esc_html__('Repeat the password properly.', 'fb');
			} else $errors['current-password'] = esc_html__('Password is not correct.', 'fb');
		}
		if (!empty($errors)) {
			$return_data = array('status' => 'ERROR', 'errors' => $errors);
			echo json_encode($return_data);
			exit;
		}
		$wpdb->query("UPDATE ".$wpdb->prefix."users SET timezone = '".esc_sql($timezone)."', name = '".esc_sql($name)."'".(!empty($password) ? ", password = '".esc_sql(password_hash($password, PASSWORD_DEFAULT))."'" : "")." WHERE id = '".esc_sql($user_details['id'])."'");
		$return_object = array('status' => 'OK', 'message' => esc_html__('Settings successfully saved.', 'fb'));
		echo json_encode($return_object);
		exit;
	}

	function ajax_google_disconnect() {
		global $wpdb, $options, $user_details, $language;

		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Please enter your account to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
		$wpdb->query("UPDATE ".$wpdb->prefix."user_connections SET deleted = '1' WHERE user_id = '".esc_sql($user_details['id'])."' AND source = 'google'");
		$html = '
		<a class="social-button social-button-google" href="https://accounts.google.com/o/oauth2/auth?client_id='.urlencode($options['google-client-id']).'&scope=profile%20email&response_type=code&redirect_uri='.urlencode(url('auth.php')).'?google=auth">
			<i class="fab fa-google"></i> '.esc_html__('Connect to Google', 'fb').'
		</a>';
		$return_object = array('status' => 'OK', 'html' => $html, 'message' => esc_html__('Google Account successfully disconnected.', 'fb'));
		echo json_encode($return_object);
		exit;
	}

	function ajax_facebook_disconnect() {
		global $wpdb, $options, $user_details, $language;

		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Please enter your account to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
		$wpdb->query("UPDATE ".$wpdb->prefix."user_connections SET deleted = '1' WHERE user_id = '".esc_sql($user_details['id'])."' AND source = 'facebook'");
		$html = '
		<a class="social-button social-button-facebook" href="https://www.facebook.com/dialog/oauth?client_id='.$options['facebook-client-id'].'&scope=public_profile,email&redirect_uri='.urlencode(url('auth.php')).'?facebook=auth">
			<i class="fab fa-facebook-f"></i> '.esc_html__('Connect to Facebook', 'fb').'
		</a>';
		$return_object = array('status' => 'OK', 'html' => $html, 'message' => esc_html__('Facebook Account successfully disconnected.', 'fb'));
		echo json_encode($return_object);
		exit;
	}

	function ajax_vk_disconnect() {
		global $wpdb, $options, $user_details, $language;

		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Please enter your account to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
		$wpdb->query("UPDATE ".$wpdb->prefix."user_connections SET deleted = '1' WHERE user_id = '".esc_sql($user_details['id'])."' AND source = 'vk'");
		$html = '
		<a class="social-button social-button-vk" href="https://oauth.vk.com/authorize?client_id='.urlencode($options['vk-client-id']).'&display=page&redirect_uri='.urlencode(url('auth-vk.php')).'&scope=email&response_type=code&v=6.00">
			<i class="fab fa-vk"></i> '.esc_html__('Connect to VK', 'fb').'
		</a>';
		$return_object = array('status' => 'OK', 'html' => $html, 'message' => esc_html__('VK Account successfully disconnected.', 'fb'));
		echo json_encode($return_object);
		exit;
	}

	function page_uploads() {
		global $wpdb, $options, $user_details, $language, $free_membership, $languages;

		if (empty($user_details)) {
			header("Location: ".url('login.php').'?redirect='.urlencode(url('?page=media')));
			exit;
		}

		$tmp = $wpdb->get_row("SELECT COUNT(*) AS total FROM ".$wpdb->prefix."uploads WHERE user_id = '".esc_sql($user_details['id'])."' AND deleted != '1'", ARRAY_A);
		$total = $tmp["total"];
		$totalpages = ceil($total/RECORDS_PER_PAGE);
		if ($totalpages == 0) $totalpages = 1;
		if (array_key_exists('p', $_GET)) $page = intval($_GET["p"]);
		else $page = 1;
		if ($page < 1 || $page > $totalpages) $page = 1;
		$switcher = page_switcher(url('?page=media'), $page, $totalpages);
		$uploads = $wpdb->get_results("SELECT t1.*, t2.uuid AS user_uuid FROM ".$wpdb->prefix."uploads t1
				LEFT JOIN ".$wpdb->prefix."users t2 ON t2.id = t1.user_id
			WHERE t1.user_id = '".esc_sql($user_details['id'])."' AND t1.deleted != '1' ORDER BY t1.created DESC LIMIT ".(($page-1)*RECORDS_PER_PAGE).", ".RECORDS_PER_PAGE, ARRAY_A);

		$upload_dir = upload_dir();

		$content = '';
		$page_title = esc_html__('My media', 'fb');
		$content .= '
		<h1>'.$page_title.'</h1>
		<div class="table-funcbar">
			<div class="table-pageswitcher">'.$switcher.'</div>
			<div class="table-buttons">
				<a href="#" class="button button-small" onclick="if(file_uploading==false){jQuery(\'.upload-form input[type=file]\').click();} return false;"><i class="fas fa-plus"></i><span>'.esc_html__('Upload new file', 'fb').'</span></a>
			</div>
		</div>
		<div class="upload-container">';
		if (sizeof($uploads) > 0) {
			foreach ($uploads as $upload) {
				$thumbnail_url = '';
				$file_url = '';
				$upload_options = json_decode($upload['options'], true);
				if (file_exists($upload_dir['basedir'].'/'.$user_details['uuid'].'/'.$upload['filename'])) {
					$file_url = $upload_dir['baseurl'].'/'.$upload['user_uuid'].'/'.$upload['filename'];
					$thumbnail_url = $file_url;
				}
				if (is_array($upload_options) && array_key_exists('thumbnail', $upload_options) && file_exists($upload_dir['basedir'].'/'.$user_details['uuid'].'/'.$upload_options['thumbnail'])) $thumbnail_url = $upload_dir['baseurl'].'/'.$upload['user_uuid'].'/'.$upload_options['thumbnail'];
				$content .= '
			<div class="upload-element" data-id="'.esc_html($upload['uuid']).'" data-url="'.esc_html($file_url).'" data-thumbnail="'.esc_html($thumbnail_url).'">
				'.(!empty($file_url) ? '<a class="upload-preview" href="'.esc_html($file_url).'" onclick="return upload_preview(this);"></a>' : '').'
				<div class="upload-element-checkbox">
					<input class="checkbox-fa-check" type="checkbox" name="records[]" id="upload-'.esc_html($upload['uuid']).'" value="'.esc_html($upload['uuid']).'"><label for="upload-'.esc_html($upload['uuid']).'"></label>
				</div>
				<div class="upload-element-actions">
					<a class="tooltipster single-action single-action-red" href="#" title="'.esc_html__('Delete file', 'fb').'" data-id="'.esc_html($upload['uuid']).'" onclick="return upload_delete(this);"><i class="far fa-trash-alt"></i></a>
				</div>
				<div class="upload-element-image">
					'.(!empty($thumbnail_url) ? '
					<img src="'.esc_html($thumbnail_url).'" alt="'.esc_html($upload['filename']).'">' : esc_html__('Image does not exist.', 'fb')).'
				</div>
			</div>';
			}
		} else {
			$content .= '
			<div class="noupload">
					<p>'.esc_html__('Welcome. There are no available media yet.', 'fb').'</p>
			</div>';
		}
		$content .= '
			<div class="upload-element upload-element-template">
				<a class="upload-preview" onclick="return upload_preview(this);" href="#"></a>
				<div class="upload-element-checkbox">
					<input class="checkbox-fa-check" type="checkbox" name="records[]" id="upload-template" value=""><label for="upload-template"></label>
				</div>
				<div class="upload-element-actions">
					<a class="tooltipster single-action single-action-red" href="#" title="'.esc_html__('Delete file', 'fb').'" data-id="template" onclick="return upload_delete(this);"><i class="far fa-trash-alt"></i></a>
				</div>
				<div class="upload-element-image">
					<img src="" alt="">
					<i class="fas fa-spinner fa-spin"></i>
				</div>
			</div>
		</div>
		<div class="table-funcbar">
			<div class="table-pageswitcher">'.$switcher.'</div>
			<div class="table-buttons">
				<div class="multi-button multi-button-small">
					<span>'.esc_html__('Bulk actions', 'fb').'<i class="fas fa-angle-down"></i></span>
					<ul>
						<li><a href="#" data-action="delete" data-doing="'.esc_html__('Deleting...', 'fb').'" onclick="return uploads_bulk_delete(this);">'.esc_html__('Delete', 'fb').'</a></li>
					</ul>
				</div>
				<a href="#" class="button button-small" onclick="if(file_uploading==false){jQuery(\'.upload-form input[type=file]\').click();} return false;"><i class="fas fa-plus"></i><span>'.esc_html__('Upload new file', 'fb').'</span></a>
			</div>
		</div>
		<form class="upload-form" action="'.url('ajax.php').'" method="POST" enctype="multipart/form-data" target="upload-iframe" onsubmit="return upload_start(this);" style="display: none !important; width: 0 !important; height: 0 !important;">
			<input type="hidden" name="action" value="image-uploader-action" />
			<input type="file" name="file" accept="image/*" onchange="jQuery(this).parent().submit();" style="display: none !important; width: 0 !important; height: 0 !important;" />
			<input type="submit" value="Upload" style="display: none !important; width: 0 !important; height: 0 !important;" />
		</form>											
		<iframe data-loading="false" id="upload-iframe" name="upload-iframe" src="about:blank" onload="upload_finish(this);" style="display: none !important; width: 0 !important; height: 0 !important;"></iframe>';
		return array('title' => $page_title, 'content' => $content);
	}

	function ajax_upload_delete() {
		global $wpdb, $options, $user_details, $language;

		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Please enter your account to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
	
		$upload_uid = trim(stripslashes($_REQUEST['upload-id']));
		
		$upload = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."uploads WHERE user_id = '".esc_sql($user_details['id'])."' AND uuid = '".esc_sql($upload_uid)."' AND deleted != '1'", ARRAY_A);
		if (empty($upload)) {
			$return_object = array('status' => 'ERROR', 'message' => esc_html__('File not found.', 'fb'));
			echo json_encode($return_object);
			exit;
		}

		$upload_dir = upload_dir();
		if (file_exists($upload_dir['basedir'].'/'.$user_details['uuid'].'/'.$upload['filename'])) unlink($upload_dir['basedir'].'/'.$user_details['uuid'].'/'.$upload['filename']);
		$wpdb->query("UPDATE ".$wpdb->prefix."uploads SET deleted = '1' WHERE id = '".esc_sql($upload['id'])."'");
	
		$return_object = array('status' => 'OK', 'message' => esc_html__('File successfully deleted.', 'fb'));
		echo json_encode($return_object);
		exit;
	}

	function ajax_uploads_delete() {
		global $wpdb, $options, $user_details, $language;

		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Please enter your account to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
	
		$records = array();
		if (array_key_exists('records', $_REQUEST) && is_array($_REQUEST['records'])) {
			foreach ($_REQUEST['records'] as $record_id) {
				$records[] = preg_replace('/[^a-zA-Z0-9-]/', '', $record_id);
			}
		}
		if (empty($records)) {
			$return_object = array('status' => 'ERROR', 'message' => esc_html__('No files selected.', 'fb'));
			echo json_encode($return_object);
			exit;
		}
	
		$upload_dir = upload_dir();
		$uploads = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."uploads WHERE user_id = '".esc_sql($user_details['id'])."' AND deleted != '1' AND uuid IN ('".implode("','", $records)."')", ARRAY_A);
		foreach ($uploads as $upload) {
			if (file_exists($upload_dir['basedir'].'/'.$user_details['uuid'].'/'.$upload['filename'])) unlink($upload_dir['basedir'].'/'.$user_details['uuid'].'/'.$upload['filename']);
		}
		$wpdb->query("UPDATE ".$wpdb->prefix."uploads SET deleted = '1' WHERE user_id = '".esc_sql($user_details['id'])."' AND deleted != '1' AND uuid IN ('".implode("','", $records)."')");
	
		$_SESSION['success-message'] = esc_html__('Selected files successfully deleted.', 'fb');
		$return_object = array('status' => 'OK', 'message' => esc_html__('Selected files successfully deleted.', 'fb'));
		echo json_encode($return_object);
		exit;
	}

	function ajax_upload_select() {
		global $wpdb, $options, $user_details, $language;

		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Please enter your account to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		}

		$uploads = $wpdb->get_results("SELECT t1.*, t2.uuid AS user_uuid FROM ".$wpdb->prefix."uploads t1
				LEFT JOIN ".$wpdb->prefix."users t2 ON t2.id = t1.user_id
			WHERE t1.user_id = '".esc_sql($user_details['id'])."' AND t1.deleted != '1' ORDER BY t1.created DESC", ARRAY_A);

		$upload_dir = upload_dir();

		$content = '';
		$content .= '
		<div class="upload-container upload-container-small">';
		if (sizeof($uploads) > 0) {
			foreach ($uploads as $upload) {
				$thumbnail_url = '';
				$file_url = '';
				$upload_options = json_decode($upload['options'], true);
				if (file_exists($upload_dir['basedir'].'/'.$user_details['uuid'].'/'.$upload['filename'])) {
					$file_url = $upload_dir['baseurl'].'/'.$upload['user_uuid'].'/'.$upload['filename'];
					$thumbnail_url = $file_url;
				}
				if (is_array($upload_options) && array_key_exists('thumbnail', $upload_options) && file_exists($upload_dir['basedir'].'/'.$user_details['uuid'].'/'.$upload_options['thumbnail'])) $thumbnail_url = $upload_dir['baseurl'].'/'.$upload['user_uuid'].'/'.$upload_options['thumbnail'];
				$content .= '
			<div class="upload-element" data-id="'.esc_html($upload['uuid']).'" data-url="'.esc_html($file_url).'" data-thumbnail="'.esc_html($thumbnail_url).'">
				<a onclick="return upload_selected(this);" href="#"></a>
				<div class="upload-element-image">
					'.(!empty($thumbnail_url) ? '
					<img src="'.esc_html($thumbnail_url).'" alt="'.esc_html($upload['filename']).'">' : esc_html__('Image does not exist.', 'fb')).'
				</div>
			</div>';
			}
		} else {
			$content .= '
			<div class="noupload">
					<p>'.esc_html__('Welcome. There are no available media yet.', 'fb').'</p>
			</div>';
		}
		$content .= '
			<div class="upload-element upload-element-template">
				<a onclick="return upload_selected(this);" href="#"></a>
				<div class="upload-element-image">
					<img src="" alt="">
					<i class="fas fa-spinner fa-spin"></i>
				</div>
			</div>
		</div>
		<form class="upload-form" action="'.url('ajax.php').'" method="POST" enctype="multipart/form-data" target="upload-iframe" onsubmit="return upload_start(this);" style="display: none !important; width: 0 !important; height: 0 !important;">
			<input type="hidden" name="action" value="image-uploader-action" />
			<input type="file" name="file" accept="image/*" onchange="jQuery(this).parent().submit();" style="display: none !important; width: 0 !important; height: 0 !important;" />
			<input type="submit" value="Upload" style="display: none !important; width: 0 !important; height: 0 !important;" />
		</form>											
		<iframe data-loading="false" id="upload-iframe" name="upload-iframe" src="about:blank" onload="upload_finish(this);" style="display: none !important; width: 0 !important; height: 0 !important;"></iframe>';

		$button_html = '<a href="#" class="dialog-button dialog-button-custom" onclick="if(file_uploading==false){jQuery(\'.upload-form input[type=file]\').click();} return false;"><i class="fas fa-plus"></i><span>'.esc_html__('Upload new file', 'fb').'</span></a>';

		$return_object = array('status' => 'OK', 'html' => $content, 'button_html' => $button_html);
		echo json_encode($return_object);
		exit;
	}

	function page_user_data() {
		global $wpdb, $options, $user_details, $language, $languages, $mail_methods, $smtp_secures;

		if (empty($user_details)) {
			header("Location: ".url('login.php').'?redirect='.urlencode(url('?page=user-data')));
			exit;
		}
		echo '
<!DOCTYPE html>
<html lang="en">
<head> 
	<meta name="robots" content="noindex,nofollow">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" /> 
	<meta http-equiv="content-style-type" content="text/css" /> 
	<title>'.esc_html($user_details['name']).' ('.esc_html($user_details['email']).')</title>
	<style>
	html, body {
		margin: 0;
		padding: 1em;
		font-size: 16px;
		line-height: 1.475;
		height: 100%;
	}
	* {
		font-family: arial;
		box-sizing: border-box;
	}
	::after, ::before {
		box-sizing: border-box;
	}
	.h-table {
		table: 100%;
	}
	.h-table th {
		width: 20%;
		min-width: 320px;
		text-align: left;
		vertical-align: top;
	}
	.v-table {
		width: 100%;
	}
	table .empty-table {
		padding: 1em;
		text-align: center;
	}
	.v-table th {
		text-align: left;
		vertical-align: top;
		width: auto;
		min-width: auto;
	}
	.footer {
		text-align: center;
		padding: 3em 0 1em 0;
	}
	</style>
</head>
<body>';
		$title = translatable_parse($options['title']);
		$connections = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."user_connections WHERE user_id = '".esc_sql($user_details['id'])."'", ARRAY_A);
		$uploads = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."uploads WHERE user_id = '".esc_sql($user_details['id'])."'", ARRAY_A);
		$sessions = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."sessions WHERE user_id = '".esc_sql($user_details['id'])."'", ARRAY_A);
		echo '
	<h1>'.esc_html($user_details['name']).' ('.esc_html($user_details['email']).')</h1>
	<h2>'.esc_html__('General info', 'fb').'</h2>
	<table class="h-table">
		<tr>
			<th>'.esc_html__('Site', 'fb').':</th>
			<td>'.(array_key_exists($language, $title) && !empty($title[$language]) ? esc_html($title[$language]) : esc_html($title['default'])).'</td>
		</tr>
		<tr>
			<th>'.esc_html__('URL', 'fb').':</th>
			<td>'.esc_html($options['url']).'</td>
		</tr>
		<tr>
			<th>'.esc_html__('Name', 'fb').':</th>
			<td>'.esc_html($user_details['name']).'</td>
		</tr>
		<tr>
			<th>'.esc_html__('Email', 'fb').':</th>
			<td>'.esc_html($user_details['email']).'</td>
		</tr>
		<tr>
			<th>'.esc_html__('Email confirmed', 'fb').':</th>
			<td>'.($user_details['email_confirmed'] == '1' ? esc_html__('Yes', 'fb') : esc_html__('No', 'fb')).'</td>
		</tr>
		<tr>
			<th>'.esc_html__('Role', 'fb').':</th>
			<td>'.esc_html($user_details['role']).'</td>
		</tr>
		<tr>
			<th>'.esc_html__('Status', 'fb').':</th>
			<td>'.esc_html($user_details['status']).'</td>
		</tr>
		<tr>
			<th>'.esc_html__('Timezone', 'fb').':</th>
			<td>'.esc_html($user_details['timezone']).'</td>
		</tr>
		<tr>
			<th>'.esc_html__('Created', 'fb').':</th>
			<td>'.esc_html(timestamp_string($user_details['created'], $options['date-format'].' H:i')).'</td>
		</tr>
	</table>
	<h2>'.esc_html__('Connections', 'fb').'</h2>
	<table class="v-table">
		<tr>
			<th>'.esc_html__('Source', 'fb').'</th>
			<th>'.esc_html__('ID', 'fb').'</th>
			<th>'.esc_html__('Deleted', 'fb').'</th>
			<th>'.esc_html__('Created', 'fb').'</th>
		</tr>';
		if (sizeof($connections) > 0) {
			foreach ($connections as $connection) {
				echo '
		<tr>
			<td>'.esc_html($connection['source']).'</td>
			<td>'.esc_html($connection['source_id']).'</td>
			<td>'.($connection['deleted'] == '1' ? esc_html__('Yes', 'fb') : esc_html__('No', 'fb')).'</td>
			<td>'.esc_html(timestamp_string($connection['created'], $options['date-format'].' H:i')).'</td>
		</tr>';
			}
		} else {
			echo '
		<tr>
			<td class="empty-table" colspan="4">'.esc_html__('None', 'fb').'</td>
		</tr>';
		}
		echo '
	</table>
	<h2>'.esc_html__('Uploads', 'fb').'</h2>
	<table class="v-table">
		<tr>
			<th>'.esc_html__('Filename', 'fb').'</th>
			<th>'.esc_html__('Deleted', 'fb').'</th>
			<th>'.esc_html__('Created', 'fb').'</th>
		</tr>';
		if (sizeof($uploads) > 0) {
			foreach ($uploads as $upload) {
				echo '
		<tr>
			<td>'.esc_html($upload['original_filename']).'</td>
			<td>'.($upload['deleted'] == '1' ? esc_html__('Yes', 'fb') : esc_html__('No', 'fb')).'</td>
			<td>'.esc_html(timestamp_string($upload['created'], $options['date-format'].' H:i')).'</td>
		</tr>';
			}
		} else {
			echo '
		<tr>
			<td class="empty-table" colspan="3">'.esc_html__('None', 'fb').'</td>
		</tr>';
		}
		echo '
	</table>
	<h2>'.esc_html__('Sessions', 'fb').'</h2>
	<table class="v-table">
		<tr>
			<th>'.esc_html__('Source', 'fb').'</th>
			<th>'.esc_html__('IP', 'fb').'</th>
			<th>'.esc_html__('Created', 'fb').'</th>
			<th>'.esc_html__('Last Access', 'fb').'</th>
		</tr>';
		if (sizeof($sessions) > 0) {
			foreach ($sessions as $session) {
				echo '
		<tr>
			<td>'.esc_html($session['source']).'</td>
			<td>'.esc_html($session['ip']).'</td>
			<td>'.esc_html(timestamp_string($session['created'], $options['date-format'].' H:i')).'</td>
			<td>'.esc_html(timestamp_string($session['registered'], $options['date-format'].' H:i')).'</td>
		</tr>';
			}
		} else {
			echo '
		<tr>
			<td class="empty-table" colspan="4">'.esc_html__('None', 'fb').'</td>
		</tr>';
		}
		echo '
	</table>';
		do_action('user_info');
		echo '
	<div class="footer">'.sprintf(esc_html__('Report cereated: %s'), timestamp_string(time(), $options['date-format'].' H:i')).'</div>
</body>
</html>';
		exit;
	}

	function ajax_account_remove() {
		global $wpdb, $options, $user_details, $language, $admin_session_details;

		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Please enter your account to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
		$email = trim(stripslashes($_REQUEST['email']));
		if ($user_details['login'] != create_login($email)) {
			$return_data = array('status' => 'ERROR', 'message' => esc_html__('Invalid email address.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
		if ($user_details['role'] == 'admin') {
			$return_data = array('status' => 'ERROR', 'message' => esc_html__('Administrator can not remove account.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
		do_action('user_remove');
		$upload_dir = upload_dir();
		$uploads = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."uploads WHERE user_id = '".esc_sql($user_details['id'])."'", ARRAY_A);
		foreach ($uploads as $upload) {
			if (file_exists($upload_dir['basedir'].'/'.$user_details['uuid'].'/'.$upload['filename'])) unlink($upload_dir['basedir'].'/'.$user_details['uuid'].'/'.$upload['filename']);
		}
		$wpdb->query("DELETE t1 FROM ".$wpdb->prefix."uploads t1
			WHERE t1.user_id = '".esc_sql($user_details['id'])."'");
	
		$wpdb->query("DELETE t1 FROM ".$wpdb->prefix."user_connections t1
			WHERE t1.user_id = '".esc_sql($user_details['id'])."'");
	
		$wpdb->query("DELETE t1 FROM ".$wpdb->prefix."sessions t1
			WHERE t1.user_id = '".esc_sql($user_details['id'])."'");
	
		$wpdb->query("DELETE t1 FROM ".$wpdb->prefix."users t1
			WHERE t1.id = '".esc_sql($user_details['id'])."'");
	
		if (!empty($admin_session_details)) {
			if (PHP_VERSION_ID < 70300) setcookie('fb-auth', $admin_session_details['session_id'], time()+3600*24*60, '; samesite=lax');
			else setcookie('fb-auth', $admin_session_details['session_id'], array('expires' => time()+3600*24*60, 'samesite' => 'Lax'));
			if (PHP_VERSION_ID < 70300) setcookie('fb-auth-admin', null, -1, '; samesite=lax');
			else setcookie('fb-auth-admin', null, array('expires' => -1, 'samesite' => 'Lax'));
			$url = url('?page=admin-users');
		} else $url = url('');
	
		$_SESSION['success-message'] = esc_html__('Account successfully removed.', 'fb');
		$return_object = array('status' => 'OK', 'message' => esc_html__('Account successfully removed.', 'fb'), 'url' => $url);
		echo json_encode($return_object);
		exit;
	}

	function page_upgrade() {
		global $wpdb, $options, $user_details, $language, $free_membership, $membership_billing_periods;

		if (empty($user_details)) {
			header("Location: ".url('login.php').'?redirect='.urlencode(url('?page=upgrade')));
			exit;
		}

		if (!MEMBERSHIP_ENABLE) {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}

		$memberships = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."memberships WHERE deleted != '1'AND status != 'archive' ORDER BY seq ASC", ARRAY_A);

		if (sizeof($memberships) == 0) {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}

		$content = '';
		$page_title = esc_html__('Upgrade account', 'fb');

		$content .= '<h1>'.$page_title.'</h1>
		<div class="memberships memberships-sortable">';
		if (sizeof($memberships) > 0) {
			foreach ($memberships as $membership) {
				$membership_prices = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."membership_prices WHERE membership_id = '".esc_sql($membership['id'])."' AND deleted != '1' AND status != 'archive' ORDER BY seq ASC", ARRAY_A);
				if (sizeof($membership_prices) < 1) continue;
				$title = translatable_parse($membership['title']);
				$description = translatable_parse($membership['description']);
				$membership_description = (array_key_exists($language, $description) && !empty($description[$language]) ? $description[$language] : $description['default']);
				$footer = translatable_parse($membership['footer']);
				$membership_footer = (array_key_exists($language, $footer) && !empty($footer[$language]) ? $footer[$language] : $footer['default']);
				$content .= '
			<div class="membership-panel membership-panel-'.esc_html($membership['color']).' membership-panel-'.esc_html($membership['status']).'" data-id="'.esc_html($membership['id']).'">
				<a'.(sizeof($membership_prices) == 1 ? ' href="'.url('?page=upgrading&po='.$membership_prices[0]['uuid']).'"' : ' href="#" onclick="return memberships_expand_prices(this);"').'></a>
				<div class="membership-panel-header">
					<h2>'.(array_key_exists($language, $title) && !empty($title[$language]) ? esc_html($title[$language]) : esc_html($title['default'])).'</h2>
				</div>
				<div class="membership-panel-body">';
				$features = json_decode($membership['features'], true);
				if (is_array($features) && !empty($features)) {
					$content .= '
					<ul class="membership-panel-features">';
					foreach ($features as $feature) {
						$label = translatable_parse($feature['label']);
						$feature_label = (array_key_exists($language, $label) && !empty($label[$language]) ? $label[$language] : $label['default']);
						if (empty($feature_label)) {
							$feature_label = '-';
							$feature['bullet'] = 'no';
						}
						$content .= '
						<li class="membership-panel-feature membership-panel-feature-'.esc_html($feature['bullet']).'">
							'.esc_html($feature_label).'
						</li>';
					}
					$content .= '
					</ul>';
				} else if (!empty($membership_description)) {
					$content .= '
					<div class="membership-panel-description">
						<p>'.esc_html($membership_description).'</p>
					</div>';
				}
				$content .= '
				</div>
				<div class="membership-panel-footer">
					<div class="membership-panel-footer-label membership-panel-footer-standard-label">
						'.esc_html($membership_footer).'
					</div>';
				if (sizeof($membership_prices) > 1) {
					$content .= '
					<div class="membership-panel-footer-label membership-panel-footer-prices-label">
						'.esc_html__('Price options', 'fb').'
					</div>
					<div class="membership-panel-footer-prices">
						<ul class="membership-panel-prices">';
					foreach ($membership_prices as $membership_price) {
						$title = translatable_parse($membership_price['title']);
						$price_title = (array_key_exists($language, $title) && !empty($title[$language]) ? $title[$language] : $title['default']);
						$content .= '<li class="membership-panel-price"><a href="'.url('?page=upgrading&po='.$membership_price['uuid']).'">'.(!empty($price_title) ? '<h4>'.esc_html($price_title).'</h4>' : '').number_format($membership_price['price'], 2, '.', '').' '.esc_html($membership_price['currency']).' '.$membership_billing_periods[$membership_price['billing_period']]['per-label'].'</a></li>';
					}
					$content .= '
						</ul>
					</div>';
				}
			$content .= '
				</div>
			</div>';
			}
		}
		$content .= '
		</div>';

		return array('title' => $page_title, 'content' => $content);
	}

	function page_upgrading() {
		global $wpdb, $options, $user_details, $language, $free_membership, $membership_billing_periods, $stripe_no_100;

		if (empty($user_details)) {
			header("Location: ".url('login.php').'?redirect='.urlencode(url('?page=upgrade')));
			exit;
		}

		if (!MEMBERSHIP_ENABLE) {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}

		if ($options['stripe-enable'] != 'on') {
			$_SESSION['info-message'] = esc_html__('No payment gateways are configured.', 'fb');
			header("Location: ".url('?page=upgrade'));
			exit;
		}

		if (!array_key_exists('po', $_GET)) {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}
		$po_uid = preg_replace('/[^a-zA-Z0-9-]/', '', trim(stripslashes($_GET['po'])));
		$membership_price = $wpdb->get_row("SELECT t1.*, t2.uuid AS membership_uuid, t2.title AS membership_title, t2.options AS membership_options FROM ".$wpdb->prefix."membership_prices t1 
				INNER JOIN ".$wpdb->prefix."memberships t2 ON t2.id = t1.membership_id
			WHERE t1.uuid = '".esc_sql($po_uid)."' AND t1.deleted != '1' AND t1.status != 'archive' AND t2.deleted != '1' AND t2.status != 'archive'", ARRAY_A);

		if (empty($membership_price)) {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}

		if (!class_exists("\Stripe\Stripe")) require_once(dirname(__FILE__).'/stripe/init.php');
		
		$membership_options = array('stripe' => array());
		$tmp = json_decode($membership_price['membership_options'], true);
		if (!empty($tmp) && is_array($tmp)) $membership_options = array_merge($membership_options, $tmp);
		$membership_price_options = array('stripe' => array());
		$tmp = json_decode($membership_price['options'], true);
		if (!empty($tmp) && is_array($tmp)) $membership_price_options = array_merge($membership_price_options, $tmp);

		try {
			\Stripe\Stripe::setApiKey($options['stripe-secret-key']);
		} catch(Exception $e) {
			$body = $e->getJsonBody();
			$_SESSION['info-message'] = esc_html(rtrim($body['error']['message'], '.').'.');
			header("Location: ".url('?page=upgrade'));
			exit;
		}
// Create new product in Stripe, if it doesn't exist - begin
		$membership_title = translatable_parse($membership_price['membership_title']);
		try {
			if (!array_key_exists('product', $membership_options['stripe'])) {
				throw new Exception("Product doesn't exist.");
			}
			$product = \Stripe\Product::retrieve($membership_options['stripe']['product'], []);
		} catch(Exception $e) {
			try {
				$product = \Stripe\Product::create([
					'name' => !empty($membership_title['default']) ? $membership_title['default'] : esc_html__('Membership', 'fb'),
				]);
				$membership_options['stripe']['product'] = $product->id;
				$wpdb->query("UPDATE ".$wpdb->prefix."memberships SET status = 'active', options = '".esc_sql(json_encode($membership_options))."' WHERE id = '".esc_sql($membership_price['membership_id'])."'");
			} catch(Exception $e) {
				$body = $e->getJsonBody();
				$_SESSION['error-message'] = esc_html(rtrim($body['error']['message'], '.').'.');
				header("Location: ".url('?page=upgrade'));
				exit;
			}
		}
// Create new product in Stripe, if it doesn't exist - end

// Create new price in Stripe, if it doesn't exist - begin
		if (in_array($membership_price["currency"], $stripe_no_100)) $multiplier = 1;
		else $multiplier = 100;
		try {
			if (!array_key_exists('price', $membership_price_options['stripe'])) {
				throw new Exception("Price doesn't exist.");
			}
			$price = \Stripe\Price::retrieve($membership_price_options['stripe']['price'], []);
		} catch(Exception $e) {
			try {
				$price_element = array(
					'product' => $membership_options['stripe']['product'],
					'unit_amount' => intval($membership_price["price"]*$multiplier),
					'currency' => $membership_price["currency"]
				);
				if ($membership_price["billing_period"] != 'single') {
					$price_element['recurring'] = array(
						'interval' => (in_array($membership_price["billing_period"], array('quarter', 'semiannual')) ? 'month' : $membership_price["billing_period"]),
						'interval_count' => ($membership_price["billing_period"] == 'quarter' ? 3 : ($membership_price["billing_period"] == 'semiannual' ? 6 : 1))
					);
				}
				$price = \Stripe\Price::create($price_element);				
				$membership_price_options['stripe']['price'] = $price->id;
				$wpdb->query("UPDATE ".$wpdb->prefix."membership_prices SET status = 'active',  options = '".esc_sql(json_encode($membership_price_options))."' WHERE id = '".esc_sql($membership_price['id'])."'");
			} catch(Exception $e) {
				$body = $e->getJsonBody();
				$_SESSION['error-message'] = esc_html(rtrim($body['error']['message'], '.').'.');
				header("Location: ".url('?page=upgrade'));
				exit;
			}
		}
// Create new price in Stripe, if it doesn't exist - end

// Create new customer in Stripe, if it doesn't exist - begin
		$stripe_customer = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."user_customers WHERE user_id = '".esc_sql($user_details['id'])."' AND deleted != '1' AND gateway = 'stripe'", ARRAY_A);
		try {
			if (empty($stripe_customer)) {
				throw new Exception("Customer doesn't exist.");
			}
			$customer = \Stripe\Customer::retrieve($stripe_customer['customer_id'], []);
			if ($customer->deleted) {
				throw new Exception("Customer doesn't exist.");
			}
		} catch(Exception $e) {
			try {
				$customer = \Stripe\Customer::create([
					'email' => $user_details['email'],
					'name' => $user_details['name']
				]);
				if (empty($stripe_customer)) {
					$stripe_customer = array('customer_id' => $customer->id);
					$wpdb->query("INSERT INTO ".$wpdb->prefix."user_customers (
						user_id, 
						gateway, 
						customer_id, 
						deleted, 
						created
					) VALUES (
						'".esc_sql($user_details['id'])."',
						'stripe',
						'".esc_sql($stripe_customer['customer_id'])."',
						'0',
						'".time()."'
					)");
				} else {
					$stripe_customer['customer_id'] = $customer->id;
					$wpdb->query("UPDATE ".$wpdb->prefix."user_customers SET customer_id = '".esc_sql($stripe_customer['customer_id'])."' WHERE id = '".esc_sql($stripe_customer['id'])."'");
				}
			} catch(Exception $e) {
				$body = $e->getJsonBody();
				$_SESSION['error-message'] = esc_html(rtrim($body['error']['message'], '.').'.');
				header("Location: ".url('?page=upgrade'));
				exit;
			}
		}
// Create new customer in Stripe, if it doesn't exist - end

// Create new subscription in Stripe, if it doesn't exist - begin
		try {
			$stripe_session = \Stripe\Checkout\Session::create([
				'success_url' => url('?page=profile'),
				'cancel_url' => url('?page=profile'),
				'customer' => $stripe_customer['customer_id'],
				'payment_method_types' => ['card'],
				'line_items' => [[
					'price' => $membership_price_options['stripe']['price'],
					'quantity' => 1
				]],				
				'mode' => $membership_price["billing_period"] != 'single' ? 'subscription' : 'payment',
				'client_reference_id' => 'membership-'.$membership_price["id"]
			]);
		} catch(Exception $e) {
			$body = $e->getJsonBody();
			$_SESSION['error-message'] = esc_html(rtrim($body['error']['message'], '.').'.');
			header("Location: ".url('?page=upgrade'));
			exit;
		}
// Create new subscription in Stripe, if it doesn't exist - end
		header("Location: ".$stripe_session->url);
		exit;
	}

	function page_cancel_membership() {
		global $wpdb, $options, $user_details, $language;

		if (empty($user_details)) {
			header("Location: ".url('login.php').'?redirect='.urlencode(url('?page=profile')));
			exit;
		}

		if (!MEMBERSHIP_ENABLE) {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}

		if ($user_details['membership_txn_id'] == 0) {
			$_SESSION['info-message'] = esc_html__('Service temporarily not available.', 'fb');
			header("Location: ".url('?page=profile'));
			exit;
		}

		$transaction = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."transactions WHERE id = '".esc_sql($user_details['membership_txn_id'])."' AND deleted != '1' AND gateway = 'stripe'", ARRAY_A);
		if (empty($transaction)) {
			// TODO: Sent notification to administrator.
			$_SESSION['info-message'] = esc_html__('Service temporarily not available. Please contact us.', 'fb');
			header("Location: ".url('?page=profile'));
			exit;
		}


		if (!class_exists("\Stripe\Stripe")) require_once(dirname(__FILE__).'/stripe/init.php');
		
		try {
			$stripe = new \Stripe\StripeClient($options['stripe-secret-key']);
		} catch(Exception $e) {
			// TODO: Sent notification to administrator.
			$body = $e->getJsonBody();
			$_SESSION['info-message'] = esc_html(rtrim($body['error']['message'], '.').'.');
			header("Location: ".url('?page=profile'));
			exit;
		}

		try {
			$subscription = $stripe->subscriptions->retrieve($transaction['subscription_id'], []);
			$subscription = $stripe->subscriptions->cancel($transaction['subscription_id'], []);
		} catch(Exception $e) {
			// TODO: Sent notification to administrator.
			$body = $e->getJsonBody();
			$_SESSION['info-message'] = esc_html(rtrim($body['error']['message'], '.').'.');
			header("Location: ".url('?page=profile'));
			exit;
		}
		$wpdb->query("UPDATE ".$wpdb->prefix."users SET membership_id = '0', membership_txn_id = '0' AND membership_expires = '0' WHERE id = '".esc_sql($user_details['id'])."'");
		$_SESSION['success-message'] = esc_html__('Your subscription successfully cancelled.', 'fb');
		header("Location: ".url('?page=profile'));
		exit;
	}

}
$user = new user_class();
?>