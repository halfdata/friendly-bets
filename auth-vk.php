<?php
include(dirname(__FILE__).'/inc/functions.php');
include(dirname(__FILE__).'/inc/icdb.php');
include(dirname(__FILE__).'/inc/common.php');

if (true) {
	if (array_key_exists('code', $_REQUEST)) {
		$code = $_REQUEST["code"];
		$token_url = 'https://oauth.vk.com/access_token?client_id='.urlencode($options['vk-client-id']).'&redirect_uri='.urlencode(url('auth-vk.php')).'&client_secret='.urlencode($options['vk-client-secret']).'&code='.urlencode($code);

		$curl = curl_init($token_url);
		curl_setopt($curl, CURLOPT_POST, false);
		curl_setopt($curl, CURLOPT_PORT, 443);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
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
			    header('Location: '.url('?page=profile').'#tab-connections');
            } else {
                header('Location: '.url('login.php'));
            }
			exit;
		}
		$graph_url = 'https://api.vk.com/method/users.get?access_token='.urlencode($access_token->access_token).'&user_id='.urlencode($access_token->user_id).'&v=6.00';
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
		
		$vk_data = json_decode($data);
		if ($vk_data) {
			if (empty($access_token->email)) {
                $_SESSION['error-message'] = esc_html__('Something went wrong. Email address was not provided.', 'fb');
                if (!empty($user_details)) {
                    header('Location: '.url('?page=profile').'#tab-connections');
                } else {
                    header('Location: '.url('login.php'));
                }
                exit;
            }
            if (empty($user_details)) {                 // Try to login
                $login = create_login($access_token->email);
                $connection_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."user_connections WHERE source_id = '".esc_sql($access_token->email)."' AND source = 'vk' AND deleted != '1'", ARRAY_A);
                if (empty($connection_details)) {       // VK connection not found
                    $user_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."users WHERE login = '".esc_sql($login)."' AND deleted != '1'", ARRAY_A);
                    if (empty($user_details)) {         // Account with the same email not found
						if ($options['enable-register'] != 'on') {
							$_SESSION['error-message'] = sprintf(esc_html__('Email address "%s" was not registered.', 'fb'), $access_token->email);
							header('Location: '.url('login.php'));
							exit;
						}
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
								'".esc_sql($access_token->email)."',
								'".esc_sql($vk_data->response[0]->first_name.' '.$vk_data->response[0]->last_name)."',
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
								'vk',
								'".esc_sql($access_token->email)."',
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
								'vk',
								'".esc_sql($session_id)."',
								'".esc_sql($user_id)."',
								'".esc_sql($_SERVER['REMOTE_ADDR'])."',
								'".time()."',
								'".time()."',
								 '86400'
							)");
						if (PHP_VERSION_ID < 70300) setcookie('fb-auth', $session_id, time()+3600*24*60, parse_url($options['url'], PHP_URL_PATH).'; samesite=lax');
						else setcookie('fb-auth', $session_id, array('expires' => time()+3600*24*60, 'samesite' => 'Lax', 'path' => parse_url($options['url'], PHP_URL_PATH)));
						if (array_key_exists('login-redirect', $_SESSION) && !empty($_SESSION['login-redirect'])) {
							$redirect_url = $_SESSION['login-redirect'];
							unset($_SESSION['login-redirect']);
						} else $redirect_url = $options['url'];
                        header('Location: '.$redirect_url);
                        exit;
					} else {                            // Account with the same email found
                        $_SESSION['error-message'] = sprintf(esc_html__('Email address "%s" already registered but not connected to VK. Please do it on Account Settings.', 'fb'), $access_token->email);
                        header('Location: '.url('login.php'));
                        exit;
                    }
                } else {                                // VK connection found
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
					} else {
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
								'vk',
								'".esc_sql($session_id)."',
								'".esc_sql($user_details['id'])."',
								'".esc_sql($_SERVER['REMOTE_ADDR'])."',
								'".time()."',
								'".time()."',
								 '86400'
							)");
						if (PHP_VERSION_ID < 70300) setcookie('fb-auth', $session_id, time()+3600*24*60, parse_url($options['url'], PHP_URL_PATH).'; samesite=lax');
						else setcookie('fb-auth', $session_id, array('expires' => time()+3600*24*60, 'samesite' => 'Lax', 'path' => parse_url($options['url'], PHP_URL_PATH)));
						if (array_key_exists('login-redirect', $_SESSION) && !empty($_SESSION['login-redirect'])) {
							$redirect_url = $_SESSION['login-redirect'];
							unset($_SESSION['login-redirect']);
						} else $redirect_url = $options['url'];
                        header('Location: '.$redirect_url);
                        exit;
					}
                }
            } else {                                    // Try to connect from Account Settings
				$connection_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."user_connections WHERE source_id = '".esc_sql($access_token->email)."' AND source = 'vk' AND user_id != '".esc_sql($user_details['id'])."' AND deleted != '1'", ARRAY_A);
				if (empty($connection_details)) {		// VK Account is not used by another user.
					$connection_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."user_connections WHERE source_id = '".esc_sql($access_token->email)."' AND source = 'vk' AND user_id = '".esc_sql($user_details['id'])."' AND deleted != '1'", ARRAY_A);
					if (empty($connection_details)) {		// VK Account is not used by this user.
						$wpdb->query("INSERT INTO ".$wpdb->prefix."user_connections (
							user_id, 
							source, 
							source_id, 
							deleted, 
							created
						) VALUES (
							'".esc_sql($user_details['id'])."',
							'vk',
							'".esc_sql($access_token->email)."',
							'0',
							'".time()."'
						)");
						header('Location: '.url('?page=profile'));
						exit;
					} else {								// VK Account is used by this user.
						$_SESSION['error-message'] = esc_html__('This VK Account is already connected to your account.', 'fb');
						header('Location: '.url('?page=profile'));
						exit;
					}
				} else {								// VK Account is used by another user.
					$_SESSION['error-message'] = esc_html__('This VK Account is already connected to another user.', 'fb');
					header('Location: '.url('?page=profile'));
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