<?php

class CreateAPageHooks {

	/**
	 * When AdvancedEdit button is used, the existing content is preloaded
	 *
	 * @todo Nowadays there is a hook called 'EditFormPreloadText', which is only
	 * executed when editing nonexistent articles. Evaluate using that hook.
	 * However it does not seem to provide access to the EditPage object...
	 *
	 * @param EditPage $editPage
	 */
	public static function preloadContent( $editPage ) {
		if ( $editPage->getContext()->getRequest()->getCheck( 'createpage' ) ) {
			$editPage->textbox1 = isset( $_SESSION['article_content'] ) && $_SESSION['article_content'] ? $_SESSION['article_content'] : null;
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
	public static function onCustomEditor( $article, $user ) {
		global $wgRequest, $wgContentNamespaces, $wgCreatePageCoverRedLinks;

		if ( !$wgCreatePageCoverRedLinks ) {
			return true;
		}

		$namespace = $article->getTitle()->getNamespace();
		if (
			( $user->getOption( 'createpage-redlinks', 1 ) == 0 ) ||
			!in_array( $namespace, $wgContentNamespaces )
		)
		{
			return true;
		}

		// nomulti should always bypass that (this is for AdvancedEdit mode)
		if (
			$article->getTitle()->exists() ||
			( $wgRequest->getVal( 'editmode' ) == 'nomulti' )
		)
		{
			return true;
		} else {
			if ( $wgRequest->getCheck( 'wpPreview' ) ) {
				return true;
			}
			$mainForm = new CreatePageCreateplateForm();
			$mainForm->mTitle = $wgRequest->getVal( 'title' );
			$mainForm->mRedLinked = true;
			$mainForm->showForm( '' );
			$mainForm->showCreateplate( true );
			return false;
		}
	}

	/**
	 * Adds a new toggle into Special:Preferences when $wgCreatePageCoverRedLinks
	 * is set to true.
	 *
	 * @param User $user Current User object
	 * @param array &$preferences Array of existing preference information
	 */
	public static function onGetPreferences( $user, &$preferences ) {
		global $wgCreatePageCoverRedLinks;
		if ( $wgCreatePageCoverRedLinks ) {
			$preferences['create-page-redlinks'] = [
				'type' => 'toggle',
				'section' => 'editing/advancedediting',
				'label-message' => 'tog-createpage-redlinks',
			];
		}
	}

}