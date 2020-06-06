<?php
/**
 * This class takes care for the createplate loader form
 *
 * @file
 */
class CreatePageCreateplateForm {
	/**
	 * @var string $mCreateplatesLocation Name of the MediaWiki: msg containing the
	 *   list of createplates (without the MediaWiki: namespace)
	 */
	public $mCreateplatesLocation;

	/**
	 * @var string $mTitle User-supplied name of the page to be created
	 */
	public $mTitle;

	/**
	 * @var string $mCreateplate Name of the createplate to use, e.g. "character"
	 *   for [[MediaWiki:Createplate-character]]
	 */
	public $mCreateplate;

	/**
	 * @var bool $mRedLinked Are we previewing a page in the "red link" mode?
	 */
	public $mRedLinked;

	/**
	 * Constructor
	 *
	 * @param mixed|null $par Parameter passed to the Special:CreatePage page in the URL, if any
	 */
	public function __construct( $par = null ) {
		global $wgRequest;

		$this->mCreateplatesLocation = 'Createplate-list';

		if ( $wgRequest->getVal( 'action' ) == 'submit' ) {
			$this->mTitle = $wgRequest->getVal( 'Createtitle' );
			$this->mCreateplate = $wgRequest->getVal( 'createplates' );
			// for preview in red link mode
			if ( $wgRequest->getCheck( 'Redlinkmode' ) ) {
				$this->mRedLinked = true;
			}
		} else {
			// title override
			if ( $wgRequest->getVal( 'Createtitle' ) != '' ) {
				$this->mTitle = $wgRequest->getVal( 'Createtitle' );
				$this->mRedLinked = true;
			} else {
				$this->mTitle = '';
			}
			// URL override
			$this->mCreateplate = $wgRequest->getVal( 'createplates' );
		}
	}

	private function makePrefix( $title ) {
		$title = str_replace( '_', ' ', $title );
		return $title;
	}

	// show form
	public function showForm( $err, $content_prev = false, $formCallback = null ) {
		global $wgOut, $wgUser, $wgRequest;

		if ( $wgRequest->getCheck( 'wpPreview' ) ) {
			$wgOut->setPageTitle( wfMessage( 'preview' )->text() );
		} else {
			if ( $this->mRedLinked ) {
				$wgOut->setPageTitle( wfMessage( 'editing', $this->makePrefix( $this->mTitle ) )->text() );
			} else {
				$wgOut->setPageTitle( wfMessage( 'createpage-title' )->text() );
			}
		}

		$token = htmlspecialchars( $wgUser->getEditToken() );
		$titleObj = SpecialPage::getTitleFor( 'CreatePage' );
		$action = htmlspecialchars( $titleObj->getLocalURL( 'action=submit' ) );

		if ( $wgRequest->getCheck( 'wpPreview' ) ) {
			$wgOut->addHTML(
				'<div class="previewnote"><p>' .
				wfMessage( 'previewnote' )->text() .
				'</p></div>'
			);
		} else {
			$wgOut->addHTML( wfMessage( 'createpage-title-additional' )->text() );
		}

		if ( $err != '' ) {
			$wgOut->setSubtitle( wfMessage( 'formerror' )->text() );
			$wgOut->addHTML( "<p class='error'>{$err}</p>\n" );
		}

		// show stuff like on normal edit page, but just for red links
		if ( $this->mRedLinked ) {
			$helpLink = wfExpandUrl( Skin::makeInternalOrExternalUrl(
				wfMessage( 'helppage' )->inContentLanguage()->text()
			) );
			if ( $wgUser->isLoggedIn() ) {
				$wgOut->wrapWikiMsg(
					// Suppress the external link icon, consider the help URL an internal one
					"<div class=\"mw-newarticletext plainlinks\">\n$1\n</div>",
					[
						'newarticletext',
						$helpLink
					]
				);
			} else {
				$wgOut->wrapWikiMsg(
					// Suppress the external link icon, consider the help URL an internal one
					"<div class=\"mw-newarticletextanon plainlinks\">\n$1\n</div>",
					[
						'newarticletextanon',
						$helpLink
					]
				);
			}
			if ( $wgUser->isAnon() ) {
				if ( !$wgRequest->getCheck( 'wpPreview' ) ) {
					$returnToQuery = array_diff_key(
						$wgRequest->getValues(),
						[
							'title' => false,
							'returnto' => true,
							'returntoquery' => true
						]
					);
					$returnToPageTitle = $wgRequest->getVal( 'title' );
					// Note: in red link mode, regular redlink URLs behave as if they were Special:CreatePage.
					// That's why returnto is not Special:CreatePage but the page title of the new page
					// to be created.
					$wgOut->wrapWikiMsg(
						"<div id='mw-anon-edit-warning' class='warningbox'>\n$1\n</div>",
						[ 'anoneditwarning',
							// Log-in link
							SpecialPage::getTitleFor( 'Userlogin' )->getFullURL( [
								'returnto' => Title::newFromText( $returnToPageTitle )->getPrefixedDBkey(),
								'returntoquery' => wfArrayToCgi( $returnToQuery ),
							] ),
							// Sign-up link
							SpecialPage::getTitleFor( 'CreateAccount' )->getFullURL( [
								'returnto' => Title::newFromText( $returnToPageTitle )->getPrefixedDBkey(),
								'returntoquery' => wfArrayToCgi( $returnToQuery ),
							] )
						]
					);
				} else {
					$wgOut->wrapWikiMsg(
						"<div id=\"mw-anon-preview-warning\" class=\"warningbox\">\n$1</div>",
						'anonpreviewwarning'
					);
				}
			}
		}

		// Add CSS & JS
		$wgOut->addModuleStyles( 'ext.createAPage.styles' );
		$wgOut->addModules( 'ext.createAPage' );

		// Add WikiEditor to the textarea(s) if enabled for the current user
		if ( ExtensionRegistry::getInstance()->isLoaded( 'WikiEditor' ) && $wgUser->getOption( 'usebetatoolbar' ) ) {
			$wgOut->addModules( 'ext.createAPage.wikiEditor' );
		}

		// @todo This was originally used by the Wikia CreatePage ([sic]! it's different from
		// _this_ extension) extension, which was once a part of the Wikiwyg extension
		// (super legacy WYSIWYG editing extension that was relevant circa 2007 or so)
		// Nothing in CreateAPage seems to use this, i.e. this element will always
		// remain hidden. Can we safely just ditch this? --ashley, 10 December 2019
		$alternateLink = '<a href="#" id="createapage-go-to-normal-editor">' .
			wfMessage( 'createpage-here' )->text() . '</a>';
		$wgOut->addHTML(
			'<div id="createpage_subtitle" style="display:none">' .
				wfMessage( 'createpage-alternate-creation', $alternateLink )->text() .
			'</div>'
		);

		if ( $wgRequest->getCheck( 'wpPreview' ) ) {
			$this->showPreview( $content_prev, $wgRequest->getVal( 'Createtitle' ) );
		}

		$html = "
<form name=\"createpageform\" enctype=\"multipart/form-data\" method=\"post\" action=\"{$action}\" id=\"createpageform\">
	<div id=\"createpage_messenger\" style=\"display:none; color:red\"></div>
		<noscript>
		<style type=\"text/css\">
			#loading_mesg, #image_upload {
				display: none;
			}
		</style>
		</noscript>";

		$html .= '
		<input type="hidden" name="wpEditToken" value="' . $token . '" />
		<input type="hidden" name="wpCreatePage" value="true" />';

		$wgOut->addHTML( $html );
		// adding this for CAPTCHAs and the like
		if ( is_callable( $formCallback ) ) {
			call_user_func_array( $formCallback, [ &$wgOut ] );
		}

		$parsedTemplates = $this->getCreateplates();
		$showField = '';
		if ( !$parsedTemplates ) {
			$showField = ' style="display: none";';
		}

		if ( !$wgRequest->getCheck( 'wpPreview' ) ) {
			$wgOut->addHTML(
				'<fieldset id="cp-chooser-fieldset"' . $showField . '>
				<legend>' . wfMessage( 'createpage-choose-createplate' )->text() .
				'<span>[<a id="cp-chooser-toggle" title="toggle" href="#">'
				. wfMessage( 'createpage-hide' )->text() . '</a>]</span>
				</legend>' . "\n"
			);
			$wgOut->addHTML( '<div id="cp-chooser" style="display: block;">' . "\n" );
		}

		$this->produceRadioList( $parsedTemplates );
	}

	/**
	 * Get the list of createplates from a MediaWiki namespace page,
	 * parse the content into an array and return it.
	 *
	 * @return array|bool Array on success, boolean false if the message is empty
	 */
	private function getCreateplates() {
		$createplates_txt = wfMessage( $this->mCreateplatesLocation )->inContentLanguage()->text();
		if ( $createplates_txt != '' ) {
			$lines = preg_split( "/[\n]+/", $createplates_txt );
		}

		$createplates = [];
		if ( !empty( $lines ) ) {
			// each createplate is listed in a new line, has two required and one optional
			// parameter, all separated by pipes
			foreach ( $lines as $line ) {
				if ( preg_match( "/^[^\|]+\|[^\|]+\|[^\|]+$/", $line ) ) {
					// three parameters
					$line_pars = preg_split( "/\|/", $line );
					$createplates[] = [
						'page' 	=> $line_pars[0],
						'label' => $line_pars[1],
						'preview' => $line_pars[2]
					];
				} elseif ( preg_match( "/^[^\|]+\|[^\|]+$/", $line ) ) {
					// two parameters
					$line_pars = preg_split( "/\|/", $line );
					$createplates[] = [
						'page' 	=> $line_pars[0],
						'label' => $line_pars[1]
					];
				}
			}
		}

		if ( empty( $createplates ) ) {
			return false;
		} else {
			return $createplates;
		}
	}

	// return checked createplate
	private function getChecked( $createplate, $current, &$checked ) {
		if ( !$createplate ) {
			if ( !$checked ) {
				$this->mCreateplate = $current;
				$checked = true;
				return 'checked';
			}
			return '';
		} else {
			if ( $createplate == $current ) {
				$this->mCreateplate = $current;
				return 'checked';
			} else {
				return '';
			}
		}
	}

	/**
	 * Produce a list of radio buttons from the given createplate array and
	 * output the generated HTML.
	 *
	 * @param array $createplates Array of createplates
	 */
	private function produceRadioList( $createplates ) {
		global $wgOut, $wgRequest, $wgServer, $wgScript;

		// this checks radio buttons when we have no JavaScript...
		$selected = false;
		if ( $this->mCreateplate != '' ) {
			$selected = $this->mCreateplate;
		}
		$checked = false;
		$check = [];
		foreach ( $createplates as $createplate ) {
			$check[$createplate['page']] = $this->getChecked(
				$selected,
				$createplate['page'],
				$checked
			);
		}

		if ( $this->mRedLinked ) {
			global $wgUser;
			$parser = MediaWiki\MediaWikiServices::getInstance()->getParser();
			// @todo FIXME: should probably be $parserOptions = ParserOptions::newCanonical();
			// also the second line does nothing as the method is <s>deprecated</s> gone as of MW 1.34rc1
			$parserOptions = ParserOptions::newFromUser( $wgUser );
			// $parserOptions->setEditSection( false );
			$rtitle = Title::newFromText( $this->mTitle );
			$parsedInfo = $parser->parse(
				wfMessage( 'createpage-about-info' )->text(),
				$rtitle,
				$parserOptions
			);
			// @todo FIXME/CHECKME: Probably should _not_ use mText, @see showPreview() method
			$aboutInfo = str_replace( '</p>', '', $parsedInfo->mText );
			$aboutInfo .= wfMessage(
				'createpage-advanced-text',
				'<a href="' . $wgServer . $wgScript . '" id="wpAdvancedEdit">' .
					wfMessage( 'createpage-advanced-edit' )->text() . '</a>'
			)->text() . '</p>';
		} else {
			$aboutInfo = '';
		}

		$tmpl = new EasyTemplate( __DIR__ . '/../templates/' );
		$tmpl->set_vars( [
			'data' => $createplates,
			'selected' => $check,
			'createtitle' => $this->makePrefix( $this->mTitle ),
			'ispreview' => $wgRequest->getCheck( 'wpPreview' ),
			'isredlink' => $this->mRedLinked,
			'aboutinfo' => $aboutInfo,
		] );

		$wgOut->addHTML( $tmpl->render( 'templates-list' ) );
	}

	/**
	 * Check whether the given page exists.
	 *
	 * @param string $given Name of the page whose existence we're checking
	 * @param bool $ajax Are we in AJAX mode? Defaults to false.
	 * @return string|bool Error message if the title is missing, the page
	 *                exists and we're not in AJAX mode
	 */
	public function checkArticleExists( $given, $ajax = false ) {
		global $wgOut;

		if ( $ajax ) {
			$wgOut->setArticleBodyOnly( true );
		}

		if ( empty( $given ) && !$ajax ) {
			return wfMessage( 'createpage-give-title' )->text();
		}

		$title = Title::newFromText( $given );
		if ( is_object( $title ) ) {
			$dbr = wfGetDB( DB_REPLICA );
			$exists = $dbr->selectField(
				'page',
				'page_title',
				[
					'page_title' => $title->getDBkey(),
					'page_namespace' => $title->getNamespace()
				],
				__METHOD__
			);
			if ( $exists != '' ) {
				if ( $ajax ) {
					$wgOut->addHTML( 'pagetitleexists' );
				} else {
					// Mimick the way AJAX version displays things and use the
					// same two messages. 2 are needed for full i18n support.
					return wfMessage( 'createpage-article-exists' )->text() . ' ' .
						// @todo Use LinkRenderer instead.
						Linker::linkKnown( $title, '', [], [ 'action' => 'edit' ] ) .
						wfMessage( 'createpage-article-exists2' )->text();
				}
			}
			if ( !$ajax ) {
				return false;
			}
		} else {
			if ( !$ajax ) {
				return wfMessage( 'createpage-title-invalid' )->escaped();
			}
		}
	}

	/**
	 * Try to submit the form.
	 *
	 * @return bool|void False on failure, nothing on success; if
	 *                everything went well, the user is redirected to their new
	 *                page
	 */
	public function submitForm() {
		global $wgOut, $wgRequest, $wgServer, $wgScript, $wgUser;

		$mainform = new CreatePageCreateplateForm();

		// check if we are editing in red link mode
		if ( $wgRequest->getCheck( 'wpSubmitCreateplate' ) ) {
			$mainform->showForm( '' );
			$mainform->showCreateplate();
			return false;
		} else {
			$valid = $this->checkArticleExists( $wgRequest->getVal( 'Createtitle' ) );
			if ( $valid != '' ) {
				// no title? this means overwriting Main Page...
				$mainform->showForm( $valid );
				$editor = new CreatePageMultiEditor( $this->mCreateplate );
				$editor->generateForm( $editor->glueArticle() );
				return false;
			}

			if ( $wgRequest->getVal( 'wpSave' ) && $wgRequest->wasPosted() && $wgUser->matchEditToken( $wgRequest->getVal( 'wpEditToken' ) ) ) {
				$editor = new CreatePageMultiEditor( $this->mCreateplate );
				$rtitle = Title::newFromText( $wgRequest->getVal( 'Createtitle' ) );
				// @todo FIXME/CHECKME: should prolly be WikiPage::factory( $rtitle )? but do we then need the article ID? --ashley, 8 December 2019
				$rarticle = new Article( $rtitle, $rtitle->getArticleID() );
				$editpage = new EditPage( $rarticle );
				$editpage->mTitle = $rtitle;
				$editpage->mArticle = $rarticle;

				// ashley 8 December 2019: need this so that edits don't fail due to wpUnicodeCheck being ''...
				$editpage->importFormData( $wgRequest );

				// Order matters! importFormData overwrites textbox1 so we must define it _after_ calling it, obviously
				$editpage->textbox1 = CreateMultiPage::unescapeBlankMarker( $editor->glueArticle() );

				$editpage->minoredit = $wgRequest->getCheck( 'wpMinoredit' );
				$editpage->watchthis = $wgRequest->getCheck( 'wpWatchthis' );
				$editpage->summary = $wgRequest->getVal( 'wpSummary' );

				$_SESSION['article_createplate'] = $this->mCreateplate;

				// pipe tags to pipes
				CreateAPageUtils::unescapeKnownMarkupTags( $editpage->textbox1 );

				$status = $editpage->attemptSave();

				// Redirect to the brand new page on success or in case of a failure, display
				// an error msg
				// This first loop has been copied from PostComment
				if ( !$status->isGood() ) {
					$errors = $status->getErrorsArray();
					$errorMsg = '';
					foreach ( $errors as $error ) {
						if ( is_array( $error ) ) {
							$errorMsg = count( $error ) ? $error[0] : '';
						}
					}
					// Hacks' galore continues...
					// $errorMsg can be 'hookaborted' (e.g. the SpamRegex ext., if a user enters
					// a SpamRegexed edit summary), but it can _also_ be something like
					// '<div class="errorbox">Incorrect or missing CAPTCHA.</div>'
					// Obviously 'hookaborted' is an i18n msg key and the latter is something
					// that should be output as-is...
					if ( !preg_match( '/</', $errorMsg ) ) {
						$errorMsg = wfMessage( $errorMsg )->text();
					}
					// This is literally copypasted from the wpPreview loop below
					// with one '' changed to $errorMsg, that's it
					$editor = new CreatePageMultiEditor( $this->mCreateplate, true );
					$content = $editor->glueArticle( true, false );
					$content_static = $editor->glueArticle( true );
					$mainform->showForm( $errorMsg, $content_static );
					$editor->generateForm( $content );
					return false;
				} elseif ( $status->value == EditPage::AS_SUCCESS_NEW_ARTICLE ) {
					$wgOut->redirect( Title::newFromText( $wgRequest->getVal( 'Createtitle' ) )->getFullURL() );
				}

				return false;
			} elseif ( $wgRequest->getVal( 'wpSave' ) && $wgRequest->wasPosted() && !$wgUser->matchEditToken( $wgRequest->getVal( 'wpEditToken' ) ) ) {
				// @todo Actually, do we even need this loop? Won't EditPage#attemptSave catch CSRF for us? --ashley, 10 December 2019
				// CSRF attempt?
				$errorMsg = wfMessage( 'sessionfailure' )->escaped();
				// This is literally copypasted from the wpPreview loop below
				// with one '' changed to $errorMsg, that's it
				$editor = new CreatePageMultiEditor( $this->mCreateplate, true );
				$content = $editor->glueArticle( true, false );
				$content_static = $editor->glueArticle( true );
				$mainform->showForm( $errorMsg, $content_static );
				$editor->generateForm( $content );
				return false;
			} elseif ( $wgRequest->getCheck( 'wpPreview' ) ) {
				$editor = new CreatePageMultiEditor( $this->mCreateplate, true );
				$content = $editor->glueArticle( true, false );
				$content_static = $editor->glueArticle( true );
				$mainform->showForm( '', $content_static );
				$editor->generateForm( $content );
				return false;
			} elseif ( $wgRequest->getCheck( 'wpAdvancedEdit' ) ) {
				$editor = new CreatePageMultiEditor( $this->mCreateplate );
				$content = CreateMultiPage::unescapeBlankMarker( $editor->glueArticle() );
				CreateAPageUtils::unescapeKnownMarkupTags( $content );
				$_SESSION['article_content'] = $content;
				$wgOut->redirect(
					$wgServer . $wgScript . '?title=' .
					$wgRequest->getVal( 'Createtitle' ) .
					'&action=edit&createpage=true'
				);
			} elseif ( $wgRequest->getCheck( 'wpImageUpload' ) ) {
				$mainform->showForm( '' );
				$editor = new CreatePageMultiEditor( $this->mCreateplate );
				$content = $editor->glueArticle();
				$editor->generateForm( $content );
			} elseif ( $wgRequest->getCheck( 'wpCancel' ) ) {
				if ( $wgRequest->getVal( 'Createtitle' ) != '' ) {
					$wgOut->redirect( $wgServer . $wgScript . '?title=' . $wgRequest->getVal( 'Createtitle' ) );
				} else {
					$wgOut->redirect( $wgServer . $wgScript );
				}
			}
		}
	}

	/**
	 * Display the preview in another div
	 *
	 * @param string $content Wikitext content to parse
	 * @param string $title Page title of the page we're creating
	 */
	private function showPreview( $content, $title ) {
		global $wgOut, $wgUser;

		// @todo FIXME: should probably be $parserOptions = ParserOptions::newCanonical();
		// also the second line does nothing as the method is <s>deprecated</s> gone as of MW 1.34rc1
		$parserOptions = ParserOptions::newFromUser( $wgUser );
		// $parserOptions->setEditSection( false );
		$rtitle = Title::newFromText( $title );

		if ( is_object( $rtitle ) ) {
			CreateAPageUtils::unescapeKnownMarkupTags( $content );
			$parser = MediaWiki\MediaWikiServices::getInstance()->getParser();
			$pre_parsed = $parser->preSaveTransform(
				$content,
				$rtitle,
				$wgUser,
				$parserOptions,
				true
			);
			$output = $parser->parse( $pre_parsed, $rtitle, $parserOptions );
			$wgOut->addParserOutputMetadata( $output );
			// @todo CHECKME: Used to be $output->mText but that would cause parser
			// internal stuff like mw:toc being exposed and thus it would seem to the
			// average user who's previewing stuff that headlines etc. are duplicated.
			// --ashley, 8 December 2019
			$previewableText = $wgOut->parseAsContent( $pre_parsed );
			$wgOut->addHTML(
				"<div id=\"createpagepreview\">
					$previewableText
					<div id=\"createpage_preview_delimiter\" class=\"actionBar actionBarStrong\">" .
						wfMessage( 'createpage-preview-end' )->escaped() .
					'</div>
				</div>'
			);
		}
	}

	public function showCreateplate( $isInitial = false ) {
		if ( $this->mCreateplate ) {
			$editor = new CreatePageMultiEditor( $this->mCreateplate );
		} else {
			$editor = new CreatePageMultiEditor( 'Blank' );
		}
		$editor->mRedLinked = false;
		if ( $this->mRedLinked ) {
			$editor->mRedLinked = true;
		}
		$editor->mInitial = false;
		if ( $isInitial ) {
			$editor->mInitial = true;
		}
		$editor->generateForm();
	}
}
