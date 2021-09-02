<?php
include_once(dirname(__FILE__).'/inc/functions.php');
include_once(dirname(__FILE__).'/inc/icdb.php');
include_once(dirname(__FILE__).'/inc/common.php');

if (empty($user_details)) {
    header("Location: ".url('login.php').'?redirect='.urlencode(url('users.php')));
    exit;
} else if ($user_details['role'] != 'admin') {
    header("Location: ".url('404.php'));
    exit;
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (array_key_exists('s', $_GET)) $search_query = trim(stripslashes($_GET["s"]));
else $search_query = "";

$tmp = $wpdb->get_row("SELECT COUNT(*) AS total FROM ".$wpdb->prefix."users WHERE deleted != '1'".((strlen($search_query) > 0) ? " AND (name LIKE '%".esc_sql($search_query)."%' OR email LIKE '%".esc_sql($search_query)."%' OR login LIKE '%".esc_sql($search_query)."%')" : ""), ARRAY_A);
$total = $tmp["total"];

$totalpages = ceil($total/RECORDS_PER_PAGE);
if ($totalpages == 0) $totalpages = 1;
if (array_key_exists('p', $_GET)) $page = intval($_GET["p"]);
else $page = 1;
if ($page < 1 || $page > $totalpages) $page = 1;
$switcher = page_switcher(url('users.php').((strlen($search_query) > 0) ? "?s=".urlencode($search_query) : ""), $page, $totalpages);

$users = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."users WHERE deleted != '1'".((strlen($search_query) > 0) ? " AND (name LIKE '%".esc_sql($search_query)."%' OR email LIKE '%".esc_sql($search_query)."%' OR login LIKE '%".esc_sql($search_query)."%')" : "")." ORDER BY created DESC LIMIT ".(($page-1)*RECORDS_PER_PAGE).", ".RECORDS_PER_PAGE, ARRAY_A);
unset($page);

do_action('admin_menu');

$template_options = array(
    'title' => esc_html__('Users', 'fb')
);
include_once(dirname(__FILE__).'/inc/header.php');
?>
<h1><?php echo esc_html__('Users', 'fb'); ?></h1>
<div class="table-funcbar">
	<form action="<?php echo url('users.php'); ?>" method="get" class="table-filter-form">
		<input type="text" name="s" value="<?php echo esc_html($search_query); ?>" placeholder="<?php echo esc_html__('Search', 'fb'); ?>" />
		<input type="submit" class="button" value="<?php echo esc_html__('Search', 'fb'); ?>" />
		<?php echo ((strlen($search_query) > 0) ? '<input type="button" class="button" value="'.esc_html__('Reset search results', 'fb').'" onclick="window.location.href=\''.url('users.php').'\';" />' : ''); ?>
	</form>
</div>
<div class="table-funcbar">
	<div class="table-pageswitcher"><?php echo $switcher; ?></div>
	<div class="table-buttons"><a href="<?php echo url('add-user.php'); ?>" class="button button-small"><i class="fas fa-plus"></i><span><?php echo esc_html__('Create New User', 'fb'); ?></span></a></div>
</div>
<div class="table">
	<table>
		<thead>
			<tr>
				<th class="table-column-selector"></th>
				<th><?php echo esc_html__('Email', 'fb'); ?></th>
				<th><?php echo esc_html__('Name', 'fb'); ?></th>
				<th class="table-column-100"><?php echo esc_html__('Role', 'fb'); ?></th>
				<th class="table-column-created"><?php echo esc_html__('Created', 'fb'); ?></th>
				<th class="table-column-actions"></th>
			</tr>
		</thead>
		<tbody>
<?php
	if (sizeof($users) > 0) {
		foreach ($users as $user) {
			echo '
			<tr>
				<td data-label=""><input class="checkbox-fa-check" type="checkbox" name="records[]" id="user-'.esc_html($user['id']).'" value="'.esc_html($user['id']).'"><label for="user-'.esc_html($user['id']).'"></label></td>
				<td data-label="'.esc_html__('Email', 'fb').'"><a href="'.url('add-user.php').'?id='.esc_html($user['id']).'"><strong>'.esc_html($user['email']).'</strong></a><span class="table-badge-status">'.($user['status'] == 'inactive' ? '<span class="badge badge-danger">'.esc_html__('Inactive', 'fb').'</span>' : '').'</span><label class="table-note">UID: '.esc_html($user['uuid']).'</label></td>
				<td data-label="'.esc_html__('Name', 'fb').'">'.esc_html($user['name']).'</td>
				<td data-label="'.esc_html__('Role', 'fb').'">'.esc_html($user['role']).'</td>
				<td data-label="'.esc_html__('Created', 'fb').'">'.esc_html(timestamp_string($user['created'], $options['date-format'].' H:i')).'</td>
				<td data-label="'.esc_html__('Actions', 'fb').'">
					<div class="item-menu">
						<span><i class="fas fa-ellipsis-v"></i></span>
						<ul>
							<li><a href="'.url('add-user.php').'?id='.esc_html($user['id']).'">'.esc_html__('Edit', 'fb').'</a></li>
							'.(in_array($user['status'], array('active', 'inactive')) ? '<li><a href="#" data-status="'.esc_html($user['status']).'" data-id="'.esc_html($user['id']).'" data-doing="'.($user['status'] == 'active' ? esc_html__('Deactivating...', 'fb') : esc_html__('Activating...', 'fb')).'" onclick="return user_status_toggle(this);">'.($user['status'] == 'active' ? esc_html__('Deactivate', 'fb') : esc_html__('Activate', 'fb')).'</a></li>' : '').'
							<li class="item-menu-line"></li>
							<li><a href="#" data-id="'.esc_html($user['id']).'" data-doing="'.esc_html__('Deleting...', 'fb').'" onclick="return user_delete(this);">'.esc_html__('Delete', 'fb').'</a></li>
						</ul>
					</div>
				</td>
			</tr>';
		}
	}
	echo '
			<tr class="table-empty"'.(sizeof($users) > 0 ? ' style="display: none;"' : '').'><td colspan="6">'.((strlen($search_query) > 0) ? esc_html__('No results found for', 'fb').' "<strong>'.esc_html($search_query).'</strong>".' : esc_html__('List is empty.', 'fb')).'</td></tr>';
?>
		</tbody>
	</table>
</div>
<div class="table-funcbar">
	<div class="table-pageswitcher"><?php echo $switcher; ?></div>
	<div class="table-buttons">
		<div class="multi-button multi-button-small">
			<span><?php echo esc_html__('Bulk actions', 'fb'); ?><i class="fas fa-angle-down"></i></span>
			<ul>
				<li><a href="#" data-action="activate" data-doing="<?php echo esc_html__('Activating...', 'fb'); ?>" onclick="return users_bulk_action(this);"><?php echo esc_html__('Activate', 'fb'); ?></a></li>
				<li><a href="#" data-action="deactivate" data-doing="<?php echo esc_html__('Deactivating...', 'fb'); ?>" onclick="return users_bulk_action(this);"><?php echo esc_html__('Deactivate', 'fb'); ?></a></li>
				<li><a href="#" data-action="delete" data-doing="<?php echo esc_html__('Deleting...', 'fb'); ?>" onclick="return users_bulk_delete(this);"><?php echo esc_html__('Delete', 'fb'); ?></a></li>
			</ul>
		</div>
		<a href="<?php echo url('add-user.php'); ?>" class="button button-small"><i class="fas fa-plus"></i><span><?php echo esc_html__('Create New User', 'fb'); ?></span></a>
	</div>
</div>
<?php
echo admin_dialog_html();
include_once(dirname(__FILE__).'/inc/footer.php');
?>