<?php	 	
if (!defined('INTEGRITY')) exit;
$copyright = translatable_parse($options['copyright']);
?>
            </div>
        </div>
        <footer>
            <div class="copyright"><?php echo !empty($copyright['default']) ? (array_key_exists($language, $copyright) && !empty($copyright[$language]) ? esc_html($copyright[$language]) : esc_html($copyright['default'])) : sprintf(esc_html__('Created with %s by Ivan Churakov', 'fb'), '<i class="fas fa-heart"></i>'); ?></div>
        </footer>
    </div>
    <div id="global-message"></div>
    <?php echo session_message(); ?>
<?php
do_action('admin_head');
?>
</body>
</html>