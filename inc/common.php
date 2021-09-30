<?php
session_start();
error_reporting(-1);
//error_reporting(0);
define('INTEGRITY', true);
define('VERSION', 1.03);
define('RECORDS_PER_PAGE', 50);
define('MEMBERSHIP_ENABLE', false);
define('PERMALINKS_ENABLE', false);
define('MAX_USER_IMAGE_SIZE', 256*1024);
define('ABSPATH', dirname(dirname(__FILE__)));

include_once(dirname(__FILE__).'/php-po-parser/init.php');

$language = 'en';
$translations = array();
$global_warnings = array();
$options = array();
$site_data = array(
	'filters' => array(),
	'scripts' => array(),
	'styles' => array(),
	'menu' => array()
);
$default_options = array(
	'title' => esc_html__('Friendly Bets', 'fb'),
	'tagline' => esc_html__('Join any active totalizator or start your own one', 'fb'),
	'copyright' => '',
	'date-format' => 'yyyy-mm-dd',
	'language' => 'en',
	'pattern' => 0,
	'confirm-subject' => esc_html__('Confirm email address', 'fb'),
	'confirm-message' => esc_html__('Dear {name},', 'fb').PHP_EOL.PHP_EOL.esc_html__('Click the link below to confirm email address.', 'fb').PHP_EOL.'<a href="{confirmation-url}">{confirmation-url}</a>'.PHP_EOL.PHP_EOL.sprintf(esc_html__('If you did not sign up with %s, just ignore this message.', 'fb'), esc_html__('Friendly Bets', 'fb')).PHP_EOL.PHP_EOL.esc_html__('Regards.', 'fb'),
	'reset-subject' => esc_html__('Reset password', 'fb'),
	'reset-message' => esc_html__('Dear {name},', 'fb').PHP_EOL.PHP_EOL.esc_html__('Click the link below to reset the password.', 'fb').PHP_EOL.'<a href="{reset-password-url}">{reset-password-url}</a>'.PHP_EOL.PHP_EOL.esc_html__('If you did not request password reset, just ignore this message.', 'fb').PHP_EOL.PHP_EOL.esc_html__('Regards.', 'fb'),
	'mail-method' => 'mail',
	'mail-from-name' => esc_html__('Friendly Bets', 'fb'),
	'mail-from-email' => 'noreply@'.str_replace("www.", "", $_SERVER["SERVER_NAME"]),
	'smtp-server' => '',
	'smtp-port' => '',
	'smtp-secure' => 'none',
	'smtp-username' => '',
	'smtp-password' => '',
	'smtp-from-name' => esc_html__('Friendly Bets', 'fb'),
	'smtp-from-email' => 'noreply@'.str_replace("www.", "", $_SERVER["SERVER_NAME"]),
	'stripe-enable' => 'off',
	'stripe-public-key' => '',
	'stripe-secret-key' => '',
	'stripe-webhook-secret' => '',
	'stripe-uuid' => uuid_v4(),
	'google-enable' => 'off',
	'google-client-id' => '',
	'google-client-secret' => '',
	'facebook-enable' => 'off',
	'facebook-client-id' => '',
	'facebook-client-secret' => '',
	'vk-enable' => 'off',
	'vk-client-id' => '',
	'vk-client-secret' => '',
	'membership-grace-period' => '2'
);
$mail_methods = array('mail' => 'PHP Mail()', 'smtp' => 'SMTP');
$smtp_secures = array('none' => 'None', 'ssl' => 'SSL', 'tls' => 'TLS');

$folders = array();
if (!file_exists(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'plugins')) mkdir(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'plugins', 0777, true);
if (!file_exists(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'data')) mkdir(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'data', 0777, true);
if (!file_exists(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'temp')) mkdir(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'temp', 0777, true);


if (!is_writable(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'plugins')) {
	$folders[] = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'plugins';
} else {
	if (!file_exists(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'index.html')) {
		$result = file_put_contents(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'index.html', '<html><head><script>location.href="https://codecanyon.net/user/halfdata/portfolio?ref=halfdata";</script></head><body></body></html>');
		if (!$result) $folders[] = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'plugins';
	}
}
if (!is_writable(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'data')) {
	$folders[] = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'data';
} else {
	if (!file_exists(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'index.html')) {
		$result = file_put_contents(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'index.html', '<html><head><script>location.href="https://codecanyon.net/user/halfdata/portfolio?ref=halfdata";</script></head><body></body></html>');
		if (!$result) $folders[] = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'data';
	}
}
if (!is_writable(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'temp')) {
	$folders[] = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'temp';
} else {
	if (!file_exists(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR.'index.html')) {
		$result = file_put_contents(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR.'index.html', '<html><head><script>location.href="https://codecanyon.net/user/halfdata/portfolio?ref=halfdata";</script></head><body></body></html>');
	}
}

if (!empty($folders)) {
	$global_warnings[] = sprintf(esc_html__('Make sure that the following folders exists and writable:', 'fb')).'<br />'.implode('<br />', $folders);
}

$wpdb = null;
$ready = false;
$db_ok = true;
if (file_exists(dirname(__FILE__).'/config.php')) {
	include_once(dirname(__FILE__).'/config.php');
	try {
		$ready = true;
		$db_ok = false;
		$wpdb = new ICDB(DB_HOST, DB_HOST_PORT, DB_NAME, DB_USER, DB_PASSWORD, DB_TABLE_PREFIX);
		sync_database();
		$db_ok = true;
		$ready = false;
		$options = get_options('core', $default_options);
		$options['url'] = rtrim(get_option('url', ''), '/').'/';
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https:" : "http:";
		if (substr($options['url'], 0, 2) == '//') $options['url'] = $protocol.$options['url'];
		$tmp = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."users WHERE role = 'admin' AND deleted != '1' AND status = 'active' LIMIT 0, 1", ARRAY_A);
		if (empty($tmp)) {
			throw new Exception('No admins are available');
		}
		$ready = true;
	} catch (Exception $e) {
		if (!$db_ok) {
			echo fatal_error_html('Database connection error. Check MySQL credentials and database user privileges.');
			exit;
		}
	}
}
if (!$ready) {
	header('Location: '.url('install.php'));
	exit;
}
$user_details = null;
$session_id = '';
$session_details = null;
$admin_session_id = '';
$admin_session_details = null;
if (array_key_exists('fb-auth', $_COOKIE)) {
	$session_id = preg_replace('/[^a-zA-Z0-9-]/', '', $_COOKIE['fb-auth']);
	$session_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."sessions WHERE session_id = '".esc_sql($session_id)."' AND registered + valid_period > '".esc_sql(time())."'", ARRAY_A);
	if ($session_details) {
		$wpdb->query("UPDATE ".$wpdb->prefix."sessions SET registered = '".esc_sql(time())."', ip = '".esc_sql($_SERVER['REMOTE_ADDR'])."' WHERE session_id = '".esc_sql($session_id)."'");
		$user_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."users WHERE id = '".esc_sql($session_details['user_id'])."' AND deleted != '1'", ARRAY_A);
		if (!empty($user_details)) {
			if (MEMBERSHIP_ENABLE) {
				if ($user_details['membership_id'] > 0 && $user_details['membership_expires'] > 0 && $user_details['membership_expires'] < time()) {
					$user_details['membership_id'] = 0;
					$wpdb->query("UPDATE ".$wpdb->prefix."users SET membership_id = '0', membership_txn_id = '0' WHERE id = '".esc_sql($user_details['id'])."'");
				}
			}
			if (array_key_exists('fb-auth-admin', $_COOKIE)) {
				$admin_session_id = preg_replace('/[^a-zA-Z0-9-]/', '', $_COOKIE['fb-auth-admin']);
				$admin_session_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."sessions WHERE session_id = '".esc_sql($admin_session_id)."' AND registered + valid_period > '".esc_sql(time())."'", ARRAY_A);
			}
			if ($user_details['role'] == 'admin' && empty($admin_session_details) && array_key_exists('user', $_GET)) {
				$switch_uid = preg_replace('/[^a-zA-Z0-9-]/', '', $_GET['user']);
				if ($user_details['uuid'] != $switch_uid) {
					$switch_user_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."users WHERE uuid = '".esc_sql($switch_uid)."' AND deleted != '1'", ARRAY_A);
					if (!empty($switch_user_details)) {
						$admin_session_id = $session_id;
						$admin_session_details = $session_details;
						$user_details = $switch_user_details;
						$session_id = uuid_v4();
						$wpdb->query("INSERT INTO ".$wpdb->prefix."sessions (
								source,
								session_id, 
								user_id, 
								ip, 
								registered, 
								created,
								valid_period
							) VALUES (
								'admin-login',
								'".esc_sql($session_id)."',
								'".esc_sql($user_details['id'])."',
								'".esc_sql($_SERVER['REMOTE_ADDR'])."',
								'".time()."',
								'".time()."',
								'86400'
							)");
						$session_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."sessions WHERE id = '".esc_sql($wpdb->insert_id)."'", ARRAY_A);
						if (PHP_VERSION_ID < 70300) setcookie('fb-auth', $session_id, time()+3600, parse_url($options['url'], PHP_URL_PATH).'; samesite=lax');
						else setcookie('fb-auth', $session_id, array('expires' => time()+3600, 'samesite' => 'Lax', 'path' => parse_url($options['url'], PHP_URL_PATH)));
						if (PHP_VERSION_ID < 70300) setcookie('fb-auth-admin', $admin_session_id, time()+3600*24*60, parse_url($options['url'], PHP_URL_PATH).'; samesite=lax');
						else setcookie('fb-auth-admin', $admin_session_id, array('expires' => time()+3600*24*60, 'samesite' => 'Lax', 'path' => parse_url($options['url'], PHP_URL_PATH)));
					}
				}
			}
			if (!empty($admin_session_details)) {
				$global_info[] = sprintf(esc_html__('You are working under %s account. Click %shere%s to switch back to your account.', 'fb'), '<strong>'.$user_details['email'].'</strong>', '<a href="'.url('login.php').'?logout">', '</a>');
			}
		}
	}
}
date_default_timezone_set('UTC');
$gmt_offset = 0;
if (!empty($user_details)) {
	$gmt_offset = floatval(timezone_offset($user_details['timezone']));
}

$languages = array('en' => 'English', 'es' => 'Español', 'fr' => 'Français', 'de' => 'Deutsch', 'it' => 'Italiano', 'pt' => 'Português', 'ru' => 'Русский');
foreach ($languages as $key => $label) {
    if ($key != 'en' && !file_exists(dirname(dirname(__FILE__)).'/languages/'.$key.'.po')) {
        unset($languages[$key]);
    }
}
ksort($languages);
if (empty($options['language'])) {
	if (array_key_exists('hl', $_GET)) {
		$hl = preg_replace('/[^a-z]/', '', trim(stripslashes($_GET['hl'])));
		if (!empty($hl) && array_key_exists($hl, $languages)) {
			$language = $hl;
			if (PHP_VERSION_ID < 70300) setcookie('fb-language', $language, time()+3600*24*365, parse_url($options['url'], PHP_URL_PATH).'; samesite=lax');
			else setcookie('fb-language', $language, array('lifetime' => time()+3600*24*365, 'samesite' => 'Lax', 'path' => parse_url($options['url'], PHP_URL_PATH)));
		} else if (array_key_exists('fb-language', $_COOKIE) && array_key_exists($_COOKIE['fb-language'], $languages)) {
			$language = $_COOKIE['fb-language'];
		}
	} else if (array_key_exists('fb-language', $_COOKIE) && array_key_exists($_COOKIE['fb-language'], $languages)) {
		$language = $_COOKIE['fb-language'];
	}
} else if (array_key_exists($options['language'], $languages)) {
	$language = $options['language'];
}
load_translation('fb', $language, dirname(dirname(__FILE__)).'/languages/');

$default_free_membership = array(
	'title' => esc_html__('Free', 'fb'),
	'description' => esc_html__('All basic features are available for free account.', 'fb'),
	'footer' => esc_html__('Free', 'fb'),
	'features' => array()
);
$free_membership = get_options('membership-free', $default_free_membership);
$membership_billing_periods = array(
	'day' => array('label' => esc_html__('Daily', 'fb'), 'per-label' => esc_html__('/ day', 'fb')),
	'week' => array('label' => esc_html__('Weekly', 'fb'), 'per-label' => esc_html__('/ week', 'fb')),
	'month' => array('label' => esc_html__('Monthly', 'fb'), 'per-label' => esc_html__('/ month', 'fb')),
	'quarter' => array('label' => esc_html__('Every 3 months', 'fb'), 'per-label' => esc_html__('/ 3 months', 'fb')),
	'semiannual' => array('label' => esc_html__('Every 6 months', 'fb'), 'per-label' => esc_html__('/ 6 months', 'fb')),
	'year' => array('label' => esc_html__('Yearly', 'fb'), 'per-label' => esc_html__('/ year', 'fb')),
	'single' => array('label' => esc_html__('Single payment', 'fb'), 'per-label' => '')
);
$membership_price_statuses = array(
	'new' => esc_html__('New', 'fb'),
	'active' => esc_html__('Active', 'fb'),
	'archive' => esc_html__('Archive', 'fb')
);
$membership_colors = array('orange', 'yellow', 'green', 'blue', 'purple', 'gray');
$membership_feature_bullets = array('check', 'plus', 'times', 'minus', 'no');
$currency_list = array("USD", "AED", "AFN", "ALL", "AMD", "ANG", "AOA", "ARS", "AUD", "AWG", "AZN", "BAM", "BBD", "BDT", "BGN", "BIF", "BMD", "BND", "BOB", "BRL", "BSD", "BWP", "BZD", "CAD", "CDF", "CHF", "CLP", "CNY", "COP", "CRC", "CVE", "CZK", "DJF", "DKK", "DOP", "DZD", "EEK", "EGP", "ETB", "EUR", "FJD", "FKP", "GBP", "GEL", "GIP", "GMD", "GNF", "GTQ", "GYD", "HKD", "HNL", "HRK", "HTG", "HUF", "IDR", "ILS", "INR", "ISK", "JMD", "JPY", "KES", "KGS", "KHR", "KMF", "KRW", "KYD", "KZT", "LAK", "LBP", "LKR", "LRD", "LSL", "LTL", "LVL", "MAD", "MDL", "MGA", "MKD", "MNT", "MOP", "MRO", "MUR", "MVR", "MWK", "MXN", "MYR", "MZN", "NAD", "NGN", "NIO", "NOK", "NPR", "NZD", "PAB", "PEN", "PGK", "PHP", "PKR", "PLN", "PYG", "QAR", "RON", "RSD", "RUB", "RWF", "SAR", "SBD", "SCR", "SEK", "SGD", "SHP", "SLL", "SOS", "SRD", "STD", "SVC", "SZL", "THB", "TJS", "TOP", "TRY", "TTD", "TWD", "TZS", "UAH", "UGX", "UYU", "UZS", "VND", "VUV", "WST", "XAF", "XCD", "XOF", "XPF", "YER", "ZAR", "ZMW");
$stripe_no_100 = array("JPY");

if (!empty($user_details) && $user_details['role'] == 'admin') {
	include_once(dirname(__FILE__).'/admin.php');
}
if (!empty($user_details)) {
	include_once(dirname(__FILE__).'/user.php');
}
include_once('inc/plugins.php');
$__URL = url_parse();
?>