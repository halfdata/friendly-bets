<?php
if (!defined('INTEGRITY')) exit;

class admin_class {

	function __construct() {
		global $options, $language;
		
		add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));
		add_action('admin_menu', array(&$this, 'admin_menu'));
		add_action('admin_head', array(&$this, 'admin_head'));
		add_action('admin_init', array(&$this, 'admin_init'));
		add_action('ajax-user-delete', array(&$this, "ajax_user_delete"));
		add_action('ajax-user-status-toggle', array(&$this, "ajax_user_status_toggle"));
		add_action('ajax-users-delete', array(&$this, "ajax_users_delete"));
		add_action('ajax-users-activate', array(&$this, "ajax_users_activate"));
		add_action('ajax-users-deactivate', array(&$this, "ajax_users_deactivate"));
		add_action('ajax-user-save', array(&$this, "ajax_user_save"));
		add_action('ajax-memberships-save-list', array(&$this, "ajax_memberships_save_list"));
		add_action('ajax-membership-delete', array(&$this, "ajax_membership_delete"));
		add_action('ajax-membership-archive', array(&$this, "ajax_membership_archive"));
		add_action('ajax-membership-activate', array(&$this, "ajax_membership_activate"));
		add_action('ajax-membership-save', array(&$this, "ajax_membership_save"));
		add_action('ajax-membership-free-save', array(&$this, "ajax_membership_free_save"));
		add_action('ajax-transaction-delete', array(&$this, "ajax_transaction_delete"));
		add_action('ajax-transactions-delete', array(&$this, "ajax_transactions_delete"));
		add_action('ajax-transaction-details', array(&$this, "ajax_transaction_details"));
		add_action('ajax-session-delete', array(&$this, "ajax_session_delete"));
		add_action('ajax-sessions-delete', array(&$this, "ajax_sessions_delete"));
		add_action('ajax-test-mailing', array(&$this, "ajax_mail_test"));
		add_action('ajax-site-settings-save', array(&$this, "ajax_settings_save"));
	}

	function admin_enqueue_scripts() {
		enqueue_script("jquery");
		enqueue_style('admin-css', url('css/admin.css'), array(), VERSION);
		enqueue_script('admin-js', url('js/admin.js'), array(), VERSION);
	}

	function admin_head() {
		global $wpdb;
	}

	function admin_init() {
		global $wpdb, $options, $user_details;
	}

	function admin_menu() {
		global $user_details;
		add_menu_page(
			esc_html__('Administrator', 'fb')
			, "admin"
			, array()
			, 'admin'
			, ''
			, array()
			, 'none'
		);
		add_submenu_page(
			'admin'
			, esc_html__('Users', 'fb')
			, 'admin-users'
			, array()
			, 'admin'
			, array(&$this, 'page_users')
		);
		add_submenu_page(
			'admin'
			, ''
			, 'admin-add-user'
			, array()
			, 'admin'
			, array(&$this, 'page_add_user')
		);
		if (MEMBERSHIP_ENABLE) {
			add_submenu_page(
				'admin'
				, esc_html__('Memberships', 'fb')
				, 'admin-memberships'
				, array()
				, 'admin'
				, array(&$this, 'page_memberships')
			);
			add_submenu_page(
				'admin'
				, ''
				, 'admin-add-membership'
				, array()
				, 'admin'
				, array(&$this, 'page_add_membership')
			);
			add_submenu_page(
				'admin'
				, ''
				, 'admin-edit-free-membership'
				, array()
				, 'admin'
				, array(&$this, 'page_edit_free_membership')
			);
		}
		if (is_payment_enabled()) {
			add_submenu_page(
				'admin'
				, esc_html__('Transactions', 'fb')
				, 'admin-transactions'
				, array()
				, 'admin'
				, array(&$this, 'page_transactions')
			);
		}
		add_submenu_page(
			'admin'
			, esc_html__('Active sessions', 'fb')
			, 'admin-sessions'
			, array()
			, 'admin'
			, array(&$this, 'page_sessions')
		);
		add_submenu_page(
			'admin'
			, esc_html__('Site settings', 'fb')
			, 'admin-settings'
			, array()
			, 'admin'
			, array(&$this, 'page_settings')
		);
	}

	function page_users() {
		global $wpdb, $options, $user_details, $language, $free_membership;

		if (empty($user_details)) {
			header("Location: ".url('login.php').'?redirect='.urlencode(url('?page=admin-users')));
			exit;
		} else if ($user_details['role'] != 'admin') {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}

		if (array_key_exists('s', $_GET)) $search_query = trim(stripslashes($_GET["s"]));
		else $search_query = "";
		$tmp = $wpdb->get_row("SELECT COUNT(*) AS total FROM ".$wpdb->prefix."users WHERE deleted != '1'".((strlen($search_query) > 0) ? " AND (name LIKE '%".esc_sql($search_query)."%' OR email LIKE '%".esc_sql($search_query)."%' OR login LIKE '%".esc_sql($search_query)."%')" : ""), ARRAY_A);
		$total = $tmp["total"];
		$totalpages = ceil($total/RECORDS_PER_PAGE);
		if ($totalpages == 0) $totalpages = 1;
		if (array_key_exists('p', $_GET)) $page = intval($_GET["p"]);
		else $page = 1;
		if ($page < 1 || $page > $totalpages) $page = 1;
		$switcher = page_switcher(url('?page=admin-users').((strlen($search_query) > 0) ? "&s=".urlencode($search_query) : ""), $page, $totalpages);
		$users = $wpdb->get_results("SELECT t1.*, t2.uuid AS membership_uuid, t2.title AS membership_title FROM ".$wpdb->prefix."users t1 
				LEFT JOIN ".$wpdb->prefix."memberships t2 ON t2.id = t1.membership_id AND t2.deleted != '1'
			WHERE t1.deleted != '1'".((strlen($search_query) > 0) ? " AND (t1.name LIKE '%".esc_sql($search_query)."%' OR t1.email LIKE '%".esc_sql($search_query)."%' OR t1.login LIKE '%".esc_sql($search_query)."%')" : "")." ORDER BY t1.created DESC LIMIT ".(($page-1)*RECORDS_PER_PAGE).", ".RECORDS_PER_PAGE, ARRAY_A);

		$content = '';
		$page_title = esc_html__('Users', 'fb');
		$content .= '
		<h1>'.esc_html__('Users', 'fb').'</h1>
		<div class="table-funcbar">
			<form action="'.(PERMALINKS_ENABLE ? esc_html(url('?page=admin-users')) : esc_html(url(''))).'" method="get" class="table-filter-form">
				'.(PERMALINKS_ENABLE ? '' : '<input type="hidden" name="page" value="admin-users" />').'
				<input type="text" name="s" value="'.esc_html($search_query).'" placeholder="'.esc_html__('Search', 'fb').'" />
				<input type="submit" class="button" value="'.esc_html__('Search', 'fb').'" />
				'.((strlen($search_query) > 0) ? '<input type="button" class="button" value="'.esc_html__('Reset search results', 'fb').'" onclick="window.location.href=\''.url('?page=admin-users').'\';" />' : '').'
			</form>
		</div>
		<div class="table-funcbar">
			<div class="table-pageswitcher">'.$switcher.'</div>
			<div class="table-buttons"><a href="'.esc_html(url('?page=admin-add-user')).'" class="button button-small"><i class="fas fa-plus"></i><span>'.esc_html__('Create New User', 'fb').'</span></a></div>
		</div>
		<div class="table">
			<table>
				<thead>
					<tr>
						<th class="table-column-selector"></th>
						<th>'.esc_html__('Email', 'fb').'</th>
						<th>'.esc_html__('Name', 'fb').'</th>
						'.(MEMBERSHIP_ENABLE ? '<th>'.esc_html__('Membership', 'fb').'</th>' : '').'
						<th class="table-column-100">'.esc_html__('Role', 'fb').'</th>
						<th class="table-column-created">'.esc_html__('Created', 'fb').'</th>
						<th class="table-column-actions"></th>
					</tr>
				</thead>
				<tbody>';
		if (sizeof($users) > 0) {
			foreach ($users as $user) {
					if (MEMBERSHIP_ENABLE) {
						if (empty($user['membership_uuid'])) {
							$title = translatable_parse($free_membership['title']);
							$membership_title = (array_key_exists($language, $title) && !empty($title[$language]) ? $title[$language] : $title['default']);
						} else {
							$title = translatable_parse($user['membership_title']);
							$membership_title = (array_key_exists($language, $title) && !empty($title[$language]) ? $title[$language] : $title['default']);
						}
					}
					$content .= '
					<tr>
						<td data-label=""><input class="checkbox-fa-check" type="checkbox" name="records[]" id="user-'.esc_html($user['id']).'" value="'.esc_html($user['id']).'"><label for="user-'.esc_html($user['id']).'"></label></td>
						<td data-label="'.esc_html__('Email', 'fb').'"><a href="'.esc_html(url('?page=admin-add-user&id='.urlencode($user['id']))).'"><strong>'.esc_html($user['email']).'</strong></a><span class="table-badge-status">'.($user['status'] == 'inactive' ? '<span class="badge badge-danger">'.esc_html__('Inactive', 'fb').'</span>' : '').'</span><label class="table-note">UID: '.esc_html($user['uuid']).'</label></td>
						<td data-label="'.esc_html__('Name', 'fb').'">'.esc_html($user['name']).'</td>
						'.(MEMBERSHIP_ENABLE ? '<td data-label="'.esc_html__('Membership', 'fb').'">'.esc_html($membership_title).(!empty($user['membership_uuid']) ? '<label class="table-note">'.($user['membership_expires'] > 0 ? sprintf(esc_html__('Expires on %s', 'fb'), esc_html(timestamp_string($user['membership_expires'], $options['date-format'].' H:i'))) : esc_html__('Never expires', 'fb')).'</label>' : '').'</label></td>' : '').'
						<td data-label="'.esc_html__('Role', 'fb').'">'.esc_html($user['role']).'</td>
						<td data-label="'.esc_html__('Created', 'fb').'">'.esc_html(timestamp_string($user['created'], $options['date-format'].' H:i')).'</td>
						<td data-label="'.esc_html__('Actions', 'fb').'">
							<div class="item-menu">
								<span><i class="fas fa-ellipsis-v"></i></span>
								<ul>
									<li><a href="'.esc_html(url('?page=admin-add-user&id='.urlencode($user['id']))).'">'.esc_html__('Edit', 'fb').'</a></li>
									<li><a href="'.url('').'?user='.esc_html($user['uuid']).'">'.esc_html__('Switch to this user', 'fb').'</a></li>
									'.(is_payment_enabled() ? '<li><a href="'.esc_html(url('?page=admin-transactions&user='.urlencode($user['id']))).'">'.esc_html__('Transactions', 'fb').'</a></li>' : '').'
									'.(in_array($user['status'], array('active', 'inactive')) ? '<li><a href="#" data-status="'.esc_html($user['status']).'" data-id="'.esc_html($user['id']).'" data-doing="'.($user['status'] == 'active' ? esc_html__('Deactivating...', 'fb') : esc_html__('Activating...', 'fb')).'" onclick="return user_status_toggle(this);">'.($user['status'] == 'active' ? esc_html__('Deactivate', 'fb') : esc_html__('Activate', 'fb')).'</a></li>' : '').'
									<li class="item-menu-line"></li>
									<li><a href="#" data-id="'.esc_html($user['id']).'" data-doing="'.esc_html__('Deleting...', 'fb').'" onclick="return user_delete(this);">'.esc_html__('Delete', 'fb').'</a></li>
								</ul>
							</div>
						</td>
					</tr>';
			}
		}
		$content .= '
					<tr class="table-empty"'.(sizeof($users) > 0 ? ' style="display: none;"' : '').'><td colspan="'.(MEMBERSHIP_ENABLE ? '7' : '6').'">'.((strlen($search_query) > 0) ? esc_html__('No results found for', 'fb').' "<strong>'.esc_html($search_query).'</strong>".' : esc_html__('List is empty.', 'fb')).'</td></tr>
				</tbody>
			</table>
		</div>
		<div class="table-funcbar">
			<div class="table-pageswitcher">'.$switcher.'</div>
			<div class="table-buttons">
				<div class="multi-button multi-button-small">
					<span>'.esc_html__('Bulk actions', 'fb').'<i class="fas fa-angle-down"></i></span>
					<ul>
						<li><a href="#" data-action="activate" data-doing="'.esc_html__('Activating...', 'fb').'" onclick="return users_bulk_action(this);">'.esc_html__('Activate', 'fb').'</a></li>
						<li><a href="#" data-action="deactivate" data-doing="'.esc_html__('Deactivating...', 'fb').'" onclick="return users_bulk_action(this);">'.esc_html__('Deactivate', 'fb').'</a></li>
						<li><a href="#" data-action="delete" data-doing="'.esc_html__('Deleting...', 'fb').'" onclick="return users_bulk_delete(this);">'.esc_html__('Delete', 'fb').'</a></li>
					</ul>
				</div>
				<a href="'.url('?page=admin-add-user').'" class="button button-small"><i class="fas fa-plus"></i><span>'.esc_html__('Create New User', 'fb').'</span></a>
			</div>
		</div>';
		return array('title' => $page_title, 'content' => $content);
	}
	
	function ajax_user_delete() {
		global $wpdb, $options, $user_details, $language;

		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Please enter your account to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		} else if ($user_details['role'] != 'admin') {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('You do not have permissions to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
	
		$user_id = trim(stripslashes($_REQUEST['user-id']));
		
		$user = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."users WHERE id = '".esc_sql($user_id)."' AND deleted != '1'", ARRAY_A);
		if (empty($user)) {
			$return_object = array('status' => 'ERROR', 'message' => esc_html__('User not found.', 'fb'));
			echo json_encode($return_object);
			exit;
		} else if ($user['id'] == $user_details['id']) {
			$return_object = array('status' => 'ERROR', 'message' => esc_html__('You can not delete yourself.', 'fb'));
			echo json_encode($return_object);
			exit;
		}
	
		$wpdb->query("UPDATE ".$wpdb->prefix."users SET deleted = '1' WHERE id = '".esc_sql($user_id)."'");
		$wpdb->query("UPDATE ".$wpdb->prefix."user_connections SET deleted = '1' WHERE user_id = '".esc_sql($user_id)."' AND deleted != '1'");
	
		$return_object = array('status' => 'OK', 'message' => esc_html__('User successfully deleted.', 'fb'));
		echo json_encode($return_object);
		exit;
	}

	function ajax_user_status_toggle() {
		global $wpdb, $options, $user_details, $language;
		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Please enter your account to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		} else if ($user_details['role'] != 'admin') {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('You do not have permissions to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
		
		$user_id = trim(stripslashes($_REQUEST['user-id']));
		$status = trim(stripslashes($_REQUEST['status']));
		
		$user = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."users WHERE id = '".esc_sql($user_id)."' AND deleted != '1'", ARRAY_A);
		if (empty($user)) {
			$return_object = array('status' => 'ERROR', 'message' => esc_html__('User not found.', 'fb'));
			echo json_encode($return_object);
			exit;
		} else if ($user['id'] == $user_details['id']) {
			$return_object = array('status' => 'ERROR', 'message' => esc_html__('You can not deactivate yourself.', 'fb'));
			echo json_encode($return_object);
			exit;
		}
	
		if ($status == 'active') {
			$wpdb->query("UPDATE ".$wpdb->prefix."users SET status = 'inactive' WHERE id = '".esc_sql($user_id)."'");
			$wpdb->query("UPDATE ".$wpdb->prefix."sessions SET valid_period = '0' WHERE user_id = '".esc_sql($user_id)."' AND registered + valid_period > '".esc_sql(time())."'");
			$return_object = array(
				'status' => 'OK',
				'message' => esc_html__('User successfully deactivated.', 'fb'),
				'user_action' => esc_html__('Activate', 'fb'),
				'user_action_doing' => esc_html__('Activating...', 'fb'),
				'user_status' => 'inactive',
				'user_status_label' => esc_html__('Inactive', 'fb')
			);
		} else {
			$wpdb->query("UPDATE ".$wpdb->prefix."users SET status = 'active' WHERE id = '".esc_sql($user_id)."'");
			$return_object = array(
				'status' => 'OK',
				'message' => esc_html__('User successfully activated.', 'fb'),
				'user_action' => esc_html__('Deactivate', 'fb'),
				'user_action_doing' => esc_html__('Deactivating...', 'fb'),
				'user_status' => 'active',
				'user_status_label' => esc_html__('Active', 'fb')
			);
		}
	
		echo json_encode($return_object);
		exit;
	}
	
	function ajax_users_delete() {
		global $wpdb, $options, $user_details, $language;

		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Please enter your account to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		} else if ($user_details['role'] != 'admin') {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('You do not have permissions to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
	
		$records = array();
		if (array_key_exists('records', $_REQUEST) && is_array($_REQUEST['records'])) {
			foreach ($_REQUEST['records'] as $record_id) {
				$records[] = intval($record_id);
			}
		}
		if (empty($records)) {
			$return_object = array('status' => 'ERROR', 'message' => esc_html__('No users selected.', 'fb'));
			echo json_encode($return_object);
			exit;
		}
	
		$wpdb->query("UPDATE ".$wpdb->prefix."users SET deleted = '1' WHERE deleted != '1' AND id IN ('".implode("','", $records)."') AND id != '".esc_sql($user_details['id'])."'");
		$wpdb->query("UPDATE ".$wpdb->prefix."user_connections SET deleted = '1' WHERE deleted != '1' AND user_id IN ('".implode("','", $records)."') AND user_id != '".esc_sql($user_details['id'])."'");
	
		$_SESSION['success-message'] = esc_html__('Selected users successfully deleted.', 'fb');
		$return_object = array('status' => 'OK', 'message' => esc_html__('Selected users successfully deleted.', 'fb'));
		echo json_encode($return_object);
		exit;
	}

	function ajax_users_activate() {
		global $wpdb, $options, $user_details, $language;

		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Please enter your account to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		} else if ($user_details['role'] != 'admin') {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('You do not have permissions to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
	
		$records = array();
		if (array_key_exists('records', $_REQUEST) && is_array($_REQUEST['records'])) {
			foreach ($_REQUEST['records'] as $record_id) {
				$records[] = intval($record_id);
			}
		}
		if (empty($records)) {
			$return_object = array('status' => 'ERROR', 'message' => esc_html__('No users selected.', 'fb'));
			echo json_encode($return_object);
			exit;
		}
	
		$wpdb->query("UPDATE ".$wpdb->prefix."users SET status = 'active' WHERE deleted != '1' AND id IN ('".implode("','", $records)."') AND id != '".esc_sql($user_details['id'])."'");
	
		$_SESSION['success-message'] = esc_html__('Selected users successfully activated.', 'fb');
		$return_object = array('status' => 'OK', 'message' => esc_html__('Selected users successfully activated.', 'fb'));
		echo json_encode($return_object);
		exit;
	}

	function ajax_users_deactivate() {
		global $wpdb, $options, $user_details, $language;

		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Please enter your account to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		} else if ($user_details['role'] != 'admin') {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('You do not have permissions to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
	
		$records = array();
		if (array_key_exists('records', $_REQUEST) && is_array($_REQUEST['records'])) {
			foreach ($_REQUEST['records'] as $record_id) {
				$records[] = intval($record_id);
			}
		}
		if (empty($records)) {
			$return_object = array('status' => 'ERROR', 'message' => esc_html__('No users selected.', 'fb'));
			echo json_encode($return_object);
			exit;
		}
	
		$wpdb->query("UPDATE ".$wpdb->prefix."users SET status = 'inactive' WHERE deleted != '1' AND id IN ('".implode("','", $records)."') AND id != '".esc_sql($user_details['id'])."'");
		$wpdb->query("UPDATE ".$wpdb->prefix."sessions SET valid_period = '0' WHERE user_id IN ('".implode("','", $records)."') AND user_id != '".esc_sql($user_details['id'])."' AND registered + valid_period > '".esc_sql(time())."'");
	
		$_SESSION['success-message'] = esc_html__('Selected users successfully deactivated.', 'fb');
		$return_object = array('status' => 'OK', 'message' => esc_html__('Selected users successfully deactivated.', 'fb'));
		echo json_encode($return_object);
		exit;
	}

	function page_add_user() {
		global $wpdb, $options, $user_details, $language, $free_membership;

		if (array_key_exists('id', $_GET)) $user_id = intval($_GET["id"]);
		else $user_id = 0;

		if (empty($user_details)) {
			header("Location: ".url('login.php').'?redirect='.urlencode(url('?page=admin-add-user'.($user_id > 0 ? '&id='.$user_id : ''))));
			exit;
		} else if ($user_details['role'] != 'admin') {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}

		$user = $wpdb->get_row("SELECT t1.*, t2.id AS m_id FROM ".$wpdb->prefix."users t1 
				LEFT JOIN ".$wpdb->prefix."memberships t2 ON t2.id = t1.membership_id AND t2.deleted != '1'
			WHERE t1.id = '".esc_sql($user_id)."' AND t1.deleted != '1'", ARRAY_A);

		$content = '';
		$page_title = empty($user) ? esc_html__('Create User', 'fb') : esc_html__('Edit User', 'fb');
		$content .= '
		<h1>'.$page_title.'</h1>
		<div class="form" id="user-form">
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('User role', 'fb').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Set user role.', 'fb').'
					</div>
				</div>
				<div class="form-content">
					<div class="input-box">
						<div class="bar-selector">
							<input class="radio" id="role-admin" type="radio" name="role" value="admin"'.($user_id > 0 && $user['role'] == 'admin' ? ' checked="checked"' : '').'><label for="role-admin">'.esc_html__('Administrator', 'fb').'</label><input class="radio" id="role-user" type="radio" name="role" value="user"'.($user_id == 0 || $user['role'] != 'admin' ? ' checked="checked"' : '').'><label for="role-user">'.esc_html__('User', 'fb').'</label>
						</div>
					</div>
				</div>
			</div>
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Email', 'fb').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Specify email address of the user.', 'fb').'
					</div>
				</div>
				<div class="form-content">
					<input type="text" name="email" placeholder="'.esc_html__('Email address', 'fb').'" value="'.(!empty($user) ? esc_html($user['email']) : '').'">
				</div>
			</div>
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Full name', 'fb').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Specify full name of the user.', 'fb').'
					</div>
				</div>
				<div class="form-content">
					<div class="input-box">
						<input class="errorable" type="text" name="name" placeholder="'.esc_html__('Full name', 'fb').'" value="'.(!empty($user) ? esc_html($user['name']) : '').'">
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
						'.esc_html__('Select timezone of the user.', 'fb').'
					</div>
				</div>
				<div class="form-content">
					<div class="input-box">
						<select class="errorable" name="timezone">
							'.timezone_choice(!empty($user) ? $user['timezone'] : 'UTC').'
						</select>
					</div>
				</div>
			</div>
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Password', 'fb').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Set password of the user.', 'fb').'
					</div>
				</div>
				<div class="form-content">
					<div class="columns">
						<div class="column column-50">
							<div class="input-box">
								<input class="errorable" type="password" name="password" placeholder="'.esc_html__('Password', 'fb').'" value="">
							</div>
						</div>
						<div class="column column-50">
							<div class="input-box">
								<input class="errorable" type="password" name="repeat-password" placeholder="'.esc_html__('Repeat password', 'fb').'" value="">
							</div>
						</div>
					</div>
				</div>
			</div>';
		if (MEMBERSHIP_ENABLE) {
			$title = translatable_parse($free_membership['title']);
			$free_membership_title = (array_key_exists($language, $title) && !empty($title[$language]) ? $title[$language] : $title['default']);
			$memberships = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."memberships WHERE deleted != '1' ORDER BY seq ASC", ARRAY_A);
			$content .= '
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Membership', 'fb').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Select membership.', 'fb').'
					</div>
				</div>
				<div class="form-content">
					<div class="columns">
						<div class="column column-40">
							<div class="input-box">
								<div class="input-element">
									<select class="errorable" name="membership" onchange="membership_expiration_handle(this);">
										<option value="0"'.(!empty($user) && ($user['m_id'] == '0' || empty($user['m_id'])) ? ' selected="selected"' : '').'>'.esc_html($free_membership_title).'</option>';
			foreach ($memberships as $membership) {
				$title = translatable_parse($membership['title']);
				$membership_title = (array_key_exists($language, $title) && !empty($title[$language]) ? $title[$language] : $title['default']);
				$content .= '
										<option value="'.esc_html($membership['id']).'"'.(!empty($user) && $user['m_id'] == $membership['id'] ? ' selected="selected"' : '').'>'.esc_html($membership_title).'</option>';
			}
			$content .= '
									</select>
								</div>
								<label>'.esc_html__('Select membership', 'fb').'</label>
							</div>
						</div>
						<div class="column column-30">
							<div class="input-box membership-never-expires"'.(empty($user) || $user['membership_id'] == '0' ? ' style="display: none;"' : '').'>
								<div class="checkbox-toggle-container">
									<input class="checkbox-toggle" type="checkbox" value="on" id="membership-never-expires" name="membership-never-expires"'.(empty($user) || $user['membership_expires'] == 0 ? ' checked="checked"' : '').' onchange="membership_expiration_handle(this);">
									<label for="membership-never-expires"></label>
									<span>'.esc_html__('Never expires', 'fb').'</span>
								</div>
							</div>
						</div>
						<div class="column column-30">
							<div class="input-box membership-expires"'.(empty($user) || $user['membership_id'] == '0' || $user['membership_expires'] == '0' ? ' style="display: none;"' : '').'>
								<div class="input-element">
									<input class="errorable date" type="text" name="membership-expires" placeholder="'.esc_html__('Expiration date', 't').'" value="'.(!empty($user) && $user['membership_expires'] > 0 ? timestamp_string($user['membership_expires'], $options['date-format']) : timestamp_string(time()+3600*24*30, $options['date-format'])).'" data-default="'.(!empty($user) && $user['membership_expires'] > 0 ? timestamp_string($user['membership_expires'], 'Y-m-d') : timestamp_string(time()+3600*24*30, 'Y-m-d')).'" readonly="readonly">
								</div>
								<label>'.esc_html__('Expiration date', 'fb').'</label>
							</div>
						</div>
					</div>
				</div>
			</div>';
		}
		$content .= '
			<div class="form-row right-align">
				<input type="hidden" name="action" value="user-save">
				<input type="hidden" name="id" value="'.(!empty($user) ? esc_html($user['id']) : '0').'">
				<a class="button" href="#" onclick="return save_form(this);" data-label="'.esc_html__('Save Details', 'fb').'">
					<span>'.esc_html__('Save Details', 'fb').'</span>
					<i class="fas fa-angle-right"></i>
				</a>
			</div>
		</div>';
		return array('title' => $page_title, 'content' => $content);
	}

	function ajax_user_save() {
		global $wpdb, $options, $user_details, $language, $gmt_offset;

		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Please enter your account to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		} else if ($user_details['role'] != 'admin') {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('You do not have permissions to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
		$role = trim(stripslashes($_REQUEST['role']));
		$timezone = trim(stripslashes($_REQUEST['timezone']));
		$name = trim(stripslashes($_REQUEST['name']));
		$email = trim(stripslashes($_REQUEST['email']));
		$password = trim(stripslashes($_REQUEST['password']));
		$repeat_password = trim(stripslashes($_REQUEST['repeat-password']));
		$user_id = intval($_REQUEST['id']);
	
		if ($user_id > 0) {
			$user = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."users WHERE id = '".esc_sql($user_id)."' AND deleted != '1'", ARRAY_A);
			if (empty($user)) {
				$return_data = array('status' => 'WARNING', 'message' => esc_html__('User does not exist.', 'fb'));
				echo json_encode($return_data);
				exit;
			}
			if ($user_details['id'] == $user['id'] && $user['role'] != $role) {
				$return_data = array('status' => 'WARNING', 'message' => esc_html__('You can not change your own role.', 'fb'));
				echo json_encode($return_data);
				exit;
			}
		}
	
		$errors = array();
		$timezone_offset = timezone_offset($timezone);
		if ($timezone_offset === false) $errors['timezone'] = esc_html__('Invalid timezone.', 'fb');
	
		if (mb_strlen($name) < 2) $errors['name'] = esc_html__('The full name is too short.', 'fb');
		else if (mb_strlen($name) > 127) $errors['name'] = esc_html__('The full name is too long.', 'fb');
		
		if (!empty($password) || $user_id == 0) {
			if (mb_strlen($password) < 6) $errors['password'] = esc_html__('The password must be at least 6 characters long.', 'fb');
			else if ($password != $repeat_password) $errors['repeat-password'] = esc_html__('Repeat the password properly.', 'fb');
		}
	
		if (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,19})$/i", $email) || empty($email)) $errors['email'] = esc_html__('This is not a valid email address.', 'fb');
		else if (mb_strlen($email) > 127) $errors['email'] = esc_html__('The email address is too long.', 'fb');
		else {
			$login = create_login($email);
			$user = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."users WHERE login = '".esc_sql($login)."' AND deleted != '1'".($user_id > 0 ? " AND id != '".esc_sql($user_id)."'" : ''), ARRAY_A);
			if (!empty($user)) $errors['email'] = esc_html__('The email already registered.', 'fb');
		}
		if (!in_array($role, array('admin', 'user'))) $errors['role'] = esc_html__('Invalid user role.', 'fb');

		if (MEMBERSHIP_ENABLE) {
			$membership_id = intval($_REQUEST['membership']);
			$membership_expires = 0;
			if ($membership_id != 0) {
				$membership = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."memberships WHERE id = '".esc_sql($membership_id)."' AND deleted != '1'", ARRAY_A);
				if (empty($membership)) $errors['membership'] = esc_html__('Invalid membership.', 'fb');
				if (!array_key_exists('membership-never-expires', $_REQUEST)) {
					$date = validate_date($_REQUEST['membership-expires'].' 23:59:59', $options['date-format'].' H:i:s');
					$current_date = new DateTime();
					if ($date === false) $errors['membership-expires'] = esc_html__('Invalid date.', 't');
					else if ($date < $current_date) $errors['membership-expires'] = esc_html__('Invalid date.', 't');
					else $membership_expires = $date->getTimestamp()-3600*$gmt_offset;
				}
			}
		} else {
			$membership_id = 0;
			$membership_expires = 0;
		}
		
		if (!empty($errors)) {
			$return_data = array('status' => 'ERROR', 'errors' => $errors);
			echo json_encode($return_data);
			exit;
		}
		if ($user_id > 0) {
			$wpdb->query("UPDATE ".$wpdb->prefix."users SET 
					role = '".esc_sql($role)."', 
					login = '".esc_sql($login)."', 
					email = '".esc_sql($email)."', 
					timezone = '".esc_sql($timezone)."', 
					name = '".esc_sql($name)."'".
					(MEMBERSHIP_ENABLE ? ", membership_id = '".esc_sql($membership_id)."', membership_expires = '".esc_sql($membership_expires)."'".($membership_id == 0 ? ", membership_txn_id = '0'" : "") : "").
					(!empty($password) ? ", password = '".esc_sql(password_hash($password, PASSWORD_DEFAULT))."'" : "")."
				WHERE id = '".esc_sql($user_id)."'");
		} else {
			$wpdb->query("INSERT INTO ".$wpdb->prefix."users (
					uuid, 
					login, 
					password, 
					email, 
					name, 
					role, 
					status, 
					timezone, 
					options,
					email_confirmed,
					email_confirmation_uid, 
					password_reset_uid,
					membership_id,
					membership_expires,
					membership_txn_id,
					deleted, 
					created
				) VALUES (
					'".esc_sql(uuid_v4())."',
					'".esc_sql($login)."',
					'".esc_sql(password_hash($password, PASSWORD_DEFAULT))."',
					'".esc_sql($email)."',
					'".esc_sql($name)."',
					'".esc_sql($role)."',
					'active',
					'".esc_sql($timezone)."',
					'',
					'1',
					'".esc_sql(uuid_v4())."',
					'".esc_sql(uuid_v4())."',
					'".esc_sql($membership_id)."',
					'".esc_sql($membership_expires)."',
					'0',
					'0',
					'".time()."'
				)");
		}
		$_SESSION['success-message'] = esc_html__('User details successfully saved.', 'fb');
		$return_object = array('status' => 'OK', 'message' => esc_html__('User details successfully saved.', 'fb'), 'url' => url('?page=admin-users'));
		echo json_encode($return_object);
		exit;
	}

	function page_memberships() {
		global $wpdb, $options, $user_details, $language, $free_membership;

		if (empty($user_details)) {
			header("Location: ".url('login.php').'?redirect='.urlencode(url('?page=admin-memberships')));
			exit;
		} else if ($user_details['role'] != 'admin') {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}

		$memberships = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."memberships WHERE deleted != '1' ORDER BY seq ASC", ARRAY_A);

		$content = '';
		$page_title = esc_html__('Memberships', 'fb');

		$title = translatable_parse($free_membership['title']);
		$free_title = (array_key_exists($language, $title) && !empty($title[$language]) ? esc_html($title[$language]) : esc_html($title['default']));
		$description = translatable_parse($free_membership['description']);
		$free_description = (array_key_exists($language, $description) && !empty($description[$language]) ? $description[$language] : $description['default']);
		$footer = translatable_parse($free_membership['footer']);
		$free_footer = (array_key_exists($language, $footer) && !empty($footer[$language]) ? $footer[$language] : $footer['default']);

		$content .= '<h1>'.$page_title.'</h1>
		<div class="table-funcbar">
			<div class="table-buttons"><a href="'.esc_html(url('?page=admin-add-membership')).'" class="button button-small"><i class="fas fa-plus"></i><span>'.esc_html__('Create New Membership', 'fb').'</span></a></div>
		</div>
		<div class="memberships memberships-sortable">
			<div class="membership-panel membership-panel-gray membership-panel-free" data-id="0">
			<a href="'.esc_html(url('?page=admin-edit-free-membership')).'"></a>
			<div class="membership-panel-header">
				<h2>'.$free_title.'</h2>
			</div>
			<div class="membership-panel-body">';
		if (is_array($free_membership['features']) && !empty($free_membership['features'])) {
			$content .= '
				<ul class="membership-panel-features">';
			foreach ($free_membership['features'] as $feature) {
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
		} else if (!empty($free_description)) {
			$content .= '
				<div class="membership-panel-description">
					<p>'.esc_html($free_description).'</p>
				</div>';
		}
		$content .= '
			</div>
			<div class="membership-panel-footer">
				<div class="membership-panel-footer-label">
					'.esc_html($free_footer).'
				</div>
			</div>
		</div>';

		if (sizeof($memberships) > 0) {
			foreach ($memberships as $membership) {
				$title = translatable_parse($membership['title']);
				$description = translatable_parse($membership['description']);
				$membership_description = (array_key_exists($language, $description) && !empty($description[$language]) ? $description[$language] : $description['default']);
				$footer = translatable_parse($membership['footer']);
				$membership_footer = (array_key_exists($language, $footer) && !empty($footer[$language]) ? $footer[$language] : $footer['default']);
				$content .= '
			<div class="membership-panel membership-panel-'.esc_html($membership['color']).' membership-panel-'.esc_html($membership['status']).'" data-id="'.esc_html($membership['id']).'">
				<a href="'.esc_html(url('?page=admin-add-membership&id='.esc_html($membership['id']))).'"></a>
				<div class="membership-panel-actions">
					'.(in_array($membership['status'], array('active', 'archive')) ? 
					'<a data-id="'.esc_html($membership['id']).'" class="tooltipster single-action membership-archive-icon" href="#" onclick="return membership_archive(this);" title="'.esc_html__('Archive membership', 'fb').'"><i class="fas fa-file-import"></i></a>
					 <a data-id="'.esc_html($membership['id']).'" class="tooltipster single-action membership-activate-icon" href="#" onclick="return membership_activate(this);" title="'.esc_html__('Activate membership', 'fb').'"><i class="fas fa-file-export"></i></a>' :
					'<a data-id="'.esc_html($membership['id']).'" class="tooltipster single-action" href="#" onclick="return membership_delete(this);" title="'.esc_html__('Delete membership', 'fb').'"><i class="far fa-trash-alt"></i></a>').'
				</div>			
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
					<div class="membership-panel-footer-label">
						'.esc_html($membership_footer).'
					</div>
				</div>
			</div>';
			}
		}
		$content .= '
		</div>';

		return array('title' => $page_title, 'content' => $content);
	}
	
	function ajax_memberships_save_list() {
		global $wpdb, $options, $user_details, $language;

		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Please enter your account to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		} else if ($user_details['role'] != 'admin') {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('You do not have permissions to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
		if (!is_array($_REQUEST['memberships']) || empty($_REQUEST['memberships'])) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Invalid request.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
		$seq = 0;
		foreach ($_REQUEST['memberships'] as $membership_id) {
			$membership_id = intval($membership_id);
			$wpdb->query("UPDATE ".$wpdb->prefix."memberships SET seq = '".esc_sql($seq)."' WHERE id = '".esc_sql($membership_id)."'");
			$seq++;
		}
		$return_object = array('status' => 'OK', 'message' => esc_html__('Saved.', 'fb'));
		echo json_encode($return_object);
		exit;
	}

	function ajax_membership_delete() {
		global $wpdb, $options, $user_details, $language;

		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Please enter your account to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		} else if ($user_details['role'] != 'admin') {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('You do not have permissions to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
	
		$membership_id = intval($_REQUEST['membership-id']);
		
		$membership = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."memberships WHERE id = '".esc_sql($membership_id)."' AND deleted != '1'", ARRAY_A);
		if (empty($membership)) {
			$return_object = array('status' => 'ERROR', 'message' => esc_html__('Membership not found.', 'fb'));
			echo json_encode($return_object);
			exit;
		} else if ($membership['status'] != 'new') {
			$return_object = array('status' => 'ERROR', 'message' => esc_html__('Membership can not be removed.', 'fb'));
			echo json_encode($return_object);
			exit;
		}
	
		$wpdb->query("UPDATE ".$wpdb->prefix."memberships SET deleted = '1' WHERE id = '".esc_sql($membership_id)."'");
	
		$return_object = array('status' => 'OK', 'message' => esc_html__('Membership successfully deleted.', 'fb'));
		echo json_encode($return_object);
		exit;
	}
	
	function ajax_membership_archive() {
		global $wpdb, $options, $user_details, $language;

		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Please enter your account to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		} else if ($user_details['role'] != 'admin') {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('You do not have permissions to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
	
		$membership_id = intval($_REQUEST['membership-id']);
		
		$membership = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."memberships WHERE id = '".esc_sql($membership_id)."' AND deleted != '1'", ARRAY_A);
		if (empty($membership)) {
			$return_object = array('status' => 'ERROR', 'message' => esc_html__('Membership not found.', 'fb'));
			echo json_encode($return_object);
			exit;
		} else if ($membership['status'] != 'active') {
			$return_object = array('status' => 'ERROR', 'message' => esc_html__('Membership can not be archived.', 'fb'));
			echo json_encode($return_object);
			exit;
		}
	
		$wpdb->query("UPDATE ".$wpdb->prefix."memberships SET status = 'archive' WHERE id = '".esc_sql($membership_id)."'");
	
		$return_object = array('status' => 'OK', 'message' => esc_html__('Membership successfully archived.', 'fb'));
		echo json_encode($return_object);
		exit;
	}

	function ajax_membership_activate() {
		global $wpdb, $options, $user_details, $language;

		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Please enter your account to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		} else if ($user_details['role'] != 'admin') {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('You do not have permissions to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
	
		$membership_id = intval($_REQUEST['membership-id']);
		
		$membership = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."memberships WHERE id = '".esc_sql($membership_id)."' AND deleted != '1'", ARRAY_A);
		if (empty($membership)) {
			$return_object = array('status' => 'ERROR', 'message' => esc_html__('Membership not found.', 'fb'));
			echo json_encode($return_object);
			exit;
		} else if ($membership['status'] != 'archive') {
			$return_object = array('status' => 'ERROR', 'message' => esc_html__('Membership can not be activated.', 'fb'));
			echo json_encode($return_object);
			exit;
		}

		$membership_prices = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."membership_prices WHERE membership_id = '".esc_sql($membership['id'])."' AND deleted != '1' AND status != 'archive'", ARRAY_A);
		if (sizeof($membership_prices) == 0) {
			$return_object = array('status' => 'ERROR', 'message' => esc_html__('Membership can not be activated. No available price options.', 'fb'));
			echo json_encode($return_object);
			exit;
		}
	
		$wpdb->query("UPDATE ".$wpdb->prefix."memberships SET status = 'active' WHERE id = '".esc_sql($membership_id)."'");
	
		$return_object = array('status' => 'OK', 'message' => esc_html__('Membership successfully activated.', 'fb'));
		echo json_encode($return_object);
		exit;
	}

	function page_add_membership() {
		global $wpdb, $options, $user_details, $language, $membership_colors, $membership_feature_bullets, $membership_price_statuses, $currency_list, $membership_billing_periods;

		if (array_key_exists('id', $_GET)) $membership_id = intval($_GET["id"]);
		else $membership_id = 0;

		if (empty($user_details)) {
			header("Location: ".url('login.php').'?redirect='.urlencode(url('?page=admin-add-membership'.($membership_id > 0 ? '&id='.$membership_id : ''))));
			exit;
		} else if ($user_details['role'] != 'admin') {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}

		$membership = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."memberships WHERE id = '".esc_sql($membership_id)."' AND deleted != '1' ORDER BY seq ASC", ARRAY_A);
		$membership_prices = array();
		$membership_features = array();
		if (!empty($membership)) {
			$membership_features = json_decode($membership['features'], true);
			if (!is_array($membership_features)) $membership_features = array();
			$membership_prices = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."membership_prices WHERE membership_id = '".esc_sql($membership['id'])."' AND deleted != '1' ORDER BY seq ASC", ARRAY_A);
		}
		
		$content = '';
		$page_title = empty($membership) ? esc_html__('Create Membership', 'fb') : esc_html__('Edit Membership', 'fb');

		$content .= '
		<h1>'.$page_title.'</h1>
		<div class="form" id="membership-form">
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Color scheme', 'fb').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Specify the color scheme of the membership.', 'fb').'
					</div>
				</div>
				<div class="form-content">
					<form class="membership-color-selector">';
		foreach ($membership_colors as $color) {
			$content .= '
						<input'.((!empty($membership) && $membership['color'] == $color) || (empty($membership) && $color == "gray") ? ' checked="checked"' : '').' type="radio" name="color" id="color-'.esc_html($color).'" value="'.esc_html($color).'" /><label class="background-'.esc_html($color).'" for="color-'.esc_html($color).'"></label>';
		}
		$content .= '
					</form>
				</div>
			</div>
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Title', 'fb').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Specify the title of the membership.', 'fb').'
					</div>
				</div>
				<div class="form-content">
					'.translatable_input_html('title', (!empty($membership) ? $membership['title'] : ''), esc_html__('Membership title', 'fb')).'
				</div>
			</div>
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Description', 'fb').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Specify the description of the membership. It appears if list of features not specified.', 'fb').'
					</div>
				</div>
				<div class="form-content">
					'.translatable_textarea_html('description', (!empty($membership) ? $membership['description'] : ''), esc_html__('Describe membership...', 'fb')).'
				</div>
			</div>
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Features', 'fb').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Specify the list of membership features.', 'fb').'
					</div>
				</div>
				<div class="form-content">
					<div class="membership-features">';
		if (sizeof($membership_features) > 0) {
			$i = 0;
			foreach ($membership_features as $membership_feature) {
				$content .= '
						<div class="membership-feature">
							<div class="table-column-60" data-label="'.esc_html__('Bullet', 'fb').'">
								<div class="input-box">
									<form class="bar-selector">';
				foreach ($membership_feature_bullets as $bullet) {
					$content .= '<input'.($membership_feature['bullet'] == $bullet ? ' checked="checked"' : '').' class="radio" id="bullet-'.$i.'-'.esc_html($bullet).'" type="radio" name="bullet" value="'.esc_html($bullet).'"><label for="bullet-'.$i.'-'.esc_html($bullet).'"><i class="fas fa-'.esc_html($bullet).'"></i></label>';
					$i++;
				}
				$content .= '
									</form>
									<label>'.esc_html__('Bullet icon', 'fb').'</label>
								</div>
							</div>
							<div data-label="'.esc_html__('Label', 'fb').'">
								'.translatable_input_html('feature-label', $membership_feature['label'], esc_html__('Describe feature shortly...', 'fb')).'
							</div>
							<div class="table-column-60"><a class="tooltipster single-action" href="#" onclick="return membership_feature_delete(this);" title="'.esc_html__('Delete feature', 'fb').'"><i class="far fa-trash-alt"></i></a></div>
						</div>';
			}
		}
		$content .= '
						<div class="membership-feature membership-feature-template">
							<div class="table-column-60" data-label="'.esc_html__('Bullet', 'fb').'">
								<div class="input-box">
									<form class="bar-selector">';
								foreach ($membership_feature_bullets as $bullet) {
									$content .= '<input'.($bullet == 'check' ? ' checked="checked"' : '').' class="radio" id="bullet-'.esc_html($bullet).'" type="radio" name="bullet" value="'.esc_html($bullet).'"><label for="bullet-'.esc_html($bullet).'"><i class="fas fa-'.esc_html($bullet).'"></i></label>';
								}
								$content .= '
									</form>
									<label>'.esc_html__('Bullet icon', 'fb').'</label>
								</div>
							</div>
							<div data-label="'.esc_html__('Label', 'fb').'">
								'.translatable_input_html('feature-label', '', esc_html__('Describe feature shortly...', 'fb')).'
							</div>
							<div class="table-column-60"><a class="tooltipster single-action" href="#" onclick="return membership_feature_delete(this);" title="'.esc_html__('Delete feature', 'fb').'"><i class="far fa-trash-alt"></i></a></div>
						</div>
					</div>
					<a href="#" class="button2" onclick="membership_feature_add(this); return false;"><i class="fas fa-plus"></i>'.esc_html__('Add feature', 'fb').'</a>
				</div>
			</div>
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Footer', 'fb').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Specify the footer of the membership. It appears at the bottom of the membership panel. Ex.: 19.95 USD / month', 'fb').'
					</div>
				</div>
				<div class="form-content">
					'.translatable_input_html('footer', (!empty($membership) ? $membership['footer'] : ''), esc_html__('Footer label', 'fb')).'
				</div>
			</div>
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Price options', 'fb').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Specify price options for the membership. Price optoins might have the following statuses.', 'fb').'<br />
						<strong>'.esc_html__($membership_price_statuses['new']).'</strong> - '.esc_html__('Active option but never used by users. You can delete it.', 'fb').'<br />
						<strong>'.esc_html__($membership_price_statuses['active']).'</strong> - '.esc_html__('Active option and used by users.', 'fb').'<br />
						<strong>'.esc_html__($membership_price_statuses['archive']).'</strong> - '.esc_html__('Not available option and used by users earlier.', 'fb').'
					</div>
				</div>
				<div class="form-content">
					<div class="membership-prices">';
		if (sizeof($membership_prices) > 0) {
			foreach ($membership_prices as $membership_price) {
				$status = array_key_exists($membership_price['status'], $membership_price_statuses) ? $membership_price['status'] : 'new';
				$content .= '
					<div class="membership-price membership-price-'.esc_html($status).'" data-id="'.esc_html($membership_price['id']).'" data-status-label-active="'.esc_html($membership_price_statuses['active']).'" data-status-label-archive="'.esc_html($membership_price_statuses['archive']).'">
						<div class="membership-status">
							<span>'.esc_html($membership_price_statuses[$status]).'</span>
						</div>
						<div data-label="'.esc_html__('Title', 'fb').'">
							'.translatable_input_html('price-title', $membership_price['title'], esc_html__('Option title (ex., Save 20%)...', 'fb')).'
						</div>
						<div class="table-column-160" data-label="'.esc_html__('Price', 'fb').'">
							<div class="input-box">
								<div class="input-element input-price-element">
									<input class="errorable" type="text" name="price" placeholder="..." value="'.esc_html($membership_price['price']).'"'.($status != 'new' ? ' disabled readonly' : '').'>
									<select name="currency"'.($status != 'new' ? ' disabled readonly' : '').'>';
				foreach ($currency_list as $currency) {
					$content .= '
										<option value="'.esc_html($currency).'"'.($membership_price['currency'] == $currency ? ' selected="selected"' : '').'>'.esc_html($currency).'</option>';
				}
				$content .= '
									</select>
								</div>
								<label>'.esc_html__('Price', 'fb').'</label>
							</div>
						</div>
						<div class="table-column-180" data-label="'.esc_html__('Billing period', 'fb').'">
							<div class="input-box">
								<div class="input-element">
									<select name="billing-period"'.($status != 'new' ? ' disabled readonly' : '').'>';
				foreach ($membership_billing_periods as $billing_period => $billing_period_label) {
					$content .= '
										<option value="'.esc_html($billing_period).'"'.($membership_price['billing_period'] == $billing_period ? ' selected="selected"' : '').'>'.esc_html($billing_period_label['label']).'</option>';
				}
				$content .= '
									</select>
								</div>
								<label>'.esc_html__('Billing period', 'fb').'</label>
							</div>
						</div>
						<div class="table-column-60">
							'.(in_array($status, array('active', 'archive')) ? 
							'<a class="tooltipster single-action membership-archive-icon" href="#" onclick="return membership_price_archive(this);" title="'.esc_html__('Archive price option', 'fb').'"><i class="fas fa-file-import"></i></a>
							<a class="tooltipster single-action membership-activate-icon" href="#" onclick="return membership_price_activate(this);" title="'.esc_html__('Activate price option', 'fb').'"><i class="fas fa-file-export"></i></a>' :
							'<a class="tooltipster single-action" href="#" onclick="return membership_price_delete(this);" title="'.esc_html__('Delete price option', 'fb').'"><i class="far fa-trash-alt"></i></a>').'
						</div>
						<input type="hidden" name="status" value="'.esc_html($status).'">
					</div>';
			}
		} else {
		}
		$content .= '
					<div class="membership-price membership-price-new membership-price-template" data-id="0">
						<div class="membership-status">
							<span>'.esc_html($membership_price_statuses['new']).'</span>
						</div>
						<div data-label="'.esc_html__('Title', 'fb').'">
							'.translatable_input_html('price-title', '', esc_html__('Option title', 'fb')).'
						</div>
						<div class="table-column-160" data-label="'.esc_html__('Price', 'fb').'">
							<div class="input-box">
								<div class="input-element input-price-element">
									<input class="errorable" type="text" name="price" placeholder="..." value="">
									<select name="currency">';
						foreach ($currency_list as $currency) {
							$content .= '
										<option value="'.esc_html($currency).'">'.esc_html($currency).'</option>';
						}
						$content .= '
									</select>
								</div>
								<label>'.esc_html__('Price', 'fb').'</label>
							</div>
						</div>
						<div class="table-column-180" data-label="'.esc_html__('Billing period', 'fb').'">
							<div class="input-box">
								<div class="input-element">
									<select name="billing-period">';
						foreach ($membership_billing_periods as $billing_period => $billing_period_label) {
							$content .= '
										<option value="'.esc_html($billing_period).'"'.($billing_period == 'month' ? ' selected=""selected' : '').'>'.esc_html($billing_period_label['label']).'</option>';
						}
						$content .= '
									</select>
								</div>
								<label>'.esc_html__('Billing period', 'fb').'</label>
							</div>
						</div>
						<div class="table-column-60"><a class="tooltipster single-action" href="#" onclick="return membership_price_delete(this);" title="'.esc_html__('Delete price option', 'fb').'"><i class="far fa-trash-alt"></i></a></div>
						<input type="hidden" name="status" value="new">
					</div>
					</div>
					<a href="#" class="button2" onclick="membership_price_add(this); return false;"><i class="fas fa-plus"></i>'.esc_html__('Add price option', 'fb').'</a>
				</div>
			</div>
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Available functionality', 'fb').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Specify functionality which is available for this membership.', 'fb').'
					</div>
				</div>
				<div class="form-content membership-options">';
					$content .= apply_filters('membership-options-html', '', (!empty($membership) ? $membership['id'] : null));
					$content .= '
					<div class="membership-options-no">'.esc_html__('No additional functionality available.', 'fb').'</div>
				</div>
			</div>
			<div class="form-row right-align">
				<input type="hidden" name="action" value="membership-save">
				<input type="hidden" name="id" value="'.(!empty($membership) ? esc_html($membership['id']) : '0').'">
				<a class="button" href="#" onclick="return membership_save(this);" data-label="'.esc_html__('Save Details', 'fb').'">
					<span>'.esc_html__('Save Details', 'fb').'</span>
					<i class="fas fa-angle-right"></i>
				</a>
			</div>
		</div>';

		return array('title' => $page_title, 'content' => $content);
	}

	function ajax_membership_save() {
		global $wpdb, $options, $user_details, $language, $membership_colors, $membership_feature_bullets, $membership_price_statuses, $currency_list, $membership_billing_periods;

		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Please enter your account to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		} else if ($user_details['role'] != 'admin') {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('You do not have permissions to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
	
		if (!array_key_exists('price-options', $_REQUEST) || !is_array($_REQUEST['price-options']) || empty($_REQUEST['price-options'])) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Create at least one price option.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
	
		$title = translatable_populate($_REQUEST['title']);
		$description = translatable_populate($_REQUEST['description']);
		$footer = translatable_populate($_REQUEST['footer']);
		$membership_id = intval($_REQUEST['id']);
		$color = trim(stripslashes($_REQUEST['color']));
	
		$membership = null;
		if ($membership_id > 0) {
			$membership = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."memberships WHERE id = '".esc_sql($membership_id)."' AND deleted != '1'", ARRAY_A);
			if (empty($membership)) {
				$return_data = array('status' => 'WARNING', 'message' => esc_html__('Membership not found.', 'fb'));
				echo json_encode($return_data);
				exit;
			}
		}
	
		$errors = array();

		if (mb_strlen($title['default']) < 2) $errors['title[default]'] = esc_html__('Title is too short.', 'fb');
		else if (mb_strlen($title['default']) > 63) $errors['title[default]'] = esc_html__('Title is too long.', 'fb');
		foreach ($title as $key => $value) {
			if ($key != 'default') {
				if (mb_strlen($value) > 0 && mb_strlen($value) < 2) $errors['title['.$key.']'] = esc_html__('The translation is too short.', 'fb');
				else if (mb_strlen($value) > 63) $errors['title['.$key.']'] = esc_html__('The translation is too long.', 'fb');
			}
		}
		if (mb_strlen($description['default']) > 512) $errors['description[default]'] = esc_html__('Description is too long.', 'fb');
		foreach ($description as $key => $value) {
			if ($key != 'default') {
				if (mb_strlen($value) > 512) $errors['description['.$key.']'] = esc_html__('The translation is too long.', 'fb');
			}
		}
		if (mb_strlen($footer['default']) > 63) $errors['footer[default]'] = esc_html__('Footer is too long.', 'fb');
		foreach ($footer as $key => $value) {
			if ($key != 'default') {
				if (mb_strlen($value) > 63) $errors['footer['.$key.']'] = esc_html__('The translation is too long.', 'fb');
			}
		}
		if (!in_array($color, $membership_colors)) $errors['color'] = esc_html__('Invalid color scheme.', 'fb');
		$features = array();
		if (array_key_exists('features', $_REQUEST) && is_array($_REQUEST['features'])) {
			$seq = 0;
			foreach ($_REQUEST['features'] as $f) {
				$dom_id = trim(stripslashes($f['dom-id']));
				$feature = array(
					'label' => translatable_populate($f['label']),
					'bullet' => trim(stripslashes($f['bullet'])),
					'seq' => $seq
				);
				$seq++;
				if (mb_strlen($feature['label']['default']) > 63) $errors[$dom_id]['feature-label[default]'] = esc_html__('Label is too long.', 'fb');
				foreach ($feature['label'] as $key => $value) {
					if ($key != 'default') {
						if (mb_strlen($value) > 63) $errors[$dom_id]['feature-label['.$key.']'] = esc_html__('The translation is too long.', 'fb');
					}
				}
				if (!in_array($feature['bullet'], $membership_feature_bullets)) $errors[$dom_id]['bullet'] = esc_html__('The bullet is invalid.', 'fb');
				$features[] = $feature;
			}
		}
	
		$price_options = array('new' => array(), 'existing' => array());
		$seq = 0;
		foreach ($_REQUEST['price-options'] as $p_option) {
			$dom_id = trim(stripslashes($p_option['dom-id']));
			$price_option = array(
				'id' => intval($p_option['id']),
				'status' => trim(stripslashes($p_option['status'])),
				'title' => translatable_populate($p_option['title']),
				'price' => trim(stripslashes($p_option['price'])),
				'currency' => trim(stripslashes($p_option['currency'])),
				'billing-period' => trim(stripslashes($p_option['billing-period'])),
				'seq' => $seq
			);
			if ($price_option['id'] > 0) {
				$price_options['existing'][$price_option['id']] = $price_option;
			} else {
				$price_options['new'][] = $price_option;
			}
			$seq++;
			if (mb_strlen($price_option['title']['default']) > 31) $errors[$dom_id]['price-title[default]'] = esc_html__('Title is too long.', 'fb');
			foreach ($price_option['title'] as $key => $value) {
				if ($key != 'default') {
					if (mb_strlen($value) > 31) $errors[$dom_id]['price-title['.$key.']'] = esc_html__('The translation is too long.', 'fb');
				}
			}
			if (!is_numeric($price_option['price'])) $errors[$dom_id]['price'] = esc_html__('The price is invalid.', 'fb');
			else if ($price_option['price'] < 0.1) $errors[$dom_id]['price'] = esc_html__('The price is too low.', 'fb');
			else if ($price_option['price'] > 1000000) $errors[$dom_id]['price'] = esc_html__('The price is too high.', 'fb');
			if (!in_array($price_option['currency'], $currency_list)) $errors[$dom_id]['price'] = esc_html__('The currency is invalid.', 'fb');
			if (!array_key_exists($price_option['billing-period'], $membership_billing_periods)) $errors[$dom_id]['billing-period'] = esc_html__('The currency is invalid.', 'fb');
		}
	
		$errors = apply_filters('membership-options-check', $errors);

		if (!empty($errors)) {
			$return_data = array('status' => 'ERROR', 'errors' => $errors);
			echo json_encode($return_data);
			exit;
		}
	
		if (!empty($membership)) {
			$membership_prices = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."membership_prices WHERE membership_id = '".esc_sql($membership['id'])."' AND deleted != '1'", ARRAY_A);
			$available_price_option_exists = sizeof($price_options['new']) > 0;
			if (!empty($membership_prices)) {
				$price_ids = array();
				foreach($membership_prices as $membership_price) {
					if (array_key_exists($membership_price['id'], $price_options['existing'])) {
						if ($membership_price['status'] == "new") {
							$membership_price['price'] = $price_options['existing'][$membership_price['id']]['price'];
							$membership_price['currency'] = $price_options['existing'][$membership_price['id']]['currency'];
							$membership_price['billing_period'] = $price_options['existing'][$membership_price['id']]['billing-period'];
						} else {
							if (in_array($price_options['existing'][$membership_price['id']]['status'], array('active', 'archive'))) {
								$membership_price['status'] = $price_options['existing'][$membership_price['id']]['status'];
							}
						}
						$membership_price['seq'] = $price_options['existing'][$membership_price['id']]['seq'];
						$membership_price['title'] = json_encode($price_options['existing'][$membership_price['id']]['title']);
						$wpdb->query("UPDATE ".$wpdb->prefix."membership_prices SET
								price = '".esc_sql($membership_price['price'])."', 
								currency = '".esc_sql($membership_price['currency'])."', 
								billing_period = '".esc_sql($membership_price['billing_period'])."', 
								title = '".esc_sql($membership_price['title'])."', 
								status = '".esc_sql($membership_price['status'])."', 
								seq = '".esc_sql($membership_price['seq'])."'
							WHERE id = '".esc_sql($membership_price['id'])."' 
						");
						$price_ids[] = esc_sql($membership_price['id']);
						if ($membership_price['status'] != 'archive') $available_price_option_exists = true;
					}
				}
				$wpdb->query("UPDATE ".$wpdb->prefix."membership_prices SET deleted = '1' WHERE membership_id = '".esc_sql($membership['id'])."' AND status = 'new' AND deleted != '1' AND id NOT IN ('".implode("','", $price_ids)."')");
			}
			if (!$available_price_option_exists) $membership['status'] = 'archive';
			$wpdb->query("UPDATE ".$wpdb->prefix."memberships SET
					title = '".esc_sql(json_encode($title))."', 
					description = '".esc_sql(json_encode($description))."',
					features = '".esc_sql(json_encode($features))."',
					footer = '".esc_sql(json_encode($footer))."',
					color = '".esc_sql($color)."',  
					status = '".esc_sql($membership['status'])."'
				WHERE id = '".esc_sql($membership['id'])."' 
			");
			$membership_id = $membership['id'];
		} else {
			$wpdb->query("INSERT INTO ".$wpdb->prefix."memberships (
				uuid, 
				title, 
				description,
				features,
				footer,
				color,
				status,
				options,
				seq,
				deleted, 
				created
			) VALUES (
				'".esc_sql(uuid_v4())."',
				'".esc_sql(json_encode($title))."',
				'".esc_sql(json_encode($description))."',
				'".esc_sql(json_encode($features))."',
				'".esc_sql(json_encode($footer))."',
				'".esc_sql($color)."',
				'new',
				'',
				'".time()."',
				'0',
				'".time()."'
			)");
			$membership_id = $wpdb->insert_id;
		}
		foreach ($price_options['new'] as $price_option) {
			$wpdb->query("INSERT INTO ".$wpdb->prefix."membership_prices (
				uuid,
				membership_id,
				price,
				currency,
				billing_period, 
				title, 
				description, 
				status,
				options,
				seq,
				deleted, 
				created
			) VALUES (
				'".esc_sql(uuid_v4())."',
				'".esc_sql($membership_id)."',
				'".esc_sql($price_option['price'])."',
				'".esc_sql($price_option['currency'])."',
				'".esc_sql($price_option['billing-period'])."',
				'".esc_sql(json_encode($price_option['title']))."',
				'',
				'new',
				'',
				'".esc_sql($price_option['seq'])."',
				'0',
				'".time()."'
			)");
		}
		do_action('membership-options-save', $membership_id);

		$_SESSION['success-message'] = esc_html__('Membership details successfully saved.', 'fb');
		$return_object = array('status' => 'OK', 'message' => esc_html__('Membership details successfully saved.', 'fb'), 'url' => url('?page=admin-memberships'));
		echo json_encode($return_object);
		exit;
	}

	function page_edit_free_membership() {
		global $wpdb, $options, $user_details, $language, $membership_feature_bullets, $free_membership;

		if (empty($user_details)) {
			header("Location: ".url('login.php').'?redirect='.urlencode(url('?page=admin-edit-free-membership')));
			exit;
		} else if ($user_details['role'] != 'admin') {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}

		$content = '';
		$page_title = esc_html__('Edit Free Membership', 'fb');

		$content .= '
		<h1>'.$page_title.'</h1>
		<div class="form" id="membership-form">
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Title', 'fb').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Specify the title of the membership.', 'fb').'
					</div>
				</div>
				<div class="form-content">
					'.translatable_input_html('title', $free_membership['title'], esc_html__('Membership title', 'fb')).'
				</div>
			</div>
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Description', 'fb').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Specify the description of the membership. It appears if list of features not specified.', 'fb').'
					</div>
				</div>
				<div class="form-content">
					'.translatable_textarea_html('description', $free_membership['description'], esc_html__('Describe membership...', 'fb')).'
				</div>
			</div>
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Features', 'fb').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Specify the list of membership features.', 'fb').'
					</div>
				</div>
				<div class="form-content">
					<div class="membership-features">';
		if (sizeof($free_membership['features']) > 0) {
			$i = 0;
			foreach ($free_membership['features'] as $membership_feature) {
				$content .= '
						<div class="membership-feature">
							<div class="table-column-60" data-label="'.esc_html__('Bullet', 'fb').'">
								<div class="input-box">
									<form class="bar-selector">';
				foreach ($membership_feature_bullets as $bullet) {
					$content .= '<input'.($membership_feature['bullet'] == $bullet ? ' checked="checked"' : '').' class="radio" id="bullet-'.$i.'-'.esc_html($bullet).'" type="radio" name="bullet" value="'.esc_html($bullet).'"><label for="bullet-'.$i.'-'.esc_html($bullet).'"><i class="fas fa-'.esc_html($bullet).'"></i></label>';
					$i++;
				}
				$content .= '
									</form>
									<label>'.esc_html__('Bullet icon', 'fb').'</label>
								</div>
							</div>
							<div data-label="'.esc_html__('Label', 'fb').'">
								'.translatable_input_html('feature-label', $membership_feature['label'], esc_html__('Describe feature shortly...', 'fb')).'
							</div>
							<div class="table-column-60"><a class="tooltipster single-action" href="#" onclick="return membership_feature_delete(this);" title="'.esc_html__('Delete feature', 'fb').'"><i class="far fa-trash-alt"></i></a></div>
						</div>';
			}
		}
		$content .= '
						<div class="membership-feature membership-feature-template">
							<div class="table-column-60" data-label="'.esc_html__('Bullet', 'fb').'">
								<div class="input-box">
									<form class="bar-selector">';
								foreach ($membership_feature_bullets as $bullet) {
									$content .= '<input'.($bullet == 'check' ? ' checked="checked"' : '').' class="radio" id="bullet-'.esc_html($bullet).'" type="radio" name="bullet" value="'.esc_html($bullet).'"><label for="bullet-'.esc_html($bullet).'"><i class="fas fa-'.esc_html($bullet).'"></i></label>';
								}
								$content .= '
									</form>
									<label>'.esc_html__('Bullet icon', 'fb').'</label>
								</div>
							</div>
							<div data-label="'.esc_html__('Label', 'fb').'">
								'.translatable_input_html('feature-label', '', esc_html__('Describe feature shortly...', 'fb')).'
							</div>
							<div class="table-column-60"><a class="tooltipster single-action" href="#" onclick="return membership_feature_delete(this);" title="'.esc_html__('Delete feature', 'fb').'"><i class="far fa-trash-alt"></i></a></div>
						</div>
					</div>
					<a href="#" class="button2" onclick="membership_feature_add(this); return false;"><i class="fas fa-plus"></i>'.esc_html__('Add feature', 'fb').'</a>
				</div>
			</div>
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Footer', 'fb').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Specify the footer of the membership. It appears at the bottom of the membership panel. Ex.: 19.95 USD / month', 'fb').'
					</div>
				</div>
				<div class="form-content">
					'.translatable_input_html('footer', $free_membership['footer'], esc_html__('Footer label', 'fb')).'
				</div>
			</div>
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Available functionality', 'fb').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Specify functionality which is available for this membership.', 'fb').'
					</div>
				</div>
				<div class="form-content membership-options">';
					$content .= apply_filters('membership-options-html', '', 0);
					$content .= '
					<div class="membership-options-no">'.esc_html__('No additional functionality available.', 'fb').'</div>
				</div>
			</div>
			<div class="form-row right-align">
				<input type="hidden" name="action" value="membership-free-save">
				<a class="button" href="#" onclick="return membership_free_save(this);" data-label="'.esc_html__('Save Details', 'fb').'">
					<span>'.esc_html__('Save Details', 'fb').'</span>
					<i class="fas fa-angle-right"></i>
				</a>
			</div>
		</div>';

		return array('title' => $page_title, 'content' => $content);
	}

	function ajax_membership_free_save() {
		global $wpdb, $options, $user_details, $language, $free_membership, $membership_feature_bullets;

		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Please enter your account to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		} else if ($user_details['role'] != 'admin') {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('You do not have permissions to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
	
		$title = translatable_populate($_REQUEST['title']);
		$description = translatable_populate($_REQUEST['description']);
		$footer = translatable_populate($_REQUEST['footer']);
	
		$errors = array();

		if (mb_strlen($title['default']) < 2) $errors['title[default]'] = esc_html__('Title is too short.', 'fb');
		else if (mb_strlen($title['default']) > 63) $errors['title[default]'] = esc_html__('Title is too long.', 'fb');
		foreach ($title as $key => $value) {
			if ($key != 'default') {
				if (mb_strlen($value) > 0 && mb_strlen($value) < 2) $errors['title['.$key.']'] = esc_html__('The translation is too short.', 'fb');
				else if (mb_strlen($value) > 63) $errors['title['.$key.']'] = esc_html__('The translation is too long.', 'fb');
			}
		}
		if (mb_strlen($description['default']) > 512) $errors['description[default]'] = esc_html__('Description is too long.', 'fb');
		foreach ($description as $key => $value) {
			if ($key != 'default') {
				if (mb_strlen($value) > 512) $errors['description['.$key.']'] = esc_html__('The translation is too long.', 'fb');
			}
		}
		if (mb_strlen($footer['default']) > 63) $errors['footer[default]'] = esc_html__('Footer is too long.', 'fb');
		foreach ($footer as $key => $value) {
			if ($key != 'default') {
				if (mb_strlen($value) > 63) $errors['footer['.$key.']'] = esc_html__('The translation is too long.', 'fb');
			}
		}
		$features = array();
		if (array_key_exists('features', $_REQUEST) && is_array($_REQUEST['features'])) {
			$seq = 0;
			foreach ($_REQUEST['features'] as $f) {
				$dom_id = trim(stripslashes($f['dom-id']));
				$feature = array(
					'label' => translatable_populate($f['label']),
					'bullet' => trim(stripslashes($f['bullet'])),
					'seq' => $seq
				);
				$seq++;
				if (mb_strlen($feature['label']['default']) > 63) $errors[$dom_id]['feature-label[default]'] = esc_html__('Label is too long.', 'fb');
				foreach ($feature['label'] as $key => $value) {
					if ($key != 'default') {
						if (mb_strlen($value) > 63) $errors[$dom_id]['feature-label['.$key.']'] = esc_html__('The translation is too long.', 'fb');
					}
				}
				if (!in_array($feature['bullet'], $membership_feature_bullets)) $errors[$dom_id]['bullet'] = esc_html__('The bullet is invalid.', 'fb');
				$features[] = $feature;
			}
		}

		$errors = apply_filters('membership-options-check', $errors);
		
		if (!empty($errors)) {
			$return_data = array('status' => 'ERROR', 'errors' => $errors);
			echo json_encode($return_data);
			exit;
		}
		$free_membership = array_merge($free_membership, array(
			'title' => $title,
			'description' => $description,
			'footer' => $footer,
			'features' => $features
		));
		save_options('membership-free', $free_membership);

		do_action('membership-options-save', 0);
	
		$_SESSION['success-message'] = esc_html__('Membership details successfully saved.', 'fb');
		$return_object = array('status' => 'OK', 'message' => esc_html__('Membership details successfully saved.', 'fb'), 'url' => url('?page=admin-memberships'));
		echo json_encode($return_object);
		exit;
	}

	function page_transactions() {
		global $wpdb, $options, $user_details, $language, $free_membership;

		if (empty($user_details)) {
			header("Location: ".url('login.php').'?redirect='.urlencode(url('?page=admin-transactions')));
			exit;
		} else if ($user_details['role'] != 'admin') {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}

		$user = null;
		if (array_key_exists('user', $_GET)) {
			$user_id = intval($_GET['user']);
			$user = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."users WHERE id = '".esc_sql($user_id)."' AND deleted != '1'", ARRAY_A);
		}

		if (array_key_exists('s', $_GET)) $search_query = trim(stripslashes($_GET["s"]));
		else $search_query = "";
		$tmp = $wpdb->get_row("SELECT COUNT(*) AS total FROM ".$wpdb->prefix."transactions t1
				LEFT JOIN ".$wpdb->prefix."user_customers t2 ON t2.customer_id = t1.customer_id AND t2.deleted != '1'
				LEFT JOIN ".$wpdb->prefix."users t3 ON t3.id = t2.user_id
			WHERE t1.deleted != '1'".(!empty($user) ? " AND t3.id = '".esc_sql($user['id'])."'" : "").((strlen($search_query) > 0) ? " AND (t3.name LIKE '%".esc_sql($search_query)."%' OR t3.email LIKE '%".esc_sql($search_query)."%' OR t3.login LIKE '%".esc_sql($search_query)."%')" : ""), ARRAY_A);
		$total = $tmp["total"];
		$totalpages = ceil($total/RECORDS_PER_PAGE);
		if ($totalpages == 0) $totalpages = 1;
		if (array_key_exists('p', $_GET)) $page = intval($_GET["p"]);
		else $page = 1;
		if ($page < 1 || $page > $totalpages) $page = 1;
		$switcher = page_switcher(url('?page=admin-transactions'.(!empty($user) ? '&user='.urlencode($user['id']) : '').((strlen($search_query) > 0) ? "&s=".urlencode($search_query) : "")), $page, $totalpages);
		$transactions = $wpdb->get_results("SELECT t1.*, t3.email AS user_email, t3.name AS user_name FROM ".$wpdb->prefix."transactions t1
				LEFT JOIN ".$wpdb->prefix."user_customers t2 ON t2.customer_id = t1.customer_id AND t2.deleted != '1'
				LEFT JOIN ".$wpdb->prefix."users t3 ON t3.id = t2.user_id
			WHERE t1.deleted != '1'".(!empty($user) ? " AND t3.id = '".esc_sql($user['id'])."'" : "").((strlen($search_query) > 0) ? " AND (t3.name LIKE '%".esc_sql($search_query)."%' OR t3.email LIKE '%".esc_sql($search_query)."%' OR t3.login LIKE '%".esc_sql($search_query)."%')" : ""), ARRAY_A);

		$content = '';
		$page_title = esc_html__('Transactions', 'fb').(!empty($user) ? ' ('.esc_html($user['email']).')' : '');
		$content .= '
		<h1>'.$page_title.'</h1>
		<div class="table-funcbar">
			<form action="'.(PERMALINKS_ENABLE ? esc_html(url('?page=admin-transactions')) : esc_html(url(''))).'" method="get" class="table-filter-form">
				'.(PERMALINKS_ENABLE ? '' : '<input type="hidden" name="page" value="admin-transactions" />').'
				'.(!empty($user) ? '<input type="hidden" name="user" value="'.esc_html($user['id']).'" />' : '').'
				<input type="text" name="s" value="'.esc_html($search_query).'" placeholder="'.esc_html__('Search', 'fb').'" />
				<input type="submit" class="button" value="'.esc_html__('Search', 'fb').'" />
				'.((strlen($search_query) > 0) ? '<input type="button" class="button" value="'.esc_html__('Reset search results', 'fb').'" onclick="window.location.href=\''.url('?page=admin-transactions').'\';" />' : '').'
			</form>
		</div>
		<div class="table-funcbar">
			<div class="table-pageswitcher">'.$switcher.'</div>
		</div>
		<div class="table">
			<table>
				<thead>
					<tr>
						<th class="table-column-selector"></th>
						<th>'.esc_html__('Type', 'fb').'</th>
						<th>'.esc_html__('User', 'fb').'</th>
						<th class="table-column-100">'.esc_html__('Amount', 'fb').'</th>
						<th class="table-column-created">'.esc_html__('Created', 'fb').'</th>
						<th class="table-column-actions"></th>
					</tr>
				</thead>
				<tbody>';
		if (sizeof($transactions) > 0) {
			foreach ($transactions as $transaction) {
				$content .= '
					<tr>
						<td data-label=""><input class="checkbox-fa-check" type="checkbox" name="records[]" id="transaction-'.esc_html($transaction['id']).'" value="'.esc_html($transaction['id']).'"><label for="transaction-'.esc_html($transaction['id']).'"></label></td>
						<td data-label="'.esc_html__('Type', 'fb').'"><a href="#" data-id="'.$transaction['id'].'" onclick="transaction_details(this);return false;"><strong>'.esc_html($transaction['type']).'</strong></a><label class="table-note">'.(strlen($transaction['txn_id']) > 36 ? esc_html(substr($transaction['txn_id'], 0, 32).'...') : esc_html($transaction['txn_id'])).'</label></td>
						<td data-label="'.esc_html__('User', 'fb').'">'.(!empty($transaction['user_email']) ? esc_html($transaction['user_email']).'<label class="table-note">'.esc_html($transaction['user_name']).'</label>' : '-').'</td>
						<td data-label="'.esc_html__('Amount', 'fb').'">'.esc_html(number_format($transaction['price'], 2, '.', '').' '.$transaction['currency']).'</td>
						<td data-label="'.esc_html__('Created', 'fb').'">'.esc_html(timestamp_string($transaction['created'], $options['date-format'].' H:i')).'</td>
						<td data-label="'.esc_html__('Actions', 'fb').'"><a data-id="'.esc_html($transaction['id']).'" class="tooltipster single-action" href="#" onclick="return transaction_delete(this);" title="'.esc_html__('Delete transactions', 'fb').'"><i class="far fa-trash-alt"></i></a></td>
					</tr>';
			}
		}
		$content .= '
					<tr class="table-empty"'.(sizeof($transactions) > 0 ? ' style="display: none;"' : '').'><td colspan="6">'.((strlen($search_query) > 0) ? esc_html__('No results found for', 'fb').' "<strong>'.esc_html($search_query).'</strong>".' : esc_html__('List is empty.', 'fb')).'</td></tr>
				</tbody>
			</table>
		</div>
		<div class="table-funcbar">
			<div class="table-pageswitcher">'.$switcher.'</div>
			<div class="table-buttons">
				<div class="multi-button multi-button-small">
					<span>'.esc_html__('Bulk actions', 'fb').'<i class="fas fa-angle-down"></i></span>
					<ul>
						<li><a href="#" data-action="delete" data-doing="'.esc_html__('Deleting...', 'fb').'" onclick="return transactions_bulk_delete(this);">'.esc_html__('Delete', 'fb').'</a></li>
					</ul>
				</div>
			</div>
		</div>';
		return array('title' => $page_title, 'content' => $content);
	}

	function ajax_transaction_delete() {
		global $wpdb, $options, $user_details, $language;

		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Please enter your account to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		} else if ($user_details['role'] != 'admin') {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('You do not have permissions to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
	
		$transaction_id = trim(stripslashes($_REQUEST['transaction-id']));
		
		$transaction = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."transactions WHERE id = '".esc_sql($transaction_id)."' AND deleted != '1'", ARRAY_A);
		if (empty($transaction)) {
			$return_object = array('status' => 'ERROR', 'message' => esc_html__('Transaction not found.', 'fb'));
			echo json_encode($return_object);
			exit;
		}
	
		$wpdb->query("UPDATE ".$wpdb->prefix."transactions SET deleted = '1' WHERE id = '".esc_sql($transaction_id)."'");
	
		$return_object = array('status' => 'OK', 'message' => esc_html__('Transaction successfully deleted.', 'fb'));
		echo json_encode($return_object);
		exit;
	}

	function ajax_transactions_delete() {
		global $wpdb, $options, $user_details, $language;

		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Please enter your account to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		} else if ($user_details['role'] != 'admin') {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('You do not have permissions to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
	
		$records = array();
		if (array_key_exists('records', $_REQUEST) && is_array($_REQUEST['records'])) {
			foreach ($_REQUEST['records'] as $record_id) {
				$records[] = intval($record_id);
			}
		}
		if (empty($records)) {
			$return_object = array('status' => 'ERROR', 'message' => esc_html__('No transactions selected.', 'fb'));
			echo json_encode($return_object);
			exit;
		}
	
		$wpdb->query("UPDATE ".$wpdb->prefix."transactions SET deleted = '1' WHERE deleted != '1' AND id IN ('".implode("','", $records)."')");
	
		$_SESSION['success-message'] = esc_html__('Selected transactions successfully deleted.', 'fb');
		$return_object = array('status' => 'OK', 'message' => esc_html__('Selected transactions successfully deleted.', 'fb'));
		echo json_encode($return_object);
		exit;
	}

	function ajax_transaction_details() {
		global $wpdb, $options, $user_details, $language;

		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Please enter your account to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		} else if ($user_details['role'] != 'admin') {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('You do not have permissions to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		}

		$transaction_id = intval($_REQUEST['transaction-id']);
		$transaction_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."transactions WHERE id = '".esc_sql($transaction_id)."' AND deleted != '1'", ARRAY_A);
		if (empty($transaction_details)) {
			$return_object = array('status' => 'ERROR', 'message' => esc_html__('Transaction not found.', 'fb'));
			echo json_encode($return_object);
			exit;
		}

		$details = json_decode($transaction_details['details'], true);
		$html = '
		<table class="array-tree-table">';
		$html .= $this->_tree_details($details, 0);
		$html .= '
		</table>';

		$return_object = array('status' => 'OK', 'html' => $html);
		echo json_encode($return_object);
		exit;
	}

	function _tree_details($_details, $_level = 0) {
		$html = '';
		foreach($_details as $key => $value) {
			if (is_array($value)) $html .= '<tr><th style="padding-left:'.number_format(0.4+$_level*1, 2, '.', '').'em;">'.esc_html($key).'</th><td>...</td></tr>'.$this->_tree_details($value, $_level+1);
			else $html .= '
				<tr><th style="padding-left:'.number_format(0.4+$_level*1, 2, '.', '').'em;">'.esc_html($key).'</th><td>'.esc_html($value).'</td></tr>';
		}
		return $html;
	}

	function page_sessions() {
		global $wpdb, $options, $user_details, $language;

		if (empty($user_details)) {
			header("Location: ".url('login.php').'?redirect='.urlencode(url('?page=admin-sessions')));
			exit;
		} else if ($user_details['role'] != 'admin') {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}

		$content = '';
		$page_title = esc_html__('Active sessions', 'fb');

		if (array_key_exists('s', $_GET)) $search_query = trim(stripslashes($_GET["s"]));
		else $search_query = "";
		
		$tmp = $wpdb->get_row("SELECT COUNT(*) AS total FROM ".$wpdb->prefix."sessions t1 JOIN ".$wpdb->prefix."users t2 ON t1.user_id = t2.id WHERE t2.deleted != '1' AND t1.registered + t1.valid_period > '".esc_sql(time())."'".((strlen($search_query) > 0) ? " AND (t2.name LIKE '%".esc_sql($search_query)."%' OR t2.email LIKE '%".esc_sql($search_query)."%' OR t2.login LIKE '%".esc_sql($search_query)."%')" : ""), ARRAY_A);
		$total = $tmp["total"];
		
		$totalpages = ceil($total/RECORDS_PER_PAGE);
		if ($totalpages == 0) $totalpages = 1;
		if (array_key_exists('p', $_GET)) $page = intval($_GET["p"]);
		else $page = 1;
		if ($page < 1 || $page > $totalpages) $page = 1;
		$switcher = page_switcher(url('?page=admin-sessions').((strlen($search_query) > 0) ? "&s=".urlencode($search_query) : ""), $page, $totalpages);
		
		$sessions = $wpdb->get_results("SELECT t1.*, t2.name AS user_name, t2.login AS user_login, t2.email AS user_email FROM ".$wpdb->prefix."sessions t1 JOIN ".$wpdb->prefix."users t2 ON t1.user_id = t2.id WHERE t2.deleted != '1' AND t1.registered + t1.valid_period > '".esc_sql(time())."'".((strlen($search_query) > 0) ? " AND (t2.name LIKE '%".esc_sql($search_query)."%' OR t2.email LIKE '%".esc_sql($search_query)."%' OR t2.login LIKE '%".esc_sql($search_query)."%')" : "")." ORDER BY t1.registered DESC LIMIT ".(($page-1)*RECORDS_PER_PAGE).", ".RECORDS_PER_PAGE, ARRAY_A);
		
		$content .= '
		<h1>'.esc_html__('Active Sessions', 'fb').'</h1>
		<div class="table-funcbar">
			<form action="'.(PERMALINKS_ENABLE ? esc_html(url('?page=admin-sessions')) : esc_html(url(''))).'" method="get" class="table-filter-form">
				'.(PERMALINKS_ENABLE ? '' : '<input type="hidden" name="page" value="admin-sessions" />').'
				<input type="text" name="s" value="'.esc_html($search_query).'" placeholder="'.esc_html__('Search', 'fb').'" />
				<input type="submit" class="button" value="'.esc_html__('Search', 'fb').'" />
				'.((strlen($search_query) > 0) ? '<input type="button" class="button" value="'.esc_html__('Reset search results', 'fb').'" onclick="window.location.href=\''.esc_html(url('?page=admin-sessions')).'\';" />' : '').'
			</form>
		</div>
		<div class="table-funcbar">
			<div class="table-pageswitcher">'.$switcher.'</div>
		</div>
		<div class="table">
			<table>
				<thead>
					<tr>
						<th class="table-column-selector"></th>
						<th>'.esc_html__('User', 'fb').'</th>
						<th class="table-column-100">'.esc_html__('Source', 'fb').'</th>
						<th class="table-column-created">'.esc_html__('Created', 'fb').'</th>
						<th class="table-column-actions"></th>
					</tr>
				</thead>
				<tbody>';
		if (sizeof($sessions) > 0) {
			foreach ($sessions as $session) {
				$content .= '
					<tr>
						<td data-label=""><input class="checkbox-fa-check" type="checkbox" name="records[]" id="session-'.esc_html($session['id']).'" value="'.esc_html($session['id']).'"><label for="session-'.esc_html($session['id']).'"></label></td>
						<td data-label="'.esc_html__('User', 'fb').'"><strong>'.esc_html($session['user_email']).'</strong><label class="table-note">'.esc_html($session['user_name']).'</label></td>
						<td data-label="'.esc_html__('Source', 'fb').'">'.esc_html($session['source']).'</td>
						<td data-label="'.esc_html__('Created', 'fb').'">'.esc_html(timestamp_string($session['created'], $options['date-format'].' H:i')).'</td>
						<td data-label="'.esc_html__('Actions', 'fb').'">
							<div class="item-menu">
								<span><i class="fas fa-ellipsis-v"></i></span>
								<ul>
									<li><a href="#" data-id="'.esc_html($session['id']).'" data-doing="'.esc_html__('Closing...', 'fb').'" onclick="return session_delete(this);">'.esc_html__('Close session', 'fb').'</a></li>
								</ul>
							</div>
						</td>
					</tr>';
			}
		}
		$content .= '
					<tr class="table-empty"'.(sizeof($sessions) > 0 ? ' style="display: none;"' : '').'><td colspan="5">'.((strlen($search_query) > 0) ? esc_html__('No results found for', 'fb').' "<strong>'.esc_html($search_query).'</strong>".' : esc_html__('List is empty.', 'fb')).'</td></tr>
				</tbody>
			</table>
		</div>
		<div class="table-funcbar">
			<div class="table-pageswitcher">'.$switcher.'</div>
			<div class="table-buttons">
				<div class="multi-button multi-button-small">
					<span>'.esc_html__('Bulk actions', 'fb').'<i class="fas fa-angle-down"></i></span>
					<ul>
						<li><a href="#" data-action="delete" data-doing="'.esc_html__('Closing...', 'fb').'" onclick="return sessions_bulk_delete(this);">'.esc_html__('Close sessions', 'fb').'</a></li>
					</ul>
				</div>
			</div>
		</div>';

		return array('title' => $page_title, 'content' => $content);
	}

	function ajax_session_delete() {
		global $wpdb, $options, $user_details, $language, $session_id;

		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Please enter your account to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		} else if ($user_details['role'] != 'admin') {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('You do not have permissions to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
	
		$sid = trim(stripslashes($_REQUEST['session-id']));
		
		$session = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."sessions WHERE id = '".esc_sql($sid)."'", ARRAY_A);
		if (empty($session)) {
			$return_object = array('status' => 'ERROR', 'message' => esc_html__('Session not found.', 'fb'));
			echo json_encode($return_object);
			exit;
		} else if ($session['session_id'] == $session_id) {
			$return_object = array('status' => 'ERROR', 'message' => esc_html__('You can not close your current session.', 'fb'));
			echo json_encode($return_object);
			exit;
		}
	
		$wpdb->query("UPDATE ".$wpdb->prefix."sessions SET valid_period = '0' WHERE id = '".esc_sql($sid)."'");
	
		$return_object = array('status' => 'OK', 'message' => esc_html__('Session successfully closed.', 'fb'));
		echo json_encode($return_object);
		exit;
	}

	function ajax_sessions_delete() {
		global $wpdb, $options, $user_details, $language, $session_id;

		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Please enter your account to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		} else if ($user_details['role'] != 'admin') {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('You do not have permissions to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
	
		$records = array();
		if (array_key_exists('records', $_REQUEST) && is_array($_REQUEST['records'])) {
			foreach ($_REQUEST['records'] as $record_id) {
				$records[] = intval($record_id);
			}
		}
		if (empty($records)) {
			$return_object = array('status' => 'ERROR', 'message' => esc_html__('No sessions selected.', 'fb'));
			echo json_encode($return_object);
			exit;
		}
	
		$wpdb->query("UPDATE ".$wpdb->prefix."sessions SET valid_period = '0' WHERE id IN ('".implode("','", $records)."') AND session_id != '".esc_sql($session_id)."'");
	
		$_SESSION['success-message'] = esc_html__('Selected sessions successfully closed.', 'fb');
		$return_object = array('status' => 'OK', 'message' => esc_html__('Selected sessions successfully closed.', 'fb'));
		echo json_encode($return_object);
		exit;
	}

	function page_settings() {
		global $wpdb, $options, $user_details, $language, $languages, $mail_methods, $smtp_secures;

		if (empty($user_details)) {
			header("Location: ".url('login.php').'?redirect='.urlencode(url('?page=admin-settings')));
			exit;
		} else if ($user_details['role'] != 'admin') {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}

		$payment_enabled = is_payment_enabled();

		$content = '';
		$page_title = esc_html__('Site Settings', 'fb');
		$content .= '
		<h1>'.esc_html__('Site Settings', 'fb').'</h1>
		<div class="form" id="settings-form">
			<div class="tabs tabs-main">
				<a class="tab tab-active" href="#tab-general">'.esc_html__('General', 'fb').'</a>
				<a class="tab" href="#tab-connections">'.esc_html__('Connections', 'fb').'</a>
				'.($payment_enabled ? '<a class="tab" href="#tab-payments">'.esc_html__('Payment gateways', 'fb').'</a>' : '').'
				<a class="tab" href="#tab-mail">'.esc_html__('Mailing', 'fb').'</a>
			</div>
			<div id="tab-general" class="tab-content" style="display: block;">
				<div class="form-row">
					<div class="form-label">
						<label>'.esc_html__('Title', 'fb').'</label>
					</div>
					<div class="form-tooltip">
						<i class="fas fa-question-circle form-tooltip-anchor"></i>
						<div class="form-tooltip-content">
							'.esc_html__('Specify the title of the site.', 'fb').'
						</div>
					</div>
					<div class="form-content">
						'.translatable_input_html('title', $options['title'], esc_html__('Title', 'fb')).'
					</div>
				</div>
				<div class="form-row">
					<div class="form-label">
						<label>'.esc_html__('Tagline', 'fb').'</label>
					</div>
					<div class="form-tooltip">
						<i class="fas fa-question-circle form-tooltip-anchor"></i>
						<div class="form-tooltip-content">
							'.esc_html__('Specify the tagline of the site.', 'fb').'
						</div>
					</div>
					<div class="form-content">
						'.translatable_input_html('tagline', $options['tagline'], esc_html__('Tagline', 'fb')).'
					</div>
				</div>
				<div class="form-row">
					<div class="form-label">
						<label>'.esc_html__('Copyright', 'fb').'</label>
					</div>
					<div class="form-tooltip">
						<i class="fas fa-question-circle form-tooltip-anchor"></i>
						<div class="form-tooltip-content">
							'.esc_html__('Specify the copyright line. It appears in footer of each page.', 'fb').'
						</div>
					</div>
					<div class="form-content">
						'.translatable_input_html('copyright', $options['copyright'], esc_html__('Copyright', 'fb')).'
					</div>
				</div>
				<div class="form-row">
					<div class="form-label">
						<label>'.esc_html__('Date format', 'fb').'</label>
					</div>
					<div class="form-tooltip">
						<i class="fas fa-question-circle form-tooltip-anchor"></i>
						<div class="form-tooltip-content">
							'.esc_html__('Select date format.', 'fb').'
						</div>
					</div>
					<div class="form-content">
						<div class="columns">
							<div class="column column-30">
								<div class="input-box">
									<select class="errorable" name="date-format">
										<option value="yyyy-mm-dd"'.($options['date-format'] == 'yyyy-mm-dd' ? ' selected="selected"' : '').'>YYYY-MM-DD</option>
										<option value="mm/dd/yyyy"'.($options['date-format'] == 'mm/dd/yyyy' ? ' selected="selected"' : '').'>MM/DD/YYYY</option>
										<option value="dd/mm/yyyy"'.($options['date-format'] == 'dd/mm/yyyy' ? ' selected="selected"' : '').'>DD/MM/YYYY</option>
										<option value="dd.mm.yyyy"'.($options['date-format'] == 'dd.mm.yyyy' ? ' selected="selected"' : '').'>DD.MM.YYYY</option>
									</select>
								</div>
							</div>
							<div class="column column-30"></div>
							<div class="column column-40"></div>
						</div>
					</div>
				</div>
				<div class="form-row">
					<div class="form-label">
						<label>'.esc_html__('Language', 'fb').'</label>
					</div>
					<div class="form-tooltip">
						<i class="fas fa-question-circle form-tooltip-anchor"></i>
						<div class="form-tooltip-content">
							'.esc_html__('Select language.', 'fb').'
						</div>
					</div>
					<div class="form-content">
						<div class="columns">
							<div class="column column-30">
								<div class="input-box">
									<select class="errorable" name="language">
										<option value=""'.($options['language'] == '' ? ' selected="selected"' : '').'>'.esc_html__('Selected by user', 'fb').'</option>';
		foreach ($languages as $key => $label) {
			$content .= '
										<option value="'.esc_html($key).'"'.($options['language'] == $key ? ' selected="selected"' : '').'>'.esc_html($label).'</option>';
		}
		$content .= '
									</select>
								</div>
							</div>
							<div class="column column-70"></div>
						</div>
					</div>
				</div>
				<div class="form-row">
					<div class="form-label">
						<label>'.esc_html__('Pattern', 'fb').'</label>
					</div>
					<div class="form-tooltip">
						<i class="fas fa-question-circle form-tooltip-anchor"></i>
						<div class="form-tooltip-content">
							   '.esc_html__('Upload image that is used on left side of sign in / sign up pages. It is recommended to upload seamless pattern image.', 'fb').'
						</div>
					</div>
					<div class="form-content">
						'.image_uploader_html('pattern', $options['pattern'], url('').'images/default-pattern.png').'
					</div>
				</div>
				<div class="form-row">
					<div class="form-label">
						<label>'.esc_html__('User registration', 'fb').'</label>
					</div>
					<div class="form-tooltip">
						<i class="fas fa-question-circle form-tooltip-anchor"></i>
						<div class="form-tooltip-content">
							'.esc_html__('Allow users to register on website.', 'fb').'
						</div>
					</div>
					<div class="form-content">
						<div class="input-box">
							<div class="checkbox-toggle-container">
								<input class="checkbox-toggle" type="checkbox" value="on" id="enable-register" name="enable-register"'.($options['enable-register'] == 'on' ? ' checked="checked"' : '').'>
								<label for="enable-register"></label>
								<span>'.esc_html__('Anyone can register', 'fb').'</span>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div id="tab-connections" class="tab-content">
				<h2>'.esc_html__('Google', 'fb').'</h2>
				<div class="form-row">
					<div class="form-label">
						<label>'.esc_html__('Enable', 'fb').'</label>
					</div>
					<div class="form-tooltip">
						<i class="fas fa-question-circle form-tooltip-anchor"></i>
						<div class="form-tooltip-content">
							'.esc_html__('Allow users to use their Google Account to sign in.', 'fb').'
						</div>
					</div>
					<div class="form-content">
						<div class="input-box">
							<div class="checkbox-toggle-container">
								<input class="checkbox-toggle" type="checkbox" value="on" id="google-enable" name="google-enable"'.($options['google-enable'] == 'on' ? ' checked="checked"' : '').' onchange="if(jQuery(this).is(\':checked\')){jQuery(\'#google-parameters\').fadeIn(100);}else{jQuery(\'#google-parameters\').fadeOut(100);}">
								<label for="google-enable"></label>
							</div>
						</div>
					</div>
				</div>
				<div id="google-parameters"'.($options['google-enable'] != 'on' ? ' style="display:none;"' : '').'>
					<div class="form-row">
						<div class="form-label">
						</div>
						<div class="form-tooltip">
						</div>
						<div class="form-content">
							<div class="inline-message inline-message-noclose inline-message-success">
								'.sprintf(esc_html__('Create new OAuth 2.0 credentials in %sGoogle Cloud Platform%s and copy-paste them into fields below. Use the following parameters to create OAuth 2.0 credentials.', 'fb'), '<a href="https://console.cloud.google.com/apis/credentials" target="_blank">', '</a>').'
								<div class="prep-parameter-container">
									<label>'.esc_html__('Application type', 'fb').':</label>
									<pre>Web application</pre>
								</div>
								<div class="prep-parameter-container">
									<label>'.esc_html__('Authorized redirect URI', 'fb').':</label>
									<pre onclick="this.focus();this.select();">'.url('auth.php').'?google=auth</pre>
								</div>
							</div>
						</div>
					</div>
					<div class="form-row">
						<div class="form-label">
							<label>'.esc_html__('Client ID', 'fb').'</label>
						</div>
						<div class="form-tooltip">
							<i class="fas fa-question-circle form-tooltip-anchor"></i>
							<div class="form-tooltip-content">
								'.esc_html__('Enter your OAuth 2.0 Client ID.', 'fb').'
							</div>
						</div>
						<div class="form-content">
							<div class="input-box">
								<input class="errorable" type="text" name="google-client-id" placeholder="'.esc_html__('Client ID', 'fb').'" value="'.esc_html($options['google-client-id']).'">
							</div>
						</div>
					</div>
					<div class="form-row">
						<div class="form-label">
							<label>'.esc_html__('Client Secret', 'fb').'</label>
						</div>
						<div class="form-tooltip">
							<i class="fas fa-question-circle form-tooltip-anchor"></i>
							<div class="form-tooltip-content">
								'.esc_html__('Enter your OAuth 2.0 Client Secret.', 'fb').'
							</div>
						</div>
						<div class="form-content">
							<div class="input-box">
								<input class="errorable" type="text" name="google-client-secret" placeholder="'.esc_html__('Client Secret', 'fb').'" value="'.esc_html($options['google-client-secret']).'">
							</div>
						</div>
					</div>
				</div>
				<h2>'.esc_html__('Facebook', 'fb').'</h2>
				<div class="form-row">
					<div class="form-label">
						<label>'.esc_html__('Enable', 'fb').'</label>
					</div>
					<div class="form-tooltip">
						<i class="fas fa-question-circle form-tooltip-anchor"></i>
						<div class="form-tooltip-content">
							'.esc_html__('Allow users to use their Facebook Account to sign in.', 'fb').'
						</div>
					</div>
					<div class="form-content">
						<div class="input-box">
							<div class="checkbox-toggle-container">
								<input class="checkbox-toggle" type="checkbox" value="on" id="facebook-enable" name="facebook-enable"'.($options['facebook-enable'] == 'on' ? ' checked="checked"' : '').' onchange="if(jQuery(this).is(\':checked\')){jQuery(\'#facebook-parameters\').fadeIn(100);}else{jQuery(\'#facebook-parameters\').fadeOut(100);}">
								<label for="facebook-enable"></label>
							</div>
						</div>
					</div>
				</div>
				<div id="facebook-parameters"'.($options['facebook-enable'] != 'on' ? ' style="display:none;"' : '').'>
					<div class="form-row">
						<div class="form-label">
						</div>
						<div class="form-tooltip">
						</div>
						<div class="form-content">
							<div class="inline-message inline-message-noclose inline-message-success">
								'.sprintf(esc_html__('Create new application in %sFacebook for Developers%s and copy-paste its credentials into fields below. Use the following parameters to create an application.', 'fb'), '<a href="https://developers.facebook.com/apps/" target="_blank">', '</a>').'
								<div class="prep-parameter-container">
									<label>'.esc_html__('Application type', 'fb').':</label>
									<pre>Consumer</pre>
								</div>
								<div class="prep-parameter-container">
									<label>'.esc_html__('Product', 'fb').':</label>
									<pre>Facebook Login</pre>
								</div>
								<div class="prep-parameter-container">
									<label>'.esc_html__('Valid OAuth Redirect URI', 'fb').':</label>
									<pre onclick="this.focus();this.select();">'.url('auth.php').'?facebook=auth</pre>
								</div>
							</div>
						</div>
					</div>
					<div class="form-row">
						<div class="form-label">
							<label>'.esc_html__('Client ID', 'fb').'</label>
						</div>
						<div class="form-tooltip">
							<i class="fas fa-question-circle form-tooltip-anchor"></i>
							<div class="form-tooltip-content">
								'.esc_html__('Enter your Facebook Application Client ID.', 'fb').'
							</div>
						</div>
						<div class="form-content">
							<div class="input-box">
								<input class="errorable" type="text" name="facebook-client-id" placeholder="'.esc_html__('Client ID', 'fb').'" value="'.esc_html($options['facebook-client-id']).'">
							</div>
						</div>
					</div>
					<div class="form-row">
						<div class="form-label">
							<label>'.esc_html__('Client Secret', 'fb').'</label>
						</div>
						<div class="form-tooltip">
							<i class="fas fa-question-circle form-tooltip-anchor"></i>
							<div class="form-tooltip-content">
								'.esc_html__('Enter your Facebook Application Client Secret.', 'fb').'
							</div>
						</div>
						<div class="form-content">
							<div class="input-box">
								<input class="errorable" type="text" name="facebook-client-secret" placeholder="'.esc_html__('Client Secret', 'fb').'" value="'.esc_html($options['facebook-client-secret']).'">
							</div>
						</div>
					</div>
				</div>
				<h2>'.esc_html__('VK', 'fb').'</h2>
				<div class="form-row">
					<div class="form-label">
						<label>'.esc_html__('Enable', 'fb').'</label>
					</div>
					<div class="form-tooltip">
						<i class="fas fa-question-circle form-tooltip-anchor"></i>
						<div class="form-tooltip-content">
							'.esc_html__('Allow users to use their VK Account to sign in.', 'fb').'
						</div>
					</div>
					<div class="form-content">
						<div class="input-box">
							<div class="checkbox-toggle-container">
								<input class="checkbox-toggle" type="checkbox" value="on" id="vk-enable" name="vk-enable"'.($options['vk-enable'] == 'on' ? ' checked="checked"' : '').' onchange="if(jQuery(this).is(\':checked\')){jQuery(\'#vk-parameters\').fadeIn(100);}else{jQuery(\'#vk-parameters\').fadeOut(100);}">
								<label for="vk-enable"></label>
							</div>
						</div>
					</div>
				</div>
				<div id="vk-parameters"'.($options['vk-enable'] != 'on' ? ' style="display:none;"' : '').'>
					<div class="form-row">
						<div class="form-label">
						</div>
						<div class="form-tooltip">
						</div>
						<div class="form-content">
							<div class="inline-message inline-message-noclose inline-message-success">
								'.sprintf(esc_html__('Create new application in %sVK Developers%s and copy-paste its credentials into fields below. Use the following parameters to create an application.', 'fb'), '<a href="https://vk.com/apps/" target="_blank">', '</a>').'
								<div class="prep-parameter-container">
									<label>'.esc_html__('Platform', 'fb').':</label>
									<pre>Website</pre>
								</div>
								<div class="prep-parameter-container">
									<label>'.esc_html__('Authorized redirect URI', 'fb').':</label>
									<pre onclick="this.focus();this.select();">'.url('auth-vk.php').'</pre>
								</div>
							</div>
						</div>
					</div>
					<div class="form-row">
						<div class="form-label">
							<label>'.esc_html__('App ID', 'fb').'</label>
						</div>
						<div class="form-tooltip">
							<i class="fas fa-question-circle form-tooltip-anchor"></i>
							<div class="form-tooltip-content">
								'.esc_html__('Enter your VK App ID.', 'fb').'
							</div>
						</div>
						<div class="form-content">
							<div class="input-box">
								<input class="errorable" type="text" name="vk-client-id" placeholder="'.esc_html__('App ID', 'fb').'" value="'.esc_html($options['vk-client-id']).'">
							</div>
						</div>
					</div>
					<div class="form-row">
						<div class="form-label">
							<label>'.esc_html__('Secure Key', 'fb').'</label>
						</div>
						<div class="form-tooltip">
							<i class="fas fa-question-circle form-tooltip-anchor"></i>
							<div class="form-tooltip-content">
								'.esc_html__('Enter your VK App Secure Key.', 'fb').'
							</div>
						</div>
						<div class="form-content">
							<div class="input-box">
								<input class="errorable" type="text" name="vk-client-secret" placeholder="'.esc_html__('Secure Key', 'fb').'" value="'.esc_html($options['vk-client-secret']).'">
							</div>
						</div>
					</div>
				</div>
			</div>
			'.($payment_enabled ? '<div id="tab-payments" class="tab-content">
				<h2>'.esc_html__('Stripe', 'fb').'</h2>
				<div class="form-row">
					<div class="form-label">
						<label>'.esc_html__('Enable', 'fb').'</label>
					</div>
					<div class="form-tooltip">
						<i class="fas fa-question-circle form-tooltip-anchor"></i>
						<div class="form-tooltip-content">
							'.esc_html__('Accept payments through Stripe.', 'fb').'
						</div>
					</div>
					<div class="form-content">
						<div class="input-box">
							<div class="checkbox-toggle-container">
								<input class="checkbox-toggle" type="checkbox" value="on" id="stripe-enable" name="stripe-enable"'.($options['stripe-enable'] == 'on' ? ' checked="checked"' : '').' onchange="if(jQuery(this).is(\':checked\')){jQuery(\'#stripe-parameters\').fadeIn(100);}else{jQuery(\'#stripe-parameters\').fadeOut(100);}">
								<label for="stripe-enable"></label>
							</div>
						</div>
					</div>
				</div>
				<div id="stripe-parameters"'.($options['stripe-enable'] != 'on' ? ' style="display:none;"' : '').'>
					<div class="form-row">
						<div class="form-label">
						</div>
						<div class="form-tooltip">
						</div>
						<div class="form-content">
							<div class="inline-message inline-message-noclose inline-message-success">
								'.sprintf(esc_html__('Create new webhook with the following URL and events in your %sStripe Dashboard%s.', 'fb'), '<a href="https://dashboard.stripe.com/account/webhooks" target="_blank">', '</a>').'
								<div class="prep-parameter-container">
									<label>'.esc_html__('Events', 'fb').':</label>
									<pre>checkout.session.completed
invoice.paid
invoice.payment_failed</pre>
								</div>
								<div class="prep-parameter-container">
									<label>'.esc_html__('Endpoint URL', 'fb').':</label>
									<pre onclick="this.focus();this.select();">'.url('ipn.php').'?payment=stripe</pre>
								</div>
							</div>
						</div>
					</div>
					<div class="form-row">
						<div class="form-label">
							<label>'.esc_html__('Publishable key', 'fb').'</label>
						</div>
						<div class="form-tooltip">
							<i class="fas fa-question-circle form-tooltip-anchor"></i>
							<div class="form-tooltip-content">
								'.esc_html__('Enter your publishable key.', 'fb').'
							</div>
						</div>
						<div class="form-content">
							<div class="input-box">
								<div class="input-element">
									<input class="errorable" type="text" name="stripe-public-key" placeholder="'.esc_html__('Publishable key', 'fb').'" value="'.esc_html($options['stripe-public-key']).'">
								</div>
								<label>'.sprintf(esc_html__('Find publishable key on %sAPI Keys%s page.', 'fb'), '<a href="https://dashboard.stripe.com/account/apikeys" target="_blank">', '</a>').'</label>
							</div>
						</div>
					</div>
					<div class="form-row">
						<div class="form-label">
							<label>'.esc_html__('Secret key', 'fb').'</label>
						</div>
						<div class="form-tooltip">
							<i class="fas fa-question-circle form-tooltip-anchor"></i>
							<div class="form-tooltip-content">
								'.esc_html__('Enter your secret key.', 'fb').'
							</div>
						</div>
						<div class="form-content">
							<div class="input-box">
								<div class="input-element">
									<input class="errorable" type="text" name="stripe-secret-key" placeholder="'.esc_html__('Secret key', 'fb').'" value="'.esc_html($options['stripe-secret-key']).'">
								</div>
								<label>'.sprintf(esc_html__('Find secret key on %sAPI Keys%s page.', 'fb'), '<a href="https://dashboard.stripe.com/account/apikeys" target="_blank">', '</a>').'</label>
							</div>
						</div>
					</div>
					<div class="form-row">
						<div class="form-label">
							<label>'.esc_html__('Signing secret', 'fb').'</label>
						</div>
						<div class="form-tooltip">
							<i class="fas fa-question-circle form-tooltip-anchor"></i>
							<div class="form-tooltip-content">
								'.esc_html__('Enter valid signing secret for webhook that you created earlier.', 'fb').'
							</div>
						</div>
						<div class="form-content">
							<div class="input-box">
								<div class="input-element">
									<input class="errorable" type="text" name="stripe-webhook-secret" placeholder="'.esc_html__('Signing secret', 'fb').'" value="'.esc_html($options['stripe-webhook-secret']).'">
								</div>
								<label>'.sprintf(esc_html__('Find it on %sWebhooks%s page. Click webhook that you created earlier, and find "Signing secret" parameter.', 'fb'), '<a href="https://dashboard.stripe.com/account/webhooks" target="_blank">', '</a>').'</label>
							</div>
						</div>
					</div>
				</div>
			</div>' : '').'
			<div id="tab-mail" class="tab-content">
				<h2>'.esc_html__('Sender parameters', 'fb').'</h2>
				<div class="sender-details">
					<div class="form-row">
						<div class="form-label">
							<label>'.esc_html__('Method', 'fb').'</label>
						</div>
						<div class="form-tooltip">
							<i class="fas fa-question-circle form-tooltip-anchor"></i>
							<div class="form-tooltip-content">
								'.esc_html__('Set mailing method. All email messages are sent using this mailing method.', 'fb').'
							</div>
						</div>
						<div class="form-content">
							<div class="input-box">
								<div class="bar-selector">
									<input class="radio" id="mail-method-mail" type="radio" name="mail-method" value="mail"'.($options['mail-method'] == 'smtp' ? '' : ' checked="checked"').' onchange="toggle_mail_method(this);"><label for="mail-method-mail">'.esc_html__('PHP Mail() Function', 'fb').'</label><input class="radio" id="mail-method-smtp" type="radio" name="mail-method" value="smtp"'.($options['mail-method'] == 'smtp' ? ' checked="checked"' : '').' onchange="toggle_mail_method(this);"><label for="mail-method-smtp">'.esc_html__('SMTP', 'fb').'</label>
								</div>
							</div>
						</div>
					</div>
					<div id="mail-method-mail-content"'.($options['mail-method'] == 'smtp' ? ' style="display: none;"' : '').'>
						<div class="form-row">
							<div class="form-label">
								<label>'.esc_html__('Sender', 'fb').'</label>
							</div>
							<div class="form-tooltip">
								<i class="fas fa-question-circle form-tooltip-anchor"></i>
								<div class="form-tooltip-content">
									'.esc_html__('Set sender name and email. All email messages are sent using these credentials as "FROM:" header value.', 'fb').'
								</div>
							</div>
							<div class="form-content">
								<div class="columns">
									<div class="column column-50">
										<div class="input-box">
											<div class="input-element">
												<input class="errorable" type="text" name="mail-from-name" placeholder="'.esc_html__('Name', 'fb').'" value="'.esc_html($options['mail-from-name']).'">
											</div>
											<label>'.esc_html__('Sender name', 'fb').'</label>
										</div>
									</div>
									<div class="column column-50">
										<div class="input-box">
											<div class="input-element">
												<input class="errorable" type="text" name="mail-from-email" placeholder="'.esc_html__('Email', 'fb').'" value="'.esc_html($options['mail-from-email']).'">
											</div>
											<label>'.esc_html__('Sender email', 'fb').'</label>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div id="mail-method-smtp-content"'.($options['mail-method'] != 'smtp' ? ' style="display: none;"' : '').'>
						<div class="form-row">
							<div class="form-label">
								<label>'.esc_html__('Sender', 'fb').'</label>
							</div>
							<div class="form-tooltip">
								<i class="fas fa-question-circle form-tooltip-anchor"></i>
								<div class="form-tooltip-content">
									'.esc_html__('Set sender name and email. All email messages are sent using these credentials as "FROM:" header value.', 'fb').'
								</div>
							</div>
							<div class="form-content">
								<div class="columns">
									<div class="column column-50">
										<div class="input-box">
											<div class="input-element">
												<input class="errorable" type="text" name="smtp-from-name" placeholder="'.esc_html__('Name', 'fb').'" value="'.esc_html($options['smtp-from-name']).'">
											</div>
											<label>'.esc_html__('Sender name', 'fb').'</label>
										</div>
									</div>
									<div class="column column-50">
										<div class="input-box">
											<div class="input-element">
												<input class="errorable" type="text" name="smtp-from-email" placeholder="'.esc_html__('Email', 'fb').'" value="'.esc_html($options['smtp-from-email']).'">
											</div>
											<label>'.esc_html__('Sender email', 'fb').'</label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="form-row">
							<div class="form-label">
								<label>'.esc_html__('Server', 'fb').'</label>
							</div>
							<div class="form-tooltip">
								<i class="fas fa-question-circle form-tooltip-anchor"></i>
								<div class="form-tooltip-content">
									'.esc_html__('Set encryption, mail server hostname and port.', 'fb').'
								</div>
							</div>
							<div class="form-content">
								<div class="columns">
									<div class="column column-30">
										<div class="input-box">
											<div class="input-element">
												<select id="smtp-secure" name="smtp-secure">';
		foreach ($smtp_secures as $key => $value) {
			$content .= '
													<option value="'.esc_html($key).'"'.($key == $options['smtp-secure'] ? ' selected="selected"' : '').'>'.esc_html($value).'</option>';
			}
		$content .= '
												</select>
											</div>
											<label>'.esc_html__('Encryption', 'fb').'</label>
										</div>
									</div>
									<div class="column column-40">
										<div class="input-box">
											<div class="input-element">
												<input class="errorable" type="text" name="smtp-server" placeholder="'.esc_html__('Hostname', 'fb').'" value="'.esc_html($options['smtp-server']).'">
											</div>
											<label>'.esc_html__('Hostname', 'fb').'</label>
										</div>
									</div>
									<div class="column column-30">
										<div class="input-box">
											<div class="input-element">
												<input class="errorable" type="text" name="smtp-port" placeholder="'.esc_html__('Port', 'fb').'" value="'.esc_html($options['smtp-port']).'">
											</div>
											<label>'.esc_html__('Port', 'fb').'</label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="form-row">
							<div class="form-label">
								<label>'.esc_html__('User', 'fb').'</label>
							</div>
							<div class="form-tooltip">
								<i class="fas fa-question-circle form-tooltip-anchor"></i>
								<div class="form-tooltip-content">
									'.esc_html__('Set sender name and email. All email messages are sent using these credentials as "FROM:" header value.', 'fb').'
								</div>
							</div>
							<div class="form-content">
								<div class="columns">
									<div class="column column-50">
										<div class="input-box">
											<div class="input-element">
												<input class="errorable" type="text" name="smtp-username" placeholder="'.esc_html__('Username', 'fb').'" value="'.esc_html($options['smtp-username']).'">
											</div>
											<label>'.esc_html__('Username', 'fb').'</label>
										</div>
									</div>
									<div class="column column-50">
										<div class="input-box">
											<div class="input-element">
												<input class="errorable" type="text" name="smtp-password" placeholder="'.esc_html__('Password', 'fb').'" value="'.esc_html($options['smtp-password']).'">
											</div>
											<label>'.esc_html__('Password', 'fb').'</label>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="form-row">
					<div class="form-label">
					</div>
					<div class="form-tooltip">
					</div>
					<div class="form-content">
						<div class="input-box">
							<div id="test-mailing-message" class="inline-message inline-message-noclose inline-message-warning" style="display: none;"></div>
							<a class="social-button" href="#" onclick="return test_mailing(this);">
								<i class="far fa-envelope"></i> '.esc_html__('Test mailing', 'fb').'
							</a>
							<label>'.sprintf(esc_html__('Press button and check your inbox (%s). If you do not see test message, something does not work. Do not forget to check SPAM folder.', 'fb'), esc_html($user_details['email'])).'</label>
						</div>
					</div>
				</div>
				<h2>'.esc_html__('Confirmation email', 'fb').'</h2>
				<div class="form-row">
					<div class="form-label">
						<label>'.esc_html__('Subject', 'fb').'</label>
					</div>
					<div class="form-tooltip">
						<i class="fas fa-question-circle form-tooltip-anchor"></i>
						<div class="form-tooltip-content">
							'.esc_html__('Newly registered users must confirm their email address to receive notifications. Specify the subject of confirmation email.', 'fb').'
						</div>
					</div>
					<div class="form-content">
						<div class="input-box">
							<input class="errorable" type="text" name="confirm-subject" placeholder="'.esc_html__('Subject', 'fb').'" value="'.esc_html($options['confirm-subject']).'">
						</div>
					</div>
				</div>
				<div class="form-row">
					<div class="form-label">
						<label>'.esc_html__('Message', 'fb').'</label>
					</div>
					<div class="form-tooltip">
						<i class="fas fa-question-circle form-tooltip-anchor"></i>
						<div class="form-tooltip-content">
							'.esc_html__('Specify the message of confirmation email. You can use the following shortcodes.', 'fb').'<br />
							<code>{name}</code> - '.esc_html__('Full name', 'fb').',<br />
							<code>{email}</code> - '.esc_html__('Email address', 'fb').',<br />
							<code>{confirmation-url}</code> - '.esc_html__('URL that is used to confirm email address.', 'fb').'
						</div>
					</div>
					<div class="form-content">
						<div class="input-box">
							<textarea class="errorable" name="confirm-message" placeholder="'.esc_html__('Message', 'fb').'">'.esc_html($options['confirm-message']).'</textarea>
						</div>
					</div>
				</div>
				<h2>'.esc_html__('Reset password', 'fb').'</h2>
				<div class="form-row">
					<div class="form-label">
						<label>'.esc_html__('Subject', 'fb').'</label>
					</div>
					<div class="form-tooltip">
						<i class="fas fa-question-circle form-tooltip-anchor"></i>
						<div class="form-tooltip-content">
							'.esc_html__('Specify the subject of reset password email.', 'fb').'
						</div>
					</div>
					<div class="form-content">
						<div class="input-box">
							<input class="errorable" type="text" name="reset-subject" placeholder="'.esc_html__('Subject', 'fb').'" value="'.esc_html($options['reset-subject']).'">
						</div>
					</div>
				</div>
				<div class="form-row">
					<div class="form-label">
						<label>'.esc_html__('Message', 'fb').'</label>
					</div>
					<div class="form-tooltip">
						<i class="fas fa-question-circle form-tooltip-anchor"></i>
						<div class="form-tooltip-content">
							'.esc_html__('Specify the message of reset password email. You can use the following shortcodes.', 'fb').'<br />
							<code>{name}</code> - '.esc_html__('Full name', 'fb').',<br />
							<code>{email}</code> - '.esc_html__('Email address', 'db').',<br />
							<code>{reset-password-url}</code> - '.esc_html__('URL that is used to reset the password.', 'fb').'
						</div>
					</div>
					<div class="form-content">
						<div class="input-box">
							<textarea class="errorable" name="reset-message" placeholder="'.esc_html__('Message', 'fb').'">'.esc_html($options['reset-message']).'</textarea>
						</div>
					</div>
				</div>
			</div>
			<div class="form-row right-align">
				<input type="hidden" name="action" value="site-settings-save">
				<a class="button" href="#" onclick="return save_form(this);" data-label="'.esc_html__('Save Settings', 'fb').'">
					<span>'.esc_html__('Save Settings', 'fb').'</span>
					<i class="fas fa-angle-right"></i>
				</a>
			</div>
		</div>';
		
		return array('title' => $page_title, 'content' => $content);
	}

	function ajax_mail_test() {
		global $wpdb, $options, $user_details, $language;

		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Please enter your account to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		} else if ($user_details['role'] != 'admin') {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('You do not have permissions to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
		foreach ($options as $key => $value) {
			if (array_key_exists($key, $_REQUEST)) {
				$options[$key] = trim(stripslashes($_REQUEST[$key]));
			}
		}
	
		$title = translatable_parse($options['title']);
	
		$message = sprintf(esc_html__('This is a test message. It was sent from %s (%s) using the following mailing parameters.', 'fb'), (array_key_exists($language, $title) && !empty($title[$language]) ? esc_html($title[$language]) : esc_html($title['default'])), esc_html($options['url'])).'<br />';
		if ($options['mail-method'] == 'smtp') {
			$message .= esc_html__('Method: SMTP', 'fb').'<br />'.esc_html__('Sender Name', 'fb').': '.$options['smtp-from-name'].'<br />'.esc_html__('Sender Email', 'fb').': '.$options['smtp-from-email'].'<br />'.esc_html__('Encryption', 'fb').': '.$options['smtp-secure'].'<br />'.esc_html__('Server', 'fb').': '.$options['smtp-server'].'<br />'.esc_html__('Port', 'fb').': '.$options['smtp-port'].'<br />'.esc_html__('Username', 'fb').': '.$options['smtp-username'].'<br />'.esc_html__('Password', 'fb').': '.$options['smtp-password'];
		} else {
			$message .= esc_html__('Method: PHP Mail() function', 'fb').'<br />'.esc_html__('Sender Name', 'fb').': '.$options['mail-from-name'].'<br />'.esc_html__('Sender Email', 'fb').': '.$options['mail-from-email'];
		}
		
		$result = send_mail($user_details['email'], esc_html__('Test Message', 'fb'), $message, '', array(), true);
		if ($result !== true) {
			$return_object = array();
			$return_object['status'] = 'ERROR';
			$return_object['message'] = $result;
			echo '<fb-debug>'.json_encode($return_object).'</fb-debug>';
			exit;
		}
		
		$return_object = array('status' => 'OK', 'message' => sprintf(esc_html__('Test message successfully sent. Please check your inbox (%s).', 'fb'), esc_html($user_details['email'])));
		echo json_encode($return_object);
		exit;
	}

	function ajax_settings_save() {
		global $wpdb, $options, $user_details, $language, $languages, $mail_methods, $smtp_secures;

		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Please enter your account to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		} else if ($user_details['role'] != 'admin') {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('You do not have permissions to perform this action.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
	
		$errors = array();
	
		$tr_options = array(
			'title' => '',
			'tagline' => '',
			'copyright' => ''
		);
		foreach ($options as $key => $value) {
			if (array_key_exists($key, $_REQUEST)) {
				if (array_key_exists($key, $tr_options)) $tr_options[$key] = translatable_populate($_REQUEST[$key]);
				else $options[$key] = trim(stripslashes($_REQUEST[$key]));
			} else if (in_array($value, array('on', 'off'))) {
				$options[$key] = 'off';
			}
		}
	
		if (mb_strlen($tr_options['title']['default']) < 2) $errors['title[default]'] = esc_html__('The site title is too short.', 'fb');
		else if (mb_strlen($tr_options['title']['default']) > 127) $errors['title[default]'] = esc_html__('The site title is too long.', 'fb');
		foreach ($tr_options['title'] as $key => $value) {
			if ($key != 'default') {
				if (mb_strlen($value) > 0 && mb_strlen($value) < 2) $errors['title['.$key.']'] = esc_html__('The translation is too short.', 'fb');
				else if (mb_strlen($value) > 127) $errors['title['.$key.']'] = esc_html__('The translation is too long.', 'fb');
			}
		}
		if (mb_strlen($tr_options['tagline']['default']) > 255) $errors['tagline[default]'] = esc_html__('The site tagline is too long.', 'fb');
		foreach ($tr_options['tagline'] as $key => $value) {
			if ($key != 'default') {
				if (mb_strlen($value) > 255) $errors['tagline['.$key.']'] = esc_html__('The translation is too long.', 'fb');
			}
		}
		if (mb_strlen($tr_options['copyright']['default']) > 255) $errors['copyright[default]'] = esc_html__('The site copyright is too long.', 'fb');
		foreach ($tr_options['copyright'] as $key => $value) {
			if ($key != 'default') {
				if (mb_strlen($value) > 255) $errors['copyright['.$key.']'] = esc_html__('The translation is too long.', 'fb');
			}
		}

		$payment_enabled = is_payment_enabled();
		if ($payment_enabled) {
			if ($options['stripe-enable'] == 'on') {
				if (empty($options['stripe-public-key'])) $errors['stripe-public-key'] = esc_html__('Stripe publishable key can not be empty.', 'fb');
				if (empty($options['stripe-secret-key'])) $errors['stripe-secret-key'] = esc_html__('Stripe secret key can not be empty.', 'fb');
				if (empty($options['stripe-webhook-secret'])) $errors['stripe-webhook-secret'] = esc_html__('Stripe signing secret can not be empty.', 'fb');
			}
		}
		if ($options['google-enable'] == 'on') {
			if (empty($options['google-client-id'])) $errors['google-client-id'] = esc_html__('Google OAuth 2.0 Client ID can not be empty.', 'fb');
			if (empty($options['google-client-secret'])) $errors['google-client-secret'] = esc_html__('Google OAuth 2.0 Client Secret can not be empty.', 'fb');
		}
	
		if ($options['facebook-enable'] == 'on') {
			if (empty($options['facebook-client-id'])) $errors['facebook-client-id'] = esc_html__('Facebook Application Client ID can not be empty.', 'fb');
			if (empty($options['facebook-client-secret'])) $errors['facebook-client-secret'] = esc_html__('Facebook Application Client Secret can not be empty.', 'fb');
		}
	
		if ($options['vk-enable'] == 'on') {
			if (empty($options['vk-client-id'])) $errors['vk-client-id'] = esc_html__('VK App ID can not be empty.', 'fb');
			if (empty($options['vk-client-secret'])) $errors['vk-client-secret'] = esc_html__('VK App Secure Key can not be empty.', 'fb');
		}
	
		if ($options['mail-method'] == 'mail') {
			if (empty($options['mail-from-name'])) $errors['mail-from-name'] = esc_html__('Invalid sender name.', 'fb');
			if ($options['mail-from-email'] == '' || !preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,19})$/i", $options['mail-from-email'])) $errors['mail-from-email'] = esc_html__('Invalid sender e-mail.', 'fb');
		} else if ($options['mail-method'] == 'smtp') {
			if (empty($options['smtp-from-name'])) $errors['smtp-from-name'] = esc_html__('Invalid sender name.', 'fb');
			if ($options['smtp-from-email'] == '' || !preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,19})$/i", $options['smtp-from-email'])) $errors['smtp-from-email'] = esc_html__('Invalid sender e-mail.', 'fb');
			if (empty($options['smtp-server']) || !is_hostname($options['smtp-server'])) $errors['smtp-server'] = esc_html__('Invalid SMTP server.', 'fb');
			if (empty($options['smtp-port']) || !ctype_digit($options['smtp-port'])) $errors['smtp-port'] = esc_html__('Invalid SMTP port.', 'fb');
			if (empty($options['smtp-username'])) $errors['smtp-username'] = esc_html__('Invalid SMTP username.', 'fb');
			if (empty($options['smtp-password'])) $errors['smtp-password'] = esc_html__('Invalid SMTP password.', 'fb');
		}
	
		if (mb_strlen($options['confirm-subject']) < 2) $errors['confirm-subject'] = esc_html__('The confirmation email subject is too short.', 'fb');
		else if (mb_strlen($options['confirm-subject']) > 127) $errors['confirm-subject'] = esc_html__('The confirmation email subject is too long.', 'fb');
		if (mb_strlen($options['confirm-message']) < 2) $errors['confirm-message'] = esc_html__('The confirmation email message is too short.', 'fb');
	
		if (mb_strlen($options['reset-subject']) < 2) $errors['reset-subject'] = esc_html__('The reset password email subject is too short.', 'fb');
		else if (mb_strlen($options['reset-subject']) > 127) $errors['reset-subject'] = esc_html__('The reset password email subject is too long.', 'fb');
		if (mb_strlen($options['reset-message']) < 2) $errors['reset-message'] = esc_html__('The reset password email message is too short.', 'fb');
	
		if (!empty($errors)) {
			$return_data = array('status' => 'ERROR', 'errors' => $errors);
			echo json_encode($return_data);
			exit;
		}
	
		$image = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."uploads WHERE uuid = '".esc_sql($options['pattern'])."' AND deleted != '1'", ARRAY_A);
		if (!empty($image)) $options['pattern'] = $image['id'];
		else $options['pattern'] = 0;
	
		$options['title'] = json_encode($tr_options['title']);
		$options['tagline'] = json_encode($tr_options['tagline']);
		$options['copyright'] = json_encode($tr_options['copyright']);
	
		save_options('core', $options);
	
		$return_object = array('status' => 'OK', 'message' => esc_html__('Settings successfully saved.', 'fb'));
		echo json_encode($return_object);
		exit;
	}





	function _page_template() {
		global $wpdb, $options, $user_details, $language;

		if (empty($user_details)) {
			header("Location: ".url('login.php').'?redirect='.urlencode(url('?page=admin-users')));
			exit;
		} else if ($user_details['role'] != 'admin') {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}

		$content = '';
		$page_title = esc_html__('Users', 'fb');

		return array('title' => $page_title, 'content' => $content);
	}
	function _ajax_template() {
		global $wpdb, $options, $user_details, $language;

	}


}
$admin = new admin_class();
?>