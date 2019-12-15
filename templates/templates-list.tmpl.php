<?php
if ( !defined( 'MEDIAWIKI' ) ) {
	exit( 1 );
}
?>
<!-- s:<?php echo __FILE__ ?> -->
<noscript>
<style type="text/css">
/*<![CDATA[*/
#wpTableMultiEdit div div .createpage_input_file label,
#cp-infobox div .createpage_input_file label {
	float: left !important;
	background: #fff;
	border: none;
	color: #000;
	cursor: auto;
}

#wpTableMultiEdit div div .createpage_input_file label span,
#cp-infobox div .createpage_input_file label span {
	display: none !important;
}

#wpTableMultiEdit div div .createpage_input_file label input,
#cp-infobox div .createpage_input_file label input {
	position: relative !important;
	font-size: 9pt !important;
	line-height: 12px !important;
	opacity: 100 !important;
	zoom: 1 !important;
	filter: alpha(opacity=100) !important;
}

/*]]>*/
</style>
</noscript>

<script type="text/javascript">
/*<![CDATA[*/
<?php
$tool_arr = CreateMultiPage::getToolArray();
$tool_num = 0;
if ( false ) { // ashley 8 December 2019: @todo FIXME, shitty hack to kill JS errors for dev only
foreach ( $tool_arr as $single_tool ) { ?>
CreateAPage.toolbarButtons[<?php echo $tool_num ?>] = [];
	// @todo FIXME: bad path
CreateAPage.toolbarButtons[<?php echo $tool_num ?>]['image'] = stylepath + '/common/images/' + '<?php echo $single_tool['image'] ?>';
CreateAPage.toolbarButtons[<?php echo $tool_num ?>]['id'] = '<?php echo $single_tool['id'] ?>';
CreateAPage.toolbarButtons[<?php echo $tool_num ?>]['open'] = '<?php echo $single_tool['open'] ?>';
CreateAPage.toolbarButtons[<?php echo $tool_num ?>]['close'] = '<?php echo $single_tool['close'] ?>';
CreateAPage.toolbarButtons[<?php echo $tool_num ?>]['sample'] = '<?php echo $single_tool['sample'] ?>';
CreateAPage.toolbarButtons[<?php echo $tool_num ?>]['tip'] = '<?php echo $single_tool['tip'] ?>';
CreateAPage.toolbarButtons[<?php echo $tool_num ?>]['key'] = '<?php echo $single_tool['key'] ?>';
<?php
	$tool_num++;
}
} // if false
?>
/*]]>*/
</script>

<?php if ( !$ispreview ) { ?>

<div id="templateThumbs">
<?php
}

	if ( !$ispreview ) {
		foreach ( $data as $e => $element ) {
			$name = $element['page'];
			$label = str_replace( ' Page', '', $element['label'] );

			$thumb = '';
			if ( !empty( $element['preview'] ) ) {
				$thumb = "<img id=\"cp-template-$name-thumb\" src=\"" . $element['preview'] . "\" alt=\"$name\" />";
			}
			?>

	<div class="templateFrame<?php if ( $e == count( $data ) - 1 ) { ?> templateFrameLast<?php } ?><?php if ( $selected[$name] == 'checked' ) { ?> templateFrameSelected<?php } ?>" id="cp-template-<?php echo $name ?>">
		<label for="cp-template-<?php echo $name ?>-radio">
		<?php echo $thumb ?>
		</label>
		<div>
			<input type="radio" name="createplates" id="cp-template-<?php echo $name ?>-radio" value="<?php echo $name ?>" <?php echo $selected[$name] ?> />
			<label for="cp-template-<?php echo $name ?>-radio"><?php echo $label ?></label>
		</div>
	</div>
	<?php } // foreach ?>
</div>

<?php
	} // is not preview
?>

<div class="visualClear" style="clear: both"></div>
<?php if ( !$ispreview ) { ?>
	</div>
	</fieldset>
<?php } ?>
	<div id="createpage_createplate_list"></div>
	<noscript>
		<div class="actionBar">
			<input type="submit" name="wpSubmitCreateplate" id="wpSubmitCreateplate" value="<?php echo wfMessage( 'createpage-button-createplate-submit' )->escaped() ?>" class="button color1"/>
		</div>
	</noscript>

<br />
<div class="actionBar">
	<a name="title_loc"></a>
<?php if ( !$isredlink ) { ?>
	<label for="Createtitle" id="Createtitlelabel"><?php echo wfMessage( 'createpage-title-caption' )->escaped() ?></label>
	<input name="Createtitle" id="Createtitle" size="50" value="<?php echo htmlspecialchars( $createtitle ) ?>" maxlength="250" />
<?php } else { ?>
	<div id="createpageinfo"><?php echo $aboutinfo ?></div>
	<input type="hidden" name="Createtitle" id="Createtitle" value="<?php echo $createtitle ?>" />
	<input type="hidden" name="Redlinkmode" id="Redlinkmode" value="<?php echo $isredlink ?>" />
<?php } ?>
	<input id="wpRunInitialCheck" class="button color1" type="button" value="<?php echo wfMessage( 'createpage-initial-run' )->escaped() ?>" style="display: none;" />
<?php if ( !$isredlink ) { ?>
	<input type="submit" id="wpAdvancedEdit" name="wpAdvancedEdit" value="<?php echo wfMessage( 'createpage-edit-normal' )->escaped() ?>" class="button color1" />
<?php } ?>
	<div id="cp-title-check">&nbsp;</div>
</div>
<br />
<!-- e:<?php echo __FILE__ ?> -->
