<?php

use MediaWiki\MediaWikiServices;

class CreatePageImageUploadForm extends UploadFromFile {
	/**
	 * @var WebRequestUpload
	 */
	protected $mUpload = null;

	/**
	 * @var WebRequest
	 */
	public $mRequest;

	public $mStoredDestName, $mLastTimestamp, $mReturnedTimestamp;
	public $mCurlError;
	public $mInFix, $mPostFix;
	public $mWatchthis = 1;
	public $mComment;
	public $mErrorText;

	/**
	 * Constructor
	 *
	 * @param WebRequest &$request
	 */
	public function initializeFromRequest( &$request ) {
		# overwrite action parameter
		$_REQUEST['action'] = 'submit';
		// $request->setVal( 'action', 'submit' );

		$this->mRequest = $request;
		$infix = $this->mInFix = $request->getVal( 'wpInFix', '' );
		$postfix = $this->mPostFix = $request->getInt( 'wpPostFix' );
		$par_name = $request->getText( 'wp' . $infix . 'ParName' . $postfix );

		$upload = $request->getUpload( 'wp' . $infix . 'UploadFile' . $postfix );

		$this->mCurlError = $request->getUploadError( 'wp' . $infix . 'UploadFile' . $postfix );
		$this->mLastTimestamp = $request->getText( 'wp' . $infix . 'LastTimestamp' . $postfix );

		$desiredDestName = '';
		if ( $request->getText( 'Createtitle' ) !== '' ) {
			$desiredDestName = $request->getText( 'Createtitle' ) . ' ' . trim( $par_name );
		}

		if ( $infix !== '' ) {
			$desiredDestName = $request->getText( 'Createtitle' );
		}

		if ( !$desiredDestName ) {
			$desiredDestName = $upload->getName();
		}

		$this->initialize( $desiredDestName, $upload );
	}

	/**
	 * Initialize from a filename and a WebRequestUpload
	 *
	 * @param string $name
	 * @param WebRequestUpload $webRequestUpload
	 */
	function initialize( $name, $webRequestUpload ) {
		$this->mUpload = $webRequestUpload;
		$this->initializePathInfo(
			$name,
			$this->mUpload->getTempName(),
			$this->mUpload->getSize()
		);
	}

	/**
	 * Start doing stuff
	 *
	 * @return array Array containing keys 'error' (bool), 'msg' (string) and 'once' (bool)
	 */
	public function execute() {
		# Nothing to upload?
		# This is a special "status code" that is handled -- by which I mean silently ignored --
		# by callers; it essentially means just that: "the user chose not to upload a file,
		# that is perfectly fine, move along, nothing to see here", as the user is allowed
		# to create a page without uploading an image
		if ( $this->mUpload->getSize() === 0 ) {
			return [
				'error' => 0,
				'msg' => 'cp_no_uploaded_file',
				'timestamp' => 'cp_no_uploaded_file',
				'once' => false
			];
		}

		# Check uploading enabled
		if ( !UploadBase::isEnabled() ) {
			return [
				'error' => 1,
				'msg' => wfMessage( 'uploaddisabledtext' )->escaped(),
				'once' => true
			];
		}

		$user = RequestContext::getMain()->getUser();

		# Check permissions
		if ( UploadBase::isAllowed( $user ) !== true ) {
			if ( !$user->isRegistered() ) {
				return [
					'error' => 1,
					// [sic]! There is special handling for this "message" in the
					// CreateAPageInfobox JavaScript class. Thus this is *not* meant to be
					// an i18n'ed string here, and that's confusing as hell.
					'msg' => 'cp_no_login',
					'once' => true
				];
			} else {
				return [
					'error' => 1,
					'msg' => wfMessage( 'badaccess-group0' )->escaped(),
					'once' => true
				];
			}
		}

		# Check blocks
		if ( $user->isBlockedFromUpload() ) {
			return [
				'error' => 1,
				'msg' => wfMessage( 'blockedtext' )->escaped(),
				'once' => true
			];
		}

		if ( wfReadOnly() ) {
			return [
				'error' => 1,
				'msg' => wfMessage( 'createpage-upload-directory-read-only' )->escaped(),
				'once' => true
			];
		}

		$response = $this->processUpload();
		if ( is_string( $response ) ) {
			return [
				'error' => 1,
				'msg' => $response,
				'once' => false
			];
		}

		$this->cleanupTempFile();

		if ( $this->mDesiredDestName != '' ) {
			if ( $this->mDestName != '' ) {
				return [
					'error' => 0,
					'msg' => 'File:' . $this->mDestName,
					'timestamp' => $this->mDestName,
					'once' => false
				];
			} else {
				return [
					'error' => 0,
					'msg' => 'File:' . wfBaseName( $this->mDesiredDestName ),
					'timestamp' => wfBaseName( $this->mDesiredDestName ),
					'once' => false
				];
			}
		} else {
			return [
				'error' => 1,
				'msg' => wfMessage( 'uploaderror' )->escaped(),
				'once' => true
			];
		}
	}

	function processUpload() {
		$context = RequestContext::getMain();
		$lang = $context->getLanguage();
		$out = $context->getOutput();
		$user = $context->getUser();

		// Verify permissions for this title
		$permErrors = $this->verifyTitlePermissions( $user );
		if ( $permErrors !== true ) {
			$code = array_shift( $permErrors[0] );
			return wfMessage( $code, $permErrors[0] )->parse();
		}

		$details = null;
		$value = null;
		$value = $this->internalProcessUpload( $details );

		switch ( $value ) {
			case self::SUCCESS:
				// don't... do... REDIRECT
				return;

			// no such crap, it seems. --ashley, 8 December 2019
			// case self::BEFORE_PROCESSING:
			//	return false;

			case self::FILE_TOO_LARGE:
				return wfMessage( 'largefileserver' )->escaped();

			case self::EMPTY_FILE:
				// It is perfectly kosher for there to be an upload field and for the user
				// to choose _not_ to upload an image...
				return;
				// return wfMessage( 'emptyfile' )->escaped();

			case self::MIN_LENGTH_PARTNAME:
				return wfMessage( 'minlength1' )->escaped();

			case self::ILLEGAL_FILENAME:
				$filtered = $details['filtered'];
				return wfMessage( 'illegalfilename', $filtered )->escaped();

			// no such crap, it seems. --ashley, 8 December 2019
			// case self::PROTECTED_PAGE:
			//	return wfMessage( 'protectedpage' )->escaped();

			case self::OVERWRITE_EXISTING_FILE:
				$errorText = $details['overwrite'];
				return Status::newFatal( $out->parseAsContent( $errorText ) );

			case self::FILETYPE_MISSING:
				return wfMessage( 'filetype-missing' )->escaped();

			case self::FILETYPE_BADTYPE:
				global $wgFileExtensions;
				$finalExt = $details['finalExt'];
				$extensions = array_unique( $wgFileExtensions );
				$extensionsCount = count( $extensions );
				return wfMessage( 'filetype-banned-type', $finalExt,
					$lang->commaList( $extensions ), $extensionsCount, 1 )->escaped();

			case self::VERIFICATION_ERROR:
				$veri = $details['veri'];
				return $veri->toString();

			// case self::UPLOAD_VERIFICATION_ERROR:
			// 	$error = $details['error'];
			// 	return $error;

			// case self::UPLOAD_WARNING:
			// 	$warning = $details['warning'];
			// 	return $warning;
		}

		throw new MWException(
			__METHOD__ . ": Unknown value `{$value}`" .
			// Ugly hack, but whatever...
			// @todo FIXME
			( isset( $this->mErrorText ) ? "Details: {$this->mErrorText}" : '' )
		);
	}

	/**
	 * Check if an image by the given name exists already on the wiki.
	 *
	 * @param string $img_name Image file name, e.g. Foo.jpg
	 * @return bool|int Timestamp in regular TS_MW format on success, bool false on failure (no matches)
	 */
	private function getQuickTimestamp( $img_name ) {
		$dbr = wfGetDB( DB_REPLICA );
		$resource = $dbr->select(
			'image',
			[ 'img_timestamp' ],
			[ 'img_name' => $img_name ],
			__METHOD__
		);

		if ( $dbr->numRows( $resource ) == 0 ) {
			return false;
		}

		$res_obj = $dbr->fetchObject( $resource );
		return $res_obj->img_timestamp;
	}

	/**
	 * Since we wanted to mess up heavily here...
	 * I'm copying this stuff too
	 */
	function internalProcessUpload( &$resultDetails ) {
		/* Check for PHP error if any, requires php 4.2 or newer */
		if ( $this->mCurlError == 1 ) {
			return self::FILE_TOO_LARGE;
		}

		/**
		 * If there was no filename or a zero size given, give up quick.
		 */
		if ( $this->isEmptyFile() ) {
			return self::EMPTY_FILE;
		}

		/**
		 * Filter out illegal characters, and try to make a legible name
		 * out of it. We'll strip some silently that Title would die on.
		 */
		$nt = $this->getTitle();
		$finalExt = $this->mFinalExtension;
		if ( $nt === null ) {
			$filtered = wfBaseName( $this->mDesiredDestName ? $this->mDesiredDestName . '.' . $finalExt : $this->mStoredDestName );
			/**
			 * Filter out illegal characters, and try to make a legible name
			 * out of it. We'll strip some silently that Title would die on.
			 */
			$filtered = preg_replace( '/[^' . Title::legalChars() . ']|:/', '-', $filtered );
			$resultDetails = [ 'filtered' => $filtered ];
			return self::ILLEGAL_FILENAME;
		}
		$repoGroup = MediaWikiServices::getInstance()->getRepoGroup();
		$this->mLocalFile = $repoGroup->getLocalRepo()->newFile( $nt );
		$this->mDestName = $this->mLocalFile->getName();

		/**
		 * If the image is protected, non-sysop users won't be able
		 * to modify it by uploading a new revision.
		 */
		/*
		if ( !$nt->userCan( 'edit' ) ) {
			return self::PROTECTED_PAGE;
		}
		*/

		/**
		 * In some cases we may forbid overwriting of existing files.
		 */
		// here starts the interesting part...
		// we overwrite mDestName and give it a new twist
		$timestamp = '';
		$img_found = $repoGroup->findFile( $this->mDestName );
		if ( $img_found ) {
			// ehhh...
			// we'll do it hard way then...
			$timestamp = $this->mDestName;
		} else {
			// this timestamp should not repeat...
			$timestamp = 'invalid';
		}
		$tempname = '';
		$tmpCount = 0;

		while ( $img_found && ( $timestamp != $this->mLastTimestamp ) ) {
			$tmpCount++;
			$file_ext = explode( '.', $this->mDestName );
			$file_ext = $file_ext[0];
			$tmpDestname = $file_ext;
			$tempName = $tmpDestname . $tmpCount . '.' . $this->mFinalExtension;
			$timestamp = $tempName;
			$img_found = $repoGroup->findFile( $tempname );
		}

		if ( $tmpCount > 0 ) {
			$tempName = preg_replace( "/[^" . Title::legalChars() . "]|:/", '-', $tempName );
			$nt = Title::makeTitleSafe( NS_FILE, $tempName );
			$this->mLocalFile = $repoGroup->getLocalRepo()->newFile( $nt );
			$this->mDestName = $this->mLocalFile->getName();
			$this->mDesiredDestName = $this->mStoredDestName . $tmpCount . '.' . $this->mFinalExtension;
		} else {
			// append the extension anyway
			$this->mDesiredDestName = $this->mStoredDestName . '.' . $this->mFinalExtension;
		}

		$user = RequestContext::getMain()->getUser();

		$overwrite = $this->checkOverwrite( $user );
		if ( $overwrite !== true ) {
			$resultDetails = [ 'overwrite' => $overwrite ];
			return self::OVERWRITE_EXISTING_FILE;
		}

		/**
		 * Look at the contents of the file; if we can recognize the
		 * type but it's corrupt or data of the wrong type, we should
		 * probably not accept it.
		 */
		$veri = $this->verifyFile();
		if ( $veri !== true ) {
			$resultDetails = [ 'veri' => $veri ];
			return self::VERIFICATION_ERROR;
		}

		// Non-fatal conditions (warnings) are always ignored for the purpose of this form

		/**
		 * Try actually saving the thing...
		 * It will show an error form on failure.
		 */
		$pageText = SpecialUpload::getInitialPageText(
			$this->mComment,
			'',
			'',
			''
		);

		$status = $this->performUpload(
			// $this->mTempPath,
			$this->mComment,
			$pageText,
			$this->mWatchthis,
			$user,
			[]
		);

		if ( !$status->isGood() ) {
			// @todo FIXME: this is bullshit but better than attempting to call the nonexistent showError()
			// method, which in the day, when it existed, used to use OutputPage to display the error...
			// --ashley, 8 December 2019
			$this->mErrorText = $status->getWikiText();
			return UploadBase::HOOK_ABORTED;
		} else {
			if ( $this->mWatchthis ) {
				$user->addWatch( $this->mLocalFile->getTitle() );
			}
			// Success, redirect to description page
			$this->mReturnedTimestamp = $this->getQuickTimestamp( $this->mDestName );
			// @todo: added to avoid passing a ref to null - should this be defined somewhere?
			$img = null;
			return self::SUCCESS;
		}
	}

	function showSuccess() {
	}

	/**
	 * Ported from UploadBase@REL1_33 b/c it's private (d'oh...) with some tweaks
	 *
	 * Check if there's an overwrite conflict and, if so, if restrictions
	 * forbid this user from performing the upload.
	 *
	 * @param User $user
	 *
	 * @return mixed True on success, array on failure
	 */
	private function checkOverwrite( $user ) {
		// First check whether the local file can be overwritten
		$file = $this->getLocalFile();
		$file->load( File::READ_LATEST );
		if ( $file->exists() ) {
			if ( !UploadBase::userCanReUpload( $user, $file ) ) {
				return [ 'fileexists-forbidden', $file->getName() ];
			} else {
				return true;
			}
		}

		/* Check shared conflicts: if the local file does not exist, but
		 * RepoGroup::findFile finds a file, it exists in a shared repository.
		 */
		$file = MediaWikiServices::getInstance()->getRepoGroup()
			->findFile( $this->getTitle(), [ 'latest' => true ] );
		if ( $file && !$user->isAllowed( 'reupload-shared' ) ) {
			return [ 'fileexists-shared-forbidden', $file->getName() ];
		}

		return true;
	}
}
