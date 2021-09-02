<?php
include_once(dirname(__FILE__).'/inc/functions.php');
include_once(dirname(__FILE__).'/inc/icdb.php');
include_once(dirname(__FILE__).'/inc/common.php');

if (array_key_exists('confirm', $_GET)) {
    $confirmation_id = preg_replace('/[^a-zA-Z0-9-]/', '', $_GET['confirm']);
    $user_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."users WHERE email_confirmation_uid = '".esc_sql($confirmation_id)."' AND deleted != '1'", ARRAY_A);
    if (empty($user_details)) {
        $_SESSION['error-message'] = esc_html__('User not found.', 'fb');
        header('Location: '.$options['url']);
        exit;
    } else if ($user_details['status'] != 'active') {
        $_SESSION['error-message'] = esc_html__('Unfortunately, your account temporarily disbaled.', 'fb');
        header('Location: '.url('login.php'));
        exit;
    } else if ($user_details['email_confirmed'] == '1') {
        $_SESSION['error-message'] = esc_html__('Email address already confirmed.', 'fb');
        header('Location: '.$options['url']);
        exit;
    }
    $wpdb->query("UPDATE ".$wpdb->prefix."users SET email_confirmed = '1' WHERE id = '".esc_sql($user_details['id'])."'");
    $_SESSION['success-message'] = esc_html__('Email address successfully confirmed.', 'fb');
    header('Location: '.$options['url']);
    exit;
}

if ($user_details) {
    header('Location: '.$options['url']);
    exit;
}
if (array_key_exists('reset', $_GET)) {
    $reset_id = preg_replace('/[^a-zA-Z0-9-]/', '', $_GET['reset']);
    $user_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."users WHERE password_reset_uid = '".esc_sql($reset_id)."' AND deleted != '1'", ARRAY_A);
    if (empty($user_details)) {
        header('Location: '.$options['url']);
        exit;
    }
    if ($user_details['status'] != 'active') {
        $_SESSION['error-message'] = esc_html__('Unfortunately, your account temporarily disbaled.', 'fb');
        header('Location: '.url('login.php'));
        exit;
    }
}

do_action('admin_menu');

$upload_dir = upload_dir();
$image = null;
if ($options['pattern'] > 0) {
    $image = $wpdb->get_row("SELECT t1.*, t2.uuid AS user_uid FROM ".$wpdb->prefix."uploads t1 
            JOIN ".$wpdb->prefix."users t2 ON t2.id = t1.user_id
        WHERE t1.id = '".esc_sql($options['pattern'])."' AND t1.deleted != '1'", ARRAY_A);
}

?>
<!DOCTYPE html>
<html lang="en">
<head> 
    <meta name="robots" content="noindex,nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" /> 
    <meta http-equiv="content-style-type" content="text/css" /> 
    <title><?php echo (empty($user_details) ? esc_html__('Create Account', 'fb') : esc_html__('New password', 'fb')); ?></title> 
    <link href="//fonts.googleapis.com/css?family=Open+Sans:100,100italic,300,300italic,400,400italic,600,600italic,700,700italic,800,800italic&subset=cyrillic,cyrillic-ext,greek,greek-ext,latin,latin-ext,vietnamese" rel="stylesheet" type="text/css">
    <link href="<?php echo $options["url"]; ?>css/fontawesome.css" rel="stylesheet" type="text/css" />
    <link href="<?php echo $options["url"]; ?>css/login.css" rel="stylesheet" type="text/css" />
    <script src="<?php echo $options["url"]; ?>js/jquery.min.js"></script>
    <script src="<?php echo $options["url"]; ?>js/register.js"></script>
    <script>var ajax_url = "<?php echo url("ajax.php"); ?>";</script>
</head>
<body>
    <div class="wrapper">
        <div class="wrapper-left-column"  style="background-image: url(<?php echo (!empty($image) ? esc_html($upload_dir['baseurl'].'/'.$image['user_uid'].'/'.$image['filename']) : url('').'images/default-pattern.png'); ?>);"></div>
        <div class="wrapper-right-column">
            <nav class="top-menu-bar">
            <ul class="top-menu">
<?php
foreach($site_data['menu'] as $slug => $item) {
    if (empty($item['menu-title'])) continue;
    if (array_key_exists('query', $item) && !empty($item['query'])) $query = '&'.http_build_query($item['query']);
    else $query = '';
    $attr = '';
    if (array_key_exists('a-attr', $item['options'])) $attr = ' '.$item['options']['a-attr'];
    else $attr = '';
	echo '
    				<li class="top-menu-item'.(array_key_exists('submenu', $item) ? ' top-submenu' : '').(!empty($page) && ((array_key_exists('parent', $page) && $page['parent'] == $slug) || $page['slug'] == $slug)  ? ' top-menu-active' : '').'">
                        '.(empty($item['function']) ? '<span>'.esc_html($item['menu-title']).(array_key_exists('submenu', $item) ? '<i class="fas fa-angle-down"></i>' : '').'</span>' : '<a href="'.esc_html($options['url'].($slug == 'index' ? '' : '?page='.urlencode($slug).$query)).'"'.$attr.'>'.esc_html($item['menu-title']).(array_key_exists('submenu', $item) ? '<i class="fas fa-angle-down"></i>' : '').'</a>');
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
							<li'.($page['slug'] == $submenu_slug ? ' class="top-submenu-active"' : '').'><a href="'.esc_html($options['url'].($slug == 'index' ? '' : '?page='.urlencode($submenu_slug).$query)).'"'.$attr.'>'.esc_html($submenu_item['menu-title']).'</a></li>';
			}
			echo '
						</ul>';
		}
		echo '</li>';
	}
?>
                </ul>
                <ul class="top-menu top-menu-right">
                    <li><a href="<?php echo url("login.php").(array_key_exists('redirect', $_GET) ? '?redirect='.urlencode(urldecode($_GET['redirect'])) : ''); ?>"><?php echo esc_html__('Sign In', 'fb'); ?></a></li>
                    <?php
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
?>
                </ul>
            </nav>
<?php
if (empty($user_details)) {
?>
            <div class="form-wrapper" id="login-form">
                <div class="form">
                    <div class="form-row">
                        <h1><?php echo esc_html__('Create Account', 'fb'); ?></h1>
                    </div>
                    <div class="form-row">
                        <div class="input-box">
                            <select name="timezone" onfocus="jQuery(this).parent().find('.element-error').fadeOut(300, function(){jQuery(this).remove();});">
                                <?php echo timezone_choice(); ?>
                            </select>
                            <label><?php echo esc_html__('Select timezone', 'fb'); ?></label>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="input-box">
                            <input type="text" name="name" placeholder="<?php echo esc_html__('Full name', 'fb'); ?>" value="" onfocus="jQuery(this).parent().find('.element-error').fadeOut(300, function(){jQuery(this).remove();});">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="input-box">
                            <hr>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="input-box">
                            <input type="email" name="email" placeholder="<?php echo esc_html__('Email address', 'fb'); ?>" value="" onfocus="jQuery(this).parent().find('.element-error').fadeOut(300, function(){jQuery(this).remove();});">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="input-box">
                            <input type="password" name="password" placeholder="<?php echo esc_html__('Password', 'fb'); ?>" value="" onfocus="jQuery(this).parent().find('.element-error').fadeOut(300, function(){jQuery(this).remove();});">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="input-box">
                            <input type="password" name="repeat-password" placeholder="<?php echo esc_html__('Repeat password', 'fb'); ?>" value="" onfocus="jQuery(this).parent().find('.element-error').fadeOut(300, function(){jQuery(this).remove();});">
                        </div>
                    </div>
                    <div class="form-row">
                        <input type="hidden" name="redirect" value="<?php echo array_key_exists('redirect', $_GET) ? esc_html(urldecode($_GET['redirect'])) : ''; ?>">
                        <a class="button" href="#" onclick="return register(this);" data-label="<?php echo esc_html__('Create Account', 'fb'); ?>">
                            <span><?php echo esc_html__('Create Account', 'fb'); ?></span>
                            <i class="fas fa-angle-right"></i>
                        </a>
                    </div>
<?php
if ($options['google-enable'] == 'on') {
?>
                    <div class="form-row">
                        <?php echo esc_html__('or create account with', 'fb'); ?></a>
                    </div>
                    <div class="form-row">
<?php
    if ($options['google-enable'] == 'on') {
?>
                        <a class="social-button social-button-google" href="https://accounts.google.com/o/oauth2/auth?client_id=<?php echo urlencode($options['google-client-id']); ?>&scope=profile%20email&response_type=code&redirect_uri=<?php echo urlencode(url('auth.php').'?google=auth'); ?>" title="<?php echo esc_html__('Google', 'fb'); ?>">
                            <i class="fab fa-google"></i>
                        </a>
<?php
    }
    if ($options['facebook-enable'] == 'on') {
?>
                        <a class="social-button social-button-facebook" href="https://www.facebook.com/dialog/oauth?client_id=<?php echo urlencode($options['facebook-client-id']); ?>&scope=public_profile,email&redirect_uri=<?php echo urlencode(url('auth.php').'?facebook=auth'); ?>" title="<?php echo esc_html__('Facebook', 'fb'); ?>">
                            <i class="fab fa-facebook-f"></i>
                        </a>
<?php
    }
    if ($options['vk-enable'] == 'on') {
?>
                        <a class="social-button social-button-vk" href="https://oauth.vk.com/authorize?client_id=<?php echo urlencode($options['vk-client-id']); ?>&display=page&redirect_uri=<?php echo urlencode(url('auth-vk.php')); ?>&scope=email&response_type=code&v=6.00" title="<?php echo esc_html__('VK', 'fb'); ?>">
                            <i class="fab fa-vk"></i>
                        </a>
<?php
    }
?>
                    </div>
<?php
}
?>
                </div>
            </div>
<?php
} else {
?>
            <div class="form-wrapper" id="login-form">
                <div class="form">
                    <div class="form-row">
                        <h1><?php echo esc_html__('Set new password', 'fb'); ?></h1>
                    </div>
                    <div class="form-row">
                        <div class="input-box">
                            <input type="password" name="password" placeholder="<?php echo esc_html__('Password', 'fb'); ?>" value="" onfocus="jQuery(this).parent().find('.element-error').fadeOut(300, function(){jQuery(this).remove();});">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="input-box">
                            <input type="password" name="repeat-password" placeholder="<?php echo esc_html__('Repeat password', 'fb'); ?>" value="" onfocus="jQuery(this).parent().find('.element-error').fadeOut(300, function(){jQuery(this).remove();});">
                        </div>
                    </div>
                    <div class="form-row">
                        <input type="hidden" name="reset-id" value="<?php echo $user_details['password_reset_uid']; ?>">
                        <input type="hidden" name="redirect" value="<?php echo array_key_exists('redirect', $_GET) ? esc_html(urldecode($_GET['redirect'])) : ''; ?>">
                        <a class="button" href="#" onclick="return set_password(this);" data-label="<?php echo esc_html__('Set password', 'fb'); ?>">
                            <span><?php echo esc_html__('Set password', 'fb'); ?></span>
                            <i class="fas fa-angle-right"></i>
                        </a>
                    </div>
                </div>
            </div>
<?php
}
?>            
        </div>
    </div>
    <div id="global-message"></div>
    <?php echo session_message(); ?>
</body>
</html>