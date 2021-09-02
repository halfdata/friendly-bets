<?php
function sync_database () {
	global $wpdb;
	$table_name = $wpdb->prefix."options";
	if ($wpdb->get_var("SHOW TABLES LIKE '".$table_name."'") == $table_name) $version = get_option('version', 0);
	else $version = 0;
	if ($version < VERSION) {
		$table_name = $wpdb->prefix."options";
		if($wpdb->get_var("SHOW TABLES LIKE '".$table_name."'") != $table_name) {
			$sql = "CREATE TABLE ".$table_name." (
				id int(11) NOT NULL AUTO_INCREMENT,
				options_key varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
				options_value longtext COLLATE utf8_unicode_ci,
				UNIQUE KEY  id (id)
			);";
			$wpdb->query($sql);
		}
		$table_name = $wpdb->prefix."sessions";
		if($wpdb->get_var("SHOW TABLES LIKE '".$table_name."'") != $table_name) {
			$sql = "CREATE TABLE ".$table_name." (
				id int(11) NOT NULL AUTO_INCREMENT,
				source varchar(31) COLLATE utf8_unicode_ci DEFAULT 'email',
				session_id varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
				user_id int(11) DEFAULT NULL,
				ip varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
				registered int(11) DEFAULT NULL,
				created int(11) DEFAULT NULL,
				valid_period int(11) DEFAULT '7200',
				UNIQUE KEY  id (id)
			);";
			$wpdb->query($sql);
		}
		$table_name = $wpdb->prefix."uploads";
		if($wpdb->get_var("SHOW TABLES LIKE '".$table_name."'") != $table_name) {
			$sql = "CREATE TABLE ".$table_name." (
				id int(11) NOT NULL AUTO_INCREMENT,
				uuid varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
				user_id int(11) DEFAULT NULL,
				original_filename varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
				filename varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
				filetype varchar(31) COLLATE utf8_unicode_ci DEFAULT NULL,
				type varchar(31) COLLATE utf8_unicode_ci DEFAULT NULL,
				status varchar(31) COLLATE utf8_unicode_ci DEFAULT NULL,
				options longtext COLLATE utf8_unicode_ci,
				deleted int(11) DEFAULT '0',
				created int(11) DEFAULT NULL,
				UNIQUE KEY  id (id)
			);";
			$wpdb->query($sql);
		}
		$table_name = $wpdb->prefix."users";
		if($wpdb->get_var("SHOW TABLES LIKE '".$table_name."'") != $table_name) {
			$sql = "CREATE TABLE ".$table_name." (
				id int(11) NOT NULL AUTO_INCREMENT,
				uuid varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
				login varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
				password varchar(63) COLLATE utf8_unicode_ci DEFAULT NULL,
				email varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
				name varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
				role varchar(31) COLLATE utf8_unicode_ci DEFAULT NULL,
				status varchar(31) COLLATE utf8_unicode_ci DEFAULT NULL,
				timezone varchar(63) COLLATE utf8_unicode_ci DEFAULT NULL,
				options longtext COLLATE utf8_unicode_ci,
				email_confirmed int(11) DEFAULT '0',
				email_confirmation_uid varchar(63) COLLATE utf8_unicode_ci DEFAULT NULL,
				password_reset_uid varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
				deleted int(11) DEFAULT '0',
				created int(11) DEFAULT NULL,
				UNIQUE KEY  id (id)
			);";
			$wpdb->query($sql);
		}
		$table_name = $wpdb->prefix."user_connections";
		if($wpdb->get_var("SHOW TABLES LIKE '".$table_name."'") != $table_name) {
			$sql = "CREATE TABLE ".$table_name." (
				id int(11) NOT NULL AUTO_INCREMENT,
				user_id int(11) DEFAULT NULL,
				source varchar(31) COLLATE utf8_unicode_ci DEFAULT NULL,
				source_id varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
				deleted int(11) DEFAULT '0',
				created int(11) DEFAULT NULL,
				UNIQUE KEY  id (id)
			);";
			$wpdb->query($sql);
		}
		save_option('version', VERSION);
	}
}

function esc_html($_text) {
	if (is_array($_text)) print_r($_text);
	return htmlspecialchars($_text, ENT_QUOTES);
}
function esc_html__($_text, $_textdomain = 'default') {
	global $translations;
	if (array_key_exists($_textdomain, $translations)) {
		$entry = $translations[$_textdomain]->getEntry($_text);
		if (!empty($entry)) {
			$translation = $entry->getMsgStr();
			if (!empty($translation)) return esc_html($translation);
		}
	}
	return esc_html($_text);
}
function timezone_choice($_selected_zone = 'UTC') {
	$continents = array( 'Africa', 'America', 'Antarctica', 'Arctic', 'Asia', 'Atlantic', 'Australia', 'Europe', 'Indian', 'Pacific' );
	if (empty($_selected_zone)) $_selected_zone = 'UTC';
	$timezone_identifiers_list = timezone_identifiers_list();
	$timezone_ids = array();
	foreach ($timezone_identifiers_list as $zone) {
		$zone = explode('/', $zone, 2);
		if (!in_array($zone[0], $continents)) {
			continue;
		}
		$timezone_ids[$zone[0]][] = $zone[1];
	}
	$structure = array();
	foreach($timezone_ids as $continent => $cities) {
		if (empty($cities)) {
			$structure[] = '<option value="'.esc_html($continent).'"'.($_selected_zone == $continent ? ' selected="selected"' : '').'>'.esc_html($continent).'</option>';
		} else {
			$structure[] = '<optgroup label="'.esc_html($continent).'">';
			sort($cities);
			foreach($cities as $city) {
				$value = $continent.'/'.$city;
				$name = str_replace(array('_', '/'), array(' ', ' - '), $city);
				$structure[] = '<option value="'.esc_html($value).'"'.($_selected_zone == $value ? ' selected="selected"' : '').'>'.esc_html($name).'</option>';
			}
			$structure[] = '</optgroup>';
		}
	}
	$structure[] = '<optgroup label="UTC"><option value="UTC"'.($_selected_zone == 'UTC' ? ' selected="selected"' : '').'>UTC</option></optgroup>';
	$structure[] = '<optgroup label="'.esc_html__('Manual Offsets', 'fb').'">';
	$offset_range = array(
		-12,
		-11.5,
		-11,
		-10.5,
		-10,
		-9.5,
		-9,
		-8.5,
		-8,
		-7.5,
		-7,
		-6.5,
		-6,
		-5.5,
		-5,
		-4.5,
		-4,
		-3.5,
		-3,
		-2.5,
		-2,
		-1.5,
		-1,
		-0.5,
		0,
		0.5,
		1,
		1.5,
		2,
		2.5,
		3,
		3.5,
		4,
		4.5,
		5,
		5.5,
		5.75,
		6,
		6.5,
		7,
		7.5,
		8,
		8.5,
		8.75,
		9,
		9.5,
		10,
		10.5,
		11,
		11.5,
		12,
		12.75,
		13,
		13.75,
		14,
	);
	foreach ($offset_range as $offset) {
		if ($offset >= 0) $offset_name = '+'.$offset;
		else $offset_name = (string)$offset;
		$offset_value = 'UTC'.$offset_name;
		$offset_name  = str_replace(array('.25', '.5', '.75'), array(':15', ':30', ':45'), $offset_name);
		$offset_name  = 'UTC'.$offset_name;
		$structure[] = '<option value="'.esc_html($offset_value).'"'.($_selected_zone == $offset_value ? ' selected="selected"' : '').'>'.esc_html($offset_name).'</option>';
	}
	$structure[] = '</optgroup>';

	return join( "\n", $structure );
}
function timezone_offset($_timezone) {
	if (empty($_timezone)) {
		return false;
	} else if (preg_match( '/^UTC[+-]/', $_timezone)) {
		return preg_replace( '/UTC\+?/', '', $_timezone);
	} else {
		try {
			$timezone_object = timezone_open($_timezone);
			$datetime_object = date_create();
			if (false === $timezone_object || false === $datetime_object) return false;
			return round(timezone_offset_get($timezone_object, $datetime_object)/3600, 2);
		} catch (Exception $e) {
			return false;
		}
	}
}
function uuid_v4() {
	return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
		mt_rand(0, 0xffff), mt_rand(0, 0xffff),
		mt_rand(0, 0xffff),
		mt_rand(0, 0x0fff) | 0x4000,
		mt_rand(0, 0x3fff) | 0x8000,
		mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
}
function create_login($_email) {
	$email_parts = explode('@', strtolower(trim($_email)));
	$email_parts[0] = str_replace('.', '', $email_parts[0]);
	return implode('@', $email_parts);
}
function url($_path) {
	global $options;
	$path = ltrim($_path, '/');
	return $options['url'].$path;
}
function is_hostname($_hostname) {
	return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $_hostname)
		&& preg_match("/^.{1,253}$/", $_hostname)
		&& preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $_hostname));
}
function esc_sql($_text) {
	global $wpdb;
	return $wpdb->escape_string($_text);
}
function send_mail($_to, $_subject, $_message, $_headers = '', $_attachments = array(), $_debug = false) {
	global $phpmailer, $options;
	if (!($phpmailer instanceof PHPMailer\PHPMailer\PHPMailer)) {
		require_once dirname(__FILE__).'/phpmailer/src/PHPMailer.php';
		require_once dirname(__FILE__).'/phpmailer/src/SMTP.php';
		require_once dirname(__FILE__).'/phpmailer/src/Exception.php';		
		$phpmailer = new PHPMailer\PHPMailer\PHPMailer;
	}
	if (empty($_headers)) {
		$headers = array();
		$charset = 'utf-8';
		$content_type = 'text/html';
		if ($options['mail-method'] == 'mail') {
			$from_email = $options['mail-from-email'];
			$from_name = $options['mail-from-name'];
		} else if ($options['mail-method'] == 'smtp') {
			$from_email = (empty($options['smtp-from-email']) ? $options['smtp-username'] : $options['smtp-from-email']);
			$from_name = $options['smtp-from-name'];
		}
	} else {
		if (!is_array($_headers)) {
			$tempheaders = explode("\n", str_replace("\r\n", "\n", $_headers));
		} else {
			$tempheaders = $_headers;
		}
		$headers = array();
		$cc = array();
		$bcc = array();
		if (!empty($tempheaders)) {
			foreach ((array)$tempheaders as $header) {
				if (strpos($header, ':') === false) {
					if (false !== stripos( $header, 'boundary=' )) {
						$parts = preg_split('/boundary=/i', trim( $header ) );
						$boundary = trim( str_replace(array( "'", '"' ), '', $parts[1]));
					}
					continue;
				}
				list($name, $content) = explode(':', trim( $header ), 2);
				$name = trim($name);
				$content = trim($content);
				switch (strtolower($name)) {
					case 'from':
						$bracket_pos = strpos($content, '<');
						if ($bracket_pos !== false) {
							if ($bracket_pos > 0) {
								$from_name = substr($content, 0, $bracket_pos - 1);
								$from_name = str_replace('"', '', $from_name);
								$from_name = trim($from_name);
							}
							$from_email = substr($content, $bracket_pos + 1);
							$from_email = str_replace('>', '', $from_email);
							$from_email = trim($from_email);
						} else if ('' !== trim($content)) {
							$from_email = trim($content);
						}
						break;
					case 'content-type':
						if (strpos( $content, ';' ) !== false) {
							list($type, $charset_content) = explode(';', $content);
							$content_type = trim( $type );
							if (false !== stripos( $charset_content, 'charset=')) {
								$charset = trim(str_replace(array('charset=', '"'), '', $charset_content));
							} else if (false !== stripos( $charset_content, 'boundary=')) {
								$boundary = trim(str_replace(array('BOUNDARY=', 'boundary=', '"'), '', $charset_content));
								$charset = '';
							}
						} elseif ('' !== trim($content)) {
							$content_type = trim($content);
						}
						break;
					case 'cc':
						$cc = array_merge((array)$cc, explode(',', $content));
						break;
					case 'bcc':
						$bcc = array_merge((array)$bcc, explode(',', $content));
						break;
					default:
						$headers[trim($name)] = trim( $content );
						break;
				}
			}
		}
	}

	$phpmailer->ClearAllRecipients();
	$phpmailer->ClearAttachments();
	$phpmailer->ClearCustomHeaders();
	$phpmailer->ClearReplyTos();

	if (!isset($from_name)) {
		if ($options['mail-method'] == 'mail') $from_name = $options['mail-from-name'];
		else if ($options['mail-method'] == 'smtp') $from_name = $options['smtp-from-name'];
		else $from_name = 'Admin Panel';
	}

	if (!isset($from_email)) {
		if ($options['mail-method'] == 'mail') $from_email = $options['mail-from-email'];
		else if ($options['mail-method'] == 'smtp') $from_email = (empty($options['smtp-from-email']) ? $options['smtp-username'] : $options['smtp-from-email']);
		else $from_email = 'noreply@'.str_replace('www.', "", $_SERVER["SERVER_NAME"]);
	}
	
	$phpmailer->From = $from_email;
	$phpmailer->FromName = $from_name;
	
	if (!is_array($_to)) $to = explode(',', $_to);
	else $to = $_to;

	foreach ((array)$to as $recipient) {
		try {
			$recipient_name = '';
			if (preg_match( '/(.*)<(.+)>/', $recipient, $matches)) {
				if (count($matches) == 3) {
					$recipient_name = $matches[1];
					$recipient = $matches[2];
				}
			}
			$phpmailer->AddAddress($recipient, $recipient_name);
		} catch (phpmailerException $e) {
			continue;
		}
	}
	
	$phpmailer->Subject = $_subject;
	$phpmailer->Body    = email_body($_message);

	if (!empty($cc)) {
		foreach ((array)$cc as $recipient) {
			try {
				$recipient_name = '';
				if (preg_match( '/(.*)<(.+)>/', $recipient, $matches)) {
					if (count( $matches ) == 3) {
						$recipient_name = $matches[1];
						$recipient = $matches[2];
					}
				}
				$phpmailer->AddCc($recipient, $recipient_name);
			} catch (phpmailerException $e) {
				continue;
			}
		}
	}

	if (!empty($bcc)) {
		foreach ((array)$bcc as $recipient) {
			try {
				$recipient_name = '';
				if (preg_match( '/(.*)<(.+)>/', $recipient, $matches)) {
					if (count( $matches ) == 3) {
						$recipient_name = $matches[1];
						$recipient = $matches[2];
					}
				}
				$phpmailer->AddBcc($recipient, $recipient_name);
			} catch (phpmailerException $e) {
				continue;
			}
		}
	}

	if ($options['mail-method'] == 'smtp') {
		$phpmailer->IsSMTP();
		$phpmailer->IsHTML(true);
		$phpmailer->Timeout = 60;
		if ($_debug) {
			$phpmailer->SMTPDebug = 2;
			$phpmailer->Debugoutput = 'html';
		} else $phpmailer->SMTPDebug = 0;
		$phpmailer->Host       = $options['smtp-server'];
		$phpmailer->Port       = $options['smtp-port'];
		if ($options['smtp-secure'] != 'none') {
			$phpmailer->SMTPSecure = $options['smtp-secure'];
		}
		$phpmailer->SMTPAuth   = true;
		$phpmailer->Username   = $options['smtp-username'];
		$phpmailer->Password   = $options['smtp-password'];
	} else {
		$phpmailer->IsMail();
	}

	if (!isset($content_type)) $content_type = 'text/html';
	$phpmailer->ContentType = $content_type;
	if ('text/html' == $content_type) $phpmailer->IsHTML(true);
	if (!isset($charset)) $charset = 'utf-8';
	$phpmailer->CharSet = $charset;

	if (!empty($headers)) {
		foreach ((array)$headers as $name => $content) {
			$phpmailer->AddCustomHeader(sprintf('%1$s: %2$s', $name, $content));
		}
		if (false !== stripos($content_type, 'multipart') && ! empty($boundary))
			$phpmailer->AddCustomHeader(sprintf("Content-Type: %s;\n\t boundary=\"%s\"", $content_type, $boundary));
	}
	if (!empty($_attachments)) {
		foreach ($_attachments as $attachment) {
			try {
				$phpmailer->AddAttachment($attachment);
			} catch (phpmailerException $e) {
				continue;
			}
		}
	}
	try {
		if ($_debug && $options['mail-method'] == 'smtp') {
			ob_start();
		}
		$result = $phpmailer->send();
		if ($_debug && $options['mail-method'] == 'smtp') {
			$errors_html = ob_get_clean();
			if ($result !== true) return $errors_html;
		}
		return $result;
	} catch (Exception $e) {
		if ($_debug && $options['mail-method'] == 'smtp') {
			$errors_html = ob_get_clean();
			if (!empty($errors_html)) return $errors_html;
		}
		return false;
	}
}
function email_body($_message) {
	global $wpdb, $options, $language;
	$upload_dir = upload_dir();
	$image = null;
	if ($options['pattern'] > 0) {
		$image = $wpdb->get_row("SELECT t1.*, t2.uuid AS user_uid FROM ".$wpdb->prefix."uploads t1 
				JOIN ".$wpdb->prefix."users t2 ON t2.id = t1.user_id
			WHERE t1.id = '".esc_sql($options['pattern'])."' AND t1.deleted != '1'", ARRAY_A);
	}

	$title = translatable_parse($options['title']);
	$tagline = translatable_parse($options['tagline']);

	$content = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "https://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns=3D"https://www.w3.org/1999/xhtml">
<head>
	<title>'.esc_html($options['title']).'</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta content="width=device-width, minimal-ui, initial-scale=1.0, maximum-scale=1.0, user-scalable=0;" name="viewport" />
	<meta name="color-scheme" content="light dark" />
	<meta name="supported-color-schemes" content="light dark" />
	<meta content="telephone=no" name="format-detection" />
	<style>
	body {
        font-family: Helvetica, Arial, sans-serif;
        margin: 0;
        padding: 0;
        -webkit-font-smoothing: antialiased !important;
        -webkit-text-size-adjust: none !important;
        width: 100% !important;
        height: 100% !important;
    }
	</style>
</head>
<body style="margin-bottom: 0; -webkit-text-size-adjust: 100%; padding-bottom: 0; margin-top: 0; margin-right: 0; -ms-text-size-adjust: 100%; margin-left: 0; padding-top: 0; padding-right: 0; padding-left: 0; width: 100%;">
	<table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-spacing: 0; border-collapse: collapse; margin: 0 auto;">
		<tr>
			<td style="background-color: #f8f8f8; padding: 20px" bgcolor="#f8f8f8">
				<table cellpadding="0" cellspacing="0" align="center" border="0" width="600" style="border-collapse: collapse; margin:0 auto; min-width:600px;"> 
					<tr> 
						<td valign="top" style="text-align: center; vertical-align: top; border-collapse: collapse; background-color: #888; border: 1px solid transparent; padding: 30px; background-image: url('.(!empty($image) ? esc_html($upload_dir['baseurl'].'/'.$image['user_uid'].'/'.$image['filename']) : url('').'images/default-pattern.png').');" bgcolor="#888">
							<h1 style="font-weight: 400; font-size: 24px; line-height: 32px; color: #fff; text-align: center; text-transform: uppercase; text-shadow: 1px 1px 2px rgba(0,0,0,0.7); margin: 0;">'.(array_key_exists($language, $title) && !empty($title[$language]) ? esc_html($title[$language]) : esc_html($title['default'])).'</h1>
							'.(!empty($tagline['default']) ? '<h2 style="font-weight: 400; font-size: 16px; line-height: 24px; color: #fff; text-align: center; text-transform: uppercase; text-shadow: 1px 1px 2px rgba(0,0,0,0.7); margin: 0;">'.(array_key_exists($language, $tagline) && !empty($tagline[$language]) ? esc_html($tagline[$language]) : esc_html($tagline['default'])).'</h2>' : '').'
						</td>
					</tr>
					<tr> 
						<td valign="top" style="vertical-align: top; border-collapse: collapse; background-color: #ffffff; border: 1px solid #f0f0f0; padding: 20px;" bgcolor="#ffffff">
							<div style="font-family: Helvetica, Arial, sans-serif; font-size: 16px; letter-spacing: none; line-height: 1.475; text-align: left; color: #222;">
								'.$_message.'
							</div>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>';
	return $content;
}
function translatable_parse($_content) {
    global $languages;
    $output = array('default' => '');
    $json_decoded = json_decode($_content, true);
    if (empty($json_decoded) || !is_array($json_decoded)) {
        $output['default'] = $_content;
    } else {
		$output['default'] = $json_decoded['default'];
        foreach ($languages as $key => $value) {
            if (array_key_exists($key, $json_decoded) && !empty($json_decoded[$key])) $output[$key] = $json_decoded[$key];
        }
    }
    return $output;
}
function translatable_populate($_key) {
    global $languages;
	$field = $_REQUEST[$_key];
    $output = array('default' => '');
	if (array_key_exists('default', $field)) $output['default'] = $field['default'];
	foreach ($languages as $key => $value) {
		if (array_key_exists($key, $field) && !empty($field[$key])) $output[$key] = trim(stripslashes($field[$key]));
	}
	return $output;
}
function session_message() {
	$result = '';
	if (array_key_exists('error-message', $_SESSION)) {
		$result = "global_message_show('danger', '".$_SESSION['error-message']."')";
		unset($_SESSION['error-message']);
	} else if (array_key_exists('success-message', $_SESSION)) {
		$result = "global_message_show('success', '".$_SESSION['success-message']."')";
		unset($_SESSION['success-message']);
	}
	return '<script>'.$result.'</script>';
}
function get_option($_key, $_default_option) {
	global $wpdb;
	$option = $_default_option;
	$options_record = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."options WHERE options_key = '".esc_sql($_key)."'", ARRAY_A);
	if (!empty($options_record)) $option = $options_record['options_value'];
	return $option;
}
function save_option($_key, $_option) {
	global $wpdb;
	$options_record = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."options WHERE options_key = '".esc_sql($_key)."'", ARRAY_A);
	if (empty($options_record)) {
		$wpdb->query("INSERT INTO ".$wpdb->prefix."options (options_key, options_value) VALUES ('".esc_sql($_key)."', '".esc_sql($_option)."')");
	} else {
		$wpdb->query("UPDATE ".$wpdb->prefix."options SET options_value = '".esc_sql($_option)."' WHERE id = '".esc_sql($options_record['id'])."'");
	}
	return;
}
function get_options($_key, $_default_options) {
	global $wpdb;
	$options = $_default_options;
	$options_record = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."options WHERE options_key = '".esc_sql($_key)."'", ARRAY_A);
	if (empty($options_record)) return $options;
	$options_db = json_decode($options_record['options_value'], true);
	if (is_array($options)) {
		foreach ($options as $key => $value) {
			if (array_key_exists($key, $options_db)) $options[$key] = $options_db[$key];
		}
	} else $options = $options_db;
	return $options;
}
function save_options($_key, $_options) {
	global $wpdb;
	$options_record = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."options WHERE options_key = '".esc_sql($_key)."'", ARRAY_A);
	if (empty($options_record)) {
		$wpdb->query("INSERT INTO ".$wpdb->prefix."options (options_key, options_value) VALUES ('".esc_sql($_key)."', '".esc_sql(json_encode($_options))."')");
	} else {
		$wpdb->query("UPDATE ".$wpdb->prefix."options SET options_value = '".esc_sql(json_encode($_options))."' WHERE id = '".esc_sql($options_record['id'])."'");
	}
	return;
}
function page_switcher ($_urlbase, $_currentpage, $_totalpages) {
	$pageswitcher = "";
	if ($_totalpages > 1) {
		$pageswitcher = '<div class="table-pages"><span>';
		if (strpos($_urlbase, "?") !== false) $_urlbase .= "&";
		else $_urlbase .= "?";
		if ($_currentpage == 1) $pageswitcher .= "<a href='#' class='table-page-active' onclick='return false'>1</a> ";
		else $pageswitcher .= " <a href='".$_urlbase."p=1'>1</a> ";

		$start = max($_currentpage-3, 2);
		$end = min(max($_currentpage+3,$start+6), $_totalpages-1);
		$start = max(min($start,$end-6), 2);
		if ($start > 2) $pageswitcher .= " <strong>...</strong> ";
		for ($i=$start; $i<=$end; $i++) {
			if ($_currentpage == $i) $pageswitcher .= " <a href='#' class='table-page-active' onclick='return false'>".$i."</a> ";
			else $pageswitcher .= " <a href='".$_urlbase."p=".$i."'>".$i."</a> ";
		}
		if ($end < $_totalpages-1) $pageswitcher .= " <strong>...</strong> ";

		if ($_currentpage == $_totalpages) $pageswitcher .= " <a href='#' class='table-page-active' onclick='return false'>".$_totalpages."</a> ";
		else $pageswitcher .= " <a href='".$_urlbase."p=".$_totalpages."'>".$_totalpages."</a> ";
		$pageswitcher .= "</span></div>";
	}
	return $pageswitcher;
}
function admin_dialog_html() {
	return '
<div class="dialog-overlay" id="dialog-overlay"></div>
<div class="dialog" id="dialog">
	<div class="dialog-inner">
		<div class="dialog-title">
			<a href="#" title="'.esc_html__('Close', 'fb').'" onclick="return dialog_close();"><i class="fas fa-times"></i></a>
			<h3><i class="fas fa-cog"></i><label></label></h3>
		</div>
		<div class="dialog-content">
			<div class="dialog-content-html">
			</div>
		</div>
		<div class="dialog-buttons">
			<a class="dialog-button dialog-button-ok" href="#" onclick="return false;"><i class="fas fa-check"></i><label></label></a>
			<a class="dialog-button dialog-button-cancel" href="#" onclick="return false;"><i class="fas fa-times"></i><label></label></a>
		</div>
		<div class="dialog-loading"><i class="fas fa-spinner fa-spin"></i></div>
	</div>
</div>';
}
function image_uploader_html($_input_name, $_upload_id = 0, $_default_image_url = '', $_action = 'image-uploader-action', $_options = array()) {
	global $wpdb;
	$upload = null;
	if ($_upload_id > 0) {
		$upload = $wpdb->get_row("SELECT t1.*, t2.uuid AS user_uid FROM ".$wpdb->prefix."uploads t1 
				JOIN ".$wpdb->prefix."users t2 ON t2.id = t1.user_id
			WHERE t1.id = '".esc_sql($_upload_id)."' AND t1.deleted != '1'", ARRAY_A);
	}
	$upload_dir = upload_dir();
	$current_image_url = !empty($upload) ? $upload_dir['baseurl'].'/'.$upload['user_uid'].'/'.$upload['filename'] : '';

	$uid = uuid_v4();
	return '
<div class="image-uploader">
	<div class="image-uploader-preview"'.(empty($current_image_url) && empty($_default_image_url) ? ' style="display: none;"' : '').'>
		<span'.(empty($current_image_url) ? ' style="display: none;"' : '').' onclick="image_uploader_delete(this);"><i class="far fa-trash-alt"></i></span>
		<img src="'.(!empty($current_image_url) ? esc_html($current_image_url) : esc_html($_default_image_url)).'" data-default="'.esc_html($_default_image_url).'" alt="" />
	</div>
	<a class="button image-uploader-button" data-label="'.esc_html__('Upload Image', 'fb').'" data-loading="'.esc_html__('Uploading...', 'fb').'" onclick="jQuery(this).next().find(\'input[type=file]\').click(); return false;"><i class="fas fa-upload"></i><label>'.esc_html__('Upload Image', 'fb').'<label></a>
	<form class="image-uploader-form" action="'.url('ajax.php').'" method="POST" enctype="multipart/form-data" target="image-uploader-iframe-'.esc_html($uid).'" onsubmit="return image_uploader_start(this);" style="display: none !important; width: 0 !important; height: 0 !important;">
		<input type="hidden" name="action" value="'.esc_html($_action).'" />
		<input type="file" name="file" accept="image/*" onchange="jQuery(this).parent().submit();" style="display: none !important; width: 0 !important; height: 0 !important;" />
		<input type="submit" value="Upload" style="display: none !important; width: 0 !important; height: 0 !important;" />
	</form>											
	<iframe data-loading="false" id="image-uploader-iframe-'.esc_html($uid).'" name="image-uploader-iframe-'.esc_html($uid).'" src="about:blank" data-name="'.esc_html($_input_name).'" onload="image_uploader_finish(this);" style="display: none !important; width: 0 !important; height: 0 !important;"></iframe>
</div>
<input type="hidden" name="'.esc_html($_input_name).'" value="'.(!empty($upload) ? esc_html($upload['uuid']) : '').'" />';
}
function content_404() {
	return '
<div class="container-404">
	<div class="box-404">
		<i class="far fa-frown"></i>
		<h1>404</h1>
		<h2>'.esc_html__('page not found', 'fb').'</h2>
	</div>
</div>';
}
function fatal_error_html($_message) {
	return '<!DOCTYPE html>
<html lang="en">
<head> 
	<meta name="robots" content="noindex,nofollow">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" /> 
	<meta http-equiv="content-style-type" content="text/css" /> 
	<title>'.esc_html($_message).'</title>
	<style>
		body {
			color: #222;
			font-size: 15px;
			margin: 0;
			padding: 0;
			line-height: 1.475;
			background: #f8f8f8;
			font-family: \'Open Sans\', arial;
		}
		.wrapper {
			margin: 10% auto;
			max-width: 640px;
			text-align: center;
			border: 1px solid #ccc;
			border-radius: 5px;
			padding: 2em;
			background: #fff;
			box-shadow: 1px 1px 5px -3px rgb(0 0 0);
		}
	</style>
</head>
<body>
	<div class="wrapper">
		'.$_message.'
	</div>
</body>
</html>';
}
function global_warning_html($_message) {
	return '
<div class="header-menu-top global-danger">
	<div class="header-menu-top-container">
		<div class="global-danger-box">
			<div class="global-danger-icon"><i class="fas fa-exclamation-circle"></i></div>
			<div class="global-danger-message">
				'.$_message.'
			</div>
		</div>
	</div>
</div>';
}
function translatable_input_html($_name, $_value, $_placeholder = '') {
    global $languages;
    $value = translatable_parse($_value);
    $output = '
<div class="input-box">
    <div class="input-element">
        <input class="errorable" type="text" name="'.esc_html($_name).'[default]" placeholder="'.esc_html($_placeholder).'" value="'.esc_html($value['default']).'">
    </div>
    '.(sizeof($languages) > 1 ? '<label>'.esc_html__('Default text. It is used if translation not defined.', 'fb').'</label>' : '').'
</div>';
    if (sizeof($languages) > 1) {
        $output .= '
'.(sizeof($value) > 1 ? '' : '<a href="#" class="button2 button-small" onclick="jQuery(this).hide(); jQuery(this).next().show(); return false;"><i class="fas fa-plus"></i>'.esc_html__('Add translations', 'fb').'</a>').'
<div id="'.esc_html($_name).'-translations"'.(sizeof($value) > 1 ? '' : ' style="display: none;"').'>';
        foreach ($languages as $key => $label) {
            $output .= '
    <div class="input-box">
        <div class="input-element">
            <input class="errorable" type="text" name="'.esc_html($_name).'['.esc_html($key).']" placeholder="'.esc_html($label).'" value="'.(array_key_exists($key, $value) ? esc_html($value[$key]) : '').'">
        </div>
        <label>'.esc_html($label).'</label>
    </div>';
        }
        $output .= '
</div>';
    }
    return $output;
}
function translatable_textarea_html($_name, $_value, $_placeholder = '') {
    global $languages;
    $value = translatable_parse($_value);
    $output = '
<div class="input-box">
    <div class="input-element">
		<textarea class="errorable" name="'.esc_html($_name).'[default]" placeholder="'.esc_html($_placeholder).'">'.esc_html($value['default']).'</textarea>
    </div>
    '.(sizeof($languages) > 1 ? '<label>'.esc_html__('Default text. It is used if translation not defined.', 'fb').'</label>' : '').'
</div>';
    if (sizeof($languages) > 1) {
        $output .= '
'.(sizeof($value) > 1 ? '' : '<a href="#" class="button2 button-small" onclick="jQuery(this).hide(); jQuery(this).next().show(); return false;"><i class="fas fa-plus"></i>'.esc_html__('Add translations', 'fb').'</a>').'
<div id="'.esc_html($_name).'-translations"'.(sizeof($value) > 1 ? '' : ' style="display: none;"').'>';
        foreach ($languages as $key => $label) {
            $output .= '
    <div class="input-box">
        <div class="input-element">
			<textarea class="errorable" name="'.esc_html($_name).'['.esc_html($key).']" placeholder="'.esc_html($label).'">'.(array_key_exists($key, $value) ? esc_html($value[$key]) : '').'</textarea>
        </div>
        <label>'.esc_html($label).'</label>
    </div>';
        }
        $output .= '
</div>';
    }
    return $output;
}
function add_filter($_tag, $_function_to_add, $_priority = 10, $_accepted_args = 1) {
	global $site_data;
	$site_data['filters'][$_tag][$_priority][] = array('function' => $_function_to_add, 'accepted_args' => $_accepted_args);
	return true;
}
function apply_filters($_tag, $_value) {
	global $site_data;
	if (!array_key_exists($_tag, $site_data['filters'])) return $_value;
	
	$args = array();
	$args = func_get_args();
	ksort($site_data['filters'][$_tag]);
	reset($site_data['filters'][$_tag]);
	do {
		foreach ((array)current($site_data['filters'][$_tag]) as $the_)
			if (!is_null($the_['function']) ){
				$args[1] = $_value;
				$_value = call_user_func_array($the_['function'], array_slice($args, 1, (int)$the_['accepted_args']));
			}

	} while (next($site_data['filters'][$_tag]) !== false );
	return $_value;
}
function add_action($_tag, $_function_to_add, $_priority = 10, $_accepted_args = 1) {
	return add_filter($_tag, $_function_to_add, $_priority, $_accepted_args);
}
function do_action($_tag, $_arg = '') {
	global $site_data;

	if (!array_key_exists($_tag, $site_data['filters'])) return;
	$args = array();
	if (is_array($_arg) && 1 == count($_arg) && isset($_arg[0]) && is_object($_arg[0])) $args[] =& $_arg[0];
	else $args[] = $_arg;
	for ($a=2, $num=func_num_args(); $a<$num; $a++) {
		$args[] = func_get_arg($a);
	}
	ksort($site_data['filters'][$_tag]);
	
	reset($site_data['filters'][$_tag]);
	do {
		foreach ((array)current($site_data['filters'][$_tag]) as $the_ )
			if (!is_null($the_['function']))
				call_user_func_array($the_['function'], array_slice($args, 0, (int)$the_['accepted_args']));

	} while (next($site_data['filters'][$_tag]) !== false);
}
function add_menu_page($_menu_title, $_menu_slug, $_query_params = array(), $_role = '', $_function = '', $_options = array(), $_icon = 'fab fa-cog') {
	global $site_data, $user_details;
//    if ($_role == 'admin' && (empty($user_details) || $user_details['role'] ) != 'admin') return;
//    if ($_role == 'user' && empty($user_details)) return;
	$site_data['menu'][$_menu_slug] = array(
		'menu-title' => $_menu_title,
		'role' => $_role,
		'query' => $_query_params,
		'function' => $_function,
		'options' => $_options,
		'icon' => $_icon
	);
}
function add_submenu_page($_parent_slug, $_menu_title, $_menu_slug, $_query_params = array(), $_role = '', $_function = '', $_options = array()) {
	global $site_data, $user_details;
//    if ($_role == 'admin' && (empty($user_details) || $user_details['role'] ) != 'admin') return;
//    if ($_role == 'user' && empty($user_details)) return;
	if (array_key_exists($_parent_slug, $site_data['menu'])) {
		$site_data['menu'][$_parent_slug]['submenu'][$_menu_slug] = array(
			'menu-title' => $_menu_title,
			'role' => $_role,
			'query' => $_query_params,
			'function' => $_function,
			'options' => $_options,
		);
	}
}
function is_admin() {
	if (defined('DOING_FRONT')) return false;
	return true;
}
function plugins_url($_path = '', $_plugin = '') {
	global $options;
	$i = 0;
	if (empty($_plugin)) return $options['url'].'content/plugins';
	$directory = dirname($_plugin);
	$i++;
	while (!file_exists($directory.'/plugin.txt') && $i < 4) {
		$directory = dirname($directory);
		$i++;
	}
	if ($i == 4) return $options['url'].'content/plugins';
	return $options['url'].'content/plugins/'.basename($directory).$_path;
}
function enqueue_script($_slug, $_url = null, $_deps = array(), $_ver = VERSION) {
	global $site_data;
	switch (strtolower($_slug)) {
		case 'jquery':
			break;
			
		default:
			if (!empty($_url)) {
				if (strpos($_url, '?') === false) $_url .= '?ver='.$_ver;
				else  $_url .= '&ver='.$_ver;
				$site_data['scripts'][$_slug] = array(
					'url' => $_url,
					'deps' => $_deps
				);
			}
			break;
	}
}
function enqueue_style($_slug, $_url = null, $_deps = array(), $_ver = VERSION) {
	global $site_data;
	switch (strtolower($_slug)) {
		case 'jquery':
			break;
			
		default:
			if (!empty($_url)) {
				if (strpos($_url, '?') === false) $_url .= '?ver='.$_ver;
				else  $_url .= '&ver='.$_ver;
				$site_data['styles'][$_slug] = array(
					'url' => $_url,
					'deps' => $_deps
				);
			}
			break;
	}
}
function load_translation($_domain, $_language, $_dir) {
	global $translations;
	if (file_exists($_dir.$_language.'.po')) {
		$catalog = Sepia\PoParser\Parser::parseFile($_dir.$_language.'.po');
		if ($catalog) $translations[$_domain] = $catalog;
	}
}
function upload_dir($_key = null) {
	global $options;
	$dir = array(
		'basedir' => dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'data',
		'baseurl' => $options['url'].'content/data'
	);
	if (empty($_key) || !array_key_exists($_key, $dir)) return $dir;
	return $dir[$_key];
}
function get_filename($_path, $_filename) {
	$filename = preg_replace('/[^a-zA-Z0-9\s\-\.\_\(\)]/', ' ', $_filename);
	$filename = preg_replace('/(\s\s)+/', ' ', $filename);
	$filename = trim($filename);
	$filename = preg_replace('/\s+/', '-', $filename);
	$filename = preg_replace('/\-+/', '-', $filename);
	if (strlen($filename) == 0) $filename = "file";
	else if ($filename[0] == ".") $filename = "file".$filename;
	while (file_exists($_path.$filename)) {
		$pos = strrpos($filename, ".");
		if ($pos !== false) {
			$ext = substr($filename, $pos);
			$filename = substr($filename, 0, $pos);
		} else {
			$ext = "";
		}
		$pos = strrpos($filename, "-");
		if ($pos !== false) {
			$suffix = substr($filename, $pos+1);
			if (ctype_digit($suffix)) {
				$suffix++;
				$filename = substr($filename, 0, $pos)."-".$suffix.$ext;
			} else {
				$filename = $filename."-1".$ext;
			}
		} else {
			$filename = $filename."-1".$ext;
		}
	}
	return $filename;
}
function mkdir_p($_target) {
	if (file_exists($_target)) return is_dir($_target);
	return mkdir($_target, 0777, true);
}
function validate_date($_date, $_format = 'Y-m-d') {
	$replacements = array(
		'yyyy-mm-dd' => 'Y-m-d',
		'dd/mm/yyyy' => 'd/m/Y',
		'mm/dd/yyyy' => 'm/d/Y',
		'dd.mm.yyyy' => 'd.m.Y'
	);
	$_format = strtr($_format, $replacements);
	$date = DateTime::createFromFormat($_format, $_date);
	if ($date && $date->format($_format) === $_date) return $date;
	return false;
}
function timestamp_string($_time, $_format = "Y-m-d H:i") {
	global $gmt_offset;
	$_format = str_replace(array('yyyy', 'mm', 'dd'), array('Y', 'm', 'd'), $_format);
	return date($_format, $_time+3600*$gmt_offset);
}
function register_activation_hook($_method) {
	global $wpdb;
	call_user_func($_method);
}
function random_string($_length = 16) {
	$symbols = '123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$string = "";
	for ($i=0; $i<$_length; $i++) {
		$string .= $symbols[rand(0, strlen($symbols)-1)];
	}
	return $string;
}
?>