<?php
include_once(dirname(__FILE__).'/inc/functions.php');
include_once(dirname(__FILE__).'/inc/icdb.php');
include_once(dirname(__FILE__).'/inc/common.php');

if (empty($user_details)) {
    header("Location: ".url('login.php').'?redirect='.urlencode(url('settings.php')));
    exit;
} else if ($user_details['role'] != 'admin') {
    header("Location: ".url('404.php'));
    exit;
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

do_action('admin_menu');

$template_options = array(
    'title' => esc_html__('Site Settings', 'fb')
);
include_once(dirname(__FILE__).'/inc/header.php');
?>
<h1><?php echo esc_html__('Site Settings', 'fb'); ?></h1>
<div class="form" id="settings-form">
    <div class="tabs tabs-main">
		<a class="tab tab-active" href="#tab-general"><?php echo esc_html__('General', 'fb'); ?></a>
		<a class="tab" href="#tab-connections"><?php echo esc_html__('Connections', 'fb'); ?></a>
        <a class="tab" href="#tab-mail"><?php echo esc_html__('Mailing', 'fb'); ?></a>
	</div>
    <div id="tab-general" class="tab-content" style="display: block;">
        <div class="form-row">
            <div class="form-label">
                <label><?php echo esc_html__('Title', 'fb'); ?></label>
            </div>
            <div class="form-tooltip">
                <i class="fas fa-question-circle form-tooltip-anchor"></i>
                <div class="form-tooltip-content">
                    <?php echo esc_html__('Specify the title of the site.', 'fb'); ?>
                </div>
            </div>
            <div class="form-content">
                <?php echo translatable_input_html('title', $options['title'], esc_html__('Title', 'fb')); ?>
            </div>
        </div>
        <div class="form-row">
            <div class="form-label">
                <label><?php echo esc_html__('Tagline', 'fb'); ?></label>
            </div>
            <div class="form-tooltip">
                <i class="fas fa-question-circle form-tooltip-anchor"></i>
                <div class="form-tooltip-content">
                    <?php echo esc_html__('Specify the tagline of the site.', 'fb'); ?>
                </div>
            </div>
            <div class="form-content">
                <?php echo translatable_input_html('tagline', $options['tagline'], esc_html__('Tagline', 'fb')); ?>
            </div>
        </div>
        <div class="form-row">
            <div class="form-label">
                <label><?php echo esc_html__('Copyright', 'fb'); ?></label>
            </div>
            <div class="form-tooltip">
                <i class="fas fa-question-circle form-tooltip-anchor"></i>
                <div class="form-tooltip-content">
                    <?php echo esc_html__('Specify the copyright line. It appears in footer of each page.', 'fb'); ?>
                </div>
            </div>
            <div class="form-content">
                <?php echo translatable_input_html('copyright', $options['copyright'], esc_html__('Copyright', 'fb')); ?>
            </div>
        </div>
        <div class="form-row">
            <div class="form-label">
                <label><?php echo esc_html__('Date format', 'fb'); ?></label>
            </div>
            <div class="form-tooltip">
                <i class="fas fa-question-circle form-tooltip-anchor"></i>
                <div class="form-tooltip-content">
                    <?php echo esc_html__('Select date format.', 'fb'); ?>
                </div>
            </div>
            <div class="form-content">
                <div class="columns">
                    <div class="column column-30">
                        <div class="input-box">
                            <select class="errorable" name="date-format">
                                <option value="yyyy-mm-dd"<?php echo $options['date-format'] == 'yyyy-mm-dd' ? ' selected="selected"' : ''; ?>>YYYY-MM-DD</option>
                                <option value="mm/dd/yyyy"<?php echo $options['date-format'] == 'mm/dd/yyyy' ? ' selected="selected"' : ''; ?>>MM/DD/YYYY</option>
                                <option value="dd/mm/yyyy"<?php echo $options['date-format'] == 'dd/mm/yyyy' ? ' selected="selected"' : ''; ?>>DD/MM/YYYY</option>
                                <option value="dd.mm.yyyy"<?php echo $options['date-format'] == 'dd.mm.yyyy' ? ' selected="selected"' : ''; ?>>DD.MM.YYYY</option>
                            </select>
                        </div>
                    </div>
                    <div class="column column-30"></div>
                    <div class="column column-40"></div>
                </div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-label">
                <label><?php echo esc_html__('Language', 'fb'); ?></label>
            </div>
            <div class="form-tooltip">
                <i class="fas fa-question-circle form-tooltip-anchor"></i>
                <div class="form-tooltip-content">
                    <?php echo esc_html__('Select language.', 'fb'); ?>
                </div>
            </div>
            <div class="form-content">
                <div class="columns">
                    <div class="column column-30">
                        <div class="input-box">
                            <select class="errorable" name="language">
<?php
echo '<option value=""'.($options['language'] == '' ? ' selected="selected"' : '').'>'.esc_html__('Selected by user', 'fb').'</option>';
foreach ($languages as $key => $label) {
    echo '<option value="'.esc_html($key).'"'.($options['language'] == $key ? ' selected="selected"' : '').'>'.esc_html($label).'</option>';
}
?>
                            </select>
                        </div>
                    </div>
                    <div class="column column-30"></div>
                    <div class="column column-40"></div>
                </div>
            </div>
        </div>
        <div class="form-row">
			<div class="form-label">
				<label><?php echo esc_html__('Pattern', 'fb'); ?></label>
			</div>
			<div class="form-tooltip">
				<i class="fas fa-question-circle form-tooltip-anchor"></i>
				<div class="form-tooltip-content">
                       <?php echo esc_html__('Upload image that is used on left side of sign in / sign up pages. It is recommended to upload seamless pattern image.', 'fb'); ?>
				</div>
			</div>
			<div class="form-content">
				<?php echo image_uploader_html('pattern', $options['pattern'], url('').'images/default-pattern.png'); ?>
			</div>
		</div>
    </div>
    <div id="tab-connections" class="tab-content">
        <h2><?php echo esc_html__('Google', 'fb'); ?></h2>
        <div class="form-row">
            <div class="form-label">
                <label><?php echo esc_html__('Enable', 'fb'); ?></label>
            </div>
            <div class="form-tooltip">
                <i class="fas fa-question-circle form-tooltip-anchor"></i>
                <div class="form-tooltip-content">
                    <?php echo esc_html__('Allow users to use their Google Account to sign in.', 'fb'); ?>
                </div>
            </div>
            <div class="form-content">
                <div class="input-box">
                    <div class="checkbox-toggle-container">
						<input class="checkbox-toggle" type="checkbox" value="on" id="google-enable" name="google-enable"<?php echo ($options['google-enable'] == 'on' ? ' checked="checked"' : ''); ?> onchange="if(jQuery(this).is(':checked')){jQuery('#google-parameters').fadeIn(100);}else{jQuery('#google-parameters').fadeOut(100);}">
                        <label for="google-enable"></label>
					</div>
                </div>
            </div>
        </div>
        <div id="google-parameters"<?php echo ($options['google-enable'] != 'on' ? ' style="display:none;"' : ''); ?>>
            <div class="form-row">
                <div class="form-label">
                </div>
                <div class="form-tooltip">
                </div>
                <div class="form-content">
                    <div class="inline-message inline-message-noclose inline-message-success">
                        <?php echo sprintf(esc_html__('Create new OAuth 2.0 credentials in %sGoogle Cloud Platform%s and copy-paste them into fields below. Use the following parameters to create OAuth 2.0 credentials.', 'fb'), '<a href="https://console.cloud.google.com/apis/credentials" target="_blank">', '</a>'); ?>
                        <div class="prep-parameter-container">
                            <label><?php echo esc_html__('Application type', 'fb'); ?>:</label>
                            <pre>Web application</pre>
                        </div>
                        <div class="prep-parameter-container">
                            <label><?php echo esc_html__('Authorized redirect URI', 'fb'); ?>:</label>
                            <pre onclick="this.focus();this.select();"><?php echo url('auth.php').'?google=auth' ?></pre>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-label">
                    <label><?php echo esc_html__('Client ID', 'fb'); ?></label>
                </div>
                <div class="form-tooltip">
                    <i class="fas fa-question-circle form-tooltip-anchor"></i>
                    <div class="form-tooltip-content">
                        <?php echo esc_html__('Enter your OAuth 2.0 Client ID.', 'fb'); ?>
                    </div>
                </div>
                <div class="form-content">
                    <div class="input-box">
                        <input class="errorable" type="text" name="google-client-id" placeholder="<?php echo esc_html__('Client ID', 'fb'); ?>" value="<?php echo esc_html($options['google-client-id']); ?>">
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-label">
                    <label><?php echo esc_html__('Client Secret', 'fb'); ?></label>
                </div>
                <div class="form-tooltip">
                    <i class="fas fa-question-circle form-tooltip-anchor"></i>
                    <div class="form-tooltip-content">
                        <?php echo esc_html__('Enter your OAuth 2.0 Client Secret.', 'fb'); ?>
                    </div>
                </div>
                <div class="form-content">
                    <div class="input-box">
                        <input class="errorable" type="text" name="google-client-secret" placeholder="<?php echo esc_html__('Client Secret', 'fb'); ?>" value="<?php echo esc_html($options['google-client-secret']); ?>">
                    </div>
                </div>
            </div>
        </div>
        <h2><?php echo esc_html__('Facebook', 'fb'); ?></h2>
        <div class="form-row">
            <div class="form-label">
                <label><?php echo esc_html__('Enable', 'fb'); ?></label>
            </div>
            <div class="form-tooltip">
                <i class="fas fa-question-circle form-tooltip-anchor"></i>
                <div class="form-tooltip-content">
                    <?php echo esc_html__('Allow users to use their Facebook Account to sign in.', 'fb'); ?>
                </div>
            </div>
            <div class="form-content">
                <div class="input-box">
                    <div class="checkbox-toggle-container">
						<input class="checkbox-toggle" type="checkbox" value="on" id="facebook-enable" name="facebook-enable"<?php echo ($options['facebook-enable'] == 'on' ? ' checked="checked"' : ''); ?> onchange="if(jQuery(this).is(':checked')){jQuery('#facebook-parameters').fadeIn(100);}else{jQuery('#facebook-parameters').fadeOut(100);}">
                        <label for="facebook-enable"></label>
					</div>
                </div>
            </div>
        </div>
        <div id="facebook-parameters"<?php echo ($options['facebook-enable'] != 'on' ? ' style="display:none;"' : ''); ?>>
            <div class="form-row">
                <div class="form-label">
                </div>
                <div class="form-tooltip">
                </div>
                <div class="form-content">
                    <div class="inline-message inline-message-noclose inline-message-success">
                        <?php echo sprintf(esc_html__('Create new application in %sFacebook for Developers%s and copy-paste its credentials into fields below. Use the following parameters to create an application.', 'fb'), '<a href="https://developers.facebook.com/apps/" target="_blank">', '</a>'); ?>
                        <div class="prep-parameter-container">
                            <label><?php echo esc_html__('Application type', 'fb'); ?>:</label>
                            <pre>Consumer</pre>
                        </div>
                        <div class="prep-parameter-container">
                            <label><?php echo esc_html__('Product', 'fb'); ?>:</label>
                            <pre>Facebook Login</pre>
                        </div>
                        <div class="prep-parameter-container">
                            <label><?php echo esc_html__('Valid OAuth Redirect URI', 'fb'); ?>:</label>
                            <pre onclick="this.focus();this.select();"><?php echo url('auth.php').'?facebook=auth' ?></pre>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-label">
                    <label><?php echo esc_html__('Client ID', 'fb'); ?></label>
                </div>
                <div class="form-tooltip">
                    <i class="fas fa-question-circle form-tooltip-anchor"></i>
                    <div class="form-tooltip-content">
                        <?php echo esc_html__('Enter your Facebook Application Client ID.', 'fb'); ?>
                    </div>
                </div>
                <div class="form-content">
                    <div class="input-box">
                        <input class="errorable" type="text" name="facebook-client-id" placeholder="<?php echo esc_html__('Client ID', 'fb'); ?>" value="<?php echo esc_html($options['facebook-client-id']); ?>">
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-label">
                    <label><?php echo esc_html__('Client Secret', 'fb'); ?></label>
                </div>
                <div class="form-tooltip">
                    <i class="fas fa-question-circle form-tooltip-anchor"></i>
                    <div class="form-tooltip-content">
                        <?php echo esc_html__('Enter your Facebook Application Client Secret.', 'fb'); ?>
                    </div>
                </div>
                <div class="form-content">
                    <div class="input-box">
                        <input class="errorable" type="text" name="facebook-client-secret" placeholder="<?php echo esc_html__('Client Secret', 'fb'); ?>" value="<?php echo esc_html($options['facebook-client-secret']); ?>">
                    </div>
                </div>
            </div>
        </div>
        <h2><?php echo esc_html__('VK', 'fb'); ?></h2>
        <div class="form-row">
            <div class="form-label">
                <label><?php echo esc_html__('Enable', 'fb'); ?></label>
            </div>
            <div class="form-tooltip">
                <i class="fas fa-question-circle form-tooltip-anchor"></i>
                <div class="form-tooltip-content">
                    <?php echo esc_html__('Allow users to use their VK Account to sign in.', 'fb'); ?>
                </div>
            </div>
            <div class="form-content">
                <div class="input-box">
                    <div class="checkbox-toggle-container">
						<input class="checkbox-toggle" type="checkbox" value="on" id="vk-enable" name="vk-enable"<?php echo ($options['vk-enable'] == 'on' ? ' checked="checked"' : ''); ?> onchange="if(jQuery(this).is(':checked')){jQuery('#vk-parameters').fadeIn(100);}else{jQuery('#vk-parameters').fadeOut(100);}">
                        <label for="vk-enable"></label>
					</div>
                </div>
            </div>
        </div>
        <div id="vk-parameters"<?php echo ($options['vk-enable'] != 'on' ? ' style="display:none;"' : ''); ?>>
            <div class="form-row">
                <div class="form-label">
                </div>
                <div class="form-tooltip">
                </div>
                <div class="form-content">
                    <div class="inline-message inline-message-noclose inline-message-success">
                        <?php echo sprintf(esc_html__('Create new application in %sVK Developers%s and copy-paste its credentials into fields below. Use the following parameters to create an application.', 'fb'), '<a href="https://vk.com/apps/" target="_blank">', '</a>'); ?>
                        <div class="prep-parameter-container">
                            <label><?php echo esc_html__('Platform', 'fb'); ?>:</label>
                            <pre>Website</pre>
                        </div>
                        <div class="prep-parameter-container">
                            <label><?php echo esc_html__('Authorized redirect URI', 'fb'); ?>:</label>
                            <pre onclick="this.focus();this.select();"><?php echo url('auth-vk.php'); ?></pre>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-label">
                    <label><?php echo esc_html__('App ID', 'fb'); ?></label>
                </div>
                <div class="form-tooltip">
                    <i class="fas fa-question-circle form-tooltip-anchor"></i>
                    <div class="form-tooltip-content">
                        <?php echo esc_html__('Enter your VK App ID.', 'fb'); ?>
                    </div>
                </div>
                <div class="form-content">
                    <div class="input-box">
                        <input class="errorable" type="text" name="vk-client-id" placeholder="<?php echo esc_html__('App ID', 'fb'); ?>" value="<?php echo esc_html($options['vk-client-id']); ?>">
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-label">
                    <label><?php echo esc_html__('Secure Key', 'fb'); ?></label>
                </div>
                <div class="form-tooltip">
                    <i class="fas fa-question-circle form-tooltip-anchor"></i>
                    <div class="form-tooltip-content">
                        <?php echo esc_html__('Enter your VK App Secure Key.', 'fb'); ?>
                    </div>
                </div>
                <div class="form-content">
                    <div class="input-box">
                        <input class="errorable" type="text" name="vk-client-secret" placeholder="<?php echo esc_html__('Secure Key', 'fb'); ?>" value="<?php echo esc_html($options['vk-client-secret']); ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="tab-mail" class="tab-content">
        <h2><?php echo esc_html__('Sender parameters', 'fb'); ?></h2>
        <div class="sender-details">
            <div class="form-row">
                <div class="form-label">
                    <label><?php echo esc_html__('Method', 'fb'); ?></label>
                </div>
                <div class="form-tooltip">
                    <i class="fas fa-question-circle form-tooltip-anchor"></i>
                    <div class="form-tooltip-content">
                        <?php echo esc_html__('Set mailing method. All email messages are sent using this mailing method.', 'fb'); ?>
                    </div>
                </div>
                <div class="form-content">
                    <div class="input-box">
                        <div class="bar-selector">
                            <input class="radio" id="mail-method-mail" type="radio" name="mail-method" value="mail"<?php echo $options['mail-method'] == 'smtp' ? '' : ' checked="checked"'; ?> onchange="toggle_mail_method(this);"><label for="mail-method-mail"><?php echo esc_html__('PHP Mail() Function', 'fb'); ?></label><input class="radio" id="mail-method-smtp" type="radio" name="mail-method" value="smtp"<?php echo $options['mail-method'] == 'smtp' ? ' checked="checked"' : ''; ?> onchange="toggle_mail_method(this);"><label for="mail-method-smtp"><?php echo esc_html__('SMTP', 'fb'); ?></label>
                        </div>
                    </div>
                </div>
            </div>
            <div id="mail-method-mail-content"<?php echo $options['mail-method'] == 'smtp' ? ' style="display: none;"' : ''; ?>>
                <div class="form-row">
                    <div class="form-label">
                        <label><?php echo esc_html__('Sender', 'fb'); ?></label>
                    </div>
                    <div class="form-tooltip">
                        <i class="fas fa-question-circle form-tooltip-anchor"></i>
                        <div class="form-tooltip-content">
                            <?php echo esc_html__('Set sender name and email. All email messages are sent using these credentials as "FROM:" header value.', 'fb'); ?>
                        </div>
                    </div>
                    <div class="form-content">
                        <div class="columns">
                            <div class="column column-50">
                                <div class="input-box">
                                    <div class="input-element">
                                        <input class="errorable" type="text" name="mail-from-name" placeholder="<?php echo esc_html__('Name', 'fb'); ?>" value="<?php echo esc_html($options['mail-from-name']); ?>">
                                    </div>
                                    <label><?php echo esc_html__('Sender name', 'fb'); ?></label>
                                </div>
                            </div>
                            <div class="column column-50">
                                <div class="input-box">
                                    <div class="input-element">
                                        <input class="errorable" type="text" name="mail-from-email" placeholder="<?php echo esc_html__('Email', 'fb'); ?>" value="<?php echo esc_html($options['mail-from-email']); ?>">
                                    </div>
                                    <label><?php echo esc_html__('Sender email', 'fb'); ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="mail-method-smtp-content"<?php echo $options['mail-method'] != 'smtp' ? ' style="display: none;"' : ''; ?>>
                <div class="form-row">
                    <div class="form-label">
                        <label><?php echo esc_html__('Sender', 'fb'); ?></label>
                    </div>
                    <div class="form-tooltip">
                        <i class="fas fa-question-circle form-tooltip-anchor"></i>
                        <div class="form-tooltip-content">
                            <?php echo esc_html__('Set sender name and email. All email messages are sent using these credentials as "FROM:" header value.', 'fb'); ?>
                        </div>
                    </div>
                    <div class="form-content">
                        <div class="columns">
                            <div class="column column-50">
                                <div class="input-box">
                                    <div class="input-element">
                                        <input class="errorable" type="text" name="smtp-from-name" placeholder="<?php echo esc_html__('Name', 'fb'); ?>" value="<?php echo esc_html($options['smtp-from-name']); ?>">
                                    </div>
                                    <label><?php echo esc_html__('Sender name', 'fb'); ?></label>
                                </div>
                            </div>
                            <div class="column column-50">
                                <div class="input-box">
                                    <div class="input-element">
                                        <input class="errorable" type="text" name="smtp-from-email" placeholder="<?php echo esc_html__('Email', 'fb'); ?>" value="<?php echo esc_html($options['smtp-from-email']); ?>">
                                    </div>
                                    <label><?php echo esc_html__('Sender email', 'fb'); ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-label">
                        <label><?php echo esc_html__('Server', 'fb'); ?></label>
                    </div>
                    <div class="form-tooltip">
                        <i class="fas fa-question-circle form-tooltip-anchor"></i>
                        <div class="form-tooltip-content">
                            <?php echo esc_html__('Set encryption, mail server hostname and port.', 'fb'); ?>
                        </div>
                    </div>
                    <div class="form-content">
                        <div class="columns">
                            <div class="column column-30">
                                <div class="input-box">
                                    <div class="input-element">
                                        <select id="smtp-secure" name="smtp-secure">
<?php
                foreach ($smtp_secures as $key => $value) {
                    echo '
                                        <option value="'.esc_html($key).'"'.($key == $options['smtp-secure'] ? ' selected="selected"' : '').'>'.esc_html($value).'</option>';
                }
?>
                                        </select>
                                    </div>
                                    <label><?php echo esc_html__('Encryption', 'fb'); ?></label>
                                </div>
                            </div>
                            <div class="column column-40">
                                <div class="input-box">
                                    <div class="input-element">
                                        <input class="errorable" type="text" name="smtp-server" placeholder="<?php echo esc_html__('Hostname', 'fb'); ?>" value="<?php echo esc_html($options['smtp-server']); ?>">
                                    </div>
                                    <label><?php echo esc_html__('Hostname', 'fb'); ?></label>
                                </div>
                            </div>
                            <div class="column column-30">
                                <div class="input-box">
                                    <div class="input-element">
                                        <input class="errorable" type="text" name="smtp-port" placeholder="<?php echo esc_html__('Port', 'fb'); ?>" value="<?php echo esc_html($options['smtp-port']); ?>">
                                    </div>
                                    <label><?php echo esc_html__('Port', 'fb'); ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-label">
                        <label><?php echo esc_html__('User', 'fb'); ?></label>
                    </div>
                    <div class="form-tooltip">
                        <i class="fas fa-question-circle form-tooltip-anchor"></i>
                        <div class="form-tooltip-content">
                            <?php echo esc_html__('Set sender name and email. All email messages are sent using these credentials as "FROM:" header value.', 'fb'); ?>
                        </div>
                    </div>
                    <div class="form-content">
                        <div class="columns">
                            <div class="column column-50">
                                <div class="input-box">
                                    <div class="input-element">
                                        <input class="errorable" type="text" name="smtp-username" placeholder="<?php echo esc_html__('Username', 'fb'); ?>" value="<?php echo esc_html($options['smtp-username']); ?>">
                                    </div>
                                    <label><?php echo esc_html__('Username', 'fb'); ?></label>
                                </div>
                            </div>
                            <div class="column column-50">
                                <div class="input-box">
                                    <div class="input-element">
                                        <input class="errorable" type="text" name="smtp-password" placeholder="<?php echo esc_html__('Password', 'fb'); ?>" value="<?php echo esc_html($options['smtp-password']); ?>">
                                    </div>
                                    <label><?php echo esc_html__('Password', 'fb'); ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-label">
            </div>
            <div class="form-tooltip">
            </div>
            <div class="form-content">
                <div class="input-box">
                    <div id="test-mailing-message" class="inline-message inline-message-noclose inline-message-warning" style="display: none;"></div>
                    <a class="social-button" href="#" onclick="return test_mailing(this);">
                        <i class="far fa-envelope"></i> <?php echo esc_html__('Test mailing', 'fb'); ?>
                    </a>
                    <label><?php echo sprintf(esc_html__('Press button and check your inbox (%s). If you do not see test message, something does not work. Do not forget to check SPAM folder.', 'fb'), esc_html($user_details['email'])); ?></label>
                </div>
            </div>
        </div>
        <h2><?php echo esc_html__('Confirmation email', 'fb'); ?></h2>
        <div class="form-row">
            <div class="form-label">
                <label><?php echo esc_html__('Subject', 'fb'); ?></label>
            </div>
            <div class="form-tooltip">
                <i class="fas fa-question-circle form-tooltip-anchor"></i>
                <div class="form-tooltip-content">
                    <?php echo esc_html__('Newly registered users must confirm their email address to receive notifications. Specify the subject of confirmation email.', 'fb'); ?>
                </div>
            </div>
            <div class="form-content">
                <div class="input-box">
                    <input class="errorable" type="text" name="confirm-subject" placeholder="<?php echo esc_html__('Subject', 'fb'); ?>" value="<?php echo esc_html($options['confirm-subject']); ?>">
                </div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-label">
                <label><?php echo esc_html__('Message', 'fb'); ?></label>
            </div>
            <div class="form-tooltip">
                <i class="fas fa-question-circle form-tooltip-anchor"></i>
                <div class="form-tooltip-content">
                    <?php echo esc_html__('Specify the message of confirmation email. You can use the following shortcodes.', 'fb'); ?><br />
                    <code>{name}</code> - <?php echo esc_html__('Full name', 'fb'); ?>,<br />
                    <code>{email}</code> - <?php echo esc_html__('Email address', 'fb'); ?>,<br />
                    <code>{confirmation-url}</code> - <?php echo esc_html__('URL that is used to confirm email address.', 'fb'); ?>
                </div>
            </div>
            <div class="form-content">
                <div class="input-box">
                    <textarea class="errorable" name="confirm-message" placeholder="<?php echo esc_html__('Message', 'fb'); ?>"><?php echo esc_html($options['confirm-message']); ?></textarea>
                </div>
            </div>
        </div>
        <h2><?php echo esc_html__('Reset password', 'fb'); ?></h2>
        <div class="form-row">
            <div class="form-label">
                <label><?php echo esc_html__('Subject', 'fb'); ?></label>
            </div>
            <div class="form-tooltip">
                <i class="fas fa-question-circle form-tooltip-anchor"></i>
                <div class="form-tooltip-content">
                    <?php echo esc_html__('Specify the subject of reset password email.', 'fb'); ?>
                </div>
            </div>
            <div class="form-content">
                <div class="input-box">
                    <input class="errorable" type="text" name="reset-subject" placeholder="<?php echo esc_html__('Subject', 'fb'); ?>" value="<?php echo esc_html($options['reset-subject']); ?>">
                </div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-label">
                <label><?php echo esc_html__('Message', 'fb'); ?></label>
            </div>
            <div class="form-tooltip">
                <i class="fas fa-question-circle form-tooltip-anchor"></i>
                <div class="form-tooltip-content">
                    <?php echo esc_html__('Specify the message of reset password email. You can use the following shortcodes.', 'fb'); ?><br />
                    <code>{name}</code> - <?php echo esc_html__('Full name', 'fb'); ?>,<br />
                    <code>{email}</code> - <?php echo esc_html__('Email address', 'db'); ?>,<br />
                    <code>{reset-password-url}</code> - <?php echo esc_html__('URL that is used to reset the password.', 'fb'); ?>
                </div>
            </div>
            <div class="form-content">
                <div class="input-box">
                    <textarea class="errorable" name="reset-message" placeholder="<?php echo esc_html__('Message', 'fb'); ?>"><?php echo esc_html($options['reset-message']); ?></textarea>
                </div>
            </div>
        </div>
    </div>
    <div class="form-row right-align">
        <input type="hidden" name="action" value="save-site-settings">
        <a class="button" href="#" onclick="return save_form(this);" data-label="<?php echo esc_html__('Save Settings', 'fb'); ?>">
            <span><?php echo esc_html__('Save Settings', 'fb'); ?></span>
            <i class="fas fa-angle-right"></i>
        </a>
    </div>
</div>
<?php
include_once(dirname(__FILE__).'/inc/footer.php');
?>