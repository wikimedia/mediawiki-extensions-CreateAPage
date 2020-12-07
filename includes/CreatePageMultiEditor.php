<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionAccessException;
use MediaWiki\Revision\SlotRecord;

// wraps up special multi editor class
class CreatePageMultiEditor extends CreatePageEditor {
	public $mRedLinked, $mInitial, $mPreviewed;

	function __construct( $template, $redlinked = false, $initial = false, $previewed = false ) {
		$this->mTemplate = $template;
		$this->mRedLinked = $redlinked;
		$this->mInitial = $initial;
		$this->mPreviewed = $previewed;
	}

	function generateForm( $content = false ) {
		$context = RequestContext::getMain();
		$out = $context->getOutput();
		$request = $context->getRequest();
		$user = $context->getUser();

		$optional_sections = [];

		foreach ( $_POST as $key => $value ) {
			if ( strpos( $key, 'wpOptionalInput' ) !== false ) {
				$optional_sections[] = str_replace( 'wpOptionalInput', '', $key );
			}
		}
		if ( !$content ) {
			$title = Title::newFromText( 'Createplate-' . $this->mTemplate, NS_MEDIAWIKI );
			if ( $title->exists() ) {
				$rev = MediaWikiServices::getInstance()->getRevisionLookup()->getRevisionByTitle( $title );
				if ( $rev !== null ) {
					$contentObj = null;
					try {
						$contentObj = $rev->getContent( SlotRecord::MAIN );
					} catch ( RevisionAccessException $ex ) {
						// Just ignore it for now and fall back to rendering a blank template (below)
					}
					if ( $contentObj !== null ) {
						$contentText = ContentHandler::getContentText( $contentObj );
					} else {
						$contentText = '<!---blanktemplate--->';
					}
					$me = CreateMultiPage::multiEditParse( 10, 10, '?', $contentText, $optional_sections );
				}
			} else {
				$me = CreateMultiPage::multiEditParse( 10, 10, '?', '<!---blanktemplate--->' );
			}
		} else {
			$me = CreateMultiPage::multiEditParse( 10, 10, '?', $content, $optional_sections );
		}

		$captchaForm = '';
		if ( ExtensionRegistry::getInstance()->isLoaded( 'ConfirmEdit' ) ) {
			$captcha = ConfirmEditHooks::getInstance();
			if (
				// @todo I hate this conditional, but ConfirmEdit's shouldCheck() -- which,
				// I guess, we should be using here, has an atrocious interface that assumes,
				// among other things, that we have a title of some kind, but we may literally
				// not even have one at this point, as we are oftentimes in the context of a
				// special page (Special:CreatePage) that lets users supply the title of the
				// page to be created, but this editing form is rendered *before* the user
				// has done that. Thus we partially reimplement shouldCheck() here, sadly...
				(
					$captcha->triggersCaptcha( 'edit' ) ||
					$captcha->triggersCaptcha( 'create' ) ||
					$captcha->triggersCaptcha( 'addurl' )
				) &&
				!$captcha->canSkipCaptcha( $user,
					\MediaWiki\MediaWikiServices::getInstance()->getMainConfig() )
			) {
				$formInformation = $captcha->getFormInformation();
				$formMetainfo = $formInformation;
				unset( $formMetainfo['html'] );
				$captcha->addFormInformationToOutput( $out, $formMetainfo );
				$captchaForm = $captcha->getMessage( 'edit' ) . $formInformation['html'];
			}
		}

		/*
		// New templated code - 10 December 2019
		// Mostly works, I think, though there are some teeny tiny discrepancies between
		// this and the current version
		// Still, this is useful enough that I'm keeping it around as commented-out to be
		// revisited in the future.
		global $wgRightsText;

		$watchThisCheck = false;
		$minorEditCheck = false;

		if ( $this->mInitial ) {
			if ( $user->getOption( 'watchcreations' ) ) {
				$watchThisCheck = true;
			}
			if ( $user->getOption( 'minordefault' ) ) {
				$minorEditCheck = true;
			}
		} else {
			if ( $request->getCheck( 'wpWatchthis' ) ) {
				$watchThisCheck = true;
			}
			if ( $request->getCheck( 'wpMinoredit' ) ) {
				$minorEditCheck = true;
			}
		}

		$templateParser = new TemplateParser( __DIR__ . '/templates' );
		$html = $templateParser->processTemplate(
			'multi-editor-bottom',
			[
				'me' => $me,
				'copyright-warning' => wfMessage(
					$wgRightsText ? 'copyrightwarning' : 'copyrightwarning2',
					'[[' . wfMessage( 'copyrightpage' )->inContentLanguage()->text() . ']]',
					$wgRightsText
				)->parse(),
				'summary' => wfMessage( 'summary' )->text(),
				'summaryVal' => $request->getVal( 'wpSummary' ) ?: '',
				'captchaForm' => $captchaForm,
				'watchThisCheck' => $watchThisCheck,
				'minorEditCheck' => $minorEditCheck,
				'tooltip-minoredit' => wfMessage( 'tooltip-minoredit' )->text(),
				'minoredit' => wfMessage( 'minoredit' )->text(),
				'tooltip-watch' => wfMessage( 'tooltip-watch' )->text(),
				'watchthis' => wfMessage( 'watchthis' )->text(),
				'createpage-save' => wfMessage( 'createpage-save' )->text(),
				'preview' => wfMessage( 'preview' )->text(),
				'cancel' => wfMessage( 'cancel' )->text(),
				'unicodeCheck' => EditPage::UNICODE_CHECK,
				'showEditTools' => ( $this->mRedLinked && ( $this->mTemplate == 'Blank' ) ),
				'editTools' => wfMessage( 'edittools' )->inContentLanguage()->text(),
			]
		);

		$out->addHTML( $html );
		*/
		$out->addHTML(
			'<div id="cp-restricted">
			<div id="createpageoverlay">
				<div class="hd"></div>
				<div class="bd"></div>
				<div class="ft"></div>
			</div>'
		);

		$out->addHTML( "<div id=\"cp-multiedit\">{$me}</div>" );
		// check for already submitted values - for a preview, for example
		$summaryVal = '';
		if ( $request->getVal( 'wpSummary' ) != '' ) {
			$summaryVal = $request->getVal( 'wpSummary' );
		}
		if ( $this->mInitial ) {
			if ( $user->getOption( 'watchcreations' ) ) {
				$watchThisCheck = 'checked="checked"';
			} else {
				$watchThisCheck = '';
			}

			if ( $user->getOption( 'minordefault' ) ) {
				$minorEditCheck = 'checked="checked"';
			} else {
				$minorEditCheck = '';
			}
		} else {
			$watchThisCheck = '';
			$minorEditCheck = '';
			if ( $request->getCheck( 'wpWatchthis' ) ) {
				$watchThisCheck = 'checked="checked"';
			}
			if ( $request->getCheck( 'wpMinoredit' ) ) {
				$minorEditCheck = 'checked="checked"';
			}
		}

		global $wgRightsText;
		$copywarn = "<div id=\"editpage-copywarn\">\n" .
					wfMessage( $wgRightsText ? 'copyrightwarning' : 'copyrightwarning2',
							'[[' . wfMessage( 'copyrightpage' )->inContentLanguage()->text() . ']]',
					$wgRightsText )->text() . "\n</div>";
		$out->addWikiTextAsInterface( $copywarn );

		$editSummary = '<span id="wpSummaryLabel"><label for="wpSummary">' .
			wfMessage( 'summary' )->escaped() . "</label></span>\n<input type='text' value=\"" .
			htmlspecialchars( $summaryVal ) . '" name="wpSummary" id="wpSummary" maxlength="200" size="60" /><br />';

		$checkboxHTML = '<input id="wpMinoredit" type="checkbox" accesskey="i" value="1" name="wpMinoredit" ' . $minorEditCheck . '/>' . "\n" .
		'<label accesskey="i" title="' . wfMessage( 'tooltip-minoredit' )->escaped() . ' [alt-shift-i]" for="wpMinoredit">' . wfMessage( 'minoredit' )->escaped() . '</label>';
		$checkboxHTML .= '<input id="wpWatchthis" type="checkbox" accesskey="w" value="1" name="wpWatchthis" ' . $watchThisCheck . '/>' . "\n" .
		'<label accesskey="w" title="' . wfMessage( 'tooltip-watch' )->escaped() . ' [alt-shift-w]" for="wpWatchthis">' . wfMessage( 'watchthis' )->escaped() . '</label>';

		$out->addHTML(
			'<div id="createpagebottom">' .
			$editSummary .
			$captchaForm .
			$checkboxHTML .
			'</div>'
		);

		$out->addHTML(
			'<div class="actionBar buttonBar">
		<input type="submit" id="wpSave" name="wpSave" value="' . wfMessage( 'createpage-save' )->escaped() . '" class="button color1" />
		<input type="submit" id="wpPreview" name="wpPreview" value="' . wfMessage( 'preview' )->escaped() . '" class="button color1" />
		<input type="submit" id="wpCancel" name="wpCancel" value="' . wfMessage( 'cancel' )->escaped() . '" class="button color1" />
		</div>'
		);

		$out->addHTML(
			Html::hidden( 'wpUnicodeCheck', EditPage::UNICODE_CHECK ) .
			// Marker for detecting truncated form data. This must be the last parameter sent in order to be of use, so do not move me.
			'<input type="hidden" value="true" name="wpUltimateParam" />'
		);

		// stuff for red links - bottom edittools, to be more precise
		if ( $this->mRedLinked && ( $this->mTemplate == 'Blank' ) ) {
			$out->addHTML( '<div id="createpage_editTools" class="mw-editTools">' );
			$out->addWikiTextAsInterface( wfMessage( 'edittools' )->inContentLanguage()->text() );
			$out->addHTML( '</div>' );
		}

		// ashley 10 December 2019: HTML validation fix
		// the old order (</form></div>) was apparently just wrong, even if it rendered
		// just as this one does, but it made W3C Validator barf
		$out->addHTML( "\n</div>\n<!-- #cp-restricted --></form>\n" );
	}

	// take given categories and glue them together
	private function glueCategories( $checkboxes_array, $categories ) {
		$text = '';
		$contLang = MediaWiki\MediaWikiServices::getInstance()->getContentLanguage();
		$ns_cat = $contLang->getFormattedNsText( NS_CATEGORY );

		foreach ( $checkboxes_array as $category ) {
			$text .= "\n[[" . $ns_cat . ':' . $category . ']]';
		}

		// parse the textarea
		$categories_array = preg_split( "/\|/", $categories, -1 );
		foreach ( $categories_array as $category ) {
			if ( !empty( $category ) ) {
				$text .= "\n[[" . $ns_cat . ':' . $category . ']]';
			}
		}

		return $text;
	}

	// get the infobox' text and substitute all known values...
	private function glueInfobox( $infoboxes_array, $infobox_text ) {
		$inf_pars = preg_split( "/\|/", $infobox_text, -1 );

		// correct for additional |'s the users may have put in here...
		$fixed_par_array = [];
		$fix_corrector = 0;

		for ( $i = 0; $i < count( $inf_pars ); $i++ ) {
			// this was cut out from user supplying '|' inside the parameter...
			if ( ( strpos( $inf_pars[$i], '=' ) === false ) && ( 0 != $i ) ) {
				$fixed_par_array[$i - ( 1 + $fix_corrector )] .= '|' . $inf_pars[$i];
				$fix_corrector++;
			} else {
				$fixed_par_array[] = $inf_pars[$i];
			}
		}

		$text = array_shift( $fixed_par_array );
		$inf_par_num = 0;

		foreach ( $fixed_par_array as $inf_par ) {
			$inf_par_pair = preg_split( '/=/', $inf_par, -1 );
			if ( is_array( $inf_par_pair ) ) {
				$text .= '|' . $inf_par_pair[0] . ' = ' .
					$this->escapeKnownMarkupTags(
						trim( @$infoboxes_array[$inf_par_num] )
					) . "\n";
				$inf_par_num++;
			}
		}

		return $text . "}}\n";
	}

	/**
	 * Since people can put in pipes and brackets without them knowing that
	 * it's BAD because it makes an infobox template writhe in agony... escape
	 * the tags
	 *
	 * @param string $text Text to escape
	 * @return string Input with pipes changed to HTML comments and brackets
	 *                 stripped out
	 */
	private function escapeKnownMarkupTags( $text ) {
		$text = str_replace( '|', '<!---pipe--->', $text );
		$text = str_replace( '{{', '', $text );
		$text = str_replace( '}}', '', $text );
		return $text;
	}

	public function glueArticle( $preview = false, $render_option = true ) {
		$context = RequestContext::getMain();
		$out = $context->getOutput();
		$request = $context->getRequest();

		$text = '';
		$infoboxes = [];
		$categories = [];
		$optionals = [];
		$images = [];
		$all_images = [];
		$error_once = false;

		foreach ( $_POST as $key => $value ) {
			if ( strpos( $key, 'wpOptionals' ) !== false ) {
				if ( $render_option ) {
					// build optional data
					$optionals = explode( ',', $value );
				}
			} elseif ( strpos( $key, 'wpTextboxes' ) !== false ) {
				// check if this was optional
				if ( !in_array( $key, $optionals ) ) {
					$text .= "\n" . $value;
				}
			} elseif ( strpos( $key, 'wpInfoboxPar' ) !== false ) {
				$infoboxes[] = $value;
			} elseif ( strpos( $key, 'category_' ) !== false ) {
				$categories[] = $value;
			} elseif ( strpos( $key, 'wpDestFile' ) !== false ) {
				// $image_value = [];
				$postfix = substr( $key, 10 );

				if ( $request->getVal( 'wpNoUse' . $postfix ) == 'Yes' ) {
					$infoboxes[] = $request->getVal( 'wpInfImg' . $postfix );
				} else {
					// $image_value['watchthis'] = $_POST['wpWatchthis' . $postfix];

					// store these for the upload class to use
					$request->setVal( 'wpPostFix', $postfix );
					$request->setVal( 'Createtitle', $request->getText( 'Createtitle' ) );

					// do the real upload
					$uploadForm = new CreatePageImageUploadForm();
					$uploadForm->initializeFromRequest( $request );
					// some of the values are fixed, we have no need to add them to the form itself
					$uploadForm->mComment = wfMessage( 'createpage-uploaded-from' )->text();
					$uploadedFile = $uploadForm->execute();

					if ( $uploadedFile['error'] == 0 ) {
						// This logic is giving me a headache, but allow me to try to explain it
						// to a future me and for the future generations...
						// When the user chose not to upload a file despite an infobox (etc.) having
						// the possibility for them to do that, $uploadedfile['msg'] is set to
						// 'cp_no_uploaded_file'. If that is the case, we need to do essentially
						// $infoboxes[] = '<!---imageupload--->'; (for page preview) to ensure that
						// the "Insert Image" button shows up when the user is previewing their page,
						// but they chose not to upload a file. They may change their mind, you know!
						$infoboxes[] = ( $uploadedFile['msg'] !== 'cp_no_uploaded_file' ? $uploadedFile['msg'] : '<!---imageupload--->' );
					} else {
						$infoboxes[] = '<!---imageupload--->';
						if ( $uploadedFile['once'] ) {
							if ( !$error_once ) {
								if ( !$preview ) {
									// certainly they'll notice things on preview
									$out->addHTML( "<p class='error'>{$uploadedFile['msg']}</p>" );
								}
							}
							$error_once = true;
						} else {
							if ( !$preview ) {
								$out->addHTML( "<p class='error'>{$uploadedFile['msg']}</p>" );
							}
						}
					}
				}
			} elseif ( strpos( $key, 'wpAllDestFile' ) !== false ) {
				// upload and glue in images that are within the article content too
				// ashley 11 December 2019: we're not even using these three vars and the last
				// line is causing E_NOTICEs so I'll just optimize these away...
				// $image_value = [];
				// $postfix = substr( $key, 13 );
				// $image_value['watchthis'] = $_POST['wpWatchthis' . $postfix];

				// slightly hacky but w/e
				$request->setVal( 'wpInFix', 'All' );

				$uploadForm = new CreatePageImageUploadForm();
				$uploadForm->initializeFromRequest( $request );
				$uploadForm->mComment = wfMessage( 'createpage-uploaded-from' )->text();
				$uploadedFile = $uploadForm->execute();
				if ( $uploadedFile['error'] == 0 ) {
					$all_images[] = ( $uploadedFile['msg'] !== 'cp_no_uploaded_file' ? $uploadedFile['msg'] : '' );
				} else {
					$all_images[] = '<!---imageupload--->';
					if ( $uploadedFile['once'] ) {
						if ( !$error_once ) {
							if ( !$preview ) {
								$out->addHTML( "<p class='error'>{$uploadedFile['msg']}</p>" );
							}
						}
						$error_once = true;
					} else {
						if ( !$preview ) {
							$out->addHTML( "<p class='error'>{$uploadedFile['msg']}</p>" );
						}
					}
				}
			}
		}

		if ( is_array( $all_images ) ) {
			// glue in images, replacing all image tags with content
			foreach ( $all_images as $myImage ) {
				if ( $myImage != '<!---imageupload--->' ) {
					$text = $this->str_replace_once(
						'<!---imageupload--->',
						'[[' . $myImage . '|thumb]]',
						$text
					);
				}
			}
		}

		if ( isset( $_POST['wpInfoboxValue'] ) ) {
			$text = $this->glueInfobox( $infoboxes, $_POST['wpInfoboxValue'] ) . $text;
		}

		if ( isset( $_POST['wpCategoryTextarea'] ) ) {
			$text .= $this->glueCategories( $categories, $_POST['wpCategoryTextarea'] );
		}

		return $text;
	}

	// by jmack@parhelic.com from php.net
	private function str_replace_once( $search, $replace, $subject ) {
		if ( ( $pos = strpos( $subject, $search ) ) !== false ) {
			$ret = substr( $subject, 0, $pos ) . $replace . substr( $subject, $pos + strlen( $search ) );
		} else {
			$ret = $subject;
		}
		return $ret;
	}
}
