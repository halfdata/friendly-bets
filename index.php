<?php
include_once(dirname(__FILE__).'/inc/functions.php');
include_once(dirname(__FILE__).'/inc/icdb.php');
include_once(dirname(__FILE__).'/inc/common.php');

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

do_action('admin_init');
do_action('admin_menu');
$page = array('slug' => 'index');
if (array_key_exists('page', $_REQUEST)) $p = $_REQUEST['page'];
else $p = 'index';
foreach ($site_data['menu'] as $slug => $item) {
	if (array_key_exists('submenu', $item)) {
		$found = false;
		foreach ($item['submenu'] as $submenu_slug => $submenu_item) {
			if ($p == $submenu_slug) {
				$page = $submenu_item;
				$page['slug'] = $submenu_slug;
				$page['parent'] = $slug;
				$found = true;
				break;
			}
		}
		if ($found) break;
	} else if ($p == $slug) {
		$page = $item;
		$page['slug'] = $slug;
		break;
	}
}

if (empty($user_details)) {
	if ($page['role'] != '') {
		header("Location: ".url('login.php?redirect='.urlencode('//'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'])));
		exit;
	}
} else {
	if ($page['role'] == 'admin' && $user_details['role'] != 'admin') {
		$_SESSION['error-message'] = esc_html__('You do not have permissions to access this area.', 'fb');
		header("Location: ".url(''));
		exit;
	}
}
if ($page['role'] == 'admin' && (empty($user_details) || $user_details['role'] ) != 'admin') return;
if ($page['role'] == 'user' && empty($user_details)) return;

$template_options = array(
    'title' => $options['title']
);

$page_data = array();
if (!empty($page['function'])) {
    $page_data = call_user_func_array($page['function'], array());
} else {
	http_response_code(404);
	$page_data = array('title' => '404', 'content' => content_404());
}
if (array_key_exists('title', $page_data)) $template_options['title'] = $page_data['title'];

include_once(dirname(__FILE__).'/inc/header.php');
echo $page_data['content'];
echo admin_dialog_html();
include_once(dirname(__FILE__).'/inc/footer.php');
?>