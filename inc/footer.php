<?php	 	
if (!defined('INTEGRITY')) exit;
$copyright = translatable_parse($options['copyright']);
echo '
            </div>
        </div>
        <footer>
            <div class="copyright">'.(!empty($copyright['default']) ? (array_key_exists($language, $copyright) && !empty($copyright[$language]) ? esc_html($copyright[$language]) : esc_html($copyright['default'])) : sprintf(esc_html__('Created with %s by Ivan Churakov', 'fb'), '<i class="fas fa-heart"></i>')).'</div>
        </footer>
    </div>
    <form class="upload-form" action="'.url('ajax.php').'" method="POST" enctype="multipart/form-data" target="upload-iframe" onsubmit="return upload_start(this);" style="display: none !important; width: 0 !important; height: 0 !important;">
			<input type="hidden" name="action" value="image-uploader-action" />
			<input type="hidden" name="_token" value="'.esc_html($csrf_token).'" />
			<input type="file" name="file" accept="image/*" onchange="jQuery(this).parent().submit();" style="display: none !important; width: 0 !important; height: 0 !important;" />
			<input type="submit" value="Upload" style="display: none !important; width: 0 !important; height: 0 !important;" />
	</form>											
	<iframe data-loading="false" id="upload-iframe" name="upload-iframe" src="about:blank" onload="upload_finish(this);" style="display: none !important; width: 0 !important; height: 0 !important;"></iframe>    
    <div id="global-message"></div>
    '.session_message();
do_action('admin_head');
echo '
</body>
</html>';
?>