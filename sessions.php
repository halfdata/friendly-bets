<?php
include_once(dirname(__FILE__).'/inc/functions.php');
include_once(dirname(__FILE__).'/inc/icdb.php');
include_once(dirname(__FILE__).'/inc/common.php');

if (empty($user_details)) {
    header("Location: ".url('login.php').'?redirect='.urlencode(url('sessions.php')));
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

$tmp = $wpdb->get_row("SELECT COUNT(*) AS total FROM ".$wpdb->prefix."sessions t1 JOIN ".$wpdb->prefix."users t2 ON t1.user_id = t2.id WHERE t2.deleted != '1' AND t1.registered + t1.valid_period > '".esc_sql(time())."'".((strlen($search_query) > 0) ? " AND (t2.name LIKE '%".esc_sql($search_query)."%' OR t2.email LIKE '%".esc_sql($search_query)."%' OR t2.login LIKE '%".esc_sql($search_query)."%')" : ""), ARRAY_A);
$total = $tmp["total"];

$totalpages = ceil($total/RECORDS_PER_PAGE);
if ($totalpages == 0) $totalpages = 1;
if (array_key_exists('p', $_GET)) $page = intval($_GET["p"]);
else $page = 1;
if ($page < 1 || $page > $totalpages) $page = 1;
$switcher = page_switcher(url('sessions.php').((strlen($search_query) > 0) ? "?s=".urlencode($search_query) : ""), $page, $totalpages);

$sessions = $wpdb->get_results("SELECT t1.*, t2.name AS user_name, t2.login AS user_login, t2.email AS user_email FROM ".$wpdb->prefix."sessions t1 JOIN ".$wpdb->prefix."users t2 ON t1.user_id = t2.id WHERE t2.deleted != '1' AND t1.registered + t1.valid_period > '".esc_sql(time())."'".((strlen($search_query) > 0) ? " AND (t2.name LIKE '%".esc_sql($search_query)."%' OR t2.email LIKE '%".esc_sql($search_query)."%' OR t2.login LIKE '%".esc_sql($search_query)."%')" : "")." ORDER BY t1.registered DESC LIMIT ".(($page-1)*RECORDS_PER_PAGE).", ".RECORDS_PER_PAGE, ARRAY_A);
unset($page);

do_action('admin_menu');

$template_options = array(
    'title' => esc_html__('Active Sessions', 'fb')
);
include_once(dirname(__FILE__).'/inc/header.php');
?>
<h1><?php echo esc_html__('Active Sessions', 'fb'); ?></h1>
<div class="table-funcbar">
	<form action="<?php echo url('sessions.php'); ?>" method="get" class="table-filter-form">
		<input type="text" name="s" value="<?php echo esc_html($search_query); ?>" placeholder="<?php echo esc_html__('Search', 'fb'); ?>" />
		<input type="submit" class="button" value="<?php echo esc_html__('Search', 'fb'); ?>" />
		<?php echo ((strlen($search_query) > 0) ? '<input type="button" class="button" value="'.esc_html__('Reset search results', 'fb').'" onclick="window.location.href=\''.url('sessions.php').'\';" />' : ''); ?>
	</form>
</div>
<div class="table-funcbar">
	<div class="table-pageswitcher"><?php echo $switcher; ?></div>
</div>
<div class="table">
	<table>
		<thead>
			<tr>
				<th class="table-column-selector"></th>
				<th><?php echo esc_html__('User', 'fb'); ?></th>
				<th class="table-column-100"><?php echo esc_html__('Source', 'fb'); ?></th>
				<th class="table-column-created"><?php echo esc_html__('Created', 'fb'); ?></th>
				<th class="table-column-actions"></th>
			</tr>
		</thead>
		<tbody>
<?php
	if (sizeof($sessions) > 0) {
		foreach ($sessions as $session) {
			echo '
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
	echo '
			<tr class="table-empty"'.(sizeof($sessions) > 0 ? ' style="display: none;"' : '').'><td colspan="5">'.((strlen($search_query) > 0) ? esc_html__('No results found for', 'fb').' "<strong>'.esc_html($search_query).'</strong>".' : esc_html__('List is empty.', 'fb')).'</td></tr>';
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
				<li><a href="#" data-action="delete" data-doing="<?php echo esc_html__('Closing...', 'fb'); ?>" onclick="return sessions_bulk_delete(this);"><?php echo esc_html__('Close sessions', 'fb'); ?></a></li>
			</ul>
		</div>
	</div>
</div>
<?php
echo admin_dialog_html();
include_once(dirname(__FILE__).'/inc/footer.php');
?>