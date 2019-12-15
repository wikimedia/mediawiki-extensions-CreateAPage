<?php

class CreatePage extends SpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'CreatePage', 'createpage' );
	}

	/**
	 * Group this special page under the correct group in Special:SpecialPages
	 *
	 * @return string
	 */
	protected function getGroupName() {
		return 'pagetools';
	}

	/**
	 * @see https://phabricator.wikimedia.org/T123591
	 * @return bool
	 */
	public function doesWrites() {
		return true;
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $par Parameter passed to the page, if any
	 */
	public function execute( $par ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		$this->checkPermissions();

		$this->checkReadOnly();

		if ( !$user->isAllowed( 'createpage' ) ) {
			throw new PermissionsError( 'createpage' );
		}

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		$mainForm = new CreatePageCreateplateForm( $par );

		$action = $request->getVal( 'action' );
		if ( $request->wasPosted() && $action == 'submit' ) {
			$mainForm->submitForm();
		} elseif ( $action == 'check' ) {
			$mainForm->checkArticleExists( $request->getVal( 'to_check' ), true );
		} else {
			$mainForm->showForm( '' );
			$mainForm->showCreateplate( true );
		}
	}

}
