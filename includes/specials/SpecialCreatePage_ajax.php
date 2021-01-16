<?php

// phpcs:disable MediaWiki.NamingConventions.PrefixedGlobalFunctions.wfPrefix

/**
 * AJAX functions for CreateAPage extension.
 */

use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;

function axMultiEditParse() {
	$me = '';

	$template = RequestContext::getMain()->getRequest()->getVal( 'template' );
	$title = Title::newFromText( "Createplate-{$template}", NS_MEDIAWIKI );

	// transfer optional sections data
	$optionalSections = [];
	foreach ( $_POST as $key => $value ) {
		if ( strpos( $key, 'wpOptionalInput' ) !== false ) {
			$optionalSections = str_replace( 'wpOptionalInput', '', $key );
		}
	}

	if ( $title->exists() ) {
		$rev = MediaWikiServices::getInstance()->getRevisionLookup()->getRevisionByTitle( $title );
		$me = CreateMultiPage::multiEditParse( 10, 10, '?', ContentHandler::getContentText( $rev->getContent( SlotRecord::MAIN ) ), $optionalSections );
	} else {
		$me = CreateMultiPage::multiEditParse( 10, 10, '?', '<!---blanktemplate--->' );
	}

	return json_encode( $me );
}

function axMultiEditImageUpload() {
	$res = [];

	$request = RequestContext::getMain()->getRequest();
	$postfix = $request->getVal( 'num' );
	$infix = '';
	if ( $request->getVal( 'infix' ) != '' ) {
		$infix = $request->getVal( 'infix' );
	}

	// store these for the upload class to use
	$request->setVal( 'wpPostFix', $postfix );
	$request->setVal( 'wpInFix', $infix );
	$request->setVal( 'Createtitle', $request->getText( 'Createtitle' ) );

	// do the real upload
	$uploadForm = new CreatePageImageUploadForm();
	$uploadForm->initializeFromRequest( $request );
	$uploadForm->mComment = wfMessage( 'createpage-uploaded-from' )->text();
	$uploadedFile = $uploadForm->execute();

	if ( $uploadedFile['error'] == 0 ) {
		if ( $uploadedFile['msg'] !== 'cp_no_uploaded_file' ) {
			$imageobj = MediaWikiServices::getInstance()->getRepoGroup()->getLocalRepo()
				->newFile( $uploadedFile['timestamp'] );
			$imageurl = $imageobj->createThumb( 60 );
		} else {
			// Crappy hack, but whatever, not uploading a file is entirely valid
			// since we have the same special page handling both the upload and the form
			// submission
			$imageURL = '';
			$uploadedFile['timestamp'] = '';
		}

		$res = [
			'error' => 0,
			'msg' => $uploadedFile['msg'],
			'url' => $imageURL,
			'timestamp' => $uploadedFile['timestamp'],
			'num' => $postfix
		];
	} else {
		if ( $uploadedFile['once'] ) {
			# if ( !$error_once ) {
				$res = [
					'error' => 1,
					'msg' => $uploadedFile['msg'],
					'num' => $postfix,
				];
			# }
			$error_once = true;
		} else {
			$res = [
				'error' => 1,
				'msg' => $uploadedFile['msg'],
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
	$mCreateplate = RequestContext::getMain()->getRequest()->getVal( 'createplates' );
	$editor = new CreatePageMultiEditor( $mCreateplate );
	$content = CreateMultiPage::unescapeBlankMarker( $editor->glueArticle() );
	CreateAPageUtils::unescapeKnownMarkupTags( $content );
	$_SESSION['article_content'] = $content;

	return json_encode( true );
}

global $wgAjaxExportList;
$wgAjaxExportList[] = 'axMultiEditParse';
$wgAjaxExportList[] = 'axMultiEditImageUpload';
$wgAjaxExportList[] = 'axCreatepageAdvancedSwitch';
