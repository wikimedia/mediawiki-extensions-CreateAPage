<?php
/**
 * CreateAPage API module for uploading files
 *
 * @file
 * @ingroup API
 * @date 28 July 2020
 */

class ApiCreateAPageUpload extends ApiBase {

	/** @var RepoGroup Injected via services magic and set up in the extension.json file */
	private $repoGroup;

	/**
	 * @param ApiMain $main
	 * @param string $action
	 * @param RepoGroup $repoGroup
	 */
	public function __construct(
		ApiMain $main,
		$action,
		RepoGroup $repoGroup
	) {
		parent::__construct( $main, $action );
		$this->repoGroup = $repoGroup;
	}

	/**
	 * @inheritDoc
	 * @note Unlike core ApiUpload, we don't need to do perms checking etc. here because
	 *  that is all done in CreatePageImageUploadForm::execute(), which we call below.
	 */
	public function execute() {
		// Get the request parameters
		$params = $this->extractRequestParams();

		$postfix = $params['num'];
		$infix = '';
		if ( isset( $params['infix'] ) && $params['infix'] !== '' ) {
			$infix = $params['infix'];
		}

		$request = $this->getMain()->getRequest();

		// store these for the upload class to use
		$request->setVal( 'wpPostFix', $postfix );
		$request->setVal( 'wpInFix', $infix );
		$request->setVal( 'Createtitle', $request->getVal( 'Createtitle' ) );

		// do the real upload
		$uploadForm = new CreatePageImageUploadForm();
		$uploadForm->initializeFromRequest( $request );
		if ( isset( $params['comment'] ) && $params['comment'] !== '' ) {
			$uploadForm->mComment = $params['comment'];
		} else {
			$uploadForm->mComment = $this->msg( 'createpage-uploaded-from' )->text();
		}
		$uploadedFile = $uploadForm->execute();

		$res = [];

		if ( $uploadedFile['error'] == 0 ) {
			if ( $uploadedFile['msg'] !== 'cp_no_uploaded_file' ) {
				$imageObj = $this->repoGroup->getLocalRepo()->newFile( $uploadedFile['timestamp'] );
				$imageURL = $imageObj->createThumb( 60 );
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
			// There used to be some special handling here for when
			// $uploadedFile['once'] is set, but said handling was literally
			// commented out so it's been removed.
			$res = [
				'error' => 1,
				'msg' => $uploadedFile['msg'],
				'num' => $postfix
			];
		}

		// Top level
		$this->getResult()->addValue( null, $this->getModuleName(), $res );

		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function needsToken() {
		return 'csrf';
	}

	/**
	 * @inheritDoc
	 */
	public function isWriteMode() {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function getAllowedParams() {
		return [
			// a.k.a postfix
			'num' => [
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => true
			],
			'infix' => [
				ApiBase::PARAM_TYPE => 'string',
			],
			'comment' => [
				ApiBase::PARAM_TYPE => 'string',
			],
			// @todo FIXME: CRAPPY HACKS' GALORE
			// DEFINING THESE TO SHUT UP WARNINGS, THIS IS 100% WRONG!
			'wpInFix' => [
				ApiBase::PARAM_TYPE => 'string',
			],
			'wpPostFix' => [
				ApiBase::PARAM_TYPE => 'string',
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages() {
		return [
			'action=createapage-upload&num=1&infix=All'
				=> 'apihelp-createapage-upload-example-1',
		];
	}
}
