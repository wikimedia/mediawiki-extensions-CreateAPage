<?php

use MediaWiki\MainConfigNames;
use MediaWiki\Title\Title;
use MediaWiki\User\UserOptionsManager;

class CreateAPageHooks implements
	\MediaWiki\Hook\CustomEditorHook,
	\MediaWiki\Hook\EditFormPreloadTextHook,
	\MediaWiki\Preferences\Hook\GetPreferencesHook
{

	private Config $config;
	private UserOptionsManager $userOptionsManager;

	public function __construct(
		Config $config,
		UserOptionsManager $userOptionsManager
	) {
		$this->config = $config;
		$this->userOptionsManager = $userOptionsManager;
	}

	/**
	 * When the "Advanced Edit" button is used, the existing content is preloaded
	 *
	 * @todo FIXME: THIS IS WOEFULLY BROKEN, see ApiCreateAPage.php, function switchToAdvancedEditing
	 * for details.
	 *
	 * @param string &$text EditPage#textbox1 contents
	 * @param Title $title
	 */
	public function onEditFormPreloadText( &$text, $title ) {
		$request = RequestContext::getMain()->getRequest();
		if ( $request->getCheck( 'createpage' ) ) {
			$text = isset( $_SESSION['article_content'] ) && $_SESSION['article_content'] ? $_SESSION['article_content'] : null;
			// $text = $request->getSession()->get( 'article_content' );
		}
	}

	/**
	 * Load CreateAPage on regular redlinks if:
	 * 1) the feature is enabled (made available to users) in site config,
	 *       e.g. $wgCreatePageCoverRedLinks = true;
	 * 2) the user has enabled it
	 * 3) we're editing a page in a content namespace
	 * 4) the said page does not yet exist
	 * 5) the URL parameter "editmode" is _not_ "nomulti"
	 *
	 * @param Article|WikiPage $article The page being edited
	 * @param User $user The user (object) who is editing
	 * @return bool True if we should bail out early for whatever reason,
	 *   false after rendering the CreateAPage form
	 */
	public function onCustomEditor( $article, $user ) {
		if ( !$this->config->get( 'CreatePageCoverRedLinks' ) ) {
			return true;
		}

		$request = $article->getContext()->getRequest();
		$title = $article->getTitle();
		$namespace = $title->getNamespace();
		if (
			$this->userOptionsManager->getOption( $user, 'createpage-redlinks', 1 ) == 0 ||
			!in_array( $namespace, $this->config->get( MainConfigNames::ContentNamespaces ) )
		) {
			return true;
		}

		// nomulti should always bypass that (this is for AdvancedEdit mode)
		if (
			$title->exists() ||
			$request->getRawVal( 'editmode' ) === 'nomulti'
		) {
			return true;
		}
		if ( $request->getCheck( 'wpPreview' ) ) {
			return true;
		}

		$mainForm = new CreatePageCreateplateForm();
		// @todo FIXME: get this from somewhere and inject it properly
		// $mainForm->set( 'output', $out );
		$mainForm->set( 'request', $request );
		$mainForm->set( 'user', $user );

		$mainForm->mTitle = $request->getVal( 'title' );
		$mainForm->mRedLinked = true;
		$mainForm->showForm( '' );
		$mainForm->showCreateplate( true );
		return false;
	}

	/**
	 * Adds a new toggle into Special:Preferences when $wgCreatePageCoverRedLinks
	 * is set to true.
	 *
	 * @param User $user Current User object
	 * @param array &$preferences Array of existing preference information
	 */
	public function onGetPreferences( $user, &$preferences ) {
		if ( $this->config->get( 'CreatePageCoverRedLinks' ) ) {
			$preferences['create-page-redlinks'] = [
				'type' => 'toggle',
				'section' => 'editing/advancedediting',
				'label-message' => 'tog-createpage-redlinks',
			];
		}
	}

}
