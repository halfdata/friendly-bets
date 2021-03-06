<?php	 	
if (!defined('INTEGRITY')) exit;
do_action('admin_enqueue_scripts');
?>
<!DOCTYPE html>
<html lang="en">
<head> 
	<meta name="robots" content="noindex,nofollow">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" /> 
	<meta http-equiv="content-style-type" content="text/css" /> 
	<title><?php echo $template_options['title']; ?></title> 
	<link href="//fonts.googleapis.com/css?family=Open+Sans:100,100italic,300,300italic,400,400italic,600,600italic,700,700italic,800,800italic&subset=cyrillic,cyrillic-ext,greek,greek-ext,latin,latin-ext,vietnamese" rel="stylesheet" type="text/css">
	<link href="<?php echo $options["url"]; ?>css/jquery-ui.min.css" rel="stylesheet" type="text/css" />
	<link href="<?php echo $options["url"]; ?>css/fontawesome.css" rel="stylesheet" type="text/css" />
	<link href="<?php echo $options["url"]; ?>css/tooltipster.bundle.min.css" rel="stylesheet" type="text/css" />
	<link href="<?php echo $options["url"]; ?>css/airdatepicker.min.css" rel="stylesheet" type="text/css" />
	<link href="<?php echo $options["url"]; ?>css/style.css" rel="stylesheet" type="text/css" />
<?php
$output = array();
do {
	$printed = false;
	foreach($site_data['styles'] as $slug => $style) {
		if (!in_array($slug, $output)) {
			$diff = array_diff($style['deps'], $output);
			if (empty($diff)) {
				$output[] = $slug;
				echo '
	<link id="'.$slug.'" href="'.$style['url'].'" rel="stylesheet">';
				$printed = true;
			}
		}
	}
} while ($printed)
?>

	<script src="<?php echo $options["url"]; ?>js/jquery.min.js"></script>
	<script src="<?php echo $options["url"]; ?>js/jquery-ui.min.js"></script>
	<script src="<?php echo $options["url"]; ?>js/tooltipster.bundle.min.js"></script>
	<script src="<?php echo $options["url"]; ?>js/airdatepicker.js"></script>
	<script src="<?php echo $options["url"]; ?>js/script.js"></script>
<?php
$output = array('jquery');
do {
	$printed = false;
	foreach($site_data['scripts'] as $slug => $script) {
		if (!in_array($slug, $output)) {
			$diff = array_diff($script['deps'], $output);
			if (empty($diff)) {
				$output[] = $slug;
				echo '
	<script id="'.esc_html($slug).'" src="'.esc_html($script['url']).'"></script>';
				$printed = true;
			}
		}
	}
} while ($printed)
?>

	<script>var ajax_url = "<?php echo url("ajax.php"); ?>"; var date_format = "<?php echo esc_html($options['date-format']); ?>"; var language = "<?php echo esc_html($options['language']); ?>";</script>
<?php
do_action('admin_head');
echo '
</head>
<body>
	<input type="hidden" name="_token" value="'.esc_html($csrf_token).'" />
	<div class="wrapper">
		<header>';
if (!empty($global_warnings)) {
	foreach ($global_warnings as $warning) {
		echo global_warning_html($warning);
	}
}
if (!empty($global_info)) {
	foreach ($global_info as $info) {
		echo global_info_html($info);
	}
}
do_action('admin_menu_top');
echo '
			<span class="top-menu-toggle" onclick="jQuery(\'.top-menu-bar\').slideToggle(100);"><i class="fas fa-bars"></i></span>
			<nav class="top-menu-bar">
				<ul class="top-menu">';
foreach($site_data['menu'] as $slug => $item) {
	if ($slug == 'admin' || $slug == 'user' || $slug == 'upgrade') continue;
	if (empty($item['menu-title'])) continue;
	if (array_key_exists('query', $item) && !empty($item['query'])) $query = '&'.http_build_query($item['query']);
	else $query = '';
	$attr = '';
	if (array_key_exists('a-attr', $item['options'])) $attr = ' '.$item['options']['a-attr'];
	else $attr = '';
	echo '
					<li class="top-menu-item'.(array_key_exists('submenu', $item) ? ' top-submenu' : '').(!empty($page) && ((array_key_exists('parent', $page) && $page['parent'] == $slug) || $page['slug'] == $slug)  ? ' top-menu-active' : '').'">
						'.(empty($item['function']) ? '<span>'.esc_html($item['menu-title']).(array_key_exists('submenu', $item) ? '<i class="fas fa-angle-down"></i>' : '').'</span>' : '<a href="'.esc_html(url($slug == 'index' ? '' : '?page='.urlencode($slug).$query)).'"'.$attr.'>'.esc_html($item['menu-title']).(array_key_exists('submenu', $item) ? '<i class="fas fa-angle-down"></i>' : '').'</a>');
	if (array_key_exists('submenu', $item)) {
			echo '
						<ul>';
			foreach ($item['submenu'] as $submenu_slug => $submenu_item) {
				if (empty($submenu_item['menu-title'])) continue;
				if (array_key_exists('query', $submenu_item) && !empty($submenu_item['query'])) $query = '&'.http_build_query($submenu_item['query']);
				else $query = '';
				if (array_key_exists('a-attr', $submenu_item['options'])) $attr = ' '.$submenu_item['options']['a-attr'];
				else $attr = '';
				echo '
							<li'.(!empty($page) && $page['slug'] == $submenu_slug ? ' class="top-submenu-active"' : '').'><a href="'.esc_html(url($submenu_slug == 'index' ? '' : '?page='.urlencode($submenu_slug).$query)).'"'.$attr.'>'.esc_html($submenu_item['menu-title']).'</a></li>';
			}
			echo '
						</ul>';
	}
	echo '
					</li>';
}
echo '
				</ul>
				<ul class="top-menu top-menu-right">';
if (!empty($user_details) && $user_details['role'] != 'admin' && array_key_exists('upgrade', $site_data['menu'])) {
	echo '
					<li class="top-menu-item top-menu-upgrade">
						<a href="'.esc_html($options['url'].'?page=upgrade').'">'.esc_html($site_data['menu']['upgrade']['menu-title']).'</a>
					</li>';
}
if (!empty($user_details) && $user_details['role'] == 'admin' && array_key_exists('admin', $site_data['menu'])) {
	echo '
					<li class="top-submenu top-submenu-right top-submenu-admin">
						<span><i class="fas fa-cogs"></i><i class="fas fa-angle-down"></i></span>
						<ul>';
	foreach ($site_data['menu']['admin']['submenu'] as $submenu_slug => $submenu_item) {
		if (empty($submenu_item['menu-title'])) continue;
		if (array_key_exists('query', $submenu_item) && !empty($submenu_item['query'])) $query = '&'.http_build_query($submenu_item['query']);
		else $query = '';
		if (array_key_exists('a-attr', $submenu_item['options'])) $attr = ' '.$submenu_item['options']['a-attr'];
		else $attr = '';
		echo '
							<li'.(!empty($page) && $page['slug'] == $submenu_slug ? ' class="top-submenu-active"' : '').'><a href="'.esc_html(url('?page='.urlencode($submenu_slug).$query)).'"'.$attr.'>'.esc_html($submenu_item['menu-title']).'</a></li>';
	}
	echo '
						</ul>
					</li>';
}
if (!empty($user_details)) {
	echo '
					<li class="top-submenu top-submenu-right">
						<span><img src="https://www.gravatar.com/avatar/'.md5(strtolower($user_details['email'])).'?d=mp" /><i class="fas fa-angle-down"></i></span>
						<ul>';
	if ($options['language'] == '' && sizeof($languages) > 1) {
		echo '
							<li class="language-selector">';
		foreach ($languages as $key => $label) {
			echo '<a href="//'.$_SERVER['HTTP_HOST'].(explode('?', $_SERVER['REQUEST_URI'])[0].'?'.(is_array($_GET) ? http_build_query(array_merge($_GET, array('hl' => $key))) : 'hl='.$key)).'"'.($key == $language ? ' class="language-selected"' : '').' title="'.esc_html($label).'">'.esc_html($key).'</a>';
		}
		echo '
							</li>';
	}
	if (array_key_exists('user', $site_data['menu'])) {
		foreach ($site_data['menu']['user']['submenu'] as $submenu_slug => $submenu_item) {
			if (empty($submenu_item['menu-title'])) continue;
			if (array_key_exists('query', $submenu_item) && !empty($submenu_item['query'])) $query = '&'.http_build_query($submenu_item['query']);
			else $query = '';
			if (array_key_exists('a-attr', $submenu_item['options'])) $attr = ' '.$submenu_item['options']['a-attr'];
			else $attr = '';
			echo '
							<li'.(!empty($page) && $page['slug'] == $submenu_slug ? ' class="top-submenu-active"' : '').'><a href="'.esc_html(url('?page='.urlencode($submenu_slug).$query)).'"'.$attr.'>'.esc_html($submenu_item['menu-title']).'</a></li>';
		}
	}
	echo '
							<li><a href="'.url('login.php?logout').'">'.esc_html__('Logout', 'fb').'<i class="fas fa-sign-out-alt"></i></a></li>
						</ul>
					</li>';
} else {
	echo '
					<li><a href="'.url("login.php".(array_key_exists('redirect', $_GET) ? '?redirect='.urlencode(urldecode($_GET['redirect'])) : '')).'">'.esc_html__('Sign In', 'fb').'</a></li>
					'.($options['enable-register'] == 'on' ? '<li><a href="'.url("register.php".(array_key_exists('redirect', $_GET) ? '?redirect='.urlencode(urldecode($_GET['redirect'])) : '')).'">'.esc_html__('Create Account', 'fb').'</a></li>' : '');
	if ($options['language'] == '' && sizeof($languages) > 1) {
		echo '
					<li class="top-submenu top-submenu-right language-selector">
						<span>'.strtoupper($language).'<i class="fas fa-angle-down"></i></span>
						<ul>';
foreach ($languages as $key => $label) {
			echo '
							<li><a href="//'.$_SERVER['HTTP_HOST'].(explode('?', $_SERVER['REQUEST_URI'])[0].'?'.(is_array($_GET) ? http_build_query(array_merge($_GET, array('hl' => $key))) : 'hl='.$key)).'">'.esc_html($label).'</a></li>';
		}
		echo '
						</ul>
					</li>';
	}
}
echo '
				</ul>
			</nav>';
do_action('admin_menu_bottom');
echo '
		</header>
		<div class="content-wrapper">
			<div class="content">';
?>