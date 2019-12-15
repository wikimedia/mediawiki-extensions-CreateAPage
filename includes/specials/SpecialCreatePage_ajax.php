<?php
/**
 * AJAX functions for CreateAPage extension.
 */
function axTitleExists() {
	global $wgRequest;

	$res = [ 'text' => false ];

	$title = $wgRequest->getVal( 'title' );
	$mode = $wgRequest->getVal( 'mode' );
	$title_object = Title::newFromText( $title );

	if ( is_object( $title_object ) ) {
		if ( $title_object->exists() ) {
			$res = [
				'url'  => $title_object->getLocalURL(),
				'text' => $title_object->getPrefixedText(),
				'mode' => $mode,
			];
		}
	} else {
		$res = [ 'empty' => true ];
	}

	return json_encode( $res );
}

function axMultiEditParse() {
	global $wgRequest;

	$me = '';

	$template = $wgRequest->getVal( 'template' );
	$title = Title::newFromText( "Createplate-{$template}", NS_MEDIAWIKI );

	// transfer optional sections data
	$optionalSections = [];
	foreach ( $_POST as $key => $value ) {
		if ( strpos( $key, 'wpOptionalInput' ) !== false ) {
			$optionalSections = str_replace( 'wpOptionalInput', '', $key );
		}
	}

	if ( $title->exists() ) {
		$rev = Revision::newFromTitle( $title );
		$me = CreateMultiPage::multiEditParse( 10, 10, '?', ContentHandler::getContentText( $rev->getContent() ), $optionalSections );
	} else {
		$me = CreateMultiPage::multiEditParse( 10, 10, '?', '<!---blanktemplate--->' );
	}

	return json_encode( $me );
}

function axMultiEditImageUpload() {
	global $wgRequest;

	$res = [];

	$postfix = $wgRequest->getVal( 'num' );
	$infix = '';
	if ( $wgRequest->getVal( 'infix' ) != '' ) {
		$infix = $wgRequest->getVal( 'infix' );
	}

	// store these for the upload class to use
	$wgRequest->setVal( 'wpPostFix', $postfix );
	$wgRequest->setVal( 'wpInFix', $infix );
	$wgRequest->setVal( 'Createtitle', $wgRequest->getText( 'Createtitle' ) );

	// do the real upload
	$uploadform = new CreatePageImageUploadForm();
	$uploadform->initializeFromRequest( $wgRequest );
	$uploadform->mComment = wfMessage( 'createpage-uploaded-from' )->text();
	$uploadedfile = $uploadform->execute();

	if ( $uploadedfile['error'] == 0 ) {
		if ( $uploadedfile['msg'] !== 'cp_no_uploaded_file' ) {
			$imageobj = wfLocalFile( $uploadedfile['timestamp'] );
			$imageurl = $imageobj->createThumb( 60 );
		} else {
			// Crappy hack, but whatever, not uploading a file is entirely valid
			// since we have the same special page handling both the upload and the form
			// submission
			$imageurl = '';
			$uploadedfile['timestamp'] = '';
		}

		$res = [
			'error' => 0,
			'msg' => $uploadedfile['msg'],
			'url' => $imageurl,
			'timestamp' => $uploadedfile['timestamp'],
			'num' => $postfix
		];
	} else {
		if ( $uploadedfile['once'] ) {
			#if ( !$error_once ) {
				$res = [
					'error' => 1,
					'msg' => $uploadedfile['msg'],
					'num' => $postfix,
				];
			#}
			$error_once = true;
		} else {
			$res = [
				'error' => 1,
				'msg' => $uploadedfile['msg'],
				'num' => $postfix,
			];
		}
	}

	$text = json_encode( $res );
	$ar = new AjaxResponse( $text );
	$ar->setContentType( 'text/html; charset=utf-8' );
	return $ar;
}

function axCreatepageAdvancedSwitch() {
	global $wgRequest;

	$mCreateplate = $wgRequest->getVal( 'createplates' );
	$editor = new CreatePageMultiEditor( $mCreateplate );
	$content = CreateMultiPage::unescapeBlankMarker( $editor->glueArticle() );
	CreateAPageUtils::unescapeKnownMarkupTags( $content );
	$_SESSION['article_content'] = $content;

	return json_encode( true );
}

global $wgAjaxExportList;
$wgAjaxExportList[] = 'axTitleExists';
$wgAjaxExportList[] = 'axMultiEditParse';
$wgAjaxExportList[] = 'axMultiEditImageUpload';
$wgAjaxExportList[] = 'axCreatepageAdvancedSwitch';