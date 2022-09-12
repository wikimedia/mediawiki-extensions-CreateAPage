<?php
/**
 * CreateAPage API module for the non-upload-related stuff
 *
 * @file
 * @ingroup API
 * @date 28 July 2020 (revised December 2021)
 */

use MediaWiki\Revision\RevisionAccessException;
use MediaWiki\Revision\SlotRecord;

class ApiCreateAPage extends ApiBase {

	/** @var MediaWiki\Revision\RevisionLookup Injected via services magic and set up in the extension.json file */
	private $revisionLookup;

	/**
	 * @param ApiMain $main
	 * @param string $action
	 * @param MediaWiki\Revision\RevisionLookup $revisionLookup
	 */
	public function __construct(
		ApiMain $main,
		$action,
		MediaWiki\Revision\RevisionLookup $revisionLookup
	) {
		parent::__construct( $main, $action );
		$this->revisionLookup = $revisionLookup;
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		// Get the request parameters
		$params = $this->extractRequestParams();

		$what = $params['what'];
		$template = $params['template'];

		switch ( $what ) {
			case 'multieditparse':
				$output = $this->multiEditParse( $template );
				break;
			case 'switchtoadvancedediting':
				$output = $this->switchToAdvancedEditing( $template );
				break;
			default:
				$output = '';
				break;
		}

		// Top level
		$this->getResult()->addValue( null, $this->getModuleName(), [ 'result' => $output ] );

		return true;
	}

	/**
	 * @param string $template Createplate name without the "Createplate-" prefix
	 * @return string
	 */
	private function multiEditParse( $template ) {
		$title = Title::newFromText( "Createplate-{$template}", NS_MEDIAWIKI );

		// transfer optional sections data
		$optionalSections = [];
		// @todo FIXME: there has to be something better than $_POST...
		foreach ( $_POST as $key => $value ) {
			if ( strpos( $key, 'wpOptionalInput' ) !== false ) {
				$optionalSections = str_replace( 'wpOptionalInput', '', $key );
			}
		}

		if ( $title->exists() ) {
			$rev = $this->revisionLookup->getRevisionByTitle( $title );

			$contentObj = null;
			try {
				$contentObj = $rev->getContent( SlotRecord::MAIN );
			} catch ( RevisionAccessException $ex ) {
				// Just ignore it for now and fall back to rendering a blank template (below)
			}

			if ( $contentObj !== null ) {
				$text = (string)ContentHandler::getContentText( $contentObj );
			} else {
				$text = '<!---blanktemplate--->';
			}

			return CreateMultiPage::multiEditParse( 10, 10, $text, $optionalSections );
		}

		return CreateMultiPage::multiEditParse( 10, 10, '<!---blanktemplate--->' );
	}

	/**
	 * When switching to advanced editing, store the original user-supplied page
	 * content for future use by CreateAPageHooks#preloadContent.
	 *
	 * @todo FIXME: THIS IS WOEFULLY BROKEN, seemingly due to CreatePageMultiEditor#glueArticle
	 * and _maybe_ related to the use of WebRequest & $_POST there? Either way, that method
	 * seems to have no knowledge whatsoever of the chosen createplate's *contents*, so if,
	 * as an end-user, you start creating a page on Special:CreatePage using a createplate and
	 * fill out some fields (assuming the createplate uses an infobox...well, actually, it shouldn't
	 * even matter) and then realize you want regular ol' ?action=edit instead and you switch
	 * using the "Advanced Edit" button, this method is supposed to stash the unsaved data;
	 * but it does not do that, in fact.
	 * --ashley, 1 January 2022
	 *
	 * @param string $template Createplate name without the "Createplate-" prefix
	 * @return bool Always true
	 */
	private function switchToAdvancedEditing( $template ) {
		$editor = new CreatePageMultiEditor( $template );
		$content = CreateMultiPage::unescapeBlankMarker( $editor->glueArticle() );
		CreateAPageUtils::unescapeKnownMarkupTags( $content );

		// RequestContext::getMain()->getRequest()->getSession()->set( 'article_content', $content );
		$_SESSION['article_content'] = $content;

		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function getAllowedParams() {
		return [
			'template' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			],
			'what' => [
				ApiBase::PARAM_TYPE => [ 'multieditparse', 'switchtoadvancedediting' ],
				ApiBase::PARAM_REQUIRED => true
			]
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages() {
		return [
			'action=createapage&what=multieditparse&template=Actor'
				=> 'apihelp-createapage-example-1',
			'action=createapage&what=switchtoadvancedediting&template=Actor'
				=> 'apihelp-createapage-example-2',
		];
	}
}
