<?php
include_once(dirname(__FILE__).'/inc/functions.php');
include_once(dirname(__FILE__).'/inc/icdb.php');
include_once(dirname(__FILE__).'/inc/common.php');

if (array_key_exists('redirect', $_GET)) {
	$redirect = urldecode($_GET['redirect']);
	if (!empty($redirect)) {
		$_SESSION['login-redirect'] = $redirect;
	}
}

if ($user_details) {
	if (array_key_exists('logout', $_GET)) {
		if (!empty($session_details)) {
			$wpdb->query("UPDATE ".$wpdb->prefix."sessions SET valid_period = '0' WHERE id = '".esc_sql($session_details['id'])."'");
			if (!empty($admin_session_details)) {
				if (PHP_VERSION_ID < 70300) setcookie('fb-auth', $admin_session_details['session_id'], time()+3600*24*60, parse_url($options['url'], PHP_URL_PATH).'; samesite=lax');
				else setcookie('fb-auth', $admin_session_details['session_id'], array('expires' => time()+3600*24*60, 'samesite' => 'Lax', 'path' => parse_url($options['url'], PHP_URL_PATH)));
				if (PHP_VERSION_ID < 70300) setcookie('fb-auth-admin', null, -1, parse_url($options['url'], PHP_URL_PATH).'; samesite=lax');
				else setcookie('fb-auth-admin', null, array('expires' => -1, 'samesite' => 'Lax', 'path' => parse_url($options['url'], PHP_URL_PATH)));
				header('Location: '.url('?page=admin-users'));
				exit;
			}
		}
	}
	header('Location: '.url(''));
	exit;
}

do_action('admin_menu');

$upload_dir = upload_dir();
$image = null;
if ($options['pattern'] > 0) {
	$image = $wpdb->get_row("SELECT t1.*, t2.uuid AS user_uid FROM ".$wpdb->prefix."uploads t1 
			JOIN ".$wpdb->prefix."users t2 ON t2.id = t1.user_id
		WHERE t1.id = '".esc_sql($options['pattern'])."' AND t1.deleted != '1'", ARRAY_A);
}
echo '<!DOCTYPE html>
<html lang="en">
<head> 
	<meta name="robots" content="noindex,nofollow">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" /> 
	<meta http-equiv="content-style-type" content="text/css" /> 
	<title>'.esc_html__('Sign In', 'fb').'</title> 
	<link href="//fonts.googleapis.com/css?family=Open+Sans:100,100italic,300,300italic,400,400italic,600,600italic,700,700italic,800,800italic&subset=cyrillic,cyrillic-ext,greek,greek-ext,latin,latin-ext,vietnamese" rel="stylesheet" type="text/css">
	<link href="'.url('').'css/fontawesome.css" rel="stylesheet" type="text/css" />
	<link href="'.url('').'css/login.css" rel="stylesheet" type="text/css" />
	<script src="'.url('').'js/jquery.min.js"></script>
	<script src="'.url('').'js/login.js"></script>
	<script>var ajax_url = "'.url("ajax.php").'";</script>
</head>
<body>
	<input type="hidden" name="_token" value="'.esc_html($csrf_token).'" />
	<div class="wrapper">
		<div class="wrapper-left-column" style="background-image: url('.(!empty($image) ? esc_html($upload_dir['baseurl'].'/'.$image['user_uid'].'/'.$image['filename']) : url('').'images/default-pattern.png').');"></div>
		<div class="wrapper-right-column">
			<nav class="top-menu-bar">
			<ul class="top-menu">';
foreach($site_data['menu'] as $slug => $item) {
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
							<li'.($page['slug'] == $submenu_slug ? ' class="top-submenu-active"' : '').'><a href="'.esc_html(url($slug == 'index' ? '' : '?page='.urlencode($submenu_slug).$query)).'"'.$attr.'>'.esc_html($submenu_item['menu-title']).'</a></li>';
		}
		echo '
						</ul>';
	}
		echo '</li>';
}
echo '
				</ul>
				<ul class="top-menu top-menu-right">
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
echo '
				</ul>
			</nav>
			<div class="form-wrapper" id="login-form">
				<div class="form">
					<div class="form-row">
						<h1>'.esc_html__('Sign In', 'fb').'</h1>
					</div>
					<div class="form-row">
						<div class="input-box">
							<input type="email" name="email" placeholder="'.esc_html__('Email address', 'fb').'" value="" onfocus="jQuery(this).parent().find(\'.element-error\').fadeOut(300, function(){jQuery(this).remove();});">
						</div>
					</div>
					<div class="form-row">
						<div class="input-box">
							<input type="password" name="password" placeholder="'.esc_html__('Password', 'fb').'" value="" onfocus="jQuery(this).parent().find(\'.element-error\').fadeOut(300, function(){jQuery(this).remove();});">
						</div>
					</div>
					<div class="form-row right-align">
						<a href="#" onclick="return switch_form(this);">'.esc_html__('Forgot password?', 'fb').'</a>
					</div>
					<div class="form-row">
						<input type="hidden" name="redirect" value="'.(array_key_exists('redirect', $_GET) ? esc_html(urldecode($_GET['redirect'])) : '').'">
						<a class="button" href="#" onclick="return login(this);" data-label="'.esc_html__('Sign In', 'fb').'">
							<span>'.esc_html__('Sign In', 'fb').'</span>
							<i class="fas fa-angle-right"></i>
						</a>
					</div>';
if ($options['google-enable'] == 'on' || $options['facebook-enable'] == 'on' || $options['vk-enable'] == 'on') {
	echo '
					<div class="form-row">
						'.esc_html__('or sign in with', 'fb').'
					</div>
					<div class="form-row">';
	if ($options['google-enable'] == 'on') {
		echo '
						<a class="social-button social-button-google" href="https://accounts.google.com/o/oauth2/auth?client_id='.urlencode($options['google-client-id']).'&scope=profile%20email&response_type=code&redirect_uri='.urlencode(url('auth.php?google=auth')).'" title="'.esc_html__('Google', 'fb').'">
							<i class="fab fa-google"></i>
						</a>';
	}
	if ($options['facebook-enable'] == 'on') {
		echo '
						<a class="social-button social-button-facebook" href="https://www.facebook.com/dialog/oauth?client_id='.urlencode($options['facebook-client-id']).'&scope=public_profile,email&redirect_uri='.urlencode(url('auth.php?facebook=auth')).'" title="'.esc_html__('Facebook', 'fb').'">
							<i class="fab fa-facebook-f"></i>
						</a>';
	}
	if ($options['vk-enable'] == 'on') {
		echo '
						<a class="social-button social-button-vk" href="https://oauth.vk.com/authorize?client_id='.urlencode($options['vk-client-id']).'&display=page&redirect_uri='.urlencode(url('auth-vk.php')).'&scope=email&response_type=code&v=6.00" title="'.esc_html__('VK', 'fb').'">
							<i class="fab fa-vk"></i>
						</a>';
	}
	echo '
					</div>';
}
echo '
				</div>
			</div>
			<div class="form-wrapper" id="reset-form" style="display: none;">
				<div class="form">
					<div class="form-row">
						<h1>'.esc_html__('Reset password', 'fb').'</h1>
					</div>
					<div class="form-row">
						<div class="input-box">
							<input type="email" name="email" placeholder="'.esc_html__('Email address', 'fb').'" value="" onfocus="jQuery(this).parent().find(\'.element-error\').fadeOut(300, function(){jQuery(this).remove();});">
						</div>
					</div>
					<div class="form-row right-align">
						'.esc_html__('Remember password?', 'fb').' <a href="#" onclick="return switch_form(this);">'.esc_html__('Sign In.', 'fb').'</a>
					</div>
					<div class="form-row">
						<input type="hidden" name="redirect" value="'.(array_key_exists('redirect', $_GET) ? esc_html(urldecode($_GET['redirect'])) : '').'">
						<a class="button" href="#" onclick="return reset(this);" data-label="'.esc_html__('Reset', 'fb').'">
							<span>'.esc_html__('Reset', 'fb').'</span>
							<i class="fas fa-angle-right"></i>
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div id="global-message"></div>
	'.session_message().'
</body>
</html>';
?>