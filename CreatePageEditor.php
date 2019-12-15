<?php
/**
 * @file
 * @ingroup Extensions
 * @author Bartek Łapiński <bartek@wikia-inc.com>
 * @copyright Copyright © 2007 Bartek Łapiński, Wikia Inc.
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

// all editor-related functions will go there
abstract class CreatePageEditor {
	public $mTemplate;

	function __construct( $template ) {
		$this->mTemplate = $template;
	}

	abstract public function generateForm();
	abstract public function glueArticle();
}

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
		global $wgOut, $wgUser, $wgRequest;

		$optional_sections = [];

		foreach ( $_POST as $key => $value ) {
			if ( strpos( $key, 'wpOptionalInput' ) !== false ) {
				$optional_sections[] = str_replace( 'wpOptionalInput', '', $key );
			}
		}
		if ( !$content ) {
			$title = Title::newFromText( 'Createplate-' . $this->mTemplate, NS_MEDIAWIKI );
			if ( $title->exists() ) {
				$rev = Revision::newFromTitle( $title );
				// @todo FIXME: $rev can be null, at least theoretically --ashley, 8 December 2019
				$me = CreateMultiPage::multiEditParse( 10, 10, '?', ContentHandler::getContentText( $rev->getContent() ), $optional_sections );
			} else {
				$me = CreateMultiPage::multiEditParse( 10, 10, '?', '<!---blanktemplate--->' );
			}
		} else {
			$me = CreateMultiPage::multiEditParse( 10, 10, '?', $content, $optional_sections );
		}

		$captchaForm = '';
		$passCaptcha = true;
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
				!$captcha->canSkipCaptcha( $wgUser,
					\MediaWiki\MediaWikiServices::getInstance()->getMainConfig() )
			) {
				$formInformation = $captcha->getFormInformation();
				$formMetainfo = $formInformation;
				unset( $formMetainfo['html'] );
				$captcha->addFormInformationToOutput( $wgOut, $formMetainfo );
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
			if ( $wgUser->getOption( 'watchcreations' ) ) {
				$watchThisCheck = true;
			}
			if ( $wgUser->getOption( 'minordefault' ) ) {
				$minorEditCheck = true;
			}
		} else {
			if ( $wgRequest->getCheck( 'wpWatchthis' ) ) {
				$watchThisCheck = true;
			}
			if ( $wgRequest->getCheck( 'wpMinoredit' ) ) {
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
				'summaryVal' => $wgRequest->getVal( 'wpSummary' ) ?: '',
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

		$wgOut->addHTML( $html );
		*/
		$wgOut->addHTML( '
			<div id="cp-restricted">
			<div id="createpageoverlay">
				<div class="hd"></div>
				<div class="bd"></div>
				<div class="ft"></div>
			</div>
		');

		$wgOut->addHTML( "<div id=\"cp-multiedit\">{$me}</div>" );
		// check for already submitted values - for a preview, for example
		$summaryVal = '';
		if ( $wgRequest->getVal( 'wpSummary' ) != '' ) {
			$summaryVal = $wgRequest->getVal( 'wpSummary' );
		}
		if ( $this->mInitial ) {
			if ( $wgUser->getOption( 'watchcreations' ) ) {
				$watchThisCheck = 'checked="checked"';
			} else {
				$watchThisCheck = '';
			}

			if ( $wgUser->getOption( 'minordefault' ) ) {
				$minorEditCheck = 'checked="checked"';
			} else {
				$minorEditCheck = '';
			}
		} else {
			$watchThisCheck = '';
			$minorEditCheck = '';
			if ( $wgRequest->getCheck( 'wpWatchthis' ) ) {
				$watchThisCheck = 'checked="checked"';
			}
			if ( $wgRequest->getCheck( 'wpMinoredit' ) ) {
				$minorEditCheck = 'checked="checked"';
			}
		}

		global $wgRightsText;
		$copywarn = "<div id=\"editpage-copywarn\">\n" .
					wfMessage( $wgRightsText ? 'copyrightwarning' : 'copyrightwarning2',
							'[[' . wfMessage( 'copyrightpage' )->inContentLanguage()->text() . ']]',
					$wgRightsText )->text() . "\n</div>";
		$wgOut->addWikiTextAsInterface( $copywarn );

		$editSummary = '<span id="wpSummaryLabel"><label for="wpSummary">' .
			wfMessage( 'summary' )->escaped() . "</label></span>\n<input type='text' value=\"" .
			htmlspecialchars( $summaryVal ) . '" name="wpSummary" id="wpSummary" maxlength="200" size="60" /><br />';

		$checkboxHTML = '<input id="wpMinoredit" type="checkbox" accesskey="i" value="1" name="wpMinoredit" ' . $minorEditCheck . '/>' . "\n" .
		'<label accesskey="i" title="' . wfMessage( 'tooltip-minoredit' )->escaped() . ' [alt-shift-i]" for="wpMinoredit">' . wfMessage( 'minoredit' )->escaped() . '</label>';
		$checkboxHTML .= '<input id="wpWatchthis" type="checkbox" accesskey="w" value="1" name="wpWatchthis" ' . $watchThisCheck . '/>' . "\n" .
		'<label accesskey="w" title="' . wfMessage( 'tooltip-watch' )->escaped() . ' [alt-shift-w]" for="wpWatchthis">' . wfMessage( 'watchthis' )->escaped() . '</label>';

		$wgOut->addHTML(
			'<div id="createpagebottom">' .
			$editSummary .
			$captchaForm .
			$checkboxHTML .
			'</div>'
		);

		$wgOut->addHTML('
			<div class="actionBar buttonBar">
		<input type="submit" id="wpSave" name="wpSave" value="' . wfMessage( 'createpage-save' )->escaped() . '" class="button color1" />
		<input type="submit" id="wpPreview" name="wpPreview" value="' . wfMessage( 'preview' )->escaped() . '" class="button color1" />
		<input type="submit" id="wpCancel" name="wpCancel" value="' . wfMessage( 'cancel' )->escaped() . '" class="button color1" />
		</div>'
		);

		$wgOut->addHTML(
			Html::hidden( 'wpUnicodeCheck', EditPage::UNICODE_CHECK ) .
			// Marker for detecting truncated form data. This must be the last parameter sent in order to be of use, so do not move me.
			'<input type="hidden" value="true" name="wpUltimateParam" />'
		);

		// stuff for red links - bottom edittools, to be more precise
		if ( $this->mRedLinked && ( $this->mTemplate == 'Blank' ) ) {
			$wgOut->addHTML( '<div id="createpage_editTools" class="mw-editTools">' );
			$wgOut->addWikiTextAsInterface( wfMessage( 'edittools' )->inContentLanguage()->text() );
			$wgOut->addHTML( '</div>' );
		}

		// ashley 10 December 2019: HTML validation fix
		// the old order (</form></div>) was apparently just wrong, even if it rendered
		// just as this one does, but it made W3C Validator barf
		$wgOut->addHTML( "\n</div>\n<!-- #cp-restricted --></form>\n" );
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
		global $wgRequest, $wgOut;

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
					$optionals = explode( ',', $value  );
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

				if ( $wgRequest->getVal( 'wpNoUse' . $postfix ) == 'Yes' ) {
					$infoboxes[] = $wgRequest->getVal( 'wpInfImg' . $postfix );
				} else {
					// $image_value['watchthis'] = $_POST['wpWatchthis' . $postfix];

					// store these for the upload class to use
					$wgRequest->setVal( 'wpPostFix', $postfix );
					$wgRequest->setVal( 'Createtitle', $wgRequest->getText( 'Createtitle' ) );

					// do the real upload
					$uploadform = new CreatePageImageUploadForm();
					$uploadform->initializeFromRequest( $wgRequest );
					// some of the values are fixed, we have no need to add them to the form itself
					$uploadform->mComment = wfMessage( 'createpage-uploaded-from' )->text();
					$uploadedfile = $uploadform->execute();

					if ( $uploadedfile['error'] == 0 ) {
						// This logic is giving me a headache, but allow me to try to explain it
						// to a future me and for the future generations...
						// When the user chose not to upload a file despite an infobox (etc.) having
						// the possibility for them to do that, $uploadedfile['msg'] is set to
						// 'cp_no_uploaded_file'. If that is the case, we need to do essentially
						// $infoboxes[] = '<!---imageupload--->'; (for page preview) to ensure that
						// the "Insert Image" button shows up when the user is previewing their page,
						// but they chose not to upload a file. They may change their mind, you know!
						$infoboxes[] = ( $uploadedfile['msg'] !== 'cp_no_uploaded_file' ? $uploadedfile['msg'] : '<!---imageupload--->' );
					} else {
						$infoboxes[] = '<!---imageupload--->';
						if ( $uploadedfile['once'] ) {
							if ( !$error_once ) {
								if ( !$preview ) {
									// certainly they'll notice things on preview
									$wgOut->addHTML( "<p class='error'>{$uploadedfile['msg']}</p>" );
								}
							}
							$error_once = true;
						} else {
							if ( !$preview ) {
								$wgOut->addHTML( "<p class='error'>{$uploadedfile['msg']}</p>" );
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

				$wgRequest->setVal( 'wpInFix', 'All' ); // slightly hacky but w/e
				$uploadform = new CreatePageImageUploadForm();
				$uploadform->initializeFromRequest( $wgRequest );
				$uploadform->mComment = wfMessage( 'createpage-uploaded-from' )->text();
				$uploadedfile = $uploadform->execute();
				if ( $uploadedfile['error'] == 0 ) {
					$all_images[] = ( $uploadedfile['msg'] !== 'cp_no_uploaded_file' ? $uploadedfile['msg'] : '' );
				} else {
					$all_images[] = '<!---imageupload--->';
					if ( $uploadedfile['once'] ) {
						if ( !$error_once ) {
							if ( !$preview ) {
								$wgOut->addHTML( "<p class='error'>{$uploadedfile['msg']}</p>" );
							}
						}
						$error_once = true;
					} else {
						if ( !$preview ) {
							$wgOut->addHTML( "<p class='error'>{$uploadedfile['msg']}</p>" );
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
