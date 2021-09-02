<?php
include(dirname(__FILE__).'/inc/functions.php');
include(dirname(__FILE__).'/inc/icdb.php');
include(dirname(__FILE__).'/inc/common.php');

if (array_key_exists('google', $_GET) && $_GET['google'] == 'auth') {
	if (array_key_exists('code', $_REQUEST)) {
		$code = $_REQUEST["code"];
		$token_url = 'https://accounts.google.com/o/oauth2/token';
		$curl = curl_init($token_url);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, 'code='.$code.'&client_id='.$options['google-client-id'].'&client_secret='.$options['google-client-secret'].'&redirect_uri='.url('auth.php').'?google=auth&grant_type=authorization_code');
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		$data = curl_exec($curl);
		curl_close($curl);
		
		$access_token = json_decode($data);
		if (!$access_token) {
			$_SESSION['error-message'] = esc_html__('Something went wrong. Please contact administrator.', 'fb');
            if (!empty($user_details)) {
			    header('Location: '.url('settings.php').'#tab-connections');
            } else {
                header('Location: '.url('login.php'));
            }
			exit;
		}
		$graph_url = 'https://www.googleapis.com/oauth2/v3/userinfo?access_token='.$access_token->access_token;
		$curl = curl_init($graph_url);
		curl_setopt($curl, CURLOPT_POST, false);
		curl_setopt($curl, CURLOPT_PORT, 443);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // verify certificate
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // check existence of CN and verify that it matches hostname
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		$data = curl_exec($curl);
		curl_close($curl);

		$google_data = json_decode($data);
    	if ($google_data) {
			if (empty($google_data->email)) {
                $_SESSION['error-message'] = esc_html__('Something went wrong. Email address was not provided.', 'fb');
                if (!empty($user_details)) {
                    header('Location: '.url('settings.php').'#tab-connections');
                } else {
                    header('Location: '.url('login.php'));
                }
                exit;
            }
            if (empty($user_details)) {                 // Try to login
                $login = create_login($google_data->email);
                $connection_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."user_connections WHERE source_id = '".esc_sql($google_data->email)."' AND source = 'google' AND deleted != '1'", ARRAY_A);
                if (empty($connection_details)) {       // Google connection not found
                    $user_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."users WHERE login = '".esc_sql($login)."' AND deleted != '1'", ARRAY_A);
                    if (empty($user_details)) {         // Account with the same email not found
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
								'".esc_sql(password_hash(uuid_v4(), PASSWORD_DEFAULT))."',
								'".esc_sql($google_data->email)."',
								'".esc_sql($google_data->name)."',
								'user',
								'active',
								'".esc_sql('UTC')."',
								'',
								'1',
								'".esc_sql(uuid_v4())."',
								'".esc_sql(uuid_v4())."',
								'0',
								'".time()."'
							)");
						$user_id = $wpdb->insert_id;
						$wpdb->query("INSERT INTO ".$wpdb->prefix."user_connections (
								user_id, 
								source, 
								source_id, 
								deleted, 
								created
							) VALUES (
								'".esc_sql($user_id)."',
								'google',
								'".esc_sql($google_data->email)."',
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
								'google',
								'".esc_sql($session_id)."',
								'".esc_sql($user_id)."',
								'".esc_sql($_SERVER['REMOTE_ADDR'])."',
								'".time()."',
								'".time()."',
								 '86400'
							)");
						if (PHP_VERSION_ID < 70300) setcookie('fb-auth', $session_id, time()+3600*24*60, '; samesite=lax');
						else setcookie('fb-auth', $session_id, array('lifetime' => time()+3600*24*60, 'samesite' => 'Lax'));
						if (array_key_exists('login-redirect', $_SESSION) && !empty($_SESSION['login-redirect'])) {
							$redirect_url = $_SESSION['login-redirect'];
							unset($_SESSION['login-redirect']);
						} else $redirect_url = $options['url'];
                        header('Location: '.$redirect_url);
                        exit;
					} else {                            // Account with the same email found
                        $_SESSION['error-message'] = sprintf(esc_html__('Email address "%s" already registered but not connected to Google. Please do it on Account Settings.', 'fb'), $google_data->email);
                        header('Location: '.url('login.php'));
                        exit;
                    }
                } else {                                // Google connection found
                    $user_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."users WHERE id = '".esc_sql($connection_details['user_id'])."' AND deleted != '1'", ARRAY_A);
                    if (empty($user_details)) {         // Connected account not found
						$wpdb->query("UPDATE ".$wpdb->prefix."user_connections SET deleted = '1' WHERE id = '".esc_sql($connection_details['id'])."'");
						$_SESSION['error-message'] = esc_html__('Service temporarily not available. Try again later.', 'fb');
                        header('Location: '.url('login.php'));
                        exit;
					} else if ($user_details['status'] != 'active') {
						$_SESSION['error-message'] = esc_html__('Unfortunately, your account temporarily disbaled.', 'fb');
                        header('Location: '.url('login.php'));
                        exit;
					} else {					        // Connected account found
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
								'google',
								'".esc_sql($session_id)."',
								'".esc_sql($user_details['id'])."',
								'".esc_sql($_SERVER['REMOTE_ADDR'])."',
								'".time()."',
								'".time()."',
								 '86400'
							)");
						if (PHP_VERSION_ID < 70300) setcookie('fb-auth', $session_id, time()+3600*24*60, '; samesite=lax');
						else setcookie('fb-auth', $session_id, array('lifetime' => time()+3600*24*60, 'samesite' => 'Lax'));
						if (array_key_exists('login-redirect', $_SESSION) && !empty($_SESSION['login-redirect'])) {
							$redirect_url = $_SESSION['login-redirect'];
							unset($_SESSION['login-redirect']);
						} else $redirect_url = $options['url'];
                        header('Location: '.$redirect_url);
                        exit;
					}
                }
            } else {                                    // Try to connect from Account Settings
				$connection_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."user_connections WHERE source_id = '".esc_sql($google_data->email)."' AND source = 'google' AND user_id != '".esc_sql($user_details['id'])."' AND deleted != '1'", ARRAY_A);
				if (empty($connection_details)) {		// Google Account is not used by another user.
					$connection_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."user_connections WHERE source_id = '".esc_sql($google_data->email)."' AND source = 'google' AND user_id = '".esc_sql($user_details['id'])."' AND deleted != '1'", ARRAY_A);
					if (empty($connection_details)) {		// Google Account is not used by this user.
						$wpdb->query("INSERT INTO ".$wpdb->prefix."user_connections (
							user_id, 
							source, 
							source_id, 
							deleted, 
							created
						) VALUES (
							'".esc_sql($user_details['id'])."',
							'google',
							'".esc_sql($google_data->email)."',
							'0',
							'".time()."'
						)");
						header('Location: '.url('settings.php'));
						exit;
					} else {								// Google Account is used by this user.
						$_SESSION['error-message'] = esc_html__('This Google Account is already connected to your account.', 'fb');
						header('Location: '.url('settings.php'));
						exit;
					}
				} else {								// Google Account is used by another user.
					$_SESSION['error-message'] = esc_html__('This Google Account is already connected to another user.', 'fb');
					header('Location: '.url('settings.php'));
					exit;
				}
            }
		} else {
			$_SESSION['error-message'] = esc_html__('Service temporarily not available. Try again later.', 'fb');
			header('Location: '.url('login.php'));
			exit;
		}
	} else {
		$_SESSION['error-message'] = esc_html__('Service temporarily not available. Try again later.', 'fb');
		header('Location: '.url('login.php'));
		exit;
	}
} else if (array_key_exists('facebook', $_GET) && $_GET['facebook'] == 'auth') {
	if (array_key_exists('code', $_REQUEST)) {
		$code = $_REQUEST["code"];
		$token_url = 'https://graph.facebook.com/oauth/access_token?client_id='.urlencode($options['facebook-client-id']).'&redirect_uri='.urlencode(url('auth.php').'?facebook=auth').'&client_secret='.urlencode($options['facebook-client-secret']).'&code='.urlencode($code);

		$curl = curl_init($token_url);
		curl_setopt($curl, CURLOPT_POST, false);
		curl_setopt($curl, CURLOPT_PORT, 443);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // verify certificate
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // check existence of CN and verify that it matches hostname
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		$data = curl_exec($curl);
		curl_close($curl);
		
		$access_token = json_decode($data);
		if (!$access_token) {
			$_SESSION['error-message'] = esc_html__('Something went wrong. Please contact administrator.', 'fb');
            if (!empty($user_details)) {
			    header('Location: '.url('settings.php').'#tab-connections');
            } else {
                header('Location: '.url('login.php'));
            }
			exit;
		}
		$graph_url = 'https://graph.facebook.com/me?access_token='.urlencode($access_token->access_token).'&fields=id,name,email';
		$curl = curl_init($graph_url);
		curl_setopt($curl, CURLOPT_POST, false);
		curl_setopt($curl, CURLOPT_PORT, 443);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // verify certificate
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // check existence of CN and verify that it matches hostname
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		$data = curl_exec($curl);
		curl_close($curl);
		
		$facebook_data = json_decode($data);
		if ($facebook_data) {
			if (empty($facebook_data->email)) {
                $_SESSION['error-message'] = esc_html__('Something went wrong. Email address was not provided.', 'fb');
                if (!empty($user_details)) {
                    header('Location: '.url('settings.php').'#tab-connections');
                } else {
                    header('Location: '.url('login.php'));
                }
                exit;
            }
            if (empty($user_details)) {                 // Try to login
                $login = create_login($facebook_data->email);
                $connection_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."user_connections WHERE source_id = '".esc_sql($facebook_data->email)."' AND source = 'facebook' AND deleted != '1'", ARRAY_A);
                if (empty($connection_details)) {       // Facebook connection not found
                    $user_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."users WHERE login = '".esc_sql($login)."' AND deleted != '1'", ARRAY_A);
                    if (empty($user_details)) {         // Account with the same email not found
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
								'".esc_sql(password_hash(uuid_v4(), PASSWORD_DEFAULT))."',
								'".esc_sql($facebook_data->email)."',
								'".esc_sql($facebook_data->name)."',
								'user',
								'active',
								'".esc_sql('UTC')."',
								'',
								'1',
								'".esc_sql(uuid_v4())."',
								'".esc_sql(uuid_v4())."',
								'0',
								'".time()."'
							)");
						$user_id = $wpdb->insert_id;
						$wpdb->query("INSERT INTO ".$wpdb->prefix."user_connections (
								user_id, 
								source, 
								source_id, 
								deleted, 
								created
							) VALUES (
								'".esc_sql($user_id)."',
								'facebook',
								'".esc_sql($facebook_data->email)."',
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
								'facebook',
								'".esc_sql($session_id)."',
								'".esc_sql($user_id)."',
								'".esc_sql($_SERVER['REMOTE_ADDR'])."',
								'".time()."',
								'".time()."',
								 '86400'
							)");
						if (PHP_VERSION_ID < 70300) setcookie('fb-auth', $session_id, time()+3600*24*60, '; samesite=lax');
						else setcookie('fb-auth', $session_id, array('lifetime' => time()+3600*24*60, 'samesite' => 'Lax'));
						if (array_key_exists('login-redirect', $_SESSION) && !empty($_SESSION['login-redirect'])) {
							$redirect_url = $_SESSION['login-redirect'];
							unset($_SESSION['login-redirect']);
						} else $redirect_url = $options['url'];
                        header('Location: '.$redirect_url);
                        exit;
					} else {                            // Account with the same email found
                        $_SESSION['error-message'] = sprintf(esc_html__('Email address "%s" already registered but not connected to Facebook. Please do it on Account Settings.', 'fb'), $facebook_data->email);
                        header('Location: '.url('login.php'));
                        exit;
                    }
                } else {                                // Facebook connection found
                    $user_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."users WHERE id = '".esc_sql($connection_details['user_id'])."' AND deleted != '1'", ARRAY_A);
                    if (empty($user_details)) {         // Connected account not found
						$wpdb->query("UPDATE ".$wpdb->prefix."user_connections SET deleted = '1' WHERE id = '".esc_sql($connection_details['id'])."'");
						$_SESSION['error-message'] = esc_html__('Service temporarily not available. Try again later.', 'fb');
                        header('Location: '.url('login.php'));
                        exit;
					} else if ($user_details['status'] != 'active') {
						$_SESSION['error-message'] = esc_html__('Unfortunately, your account temporarily disbaled.', 'fb');
                        header('Location: '.url('login.php'));
                        exit;
					} else {					        // Connected account found
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
								'facebook',
								'".esc_sql($session_id)."',
								'".esc_sql($user_details['id'])."',
								'".esc_sql($_SERVER['REMOTE_ADDR'])."',
								'".time()."',
								'".time()."',
								 '86400'
							)");
						if (PHP_VERSION_ID < 70300) setcookie('fb-auth', $session_id, time()+3600*24*60, '; samesite=lax');
						else setcookie('fb-auth', $session_id, array('lifetime' => time()+3600*24*60, 'samesite' => 'Lax'));
						if (array_key_exists('login-redirect', $_SESSION) && !empty($_SESSION['login-redirect'])) {
							$redirect_url = $_SESSION['login-redirect'];
							unset($_SESSION['login-redirect']);
						} else $redirect_url = $options['url'];
                        header('Location: '.$redirect_url);
                        exit;
					}
                }
            } else {                                    // Try to connect from Account Settings
				$connection_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."user_connections WHERE source_id = '".esc_sql($facebook_data->email)."' AND source = 'facebook' AND user_id != '".esc_sql($user_details['id'])."' AND deleted != '1'", ARRAY_A);
				if (empty($connection_details)) {		// Facebook Account is not used by another user.
					$connection_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."user_connections WHERE source_id = '".esc_sql($facebook_data->email)."' AND source = 'facebook' AND user_id = '".esc_sql($user_details['id'])."' AND deleted != '1'", ARRAY_A);
					if (empty($connection_details)) {		// Facebook Account is not used by this user.
						$wpdb->query("INSERT INTO ".$wpdb->prefix."user_connections (
							user_id, 
							source, 
							source_id, 
							deleted, 
							created
						) VALUES (
							'".esc_sql($user_details['id'])."',
							'facebook',
							'".esc_sql($facebook_data->email)."',
							'0',
							'".time()."'
						)");
						header('Location: '.url('settings.php'));
						exit;
					} else {								// Facebook Account is used by this user.
						$_SESSION['error-message'] = esc_html__('This Facebook Account is already connected to your account.', 'fb');
						header('Location: '.url('settings.php'));
						exit;
					}
				} else {								// Facebook Account is used by another user.
					$_SESSION['error-message'] = esc_html__('This Facebook Account is already connected to another user.', 'fb');
					header('Location: '.url('settings.php'));
					exit;
				}
            }
		} else {
			$_SESSION['error-message'] = esc_html__('Service temporarily not available. Try again later.', 'fb');
			header('Location: '.url('login.php'));
			exit;
		}
	} else {
		$_SESSION['error-message'] = esc_html__('Service temporarily not available. Try again later.', 'fb');
		header('Location: '.url('login.php'));
		exit;
	}
}
?>