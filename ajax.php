<?php
include_once(dirname(__FILE__).'/inc/functions.php');
include_once(dirname(__FILE__).'/inc/icdb.php');
include_once(dirname(__FILE__).'/inc/common.php');
//header('Content-Type: application/json');
if (!array_key_exists('action', $_REQUEST)) {
    $return_data = array('status' => 'FATAL', 'message' => esc_html__('Invalid request.', 'fb'));
    echo json_encode($return_data);
    exit;
}

do_action('admin_init');

if ($_REQUEST['action'] == 'register') {
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
    if (PHP_VERSION_ID < 70300) setcookie('fb-auth', $session_id, time()+3600*24*60, '; samesite=lax');
    else setcookie('fb-auth', $session_id, array('lifetime' => time()+3600*24*60, 'samesite' => 'Lax'));

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
    if (PHP_VERSION_ID < 70300) setcookie('fb-auth', $session_id, time()+3600*24*60, '; samesite=lax');
    else setcookie('fb-auth', $session_id, array('lifetime' => time()+3600*24*60, 'samesite' => 'Lax'));

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
    if (PHP_VERSION_ID < 70300) setcookie('fb-auth', $session_id, time()+3600*24*60, '; samesite=lax');
    else setcookie('fb-auth', $session_id, array('lifetime' => time()+3600*24*60, 'samesite' => 'Lax'));

    if (empty($redirect) || strpos($redirect, $options['url']) === false) $redirect = $options['url'];
    $return_object = array('status' => 'OK', 'url' => $redirect);
	echo json_encode($return_object);
	exit;
} else if ($_REQUEST['action'] == 'save-settings') {
    if (empty($user_details)) {
        $return_data = array('status' => 'WARNING', 'message' => esc_html__('Oops. Please enter your account to perform this action.', 'fb'));
        echo json_encode($return_data);
        exit;
    }
    $timezone = trim(stripslashes($_REQUEST['timezone']));
    $name = trim(stripslashes($_REQUEST['name']));
    $current_password = trim(stripslashes($_REQUEST['current-password']));
    $password = trim(stripslashes($_REQUEST['password']));
    $repeat_password = trim(stripslashes($_REQUEST['repeat-password']));
    
    $timezone_offset = timezone_offset($timezone);
    if ($timezone_offset === false) $errors['timezone'] = esc_html__('Invalid timezone.', 'fb');

    if (mb_strlen($name) < 2) $errors['name'] = esc_html__('The full name is too short.', 'fb');
    else if (mb_strlen($name) > 127) $errors['name'] = esc_html__('The full name is too long.', 'fb');
    
    if (!empty($password)) {
        if (password_verify($current_password, $user_details['password'])) {
            if (mb_strlen($password) < 6) $errors['password'] = esc_html__('The password must be at least 6 characters long.', 'fb');
            else if ($password != $repeat_password) $errors['repeat-password'] = esc_html__('Repeat the password properly.', 'fb');
        } else $errors['current-password'] = esc_html__('Password is not correct.', 'fb');
    }
    if (!empty($errors)) {
        $return_data = array('status' => 'ERROR', 'errors' => $errors);
        echo json_encode($return_data);
        exit;
    }
    $wpdb->query("UPDATE ".$wpdb->prefix."users SET timezone = '".esc_sql($timezone)."', name = '".esc_sql($name)."'".(!empty($password) ? ", password = '".esc_sql(password_hash($password, PASSWORD_DEFAULT))."'" : "")." WHERE id = '".esc_sql($user_details['id'])."'");    
    $return_object = array('status' => 'OK', 'message' => esc_html__('Settings successfully saved.', 'fb'));
    echo json_encode($return_object);
    exit;
} else if ($_REQUEST['action'] == 'google-disconnect') {
    if (empty($user_details)) {
        $return_data = array('status' => 'WARNING', 'message' => esc_html__('Oops. Please enter your account to perform this action.', 'fb'));
        echo json_encode($return_data);
        exit;
    }
    $wpdb->query("UPDATE ".$wpdb->prefix."user_connections SET deleted = '1' WHERE user_id = '".esc_sql($user_details['id'])."' AND source = 'google'");
    $html = '
    <a class="social-button social-button-google" href="https://accounts.google.com/o/oauth2/auth?client_id='.urlencode($options['google-client-id']).'&scope=profile%20email&response_type=code&redirect_uri='.urlencode(url('auth.php')).'?google=auth">
        <i class="fab fa-google"></i> '.esc_html__('Connect to Google', 'fb').'
    </a>';
    $return_object = array('status' => 'OK', 'html' => $html, 'message' => esc_html__('Google Account successfully disconnected.', 'fb'));
    echo json_encode($return_object);
    exit;
} else if ($_REQUEST['action'] == 'facebook-disconnect') {
    if (empty($user_details)) {
        $return_data = array('status' => 'WARNING', 'message' => esc_html__('Oops. Please enter your account to perform this action.', 'fb'));
        echo json_encode($return_data);
        exit;
    }
    $wpdb->query("UPDATE ".$wpdb->prefix."user_connections SET deleted = '1' WHERE user_id = '".esc_sql($user_details['id'])."' AND source = 'facebook'");
    $html = '
    <a class="social-button social-button-facebook" href="https://www.facebook.com/dialog/oauth?client_id='.$options['facebook-client-id'].'&scope=public_profile,email&redirect_uri='.urlencode(url('auth.php')).'?facebook=auth">
        <i class="fab fa-facebook-f"></i> '.esc_html__('Connect to Facebook', 'fb').'
    </a>';
    $return_object = array('status' => 'OK', 'html' => $html, 'message' => esc_html__('Facebook Account successfully disconnected.', 'fb'));
    echo json_encode($return_object);
    exit;
} else if ($_REQUEST['action'] == 'vk-disconnect') {
    if (empty($user_details)) {
        $return_data = array('status' => 'WARNING', 'message' => esc_html__('Oops. Please enter your account to perform this action.', 'fb'));
        echo json_encode($return_data);
        exit;
    }
    $wpdb->query("UPDATE ".$wpdb->prefix."user_connections SET deleted = '1' WHERE user_id = '".esc_sql($user_details['id'])."' AND source = 'vk'");
    $html = '
    <a class="social-button social-button-vk" href="https://oauth.vk.com/authorize?client_id='.urlencode($options['vk-client-id']).'&display=page&redirect_uri='.urlencode(url('auth-vk.php')).'&scope=email&response_type=code&v=6.00">
        <i class="fab fa-vk"></i> '.esc_html__('Connect to VK', 'fb').'
    </a>';
    $return_object = array('status' => 'OK', 'html' => $html, 'message' => esc_html__('VK Account successfully disconnected.', 'fb'));
    echo json_encode($return_object);
    exit;
} else if ($_REQUEST['action'] == 'test-mailing') {
    if (empty($user_details)) {
        $return_data = array('status' => 'WARNING', 'message' => esc_html__('Oops. Please enter your account to perform this action.', 'fb'));
        echo json_encode($return_data);
        exit;
    } else if ($user_details['role'] != 'admin') {
        $return_data = array('status' => 'WARNING', 'message' => esc_html__('Hmm. You do not have permissions to perform this action.', 'fb'));
        echo json_encode($return_data);
        exit;
    }
    foreach ($options as $key => $value) {
        if (array_key_exists($key, $_REQUEST)) {
            $options[$key] = trim(stripslashes($_REQUEST[$key]));
        }
    }
    $message = sprintf(esc_html__('This is a test message. It was sent from %s (%s) using the following mailing parameters.', 'fb'), esc_html($options['title']), esc_html($options['url'])).'<br />';
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
} else if ($_REQUEST['action'] == 'save-site-settings') {
    if (empty($user_details)) {
        $return_data = array('status' => 'WARNING', 'message' => esc_html__('Oops. Please enter your account to perform this action.', 'fb'));
        echo json_encode($return_data);
        exit;
    } else if ($user_details['role'] != 'admin') {
        $return_data = array('status' => 'WARNING', 'message' => esc_html__('Hmm. You do not have permissions to perform this action.', 'fb'));
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
            if (array_key_exists($key, $tr_options)) $tr_options[$key] = translatable_populate($key);
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
} else if ($_REQUEST['action'] == 'user-delete') {
    if (empty($user_details)) {
        $return_data = array('status' => 'WARNING', 'message' => esc_html__('Oops. Please enter your account to perform this action.', 'fb'));
        echo json_encode($return_data);
        exit;
    } else if ($user_details['role'] != 'admin') {
        $return_data = array('status' => 'WARNING', 'message' => esc_html__('Hmm. You do not have permissions to perform this action.', 'fb'));
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
} else if ($_REQUEST['action'] == 'users-delete') {
    if (empty($user_details)) {
        $return_data = array('status' => 'WARNING', 'message' => esc_html__('Oops. Please enter your account to perform this action.', 'fb'));
        echo json_encode($return_data);
        exit;
    } else if ($user_details['role'] != 'admin') {
        $return_data = array('status' => 'WARNING', 'message' => esc_html__('Hmm. You do not have permissions to perform this action.', 'fb'));
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

    $return_object = array('status' => 'OK', 'message' => esc_html__('Selected users successfully deleted.', 'fb'));
    echo json_encode($return_object);
    exit;
} else if ($_REQUEST['action'] == 'session-delete') {
    if (empty($user_details)) {
        $return_data = array('status' => 'WARNING', 'message' => esc_html__('Oops. Please enter your account to perform this action.', 'fb'));
        echo json_encode($return_data);
        exit;
    } else if ($user_details['role'] != 'admin') {
        $return_data = array('status' => 'WARNING', 'message' => esc_html__('Hmm. You do not have permissions to perform this action.', 'fb'));
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
} else if ($_REQUEST['action'] == 'sessions-delete') {
    if (empty($user_details)) {
        $return_data = array('status' => 'WARNING', 'message' => esc_html__('Oops. Please enter your account to perform this action.', 'fb'));
        echo json_encode($return_data);
        exit;
    } else if ($user_details['role'] != 'admin') {
        $return_data = array('status' => 'WARNING', 'message' => esc_html__('Hmm. You do not have permissions to perform this action.', 'fb'));
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

    $return_object = array('status' => 'OK', 'message' => esc_html__('Selected sessions successfully deleted.', 'fb'));
    echo json_encode($return_object);
    exit;
} else if ($_REQUEST['action'] == 'user-status-toggle') {
    if (empty($user_details)) {
        $return_data = array('status' => 'WARNING', 'message' => esc_html__('Oops. Please enter your account to perform this action.', 'fb'));
        echo json_encode($return_data);
        exit;
    } else if ($user_details['role'] != 'admin') {
        $return_data = array('status' => 'WARNING', 'message' => esc_html__('Hmm. You do not have permissions to perform this action.', 'fb'));
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
} else if ($_REQUEST['action'] == 'users-activate') {
    if (empty($user_details)) {
        $return_data = array('status' => 'WARNING', 'message' => esc_html__('Oops. Please enter your account to perform this action.', 'fb'));
        echo json_encode($return_data);
        exit;
    } else if ($user_details['role'] != 'admin') {
        $return_data = array('status' => 'WARNING', 'message' => esc_html__('Hmm. You do not have permissions to perform this action.', 'fb'));
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

    $return_object = array('status' => 'OK', 'message' => esc_html__('Selected users successfully activated.', 'fb'));
    echo json_encode($return_object);
    exit;
} else if ($_REQUEST['action'] == 'users-deactivate') {
    if (empty($user_details)) {
        $return_data = array('status' => 'WARNING', 'message' => esc_html__('Oops. Please enter your account to perform this action.', 'fb'));
        echo json_encode($return_data);
        exit;
    } else if ($user_details['role'] != 'admin') {
        $return_data = array('status' => 'WARNING', 'message' => esc_html__('Hmm. You do not have permissions to perform this action.', 'fb'));
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

    $return_object = array('status' => 'OK', 'message' => esc_html__('Selected users successfully deactivated.', 'fb'));
    echo json_encode($return_object);
    exit;
} else if ($_REQUEST['action'] == 'save-user') {
    if (empty($user_details)) {
        $return_data = array('status' => 'WARNING', 'message' => esc_html__('Oops. Please enter your account to perform this action.', 'fb'));
        echo json_encode($return_data);
        exit;
    } else if ($user_details['role'] != 'admin') {
        $return_data = array('status' => 'WARNING', 'message' => esc_html__('Hmm. You do not have permissions to perform this action.', 'fb'));
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
    }
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
    
    if (!empty($errors)) {
        $return_data = array('status' => 'ERROR', 'errors' => $errors);
        echo json_encode($return_data);
        exit;
    }
    if ($user_id > 0) {
        $wpdb->query("UPDATE ".$wpdb->prefix."users SET role = '".esc_sql($role)."', login = '".esc_sql($login)."', email = '".esc_sql($email)."', timezone = '".esc_sql($timezone)."', name = '".esc_sql($name)."'".(!empty($password) ? ", password = '".esc_sql(password_hash($password, PASSWORD_DEFAULT))."'" : "")." WHERE id = '".esc_sql($user_id)."'");
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
                '0',
                '".time()."'
            )");
    }
    $_SESSION['success-message'] = esc_html__('User details successfully saved.', 'fb');
    $return_object = array('status' => 'OK', 'message' => esc_html__('User details successfully saved.', 'fb'), 'url' => url('users.php'));
    echo json_encode($return_object);
    exit;
} else if ($_REQUEST['action'] == 'image-uploader-action') {
    if (empty($user_details)) {
        $return_data = array('status' => 'WARNING', 'message' => esc_html__('Oops. Please enter your account to perform this action.', 'fb'));
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
                $image = imagecreatefromstring($file_content);
                if ($image !== false) {
                    $data = getimagesize($_FILES["file"]["tmp_name"]);
                    switch($data['mime']){
                        case("image/png"):
                            imagealphablending($image, false);
                            imagesavealpha($image, true);
                            $success = imagepng($image, $upload_dir["basedir"].DIRECTORY_SEPARATOR.$user_details['uuid'].DIRECTORY_SEPARATOR.$filename);
                            break;
                        case('image/jpeg'):
                        case('image/pjpeg'):
                        case('image/x-jps'):
                            $success = imagejpeg($image, $upload_dir["basedir"].DIRECTORY_SEPARATOR.$user_details['uuid'].DIRECTORY_SEPARATOR.$filename);
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
                            '',
                            '0',
                            '".time()."'
                        )");
                        $return_object = array('status' => 'OK', 'file_uid' => $uuid, 'url' => $upload_dir["baseurl"].'/'.$user_details['uuid'].'/'.$filename, 'message' => esc_html__('Image was uploaded successfully.', 'fb'));
                    } else {
                        $return_object = array('status' => 'ERROR', 'message' => esc_html__('Unable to save uploaded image.', 'fb'));
                    }
                    imagedestroy($image);
                    echo json_encode($return_object);
                    exit;
                } else {
                    $return_object = array('status' => 'ERROR', 'message' => esc_html__('Image was not uploaded properly.', 'fb'));
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