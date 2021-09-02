<?php
include_once(dirname(__FILE__).'/inc/functions.php');
include_once(dirname(__FILE__).'/inc/icdb.php');
include_once(dirname(__FILE__).'/inc/common.php');

if (array_key_exists('id', $_GET)) $user_id = intval($_GET["id"]);
else $user_id = "";

if (empty($user_details)) {
    header("Location: ".url('login.php').'?redirect='.urlencode(url('add-user.php').(!empty($user_id) ? '?id='.$user_id : '')));
    exit;
} else if ($user_details['role'] != 'admin') {
    header("Location: ".url('404.php'));
    exit;
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$user = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."users WHERE id = '".esc_sql($user_id)."' AND deleted != '1'", ARRAY_A);

do_action('admin_menu');

$template_options = array(
    'title' => empty($user) ? esc_html__('Create User', 'fb') : esc_html__('Edit User', 'fb')
);
include_once(dirname(__FILE__).'/inc/header.php');
?>
<h1><?php echo empty($user) ? esc_html__('Create User', 'fb') : esc_html__('Edit User', 'fb'); ?></h1>
<div class="form" id="user-form">
	<div class="form-row">
        <div class="form-label">
            <label><?php echo esc_html__('User role', 'fb'); ?></label>
        </div>
        <div class="form-tooltip">
            <i class="fas fa-question-circle form-tooltip-anchor"></i>
            <div class="form-tooltip-content">
                <?php echo esc_html__('Set user role.', 'fb'); ?>
            </div>
        </div>
        <div class="form-content">
            <div class="input-box">
                <div class="bar-selector">
                    <input class="radio" id="role-admin" type="radio" name="role" value="admin"<?php echo $user_id > 0 && $user['role'] == 'admin' ? ' checked="checked"' : ''; ?>><label for="role-admin"><?php echo esc_html__('Administrator', 'fb'); ?></label><input class="radio" id="role-user" type="radio" name="role" value="user"<?php echo $user_id == 0 || $user['role'] != 'admin' ? ' checked="checked"' : ''; ?>><label for="role-user"><?php echo esc_html__('User', 'fb'); ?></label>
                </div>
            </div>
        </div>
    </div>
    <div class="form-row">
        <div class="form-label">
            <label><?php echo esc_html__('Email', 'fb'); ?></label>
        </div>
        <div class="form-tooltip">
			<i class="fas fa-question-circle form-tooltip-anchor"></i>
			<div class="form-tooltip-content">
				<?php echo esc_html__('Specify email address of the user.', 'fb'); ?>
			</div>
        </div>
        <div class="form-content">
			<input type="text" name="email" placeholder="<?php echo esc_html__('Email address', 'fb'); ?>" value="<?php echo !empty($user) ? esc_html($user['email']) : ''; ?>">
        </div>
    </div>
    <div class="form-row">
        <div class="form-label">
            <label><?php echo esc_html__('Full name', 'fb'); ?></label>
        </div>
        <div class="form-tooltip">
            <i class="fas fa-question-circle form-tooltip-anchor"></i>
            <div class="form-tooltip-content">
                <?php echo esc_html__('Specify full name of the user.', 'fb'); ?>
            </div>
        </div>
        <div class="form-content">
            <div class="input-box">
                <input class="errorable" type="text" name="name" placeholder="<?php echo esc_html__('Full name', 'fb'); ?>" value="<?php echo !empty($user) ? esc_html($user['name']) : ''; ?>">
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
                <?php echo esc_html__('Select timezone of the user.', 'fb'); ?>
            </div>
        </div>
        <div class="form-content">
            <div class="input-box">
                <select class="errorable" name="timezone">
                    <?php echo timezone_choice(!empty($user) ? $user['timezone'] : 'UTC'); ?>
                </select>
            </div>
        </div>
    </div>
    <div class="form-row">
        <div class="form-label">
            <label><?php echo esc_html__('Password', 'fb'); ?></label>
        </div>
        <div class="form-tooltip">
            <i class="fas fa-question-circle form-tooltip-anchor"></i>
            <div class="form-tooltip-content">
                <?php echo esc_html__('Set password of the user.', 'fb'); ?>
            </div>
        </div>
        <div class="form-content">
            <div class="columns">
                <div class="column column-50">
                    <div class="input-box">
                        <input class="errorable" type="password" name="password" placeholder="<?php echo esc_html__('Password', 'fb'); ?>" value="">
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
        <input type="hidden" name="action" value="save-user">
		<input type="hidden" name="id" value="<?php echo !empty($user) ? esc_html($user['id']) : '0'; ?>">
        <a class="button" href="#" onclick="return save_form(this);" data-label="<?php echo esc_html__('Save Details', 'fb'); ?>">
            <span><?php echo esc_html__('Save Details', 'fb'); ?></span>
            <i class="fas fa-angle-right"></i>
        </a>
    </div>
</div>
<?php
include_once(dirname(__FILE__).'/inc/footer.php');
?>