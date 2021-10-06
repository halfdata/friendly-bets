<?php
include_once(dirname(__FILE__).'/inc/functions.php');
include_once(dirname(__FILE__).'/inc/icdb.php');
include_once(dirname(__FILE__).'/inc/common.php');
//header('Content-Type: application/json');

if (array_key_exists('_token', $_REQUEST)) {
	$token_parts = explode('-', trim(stripslashes($_REQUEST['_token'])), 2);
	if (sizeof($token_parts) == 2 && is_numeric($token_parts[0])) {
		if ($token_parts[0] + 3600*6 > time()) {
			if (md5($token_parts[0].SALT1.(!empty($user_details) ? $user_details['uuid'] : '')) != $token_parts[1]) {
				$return_data = array('status' => 'WARNING', 'message' => esc_html__('Invalid token.', 'fb'));
				echo json_encode($return_data);
				exit;
			}
		} else {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Token expired. Refresh the page and try again.', 'fb'));
			echo json_encode($return_data);
			exit;
		}
	} else {
		$return_data = array('status' => 'WARNING', 'message' => esc_html__('Invalid request.', 'fb'));
		echo json_encode($return_data);
		exit;
	}
} else {
	$return_data = array('status' => 'WARNING', 'message' => esc_html__('Invalid request.', 'fb'));
	echo json_encode($return_data);
	exit;
}
if (!array_key_exists('action', $_REQUEST)) {
	$return_data = array('status' => 'FATAL', 'message' => esc_html__('Invalid request.', 'fb'));
	echo json_encode($return_data);
	exit;
}

do_action('admin_init');

if ($_REQUEST['action'] == 'register') {
	if ($options['enable-register'] != 'on') {
		$return_data = array('status' => 'WARNING', 'message' => esc_html__('Unfortunately, registration disbaled.', 'fb'));
		echo json_encode($return_data);
		exit;
	}
	$timezone = trim(stripslashes($_REQUEST['timezone']));
	$name = trim(stripslashes($_REQUEST['name']));
	$email = trim(stripslashes($_REQUEST['email']));
	$password = trim(stripslashes($_REQUEST['password']));
	$repeat_password = trim(stripslashes($_REQUEST['repeat-password']));
	$redirect = trim(stripslashes($_REQUEST['redirect']));

	$errors = array();
	if (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,19})$/i", $email) || empty($email)) $errors['email'] = esc_html__('This is not a valid email address.', 'fb');
	else if (mb_strlen($email) > 127) $errors['email'] = esc_html__('The email address is too long.', 'fb');
	else {
		$login = create_login($email);
		$user_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."users WHERE login = '".esc_sql($login)."' AND deleted != '1'", ARRAY_A);
		if ($user_details) $errors['email'] = esc_html__('The email already registered.', 'fb');
	}

	if (mb_strlen($name) < 2) $errors['name'] = esc_html__('The full name is too short.', 'fb');
	else if (mb_strlen($name) > 127) $errors['name'] = esc_html__('The full name is too long.', 'fb');

	if (mb_strlen($password) < 6) $errors['password'] = esc_html__('The password must be at least 6 characters long.', 'fb');
	else if ($password != $repeat_password) $errors['repeat-password'] = esc_html__('Repeat the password properly.', 'fb');
	
	$timezone_offset = timezone_offset($timezone);
	if ($timezone_offset === false) $errors['timezone'] = esc_html__('Invalid timezone.', 'fb');

	if (!empty($errors)) {
		$return_data = array('status' => 'ERROR', 'errors' => $errors);
		echo json_encode($return_data);
		exit;
	}
	$email_confirmation_uid = uuid_v4();
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
			'".esc_sql($name)."',
			'user',
			'active',
			'".esc_sql($timezone)."',
			'',
			'0',
			'".esc_sql($email_confirmation_uid)."',
			'".esc_sql(uuid_v4())."',
			'0',
			'".time()."'
		)");

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
			'register',
			'".esc_sql($session_id)."',
			'".esc_sql($wpdb->insert_id)."',
			'".esc_sql($_SERVER['REMOTE_ADDR'])."',
			'".time()."',
			'".time()."',
			 '86400'
		)");
	if (PHP_VERSION_ID < 70300) setcookie('fb-auth', $session_id, time()+3600*24*60, parse_url($options['url'], PHP_URL_PATH).'; samesite=lax');
	else setcookie('fb-auth', $session_id, array('expires' => time()+3600*24*60, 'samesite' => 'Lax', 'path' => parse_url($options['url'], PHP_URL_PATH)));

	$url = url('register.php').'?confirm='.$email_confirmation_uid;
	$message = str_replace(array('{name}', '{email}', '{confirmation-url}', "\n", "\r"), array($name, $email, $url, "<br />", ""), $options['confirm-message']);
	send_mail($email, $options['confirm-subject'], $message);

	if (array_key_exists('login-redirect', $_SESSION)) unset($_SESSION['login-redirect']);
	if (empty($redirect) /* || strpos($redirect, $options['url']) === false */) $redirect = $options['url'];
	$return_object = array('status' => 'OK', 'url' => $redirect);
	echo json_encode($return_object);
	exit;
} else if ($_REQUEST['action'] == 'login') {
	$email = trim(stripslashes($_REQUEST['email']));
	$password = trim(stripslashes($_REQUEST['password']));
	$redirect = trim(stripslashes($_REQUEST['redirect']));

	$error_message = '';
	if (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,19})$/i", $email) || empty($email)) $error_message = esc_html__('Invalid email or password.', 'fb');
	else if (mb_strlen($email) > 127) $error_message = esc_html__('Invalid email or password.', 'fb');
	else {
		$login = create_login($email);
		$user_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."users WHERE login = '".esc_sql($login)."' AND deleted != '1'", ARRAY_A);
		if (empty($user_details) || !password_verify($password, $user_details['password'])) $error_message = esc_html__('Invalid email or password.', 'fb');
	}

	if (!empty($error_message)) {
		$return_data = array('status' => 'ERROR', 'message' => $error_message);
		echo json_encode($return_data);
		exit;
	}

	if ($user_details['status'] != 'active') {
		$return_data = array('status' => 'ERROR', 'message' => esc_html__('Unfortunately, your account temporarily disbaled.', 'fb'));
		echo json_encode($return_data);
		exit;
	}

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
			'login',
			'".esc_sql($session_id)."',
			'".esc_sql($user_details['id'])."',
			'".esc_sql($_SERVER['REMOTE_ADDR'])."',
			'".time()."',
			'".time()."',
			 '86400'
		)");
	if (PHP_VERSION_ID < 70300) setcookie('fb-auth', $session_id, time()+3600*24*60, parse_url($options['url'], PHP_URL_PATH).'; samesite=lax');
	else setcookie('fb-auth', $session_id, array('expires' => time()+3600*24*60, 'samesite' => 'Lax', 'path' => parse_url($options['url'], PHP_URL_PATH)));

	if (array_key_exists('login-redirect', $_SESSION)) unset($_SESSION['login-redirect']);
	if (empty($redirect) /* || strpos($redirect, $options['url']) === false */) $redirect = $options['url'];
	$return_object = array('status' => 'OK', 'url' => $redirect, 'message' => esc_html__('You are successfully logged in.', 'fb'));
	echo json_encode($return_object);
	exit;
} else if ($_REQUEST['action'] == 'reset-password') {
	$email = trim(stripslashes($_REQUEST['email']));
	$redirect = trim(stripslashes($_REQUEST['redirect']));

	$errors = array();
	if (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,19})$/i", $email) || empty($email)) $errors['email'] = esc_html__('This is not a valid email address.', 'fb');
	else if (mb_strlen($email) > 127) $errors['email'] = esc_html__('The email address is too long.', 'fb');
	else {
		$login = create_login($email);
		$user_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."users WHERE login = '".esc_sql($login)."' AND deleted != '1'", ARRAY_A);
		if (empty($user_details)) $errors['email'] = esc_html__('The email address is not registered.', 'fb');
	}
	if (!empty($errors)) {
		$return_data = array('status' => 'ERROR', 'errors' => $errors);
		echo json_encode($return_data);
		exit;
	}

	if ($user_details['status'] != 'active') {
		$return_data = array('status' => 'WARNING', 'message' => esc_html__('Unfortunately, your account temporarily disbaled.', 'fb'));
		echo json_encode($return_data);
		exit;
	}

	if (empty($redirect) || strpos($redirect, $options['url']) === false) $redirect = '';
	$url = url('register.php').'?reset='.$user_details['password_reset_uid'].(!empty($redirect) && strpos($redirect, $options['url']) !== false ? '&redirect='.urlencode($redirect) : '');
	$message = str_replace(array('{name}', '{email}', '{reset-password-url}', "\n", "\r"), array($user_details['name'], $user_details['email'], $url, "<br />", ""), $options['reset-message']);
	send_mail($user_details['email'], $options['reset-subject'], $message);

	$return_object = array('status' => 'OK', 'message' => esc_html__('Check your mailbox and follow instructions.', 'fb'));
	echo json_encode($return_object);
	exit;
} else if ($_REQUEST['action'] == 'set-password') {
	$password = trim(stripslashes($_REQUEST['password']));
	$repeat_password = trim(stripslashes($_REQUEST['repeat-password']));
	$reset_id = preg_replace('/[^a-zA-Z0-9-]/', '', trim(stripslashes($_REQUEST['reset-id'])));
	$redirect = trim(stripslashes($_REQUEST['redirect']));

	$user_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."users WHERE password_reset_uid = '".esc_sql($reset_id)."' AND deleted != '1'", ARRAY_A);
	if (empty($user_details)) {
		$return_data = array('status' => 'WARNING', 'message' => esc_html__('User not found.', 'fb'));
		echo json_encode($return_data);
		exit;
	}

	if ($user_details['status'] != 'active') {
		$return_data = array('status' => 'WARNING', 'message' => esc_html__('Unfortunately, your account temporarily disbaled.', 'fb'));
		echo json_encode($return_data);
		exit;
	}

	if (mb_strlen($password) < 6) $errors['password'] = esc_html__('The password must be at least 6 characters long.', 'fb');
	else if ($password != $repeat_password) $errors['repeat-password'] = esc_html__('Repeat the password properly.', 'fb');
	if (!empty($errors)) {
		$return_data = array('status' => 'ERROR', 'errors' => $errors);
		echo json_encode($return_data);
		exit;
	}

	$wpdb->query("UPDATE ".$wpdb->prefix."users SET
			password = '".esc_sql(password_hash($password, PASSWORD_DEFAULT))."', 
			password_reset_uid = '".esc_sql(uuid_v4())."'
		WHERE id = '".esc_sql($user_details['id'])."'");

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
			'setpassword',
			'".esc_sql($session_id)."',
			'".esc_sql($user_details['id'])."',
			'".esc_sql($_SERVER['REMOTE_ADDR'])."',
			'".time()."',
			'".time()."',
			'86400'
		)");
	if (PHP_VERSION_ID < 70300) setcookie('fb-auth', $session_id, time()+3600*24*60, parse_url($options['url'], PHP_URL_PATH).'; samesite=lax');
	else setcookie('fb-auth', $session_id, array('expires' => time()+3600*24*60, 'samesite' => 'Lax', 'path' => parse_url($options['url'], PHP_URL_PATH)));

	if (empty($redirect) || strpos($redirect, $options['url']) === false) $redirect = $options['url'];
	$return_object = array('status' => 'OK', 'url' => $redirect);
	echo json_encode($return_object);
	exit;
} else if ($_REQUEST['action'] == 'image-uploader-action') {
	if (empty($user_details)) {
		$return_data = array('status' => 'WARNING', 'message' => esc_html__('Please enter your account to perform this action.', 'fb'));
		echo json_encode($return_data);
		exit;
	}
	if (is_uploaded_file($_FILES["file"]["tmp_name"])) {
		if ($user_details['role'] != 'admin' && filesize($_FILES["file"]["tmp_name"]) > MAX_USER_IMAGE_SIZE) {
			$return_object = array('status' => 'ERROR', 'message' => sprintf(esc_html__('Image is too big. Maximum allowed %skb.', 'fb'), intval(MAX_USER_IMAGE_SIZE/1024)));
			echo json_encode($return_object);
			exit;
		}
		$upload_dir = upload_dir();
		if (mkdir_p($upload_dir["basedir"].DIRECTORY_SEPARATOR.$user_details['uuid'])) {
			$filename = get_filename($upload_dir["basedir"].DIRECTORY_SEPARATOR.$user_details['uuid'].DIRECTORY_SEPARATOR, $_FILES["file"]["name"]);
			$file_content = file_get_contents($_FILES["file"]["tmp_name"]);
			if (!empty($file_content)) {
				try {
					$image = imagecreatefromstring($file_content);
				} catch (Exception $e) {
					$return_object = array('status' => 'ERROR', 'message' => esc_html__('Uploaded file is not an image.', 'fb'));
					echo json_encode($return_object);
					exit;
				}
				if ($image !== false) {
					$data = getimagesize($_FILES["file"]["tmp_name"]);
					switch($data['mime']){
						case("image/png"):
							imagealphablending($image, false);
							imagesavealpha($image, true);
							$success = imagepng($image, $upload_dir["basedir"].DIRECTORY_SEPARATOR.$user_details['uuid'].DIRECTORY_SEPARATOR.$filename, 2);
							break;
						case('image/jpeg'):
						case('image/pjpeg'):
						case('image/x-jps'):
							$success = imagejpeg($image, $upload_dir["basedir"].DIRECTORY_SEPARATOR.$user_details['uuid'].DIRECTORY_SEPARATOR.$filename, 90);
							break;
						case('image/gif'):
							imagealphablending($image, false);
							imagesavealpha($image, true);
							$success = imagegif($image, $upload_dir["basedir"].DIRECTORY_SEPARATOR.$user_details['uuid'].DIRECTORY_SEPARATOR.$filename);
							break;
						default:
							$success = false;
							break;
					}
					if ($success) {
						$upload_options = array();
						$thumbnail_filename = get_filename($upload_dir["basedir"].DIRECTORY_SEPARATOR.$user_details['uuid'].DIRECTORY_SEPARATOR, 'thumb-'.$filename);
						$thumbnail_created = create_thumbnail($upload_dir["basedir"].DIRECTORY_SEPARATOR.$user_details['uuid'].DIRECTORY_SEPARATOR.$filename, $upload_dir["basedir"].DIRECTORY_SEPARATOR.$user_details['uuid'].DIRECTORY_SEPARATOR.$thumbnail_filename, 320, 320);
						if ($thumbnail_created) {
							$upload_options['thumbnail'] = $thumbnail_filename;
						}
						$uuid = uuid_v4();
						$wpdb->query("INSERT INTO ".$wpdb->prefix."uploads (
							uuid, 
							user_id, 
							original_filename, 
							filename, 
							filetype, 
							type, 
							status, 
							options,
							deleted, 
							created
						) VALUES (
							'".esc_sql($uuid)."',
							'".esc_sql($user_details['id'])."',
							'".esc_sql($_FILES["file"]["name"])."',
							'".esc_sql($filename)."',
							'image',
							'public',
							'active',
							'".esc_sql(json_encode($upload_options))."',
							'0',
							'".time()."'
						)");
						$return_object = array(
							'status' => 'OK', 
							'file_uid' => $uuid, 
							'thumbnail' => $thumbnail_created ? $upload_dir["baseurl"].'/'.$user_details['uuid'].'/'.$thumbnail_filename : $upload_dir["baseurl"].'/'.$user_details['uuid'].'/'.$filename, 
							'url' => $upload_dir["baseurl"].'/'.$user_details['uuid'].'/'.$filename, 
							'message' => esc_html__('Image was uploaded successfully.', 'fb')
						);
					} else {
						$return_object = array('status' => 'ERROR', 'message' => esc_html__('Unable to save uploaded image.', 'fb'));
					}
					imagedestroy($image);
					echo json_encode($return_object);
					exit;
				} else {
					$return_object = array('status' => 'ERROR', 'message' => esc_html__('Uploaded file is not an image.', 'fb'));
					echo json_encode($return_object);
					exit;
				}
			} else {
				$return_object = array('status' => 'ERROR', 'message' => esc_html__('Image was not uploaded properly.', 'fb'));
				echo json_encode($return_object);
				exit;
			}
		} else {
			$return_object = array('status' => 'ERROR', 'message' => esc_html__('Unable to save uploaded image.', 'fb'));
			echo json_encode($return_object);
			exit;
		}
	}
	$return_object = array('status' => 'ERROR', 'message' => esc_html__('Image was not uploaded properly.', 'fb'));
	echo json_encode($return_object);
	exit;
} else {
	if (!empty($user_details)) {
		if (array_key_exists('ajax-'.$_REQUEST['action'], $site_data['filters'])) do_action('ajax-'.$_REQUEST['action']);
	} else {
		if (array_key_exists('ajax-nopriv-'.$_REQUEST['action'], $site_data['filters'])) do_action('ajax-nopriv-'.$_REQUEST['action']);
	}
	echo '0';
}
?>