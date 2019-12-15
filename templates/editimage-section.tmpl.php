<?php
if ( !defined( 'MEDIAWIKI' ) ) {
	exit( 1 );
}
?>
<!-- s:<?php echo __FILE__ ?> -->
<div id="createpage_upload_div_section<?php echo $imagenum ?>">
	<div class="createpage_input_file createpage_input_file_no_path">
		<div class="thumb tleft" id="createpage_main_thumb_section<?php echo $imagenum ?>" style="display: none">
			<div class="thumbinner"><img id="createpage_image_thumb_section<?php echo $imagenum ?>" src="" alt="..." /></div>
		</div>
		<label id="createpage_image_label_section<?php echo $imagenum ?>" class="button color1">
			<span id="createpage_image_text_section<?php echo $imagenum ?>"><?php echo wfMessage( 'createpage-insert-image' )->escaped() ?></span>
			<span id="createpage_image_cancel_section<?php echo $imagenum ?>" style="display: none"><?php echo wfMessage( 'cancel' )->escaped() ?></span>
			<input type="file" name="wpAllUploadFile<?php echo $imagenum ?>" id="createpage_upload_file_section<?php echo $imagenum ?>" tabindex="-1" />
		</label>
		<div id="createpage_upload_progress_section<?php echo $imagenum ?>" class="progress">&nbsp;</div>
	</div>
	<input type="hidden" id="wpAllDestFile<?php echo $imagenum ?>" name="wpAllDestFile<?php echo $imagenum ?>" value="" />
	<input type="hidden" name="wpAllIgnoreWarning<?php echo $imagenum ?>" value="1" />
	<input type="hidden" name="wpAllUploadDescription<?php echo $imagenum ?>" value="<?php echo wfMessage( 'createpage-uploaded-from' )->escaped() ?>" />
	<input type="hidden" id="wpAllLastTimestamp<?php echo $imagenum ?>" name="wpAllLastTimestamp<?php echo $imagenum ?>" value="None" />
	<input type="hidden" id="wpAllUploadTarget<?php echo $imagenum ?>" name="wpAllUploadTarget<?php echo $imagenum ?>" value="wpTextboxes<?php echo $target_tag ?>" />
	<input type="hidden" name="wpAllWatchthis<?php echo $imagenum ?>" value="1" />
	<noscript>
		<input type="submit" id="createpage_upload_submit_section<?php echo $imagenum ?>" name="wpImageUpload" value="<?php echo wfMessage( 'createpage-upload' )->escaped() ?>" class="upload_submit" />
	</noscript>
</div>
<!-- e:<?php echo __FILE__ ?> -->
