<?php
if ( !defined( 'MEDIAWIKI' ) ) {
	exit( 1 );
}
?>
<!-- s:<?php echo __FILE__ ?> -->
<!-- CSS part -->
<style type="text/css">
/*<![CDATA[*/
#wpTextbox1 {
	display: none;
}
/*]]>*/
</style>

<div id="createpage_cloud_div">
<div style="font-weight: bold;"><?php echo wfMessage( 'createpage-categories' )->escaped() ?></div>
<?php if ( isset( $cloud->tags ) ) { ?>
<div id="createpage_cloud_section">
<?php
$xnum = 0;
foreach ( $cloud->tags as $xname => $xtag ) {
?>
	<span id="tag<?php echo $xnum ?>" style="font-size:<?php echo $xtag['size'] ?>pt">
		<a href="#" id="cloud<?php echo $xnum ?>"><?php echo $xname ?></a>
	</span>
<?php
	$xnum++;
}
?>
</div>
<?php } ?>
<textarea accesskey="," name="wpCategoryTextarea" id="wpCategoryTextarea" rows="3" cols="<?php echo $cols ?>"><?php echo $text_category ?></textarea>
<input type="button" name="wpCategoryButton" id="wpCategoryButton" class="button color1" value="<?php echo wfMessage( 'createpage-addcategory' )->escaped() ?>" />
<input type="text" name="wpCategoryInput" id="wpCategoryInput" value="" />
</div>

<noscript>
<?php if ( isset( $cloud->tags ) ) { ?>
<div id="createpage_cloud_section_njs">
<?php
$xnum = 0;

foreach ( $cloud->tags as $xname => $xtag ) {
	$checked = ( array_key_exists( $xname, $array_category ) && ( $array_category[$xname] ) ) ? ' checked="checked"' : '';
	$array_category[$xname] = 0;
?>
	<span id="tag_njs_<?php echo $xnum ?>" style="font-size:9pt">
		<input<?php echo $checked ?> type="checkbox" name="category_<?php echo $xnum ?>" id="category_<?php echo $xnum ?>" value="<?php echo $xname ?>">&nbsp;<?php echo $xname ?>
	</span>
<?php
	$xnum++;
}

$display_category = [];
foreach ( $array_category as $xname => $visible ) {
	if ( $visible == 1 ) {
		$display_category[] = $xname;
	}
}
$text_category = implode( ',', $display_category );
?>
</div>
<?php } ?>
<textarea tabindex="<?php echo $num ?>" accesskey="," name="wpCategoryTextarea" id="wpCategoryTextarea" rows="3" cols="<?php echo $cols ?>"><?php echo $text_category ?></textarea>
</noscript>
<!-- e:<?php echo __FILE__ ?> -->
