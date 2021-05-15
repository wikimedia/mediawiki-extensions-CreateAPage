<?php
/**
 * This class takes care for the createplate loader form
 *
 * @file
 */
class CreatePageCreateplateForm {
	/**
	 * @var string Name of the MediaWiki: msg containing the
	 *   list of createplates (without the MediaWiki: namespace)
	 */
	public $mCreateplatesLocation;

	/**
	 * @var string User-supplied name of the page to be created
	 */
	public $mTitle;

	/**
	 * @var string Name of the createplate to use, e.g. "character"
	 *   for [[MediaWiki:Createplate-character]]
	 */
	public $mCreateplate;

	/**
	 * @var bool Are we previewing a page in the "red link" mode?
	 */
	public $mRedLinked;

	/**
	 * @var OutputPage
	 */
	public $output;

	/**
	 * @var WebRequest
	 */
	public $request;

	/**
	 * @var User
	 */
	public $user;

	/**
	 * Constructor
	 *
	 * @param mixed|null $par Parameter passed to the Special:CreatePage page in the URL, if any
	 */
	public function __construct( $par = null ) {
		$request = $this->getRequest();

		$this->mCreateplatesLocation = 'Createplate-list';

		if ( $request->getVal( 'action' ) == 'submit' ) {
			$this->mTitle = $request->getVal( 'Createtitle' );
			$this->mCreateplate = $request->getVal( 'createplates' );
			// for preview in red link mode
			if ( $request->getCheck( 'Redlinkmode' ) ) {
				$this->mRedLinked = true;
			}
		} else {
			// title override
			if ( $request->getVal( 'Createtitle' ) != '' ) {
				$this->mTitle = $request->getVal( 'Createtitle' );
				$this->mRedLinked = true;
			} else {
				$this->mTitle = '';
			}
			// URL override
			$this->mCreateplate = $request->getVal( 'createplates' );
		}
	}

	/**
	 * Hacky setter for setting global objects
	 *
	 * @param string $var Class member variable name ('output', 'request' or 'user')
	 * @param OutputPage|User|WebRequest $value
	 */
	public function set( $var, $value ) {
		$this->$var = $value;
	}

	/**
	 * Get the OutputPage object to use here
	 *
	 * @see set()
	 * @return OutputPage
	 */
	public function getOutput() {
		if ( isset( $this->output ) && $this->output instanceof OutputPage ) {
			return $this->output;
		} else {
			return RequestContext::getMain()->getOutput();
		}
	}

	/**
	 * Get the WebRequest object to use here
	 *
	 * @see set()
	 * @return WebRequest
	 */
	public function getRequest() {
		if ( isset( $this->request ) && $this->request instanceof WebRequest ) {
			return $this->request;
		} else {
			return RequestContext::getMain()->getRequest();
		}
	}

	/**
	 * Get the User object to use here
	 *
	 * @see set()
	 * @return User
	 */
	public function getUser() {
		if ( isset( $this->user ) && $this->user instanceof User ) {
			return $this->user;
		} else {
			return RequestContext::getMain()->getUser();
		}
	}

	/**
	 * @param string $title
	 * @return string
	 */
	private function makePrefix( $title ) {
		$title = str_replace( '_', ' ', $title );
		return $title;
	}

	/**
	 * Show the main editor form
	 *
	 * @param string $err Error message to show, if any; should be pre-escaped, HTML-safe
	 * @param string $content_prev Content to preview when previewing
	 * @param callable|null $formCallback Used for CAPTCHAs and the like, allegedly;
	 *   not sure if that's true anymore as of 2021
	 */
	public function showForm( $err = '', $content_prev = '', $formCallback = null ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		if ( $request->getCheck( 'wpPreview' ) ) {
			$out->setPageTitle( wfMessage( 'preview' )->text() );
		} else {
			if ( $this->mRedLinked ) {
				$out->setPageTitle( wfMessage( 'editing', $this->makePrefix( $this->mTitle ) )->text() );
			} else {
				$out->setPageTitle( wfMessage( 'createpage-title' )->text() );
			}
		}

		$token = htmlspecialchars( $user->getEditToken() );
		$titleObj = SpecialPage::getTitleFor( 'CreatePage' );
		$action = htmlspecialchars( $titleObj->getLocalURL( 'action=submit' ) );

		if ( $request->getCheck( 'wpPreview' ) ) {
			$out->addHTML(
				'<div class="previewnote"><p>' .
				wfMessage( 'previewnote' )->parse() .
				'</p></div>'
			);
		} else {
			$out->addHTML( wfMessage( 'createpage-title-additional' )->escaped() );
		}

		if ( $err != '' ) {
			$out->setSubtitle( wfMessage( 'formerror' )->text() );
			$out->addHTML( "<p class='error'>{$err}</p>\n" );
		}

		// show stuff like on normal edit page, but just for red links
		if ( $this->mRedLinked ) {
			$helpLink = wfExpandUrl( Skin::makeInternalOrExternalUrl(
				wfMessage( 'helppage' )->inContentLanguage()->text()
			) );
			if ( $user->isRegistered() ) {
				$out->wrapWikiMsg(
					// Suppress the external link icon, consider the help URL an internal one
					"<div class=\"mw-newarticletext plainlinks\">\n$1\n</div>",
					[
						'newarticletext',
						$helpLink
					]
				);
			} else {
				$out->wrapWikiMsg(
					// Suppress the external link icon, consider the help URL an internal one
					"<div class=\"mw-newarticletextanon plainlinks\">\n$1\n</div>",
					[
						'newarticletextanon',
						$helpLink
					]
				);
			}
			if ( $user->isAnon() ) {
				if ( !$request->getCheck( 'wpPreview' ) ) {
					$returnToQuery = array_diff_key(
						$request->getValues(),
						[
							'title' => false,
							'returnto' => true,
							'returntoquery' => true
						]
					);
					$returnToPageTitle = $request->getVal( 'title' );
					// Note: in red link mode, regular redlink URLs behave as if they were Special:CreatePage.
					// That's why returnto is not Special:CreatePage but the page title of the new page
					// to be created.
					$out->wrapWikiMsg(
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
					$out->wrapWikiMsg(
						"<div id=\"mw-anon-preview-warning\" class=\"warningbox\">\n$1</div>",
						'anonpreviewwarning'
					);
				}
			}
		}

		// Add CSS & JS
		$out->addModuleStyles( 'ext.createAPage.styles' );
		$out->addModules( 'ext.createAPage' );

		// Add WikiEditor to the textarea(s) if enabled for the current user
		if ( ExtensionRegistry::getInstance()->isLoaded( 'WikiEditor' ) && $user->getOption( 'usebetatoolbar' ) ) {
			$out->addModules( 'ext.createAPage.wikiEditor' );
		}

		if ( $request->getCheck( 'wpPreview' ) ) {
			$this->showPreview( $content_prev, $request->getVal( 'Createtitle' ) );
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

		$out->addHTML( $html );
		// adding this for CAPTCHAs and the like
		if ( is_callable( $formCallback ) ) {
			call_user_func_array( $formCallback, [ &$out ] );
		}

		$parsedTemplates = $this->getCreateplates();
		$showField = '';
		if ( !$parsedTemplates ) {
			$showField = ' style="display: none";';
		}

		if ( !$request->getCheck( 'wpPreview' ) ) {
			$out->addHTML(
				'<fieldset id="cp-chooser-fieldset"' . $showField . '>
				<legend>' . wfMessage( 'createpage-choose-createplate' )->escaped() .
				'<span>[<a id="cp-chooser-toggle" title="toggle" href="#">'
				. wfMessage( 'createpage-hide' )->escaped() . '</a>]</span>
				</legend>' . "\n"
			);
			$out->addHTML( '<div id="cp-chooser" style="display: block;">' . "\n" );
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

	/**
	 * Return checked createplate
	 */
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
		global $wgServer, $wgScript;

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
			$parser = MediaWiki\MediaWikiServices::getInstance()->getParser();
			// @todo FIXME: should probably be $parserOptions = ParserOptions::newCanonical();
			// also the second line does nothing as the method is <s>deprecated</s> gone as of MW 1.34rc1
			$parserOptions = ParserOptions::newFromUser( $this->getUser() );
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
			'ispreview' => $this->getRequest()->getCheck( 'wpPreview' ),
			'isredlink' => $this->mRedLinked,
			'aboutinfo' => $aboutInfo,
		] );

		$this->getOutput()->addHTML( $tmpl->render( 'templates-list' ) );
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
		if ( $ajax ) {
			$this->getOutput()->setArticleBodyOnly( true );
		}

		if ( empty( $given ) && !$ajax ) {
			return wfMessage( 'createpage-give-title' )->escaped();
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
					$this->getOutput()->addHTML( 'pagetitleexists' );
				} else {
					// Mimick the way AJAX version displays things and use the
					// same two messages. 2 are needed for full i18n support.
					return wfMessage( 'createpage-article-exists' )->escaped() . ' ' .
						// @todo Use LinkRenderer instead.
						Linker::linkKnown( $title, '', [], [ 'action' => 'edit' ] ) .
						wfMessage( 'createpage-article-exists2' )->escaped();
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
		global $wgServer, $wgScript;

		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		// check if we are editing in red link mode
		if ( $request->getCheck( 'wpSubmitCreateplate' ) ) {
			$this->showForm( '' );
			$this->showCreateplate();
			return false;
		} else {
			$valid = $this->checkArticleExists( $request->getVal( 'Createtitle' ) );
			if ( $valid != '' ) {
				// no title? this means overwriting Main Page...
				$this->showForm( $valid );
				$editor = new CreatePageMultiEditor( $this->mCreateplate );
				$editor->generateForm( $editor->glueArticle() );
				return false;
			}

			if ( $request->getVal( 'wpSave' ) && $request->wasPosted() ) {
				$hasError = false;
				$errorMsg = '';

				if ( !$user->matchEditToken( $request->getVal( 'wpEditToken' ) ) ) {
					// @todo Actually, do we even need this loop? Won't EditPage#attemptSave catch CSRF for us? --ashley, 10 December 2019
					// CSRF attempt?
					$hasError = true;
					$errorMsg = wfMessage( 'sessionfailure' )->escaped();
				}

				if ( ExtensionRegistry::getInstance()->isLoaded( 'ConfirmEdit' ) ) {
					$captcha = ConfirmEditHooks::getInstance();
					if ( !$captcha->passCaptchaFromRequest( $request, $user ) ) {
						$hasError = true;
						$errorMsg = wfMessage( 'captcha-edit-fail' )->escaped();
					}
				}

				if ( $hasError && $errorMsg !== '' ) {
					// This is literally copypasted from the wpPreview loop below
					// with one '' changed to $errorMsg, that's it
					$editor = new CreatePageMultiEditor( $this->mCreateplate, true );
					$content = $editor->glueArticle( true, false );
					$content_static = $editor->glueArticle( true );
					$this->showForm( $errorMsg, $content_static );
					$editor->generateForm( $content );
					return false;
				}

				$editor = new CreatePageMultiEditor( $this->mCreateplate );
				$rtitle = Title::newFromText( $request->getVal( 'Createtitle' ) );
				// @todo FIXME/CHECKME: should prolly be WikiPage::factory( $rtitle )? but do we then need the article ID? --ashley, 8 December 2019
				$rarticle = new Article( $rtitle, $rtitle->getArticleID() );
				$editpage = new EditPage( $rarticle );
				$editpage->mTitle = $rtitle;
				$editpage->mArticle = $rarticle;

				// ashley 8 December 2019: need this so that edits don't fail due to wpUnicodeCheck being ''...
				$editpage->importFormData( $request );

				// Order matters! importFormData overwrites textbox1 so we must define it _after_ calling it, obviously
				$editpage->textbox1 = CreateMultiPage::unescapeBlankMarker( $editor->glueArticle() );

				$editpage->minoredit = $request->getCheck( 'wpMinoredit' );
				$editpage->watchthis = $request->getCheck( 'wpWatchthis' );
				$editpage->summary = $request->getVal( 'wpSummary' );

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
						$errorMsg = wfMessage( $errorMsg )->escaped();
					}
					// This is literally copypasted from the wpPreview loop below
					// with one '' changed to $errorMsg, that's it
					$editor = new CreatePageMultiEditor( $this->mCreateplate, true );
					$content = $editor->glueArticle( true, false );
					$content_static = $editor->glueArticle( true );
					$this->showForm( $errorMsg, $content_static );
					$editor->generateForm( $content );
					return false;
				} elseif ( $status->value == EditPage::AS_SUCCESS_NEW_ARTICLE ) {
					$out->redirect( Title::newFromText( $request->getVal( 'Createtitle' ) )->getFullURL() );
				}

				return false;
			} elseif ( $request->getCheck( 'wpPreview' ) ) {
				$editor = new CreatePageMultiEditor( $this->mCreateplate, true );
				$content = $editor->glueArticle( true, false );
				$content_static = $editor->glueArticle( true );
				$this->showForm( '', $content_static );
				$editor->generateForm( $content );
				return false;
			} elseif ( $request->getCheck( 'wpAdvancedEdit' ) ) {
				$editor = new CreatePageMultiEditor( $this->mCreateplate );
				$content = CreateMultiPage::unescapeBlankMarker( $editor->glueArticle() );
				CreateAPageUtils::unescapeKnownMarkupTags( $content );
				$_SESSION['article_content'] = $content;
				$out->redirect(
					$wgServer . $wgScript . '?title=' .
					$request->getVal( 'Createtitle' ) .
					'&action=edit&createpage=true'
				);
			} elseif ( $request->getCheck( 'wpImageUpload' ) ) {
				$this->showForm( '' );
				$editor = new CreatePageMultiEditor( $this->mCreateplate );
				$content = $editor->glueArticle();
				$editor->generateForm( $content );
			} elseif ( $request->getCheck( 'wpCancel' ) ) {
				if ( $request->getVal( 'Createtitle' ) != '' ) {
					$out->redirect( $wgServer . $wgScript . '?title=' . $request->getVal( 'Createtitle' ) );
				} else {
					$out->redirect( $wgServer . $wgScript );
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
		$out = $this->getOutput();
		$user = $this->getUser();

		// @todo FIXME: should probably be $parserOptions = ParserOptions::newCanonical();
		// also the second line does nothing as the method is <s>deprecated</s> gone as of MW 1.34rc1
		$parserOptions = ParserOptions::newFromUser( $user );
		// $parserOptions->setEditSection( false );
		$rtitle = Title::newFromText( $title );

		if ( is_object( $rtitle ) ) {
			CreateAPageUtils::unescapeKnownMarkupTags( $content );
			$parser = MediaWiki\MediaWikiServices::getInstance()->getParser();
			$pre_parsed = $parser->preSaveTransform(
				$content,
				$rtitle,
				$user,
				$parserOptions,
				true
			);
			$output = $parser->parse( $pre_parsed, $rtitle, $parserOptions );
			$out->addParserOutputMetadata( $output );
			// @todo CHECKME: Used to be $output->mText but that would cause parser
			// internal stuff like mw:toc being exposed and thus it would seem to the
			// average user who's previewing stuff that headlines etc. are duplicated.
			// --ashley, 8 December 2019
			$previewableText = $out->parseAsContent( $pre_parsed );
			$out->addHTML(
				"<div id=\"createpagepreview\">
					$previewableText
					<div id=\"createpage_preview_delimiter\" class=\"actionBar actionBarStrong\">" .
						wfMessage( 'createpage-preview-end' )->escaped() .
					'</div>
				</div>'
			);
		}
	}

	/**
	 * Render a createplate form.
	 *
	 * @param bool $isInitial Are we creating a new page (I think)? This just
	 *  controls what checkboxes below the editor form are checked by default.
	 */
	public function showCreateplate( $isInitial = false ) {
		$editor = new CreatePageMultiEditor( $this->mCreateplate ?: 'Blank' );
		$editor->mRedLinked = false;
		if ( $this->mRedLinked ) {
			$editor->mRedLinked = true;
		}
		$editor->mInitial = $isInitial;
		$editor->generateForm();
	}
}
