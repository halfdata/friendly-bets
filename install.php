<?php
define('INSTALLER', true);
define('VERSION', 1);
error_reporting(0);
include_once(dirname(__FILE__).'/inc/functions.php');
include_once(dirname(__FILE__).'/inc/icdb.php');

$url_base = '//'.$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
$filename = basename(__FILE__);
if (($pos = strpos($url_base, $filename)) !== false) $url_base = substr($url_base, 0, $pos);
$url_base = rtrim($url_base, '/').'/';

$actions = array('start', 'connect-db', 'save-config', 'create-admin');
$wpdb = null;
$db_ready = false;
$admin_created = false;
$tables_created = false;
if (file_exists(dirname(__FILE__).'/inc/config.php')) {
	include_once(dirname(__FILE__).'/inc/config.php');
	try {
		$wpdb = new ICDB(DB_HOST, DB_HOST_PORT, DB_NAME, DB_USER, DB_PASSWORD, DB_TABLE_PREFIX);
		$db_ready = true;
		sync_database();
		$tables_created = true;
		$tmp = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."users WHERE role = 'admin' AND deleted != '1' AND status = 'active' LIMIT 0, 1", ARRAY_A);
		if (!empty($tmp)) $admin_created = true;
	} catch (Exception $e) {
		if (!$tables_created) {
			echo fatal_error_html('Database connection error. Check MySQL credentials and database user privileges. Remove file <strong>/inc/config.php</strong> to start installation from scratch.');
			exit;
		}
	}
}

if ($db_ready && $tables_created && $admin_created) {
	if (array_key_exists('action', $_POST)) {
		$step = 5;
	} else {
		header('Location: '.$url_base);
		exit;
	}
} else if (!array_key_exists('action', $_POST) || !in_array($_POST['action'], $actions)) {
?>
<!DOCTYPE html>
<html lang="en">
<head> 
	<meta name="robots" content="noindex,nofollow">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" /> 
	<meta http-equiv="content-style-type" content="text/css" /> 
	<title><?php echo esc_html__('Install', 'fb'); ?></title> 
	<link href="//fonts.googleapis.com/css?family=Open+Sans:100,100italic,300,300italic,400,400italic,600,600italic,700,700italic,800,800italic&subset=cyrillic,cyrillic-ext,greek,greek-ext,latin,latin-ext,vietnamese" rel="stylesheet" type="text/css">
	<link href="<?php echo $url_base; ?>css/fontawesome.css" rel="stylesheet" type="text/css" />
	<link href="<?php echo $url_base; ?>css/login.css" rel="stylesheet" type="text/css" />
	<script src="<?php echo $options["url"]; ?>js/jquery.min.js"></script>
	<script src="<?php echo $url_base; ?>js/install.js"></script>
	<script>var ajax_url = "<?php echo $url_base; ?>install.php";</script>
</head>
<body>
	<div class="wrapper">
		<div class="wrapper-left-column" style="background-image: url('images/default-pattern.png')"></div>
		<div class="wrapper-right-column">
			<div class="form-wrapper" id="installation-form">
				<div class="form">
					<div class="form-row">
						<h1><?php echo esc_html__('Script Setup', 'fb'); ?></h1>
					</div>
					<div class="form-row">
						<p><?php echo esc_html__("Hi, I am a Wizard. I gonna help you to setup this script. You just need perform several simple steps. Let's start?", 'fb'); ?></p>
					</div>
					<div class="form-row">
						<input type="hidden" name="action" value="start">
						<a class="button" href="#" onclick="return submit_form(this);">
							<span><?php echo esc_html__('Continue', 'fb'); ?></span>
							<i class="fas fa-angle-right"></i>
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div id="global-message"></div>
</body>
</html>
<?php
	exit;
} else {
	switch ($_POST['action']) {
		case 'start':
			if ($admin_created) $step = 5;
			else if ($tables_created) $step = 4;
			else $step = 2;
			break;
		
		case 'connect-db':
			if ($admin_created) $step = 5;
			else if ($tables_created) $step = 4;
			else {
				$host = trim(stripslashes($_POST['hostname']));
				$port = trim(stripslashes($_POST['port']));
				$username = trim(stripslashes($_POST['username']));
				$password = trim(stripslashes($_POST['password']));
				$database = trim(stripslashes($_POST['database']));
				$prefix = trim(stripslashes($_POST['prefix']));
				$errors = array();
				if (empty($host) || !is_hostname($host)) $errors[] = esc_html__('Inavlid MySQL Hostname.', 'fb');
				if (!empty($port) && $port != preg_replace('/[^0-9]/', '', $port)) $errors[] = esc_html__('Port value must be a number.', 'fb');
				if (empty($username)) $errors[] = esc_html__('Username can not be empty.', 'fb');
				else if (strpos($username, "'") !== false) $errors[] = esc_html__('Username can not contain single quote symbol.', 'fb');
				if (empty($database)) $errors[] = esc_html__('Invalid Database name.', 'fb');
				else if (strpos($database, "'") !== false) $errors[] = esc_html__('Database can not contain single quote symbol.', 'fb');
				if (strpos($password, "'") !== false) $errors[] = esc_html__('Password can not contain single quote symbol.', 'fb');
				if (!preg_match('/^[a-zA-Z]+[a-zA-Z_]+$/', $prefix)) $errors[] = esc_html__('Table Prefix must contain letters and/or underscore symbol.', 'fb');
				if (!empty($errors)) {
					$return_object = array();
					$return_object['status'] = 'ERROR';
					$return_object['message'] = implode('<br />', $errors);
					echo json_encode($return_object);
					exit;
				}
				try {
					$wpdb = new ICDB($host, $port, $database, $username, $password, $prefix);
				} catch (Exception $e) {
					$return_object = array();
					$return_object['status'] = 'ERROR';
					$return_object['message'] = esc_html__('Can not connect to MySQL database using provided credentials.', 'fb');
					echo json_encode($return_object);
					exit;
				}
				try {
					sync_database();
				} catch (Exception $e) {
					$return_object = array();
					$return_object['status'] = 'ERROR';
					$return_object['message'] = sprintf(esc_html__('Can not create database tables. Make sure that user %s has sufficient privileges to manipulate database.', 'fb'), '<strong>'.esc_html($username).'</strong>');
					echo json_encode($return_object);
					exit;
				}
				$config_content = "<?php
define('DB_HOST', '".$host."');
define('DB_HOST_PORT', '".$port."');
define('DB_USER', '".$username."');
define('DB_PASSWORD', '".$password."');
define('DB_NAME', '".$database."');
define('DB_TABLE_PREFIX', '".$prefix."');
?>";
				$result = file_put_contents(dirname(__FILE__).DIRECTORY_SEPARATOR.'inc'.DIRECTORY_SEPARATOR.'config.php', $config_content);
				if ($result !== false) {
					$login = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."options WHERE options_key = 'login'");
					$password = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."options WHERE options_key = 'password'");
					if (!empty($login) && !empty($password)) $step = 5;
					else $step = 4;
				} else $step = 3;
			}
			break;

		case 'save-config':
			if ($admin_created) $step = 5;
			else if ($tables_created) $step = 4;
			else {
				$return_object = array();
				$return_object['status'] = 'ERROR';
				$return_object['message'] = esc_html__('Hm. Seems config.php still does not contain correct database credentials. Please update it properly.', 'fb');
				echo json_encode($return_object);
				exit;
			}
			break;

		case 'create-admin':
			if ($admin_created) $step = 5;
			else if (!$tables_created || !$db_ready) {
				$return_object = array();
				$return_object['status'] = 'ERROR';
				$return_object['message'] = esc_html__('Something went wrong. We still can not connect to database. Please try setup procedure again. Just refresh the page.', 'fb');
				echo json_encode($return_object);
				exit;
			} else {
				$email = strtolower(trim(stripslashes($_POST['email'])));
				$password = trim(stripslashes($_POST['password']));
				$errors = array();
                if (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,19})$/i", $email) || empty($email)) $errors[] = esc_html__('This is not a valid email address.', 'fb');
                else if (strlen($email) > 127) $errors[] = esc_html__('Email address is too long.', 'fb');
                    
				if (strlen($password) < 6) $errors[] = esc_html__('Password length must be at least 6 characters.', 'fb');
				if (!empty($errors)) {
					$return_object = array();
					$return_object['status'] = 'ERROR';
					$return_object['message'] = implode('<br />', $errors);
					echo json_encode($return_object);
					exit;
				}
				try {
                    $login = create_login($email);
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
							deleted, 
							created
						) VALUES (
							'".esc_sql(uuid_v4())."',
							'".esc_sql($login)."',
							'".esc_sql(password_hash($password, PASSWORD_DEFAULT))."',
							'".esc_sql($email)."',
							'".esc_sql(esc_html__('Administrator', 'fb'))."',
							'admin',
							'active',
							'UTC',
							'',
							'1',
							'".esc_sql(uuid_v4())."',
							'".esc_sql(uuid_v4())."',
							'0',
							'".time()."'
					)");
					save_option('url', $url_base);
					$step = 5;
				} catch (Exception $e) {
					$return_object = array();
					$return_object['status'] = 'ERROR';
					$return_object['message'] = sprintf(esc_html__('Can not insert record into table. Make sure that user %s has sufficient privileges to manipulate database.', 'fb'), '<strong>'.esc_html(UAP_DB_USERNAME).'</strong>');
					echo json_encode($return_object);
					exit;
				}
			}
			break;
			
		default:
			echo esc_html__('We do not have to be here. Never.', 'fb');
			exit;
	}
}

$return_object = array();
$return_object['status'] = 'OK';
$return_object['html'] = esc_html__('We do not have to see this message. Never.', 'fb');
if ($step == 2) {
	$return_object['html'] = '
<div class="form">
	<div class="form-row">
		<h1>'.esc_html__('Setup Database', 'fb').'</h1>
	</div>
	<div class="form-row">
		<div class="input-box">
			<input type="text" name="hostname" value="localhost" placeholder="localhost" />
			<label>'.esc_html__('Enter MySQL server hostname. Usually it is a localhost, but we recommend to clarify this parameter from your hosting provider.', 'fb').'</label>
		</div>
	</div>
	<div class="form-row">
		<div class="input-box">
			<input type="text" name="port" value="" placeholder="3306" />
			<label>'.esc_html__('Enter MySQL server port. Leave it empty if you do not know the port or it is standard 3306.', 'fb').'</label>
		</div>
	</div>
	<div class="form-row">
		<div class="input-box">
			<input type="text" name="username" value="" placeholder="Username" />
			<label>'.esc_html__('Enter MySQL server username. Find it in your hosting control panel.', 'fb').'</label>
		</div>
	</div>
	<div class="form-row">
		<div class="input-box">
			<input type="text" name="password" value="" placeholder="Password" />
			<label>'.esc_html__('Enter password for MySQL server user. Find it in your hosting control panel.', 'fb').'</label>
		</div>
	</div>
	<div class="form-row">
		<div class="input-box">
			<input type="text" name="database" value="" placeholder="Database" />
			<label>'.esc_html__('Enter MySQL database name. Find it in your hosting control panel.', 'fb').'</label>
		</div>
	</div>
	<div class="form-row">
		<div class="input-box">
			<input type="text" name="prefix" value="fb_" placeholder="Table Prefix" />
			<label>'.esc_html__('Enter prefix for MySQL tables. If you plan to have several installations of this script, use unique prefix for each installation.', 'fb').'</label>
		</div>
	</div>
	<div class="form-row">
		<input type="hidden" name="action" value="connect-db">
		<a class="button" href="#" onclick="return submit_form(this);">
			<span>'.esc_html__('Continue', 'fb').'</span>
			<i class="fas fa-angle-right"></i>
		</a>
	</div>
</div>';
} else if ($step == 3) {
	$return_object['html'] = '
<div class="form">
	<div class="form-row">
		<h1>'.esc_html__('Save Config File', 'fb').'</h1>
	</div>
	<div class="form-row">
		<p>'.sprintf(esc_html__('Unfortunately, we could not save database credentials into config.php (due to file permissions). You have to do it manually. Please edit file %s and overwrite its content with the following code.', 'fb'), '<br /><strong>'.dirname(__FILE__).DIRECTORY_SEPARATOR.'inc'.DIRECTORY_SEPARATOR.'config.php'.'</strong><br />').'</p>
		<div class="input-box">
			<textarea readonly="readonly" onclick="this.focus();this.select();">'.esc_html($config_content).'</textarea>
		</div>
	</div>
	<div class="form-row">
		<input type="hidden" name="action" value="save-config">
		<a class="button" href="#" onclick="return submit_form(this);">
			<span>'.esc_html__('Continue', 'fb').'</span>
			<i class="fas fa-angle-right"></i>
		</a>
	</div>
</div>';
} else if ($step == 4) {
	$return_object['html'] = '
<div class="form">
	<div class="form-row">
		<h1>'.esc_html__('Create Admin Account', 'fb').'</h1>
	</div>
	<div class="form-row">
		<div class="input-box">
			<input type="text" name="email" value="" placeholder="admin@website.com" />
			<label>'.esc_html__('Email address is your login to enter account.', 'fb').'</label>
		</div>
	</div>
	<div class="form-row">
		<div class="input-box">
			<input type="text" name="password" value="" placeholder="Password" />
			<label>'.esc_html__('Use this password to enter your account.', 'fb').'</label>
		</div>
	</div>
	<div class="form-row">
		<input type="hidden" name="action" value="create-admin">
		<a class="button" href="#" onclick="return submit_form(this);">
			<span>'.esc_html__('Continue', 'fb').'</span>
			<i class="fas fa-angle-right"></i>
		</a>
	</div>
</div>';
} else if ($step == 5) {
	$return_object['html'] = '
<div class="form">
	<div class="form-row">
		<h1>'.esc_html__('Finished', 'fb').'</h1>
	</div>
	<div class="form-row">
		<p>'.esc_html__('Congratulation! Installation successfully completed. Now you can enter your account using created login/password and work there. Good luck!', 'fb').'</p>
	</div>
	<div class="form-row">
		<input type="hidden" name="action" value="save-config">
		<a class="button" href="'.$url_base.'">
			<span>'.esc_html__('Finish', 'fb').'</span>
			<i class="fas fa-angle-right"></i>
		</a>
	</div>
</div>';
}
echo json_encode($return_object);
exit;
?>