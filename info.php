<?php
include_once(dirname(__FILE__).'/inc/functions.php');
include_once(dirname(__FILE__).'/inc/icdb.php');
include_once(dirname(__FILE__).'/inc/common.php');

if (empty($user_details)) {
    header("Location: ".url('login.php').'?redirect='.urlencode(url('info.php')));
    exit;
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

?>
<!DOCTYPE html>
<html lang="en">
<head> 
    <meta name="robots" content="noindex,nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" /> 
    <meta http-equiv="content-style-type" content="text/css" /> 
    <title><?php echo esc_html($user_details['name']).' ('.esc_html($user_details['email']).')'; ?></title>
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
<body>
<?php
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
?>
<div class="footer"><?php echo sprintf(esc_html__('Report cereated: %s'), timestamp_string(time(), $options['date-format'].' H:i')); ?></div>
</body>
</html>