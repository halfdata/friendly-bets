<?php
include_once(dirname(__FILE__).'/inc/functions.php');
include_once(dirname(__FILE__).'/inc/icdb.php');
include_once(dirname(__FILE__).'/inc/common.php');

if (empty($user_details)) {
    header("Location: ".url('login.php').'?redirect='.urlencode(url('settings.php')));
    exit;
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

do_action('admin_menu');

$template_options = array(
    'title' => esc_html__('Settings', 'fb')
);
include_once(dirname(__FILE__).'/inc/header.php');
?>
<h1><?php echo esc_html__('Account Settings', 'fb'); ?></h1>
<div class="form" id="settings-form">
    <div class="form-row">
        <div class="form-label">
            <label><?php echo esc_html__('Email', 'fb'); ?></label>
        </div>
        <div class="form-tooltip">
        </div>
        <div class="form-content">
            <strong><?php echo esc_html($user_details['email']); ?></strong>
        </div>
    </div>
    <div class="form-row">
        <div class="form-label">
            <label><?php echo esc_html__('Full name', 'fb'); ?></label>
        </div>
        <div class="form-tooltip">
            <i class="fas fa-question-circle form-tooltip-anchor"></i>
            <div class="form-tooltip-content">
                <?php echo esc_html__('Specify your full name.', 'fb'); ?>
            </div>
        </div>
        <div class="form-content">
            <div class="input-box">
                <input class="errorable" type="text" name="name" placeholder="<?php echo esc_html__('Full name', 'fb'); ?>" value="<?php echo esc_html($user_details['name']); ?>">
            </div>
        </div>
    </div>
    <div class="form-row">
        <div class="form-label">
            <label><?php echo esc_html__('Timezone', 'fb'); ?></label>
        </div>
        <div class="form-tooltip">
            <i class="fas fa-question-circle form-tooltip-anchor"></i>
            <div class="form-tooltip-content">
                <?php echo esc_html__('Select your timezone', 'fb'); ?>
            </div>
        </div>
        <div class="form-content">
            <div class="input-box">
                <select class="errorable" name="timezone">
                    <?php echo timezone_choice($user_details['timezone']); ?>
                </select>
            </div>
        </div>
    </div>
<?php
if ($options['google-enable'] == 'on') {
?>
    <h2><?php echo esc_html__('Connections', 'fb'); ?></h2>
<?php
    if ($options['google-enable'] == 'on') {
?>
    <div class="form-row">
        <div class="form-label">
            <label><?php echo esc_html__('Google', 'fb'); ?></label>
        </div>
        <div class="form-tooltip">
            <i class="fas fa-question-circle form-tooltip-anchor"></i>
            <div class="form-tooltip-content">
                <?php echo esc_html__('Sign in using your Google Account.', 'fb'); ?>
            </div>
        </div>
        <div class="form-content">
            <div class="input-box">
<?php
        $connection_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."user_connections WHERE user_id = '".esc_sql($user_details['id'])."' AND source = 'google' AND deleted != '1'", ARRAY_A);
        if (empty($connection_details)) {
?>
                <a class="social-button social-button-google" href="https://accounts.google.com/o/oauth2/auth?client_id=<?php echo urlencode($options['google-client-id']); ?>&scope=profile%20email&response_type=code&redirect_uri=<?php echo urlencode(url('auth.php').'?google=auth'); ?>">
                    <i class="fab fa-google"></i> <?php echo esc_html__('Connect to Google', 'fb'); ?>
                </a>
<?php
        } else {
?>
                <strong><?php echo esc_html($connection_details['source_id']); ?></strong>
                <a class="social-button social-button-google" href="#" onclick="return google_disconnect(this);">
                    <i class="fab fa-google"></i> <?php echo esc_html__('Disconnect', 'fb'); ?>
                </a>
<?php
        }
?>
            </div>
        </div>
    </div>
<?php
    }
    if ($options['facebook-enable'] == 'on') {
?>
    <div class="form-row">
        <div class="form-label">
            <label><?php echo esc_html__('Facebook', 'fb'); ?></label>
        </div>
        <div class="form-tooltip">
            <i class="fas fa-question-circle form-tooltip-anchor"></i>
            <div class="form-tooltip-content">
                <?php echo esc_html__('Sign in using your Facebook Account.', 'fb'); ?>
            </div>
        </div>
        <div class="form-content">
            <div class="input-box">
<?php
        $connection_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."user_connections WHERE user_id = '".esc_sql($user_details['id'])."' AND source = 'facebook' AND deleted != '1'", ARRAY_A);
        if (empty($connection_details)) {
?>
                <a class="social-button social-button-facebook" href="https://www.facebook.com/dialog/oauth?client_id=<?php echo urlencode($options['facebook-client-id']); ?>&scope=public_profile,email&redirect_uri=<?php echo urlencode(url('auth.php').'?facebook=auth'); ?>">
                    <i class="fab fa-facebook-f"></i> <?php echo esc_html__('Connect to Facebook', 'fb'); ?>
                </a>
<?php
        } else {
?>
                <strong><?php echo esc_html($connection_details['source_id']); ?></strong>
                <a class="social-button social-button-facebook" href="#" onclick="return facebook_disconnect(this);">
                    <i class="fab fa-facebook-f"></i> <?php echo esc_html__('Disconnect', 'fb'); ?>
                </a>
<?php
        }
?>
            </div>
        </div>
    </div>
<?php
    }
    if ($options['vk-enable'] == 'on') {
?>
    <div class="form-row">
        <div class="form-label">
            <label><?php echo esc_html__('VK', 'fb'); ?></label>
        </div>
        <div class="form-tooltip">
            <i class="fas fa-question-circle form-tooltip-anchor"></i>
            <div class="form-tooltip-content">
                <?php echo esc_html__('Sign in using your VK Account.', 'fb'); ?>
            </div>
        </div>
        <div class="form-content">
            <div class="input-box">
<?php
        $connection_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."user_connections WHERE user_id = '".esc_sql($user_details['id'])."' AND source = 'vk' AND deleted != '1'", ARRAY_A);
        if (empty($connection_details)) {
?>
                <a class="social-button social-button-vk" href="https://oauth.vk.com/authorize?client_id=<?php echo urlencode($options['vk-client-id']); ?>&display=page&redirect_uri=<?php echo urlencode(url('auth-vk.php')); ?>&scope=email&response_type=code&v=6.00">
                    <i class="fab fa-vk"></i> <?php echo esc_html__('Connect to VK', 'fb'); ?>
                </a>
<?php
        } else {
?>
                <strong><?php echo esc_html($connection_details['source_id']); ?></strong>
                <a class="social-button social-button-vk" href="#" onclick="return vk_disconnect(this);">
                    <i class="fab fa-vk"></i> <?php echo esc_html__('Disconnect', 'fb'); ?>
                </a>
<?php
        }
?>
            </div>
        </div>
    </div>
<?php
    }
?>
<?php
}
?>
    <h2><?php echo esc_html__('Security Settings', 'fb'); ?></h2>
    <div class="form-row">
        <div class="form-label">
            <label><?php echo esc_html__('Current password', 'fb'); ?></label>
        </div>
        <div class="form-tooltip">
            <i class="fas fa-question-circle form-tooltip-anchor"></i>
            <div class="form-tooltip-content">
                <?php echo esc_html__('Current password. Type it if you want to change the password.', 'fb'); ?>
            </div>
        </div>
        <div class="form-content">
            <div class="input-box">
                <input class="errorable" type="password" name="current-password" placeholder="<?php echo esc_html__('Current password', 'fb'); ?>" value="">
            </div>
        </div>
    </div>
    <div class="form-row">
        <div class="form-label">
            <label><?php echo esc_html__('New password', 'fb'); ?></label>
        </div>
        <div class="form-tooltip">
            <i class="fas fa-question-circle form-tooltip-anchor"></i>
            <div class="form-tooltip-content">
                <?php echo esc_html__('Set new password. Leave this field blank if you do not want to change password.', 'fb'); ?>
            </div>
        </div>
        <div class="form-content">
            <div class="columns">
                <div class="column column-50">
                    <div class="input-box">
                        <input class="errorable" type="password" name="password" placeholder="<?php echo esc_html__('New password', 'fb'); ?>" value="">
                    </div>
                </div>
                <div class="column column-50">
                    <div class="input-box">
                        <input class="errorable" type="password" name="repeat-password" placeholder="<?php echo esc_html__('Repeat password', 'fb'); ?>" value="">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="form-row right-align">
        <input type="hidden" name="action" value="save-settings">
        <a class="button" href="#" onclick="return save_form(this);" data-label="<?php echo esc_html__('Save Settings', 'fb'); ?>">
            <span><?php echo esc_html__('Save Settings', 'fb'); ?></span>
            <i class="fas fa-angle-right"></i>
        </a>
    </div>
</div>
<?php
include_once(dirname(__FILE__).'/inc/footer.php');
?>