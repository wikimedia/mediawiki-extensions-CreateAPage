/**
 * JavaScript for the CreateAPage extension (its Special:CreatePage page).
 *
 * Classes:
 *
 * CreateAPage
 * -main class
 *
 * CreateAPageInfobox
 * -class for uploading images from infoboxes (I think)
 *
 * CreateAPageCategoryTagCloud
 * -class for managing the category tag cloud
 *
 * @file
 */

/**
 * A class for managing the category tag cloud.
 * Click handlers are set up elsewhere in this huge file.
 */
var CreateAPageCategoryTagCloud = {
	add: function ( category, num ) { // previously cloudAdd
		var category_text = document.getElementById( 'wpCategoryTextarea' );

		if ( category_text.value === '' ) {
			category_text.value += decodeURIComponent( category );
		} else {
			category_text.value += '|' + decodeURIComponent( category );
		}

		var this_button = document.getElementById( 'cloud' + num );
		if ( this_button ) {
			this_button.onclick = function () {
				CreateAPageCategoryTagCloud.remove( category, num );
				return false;
			};
			this_button.style.color = '#419636';
		}
		return false;
	},

	build: function ( o ) { // previously cloudBuild
		var categories = o.value;
		var new_text = '';

		categories = categories.split( '|' );
		for ( var i = 0; i < categories.length; i++ ) {
			if ( categories[ i ] !== '' ) {
				new_text += '[[' + mw.config.get( 'wgFormattedNamespaces' )[ 14 ] + ':' +
					categories[ i ] + ']]';
			}
		}

		return new_text;
	},

	inputAdd: function () { // previously cloudInputAdd
		var category_input = document.getElementById( 'wpCategoryInput' );
		var category_text = document.getElementById( 'wpCategoryTextarea' );
		var category = category_input.value;
		if ( category_input.value !== '' ) {
			if ( category_text.value === '' ) {
				category_text.value += decodeURIComponent( category );
			} else {
				category_text.value += '|' + decodeURIComponent( category );
			}
			category_input.value = '';
			var c_found = false;
			var core_cat = category.replace( /\|.*/, '' );
			for ( var j in CreateAPage.foundCategories ) {
				if ( CreateAPage.foundCategories[ j ] === core_cat ) {
					var this_button = document.getElementById( 'cloud' + j );
					this_button.onclick = CreateAPage.onclickCategoryFn( core_cat, j );
					this_button.style.color = '#419636';
					c_found = true;
					break;
				}
			}
			if ( !c_found ) {
				var n_cat = document.createElement( 'a' );
				var s_cat = document.createElement( 'span' );
				var n_cat_count = CreateAPage.foundCategories.length;

				var cat_full_section = document.getElementById( 'createpage_cloud_section' );
				var cat_num = n_cat_count;
				n_cat.setAttribute( 'id', 'cloud' + cat_num );
				n_cat.setAttribute( 'href', '#' );
				n_cat.onclick = CreateAPage.onclickCategoryFn( core_cat, cat_num );
				n_cat.style.color = '#419636';
				n_cat.style.fontSize = '10pt';
				s_cat.setAttribute( 'id', 'tag' + cat_num );
				var t_cat = document.createTextNode( core_cat );
				var space = document.createTextNode( ' ' );
				n_cat.appendChild( t_cat );
				s_cat.appendChild( n_cat );
				s_cat.appendChild( space );
				cat_full_section.appendChild( s_cat );
				CreateAPage.foundCategories[ n_cat_count ] = core_cat;
			}
		}
	},

	remove: function ( category, num ) { // previously cloudRemove
		var category_text = document.getElementById( 'wpCategoryTextarea' );
		var this_pos = category_text.value.indexOf( decodeURIComponent( category ) );
		if ( this_pos !== -1 ) {
			category_text.value = category_text.value.substr( 0, this_pos - 1 ) +
				category_text.value.substr( this_pos + decodeURIComponent( category ).length );
		}
		var this_button = document.getElementById( 'cloud' + num );
		this_button.onclick = function () {
			CreateAPageCategoryTagCloud.add( category, num );
			return false;
		};
		this_button.style.color = '';
		return false;
	}
};

var CreateAPage = {
	noCanDo: false,
	submitEnabled: false,

	foundCategories: [],

	myId: 0,
	previewMode: ( mw.util.getParamValue( 'wpPreview' ) !== null ) ? 'Yes' : 'No',
	redLinkMode: (
		mw.util.getParamValue( 'Redlinkmode' ) !== null ||
		mw.util.getParamValue( 'Createtitle' ) !== '' && mw.util.getParamValue( 'Createtitle' ) !== null
	) ? 'Yes' : 'No',

	/**
	 * Copy of CreatePageNormalEdit from extensions/wikiwyg/share/MediaWiki/extensions/CreatePage/js/createpage.js
	 * with a few tweaks (the textarea stuff + i18n).
	 *
	 * Asks the user for a confirmation if they want to discard all changes
	 * done via Special:CreatePage and if the answer is yes, takes them to
	 * ?action=edit (normal edit mode).
	 */
	goToNormalEditMode: function () {
		var title = document.getElementById( 'title' );
		var errorMsg = document.getElementById( 'createpage_messenger' );

		if ( title.value === '' ) {
			errorMsg.innerHTML = mw.msg( 'createpage-must-specify-title' );
			errorMsg.style.display = '';
			return;
		}

		/* check for unsaved changes (they will always be *unsaved* here... ) */
		// @todo CHECKME
		var textarea;
		var edit_textareas = CreateAPage.getElementsBy(
			CreateAPage.editTextareaTest,
			'textarea',
			document.getElementById( 'wpTableMultiEdit' ),
			CreateAPage.textareaAddToolbar
		);

		if ( edit_textareas[ 0 ].id === 'wpTextboxes0' ) {
			textarea = edit_textareas[ 0 ];
			textarea = textarea.replace( '<br>', '' );
		}

		if ( textarea !== '' ) {
			var abandonChanges = confirm(
				mw.msg( 'createpage-unsaved-changes-details' ),
				mw.msg( 'createpage-unsaved-changes' )
			);

			if ( !abandonChanges ) {
				return;
			}
		}

		// @todo Might be able to simply do window.location = mw.util.getUrl( title.value, { 'action': 'edit' } );
		var fixedArticlePath = mw.config.get( 'wgArticlePath' ).replace( '$1', '' );
		fixedArticlePath = fixedArticlePath.replace( 'index.php[^/]', 'index.php?title=' );

		window.location = fixedArticlePath + title.value + '?action=edit';
	},

	/**
	 * Callback for the title existence check function
	 *
	 * @todo FIXME: clean up this awful mess
	 *
	 * @param {Object} data JSON data from the API
	 */
	callbackTitle: function ( data ) {
		var res = data.query, helperButton, pageExists, isInvalidTitle;

		document.getElementById( 'cp-title-check' ).innerHTML = '';

		pageExists = !!res.pages[ 0 ].pageid;
		isInvalidTitle = ( res.pages[ 0 ].invalid && res.pages[ 0 ].invalid === true );

		if ( pageExists && !isInvalidTitle ) {
			var url = mw.util.getUrl( res.pages[ 0 ].title, { action: 'edit' } );
			var text = res.pages[ 0 ].title;

			document.getElementById( 'cp-title-check' ).innerHTML = '<span style="color: red;">' +
				mw.msg( 'createpage-article-exists' ) +
				' <a href="' + url + '" title="' + text + '">' + text + '</a>' +
				mw.msg( 'createpage-article-exists2' ) + '</span>';

			if ( CreateAPage.Overlay ) {
				CreateAPage.Overlay.show();
				helperButton = document.getElementById( 'wpRunInitialCheck' );
				helperButton.style.display = '';
			} else {
				CreateAPage.contentOverlay();
			}
		} else if ( isInvalidTitle ) {
			// @todo Could also show the reason why the supplied title is invalid.
			// It's stored as res.pages[ 0 ].invalidreason
			document.getElementById( 'cp-title-check' ).innerHTML =
				'<span style="color: red;">' +
				mw.msg( 'createpage-title-invalid' ) +
				'</span>';

			if ( CreateAPage.Overlay ) {
				CreateAPage.resizeOverlay( 0 );
				CreateAPage.Overlay.show();
				helperButton = document.getElementById( 'wpRunInitialCheck' );
				helperButton.style.display = '';
			} else {
				CreateAPage.contentOverlay();
			}
		} else {
			if ( CreateAPage.Overlay ) {
				CreateAPage.Overlay.hide();
				helperButton = document.getElementById( 'wpRunInitialCheck' );
				helperButton.style.display = 'none';
			}
		}

		CreateAPage.noCanDo = false;
	},

	/**
	 * The name of this function is misleading and as such it should be renamed
	 * one day.
	 *
	 * In any case, this function gets called whenever the user inputs
	 * something on the "Article Title" input on Special:CreatePage and moves
	 * the cursor elsewhere on the page or presses the "Proceed to edit" button.
	 * This function then shows a progress bar image and checks whether there
	 * is or isn't a page with the given title.
	 *
	 * If there isn't a page with the given title, the overlay on the editor is
	 * removed.
	 * If such a page however exists, a red error message ("This article
	 * already exists. Edit <article name> or specify another title.") is shown
	 * to the user.
	 */
	watchTitle: function () {
		// @todo FIXME: should use the jQuery spinner library bundled with MW core, probably
		document.getElementById( 'cp-title-check' ).innerHTML =
			'<img src="' + mw.config.get( 'wgExtensionAssetsPath' ) +
			'/CreateAPage/resources/images/progress_bar.gif" width="70" height="11" alt="' +
			mw.msg( 'createpage-please-wait' ) + '" border="0" />';
		CreateAPage.noCanDo = true;

		( new mw.Api() ).get( {
			action: 'query',
			titles: document.getElementById( 'Createtitle' ).value,
			formatversion: 2
		} ).done( function ( data ) {
			CreateAPage.callbackTitle( data );
		} ).fail( function () {
			document.getElementById( 'cp-title-check' ).innerHTML = '';
		} );
	},

	clearInput: function ( o ) {
		var cDone = false;
		$( '#wpInfoboxPar' + o.num ).on( 'focus', function () {
			var previewarea = $( '#createpagepreview' );
			if ( !cDone && ( previewarea === null ) ) {
				cDone = true;
				document.getElementById( 'wpInfoboxPar' + o.num ).value = '';
			}
		} );
	},

	/**
	 * @param {jQuery.Event} e
	 */
	goToEdit: function ( e ) {
		e.preventDefault();
		$.post(
			mw.config.get( 'wgScript' ),
			{
				action: 'ajax',
				rs: 'axCreatepageAdvancedSwitch'
			},
			function ( data ) {
				window.location = mw.util.getUrl(
					document.getElementById( 'Createtitle' ).value,
					{
						action: 'edit',
						editmode: 'nomulti',
						createpage: 'true'
					}
				);
			}
		);
		CreateAPage.warningPanel.hide();
	},

	/**
	 * Takes the user to Special:UserLogin, the login page, and sets the returnto
	 * URL parameter value appropriately, depending on whether CreateAPage is
	 * operating on all red links or not.
	 *
	 * @param {jQuery.Event} e
	 */
	goToLogin: function ( e ) {
		e.preventDefault();
		var returnto = '';
		if ( CreateAPage.redLinkMode === 'Yes' ) {
			returnto = document.getElementById( 'Createtitle' ).value;
		} else {
			returnto = 'Special:CreatePage';
		}
		window.location = mw.util.getUrl( 'Special:UserLogin', { returnto: returnto } );
	},

	/**
	 * If a warning panel element exists, hides it.
	 *
	 * @param {jQuery.Event} e
	 */
	hideWarningPanel: function ( e ) {
		if ( CreateAPage.warningPanel ) {
			CreateAPage.warningPanel.hide();
		}
	},

	/**
	 * If a warning panel element does not yet exist, builds it and then displays
	 * it.
	 *
	 * @param {jQuery.Event} e
	 */
	showWarningPanel: function ( e ) {
		// e.preventDefault();
		if ( document.getElementById( 'Createtitle' ).value !== '' ) {
			if ( !CreateAPage.warningPanel ) {
				CreateAPage.buildWarningPanel( e );
			}
			// GODFORSAKEN FILTHY HACK!
			// @todo FIXME
			// For whatever reason the dialog stuff doesn't quite work as expected
			// Ideally you should be able to click on the "Advanced Edit" button as many times as you
			// want, and each time the dialog would show up, and once you click on a button (Yes or No),
			// it goes away. In reality, as of 8 December 2019, it shows up -- seemingly -- only once,
			// and after you click on a button, "another" "empty" dialog (probably in reality
			// the same dialog but with #createpage_warning_copy having display: none; set) shows up.
			if ( $( '#createpage_warning_copy' ).length > 0 && $( '#createpage_warning_copy' ).parent().is( ':hidden' ) ) {
				$( '#createpage_warning_copy' ).parent().show();
			}
			// </hack>
			CreateAPage.warningPanel.show();
			$( '#wpCreatepageWarningYes' ).focus();
		} else {
			$( '#cp-title-check' ).html(
				'<span style="color: red;">' +
				mw.msg( 'createpage-give-title' ) +
				'</span>'
			);
		}
	},

	/**
	 * @param {jQuery.Event} e
	 */
	hideWarningLoginPanel: function ( e ) {
		if ( CreateAPage.warningLoginPanel ) {
			CreateAPage.warningLoginPanel.hide();
		}
	},

	/**
	 * @param {jQuery.Event} e
	 */
	showWarningLoginPanel: function ( e ) {
		e.preventDefault();
		if ( document.getElementById( 'Createtitle' ).value !== '' ) {
			if ( !CreateAPage.warningLoginPanel ) {
				CreateAPage.buildWarningLoginPanel( e );
			}
			CreateAPage.warningLoginPanel.show();
			$( '#wpCreatepageWarningYes' ).focus();
		} else {
			$( '#cp-title-check' ).html(
				'<span style="color: red;">' +
				mw.msg( 'createpage-give-title' ) +
				'</span>'
			);
		}
	},

	uploadCallback: function ( oResponse ) {
		var aResponse = []; // initialize it as an empty array so that JSHint can STFU
		if ( /^("(\\.|[^"\\\n\r])*?"|[,:{}[\]0-9.\-+Eaeflnr-u \n\r\t])+?$/.test( oResponse.responseText ) ) {
			aResponse = eval( '(' + oResponse.responseText + ')' );
		}
		var ProgressBar = document.getElementById( 'createpage_upload_progress_section' + aResponse.num );

		if ( aResponse.error !== 1 ) {
			ProgressBar.innerHTML = mw.msg( 'createpage-img-uploaded' );
			var target_info = document.getElementById( 'wpAllUploadTarget' + aResponse.num ).value;
			var target_tag = $( target_info );
			target_tag.value = '[[' + aResponse.msg + '|thumb]]';

			var ImageThumbnail = document.getElementById( 'createpage_image_thumb_section' + aResponse.num );
			var thumb_container = document.getElementById( 'createpage_main_thumb_section' + aResponse.num );
			var tempstamp = new Date();
			ImageThumbnail.src = aResponse.url + '?' + tempstamp.getTime();
			if ( document.getElementById( 'wpAllLastTimestamp' + aResponse.num ).value === 'None' ) {
				var break_tag = document.createElement( 'br' );
				thumb_container.style.display = '';
				var label_node = document.getElementById( 'createpage_image_label_section' + aResponse.num );
				var par_node = label_node.parentNode;
				par_node.insertBefore( break_tag, label_node );
			}
			document.getElementById( 'wpAllLastTimestamp' + oResponse.argument ).value = aResponse.timestamp;
		} else if ( ( aResponse.error === 1 ) && ( aResponse.msg === 'cp_no_login' ) ) {
			// Render the "you need to log in" message (in red, so that the user will
			// definitely notice it) + show the login panel to give 'em some options
			ProgressBar.innerHTML = '<span style="color: red">' +
				mw.message(
					'createpage-login-required',
					mw.util.getUrl( 'Special:UserLogin', { returnto: 'Special:CreatePage' } ),
					'createpage_login' + oResponse.argument
				).text() +
				'</span>';
			$( '#createpage_login' + oResponse.argument ).click( function ( e ) {
				CreateAPage.showWarningLoginPanel( e );
			} );
		} else {
			ProgressBar.innerHTML = '<span style="color: red">' + aResponse.msg + '</span>';
		}

		document.getElementById( 'createpage_image_text_section' + oResponse.argument ).innerHTML = mw.msg( 'createpage-insert-image' );
		document.getElementById( 'createpage_upload_file_section' + oResponse.argument ).style.display = '';
		document.getElementById( 'createpage_image_text_section' + oResponse.argument ).style.display = '';
		document.getElementById( 'createpage_image_cancel_section' + oResponse.argument ).style.display = 'none';
	},

	failureCallback: function ( response ) {
		response = JSON.parse( response );
		document.getElementById( 'createpage_image_text_section' + response.argument ).innerHTML = mw.msg( 'createpage-insert-image' );
		document.getElementById( 'createpage_upload_progress_section' + response.argument ).innerHTML = mw.msg( 'createpage-upload-aborted' );
		document.getElementById( 'createpage_upload_file_section' + response.argument ).style.display = '';
		document.getElementById( 'createpage_image_text_section' + response.argument ).style.display = '';
		document.getElementById( 'createpage_image_cancel_section' + response.argument ).style.display = 'none';
	},

	restoreSection: function ( section, text ) {
		var sectionContent = CreateAPage.getElementsBy(
			CreateAPage.optionalContentTest, '', section
		);
		for ( var i = 0; i < sectionContent.length; i++ ) {
			text = text.replace( sectionContent[ i ].id, '' );
		}
		section.style.display = 'block';
		return text;
	},

	unuseSection: function ( section, text ) {
		var sectionContent = CreateAPage.getElementsBy(
			CreateAPage.optionalContentTest, '', section
		);
		var first = true;
		var ivalue = '';

		for ( var i = 0; i < sectionContent.length; i++ ) {
			if ( first ) {
				if ( text !== '' ) {
					ivalue += ',';
				}
				first = false;
			} else {
				ivalue += ',';
			}
			ivalue += sectionContent[ i ].id;
		}

		section.style.display = 'none';

		return text + ivalue;
	},

	toggleSection: function ( e, o ) {
		var section = document.getElementById( 'createpage_section_' + o.num );
		var input = document.getElementById( 'wpOptionalInput' + o.num );
		var optionals = document.getElementById( 'wpOptionals' );
		if ( input.checked ) {
			optionals.value = CreateAPage.restoreSection( section, optionals.value );
		} else {
			optionals.value = CreateAPage.unuseSection( section, optionals.value );
		}
	},

	upload: function ( e, o ) {
		e.preventDefault();

		$( '#createpage_upload_progress_section' + o.num ).show().html( $.createSpinner( 'createpage' ) );

		// Use HTML5 magic to do the file upload without having to resort to super
		// heavy jQuery plugins or anything like that
		var formData = new FormData();
		formData.append( 'wpUploadFile' + o.num, document.getElementById( 'createpage_upload_file' + o.num ).files[ 0 ] );

		var sent_request = $.ajax( { // using .ajax instead of .post for better flexibility
			type: 'POST',
			url: mw.config.get( 'wgScript' ) + '?action=ajax&rs=axMultiEditImageUpload&infix=All&num=' + o.num,
			data: formData,
			contentType: false,
			cache: false,
			processData: false,
			timeout: 240000
		} ).done( function ( result ) {
			$.removeSpinner( 'createpage' );
			CreateAPageInfobox.uploadCallback( result );
		} ).fail( function ( code, result ) {
			$.removeSpinner( 'createpage' );
			CreateAPageInfobox.failureCallback( result );
		} );

		document.getElementById( 'createpage_image_cancel_section' + o.num ).style.display = '';
		document.getElementById( 'createpage_image_text_section' + o.num ).style.display = 'none';

		$( '#createpage_image_cancel_section' + o.num ).click( function () {
			sent_request.abort();
		} );

		var neoInput = document.createElement( 'input' );
		var thisInput = document.getElementById( 'createpage_upload_file_section' + o.num );
		var thisContainer = document.getElementById( 'createpage_image_label_section' + o.num );
		thisContainer.removeChild( thisInput );

		neoInput.setAttribute( 'type', 'file' );
		neoInput.setAttribute( 'id', 'createpage_upload_file_section' + o.num );
		neoInput.setAttribute( 'name', 'wpAllUploadFile' + o.num );
		neoInput.setAttribute( 'tabindex', '-1' );

		thisContainer.appendChild( neoInput );
		$( '#createpage_upload_file_section' + o.num ).change( function () {
			CreateAPage.upload( e, { num: o.num } );
		} );

		document.getElementById( 'createpage_upload_file_section' + o.num ).style.display = 'none';
	},

	/**
	 * Render the dialog which warns the user that switching to advanced (regular)
	 * edit mode may break stuff.
	 * Triggered when the user clicks on the "Advanced Edit" button on Special:CreatePage.
	 *
	 * @param {jQuery.Event} e
	 */
	buildWarningPanel: function ( e ) {
		// No longer exists in DOM, I removed it from templates-list.tmpl.php b/c
		// we should be building the entire dialog via jQuery anyway
		// var editwarn = document.getElementById( 'createpage_advanced_warning' );
		var editwarn_copy = document.createElement( 'div' );
		editwarn_copy.id = 'createpage_warning_copy';
		editwarn_copy.innerHTML = mw.msg( 'createpage-advanced-warning' ); // editwarn.innerHTML;
		document.body.appendChild( editwarn_copy );

		CreateAPage.warningPanel = $( '#createpage_warning_copy' ).dialog( {
			draggable: false,
			modal: true,
			resizable: false,
			width: 250,
			title: mw.msg( 'createpage-edit-normal' ),
			text: mw.msg( 'createpage-advanced-warning' ),
			buttons: [
				{
					text: mw.msg( 'createpage-yes' ),
					id: 'wpCreatepageWarningYes',
					click: function () {
						CreateAPage.goToEdit( e );
					}
				},
				{
					text: mw.msg( 'createpage-no' ),
					id: 'wpCreatepageWarningNo',
					click: function () {
						CreateAPage.hideWarningPanel( e );
						$( this ).dialog( 'close' );
					}
				}
			]
		} );
	},

	/**
	 * Build the login warning panel and set up its event listeners.
	 *
	 * @param {jQuery.Event} e
	 */
	buildWarningLoginPanel: function ( e ) {
		var editwarn_copy = document.createElement( 'div' );
		editwarn_copy.id = 'createpage_warning_copy2';
		editwarn_copy.innerHTML = mw.msg( 'createpage-login-warning' );
		document.body.appendChild( editwarn_copy );

		CreateAPage.warningLoginPanel = $( '#createpage_warning_copy2' ).dialog( {
			draggable: false,
			modal: true,
			resizable: false,
			width: 250,
			title: mw.msg( 'login' ),
			text: mw.msg( 'createpage-login-warning' ),
			buttons: [
				{
					text: mw.msg( 'createpage-yes' ),
					id: 'wpCreatepageWarningYes',
					click: function () {
						CreateAPage.goToLogin( e );
					}
				},
				{
					text: mw.msg( 'createpage-no' ),
					id: 'wpCreatepageWarningNo',
					click: function () {
						CreateAPage.hideWarningLoginPanel( e );
						$( this ).dialog( 'close' );
					}
				}
			]
		} );
	},

	onclickCategoryFn: function ( cat, id ) {
		return function () {
			CreateAPageCategoryTagCloud.remove( encodeURIComponent( cat ), id );
			return false;
		};
	},

	/**
	 * Remove any and all "This article already exists" messages.
	 *
	 * @param {jQuery.Event} e
	 */
	clearTitleMessage: function ( e ) {
		e.preventDefault();
		document.getElementById( 'cp-title-check' ).innerHTML = '';
	},

	/**
	 * Test whether the given element's ID matches createpage_upload_file_section
	 * or not.
	 *
	 * @param {Element} el HTML element to test
	 * @return {boolean} True if the element's ID matches, else false
	 */
	uploadTest: function ( el ) {
		if ( el.id.match( 'createpage_upload_file_section' ) ) {
			return true;
		} else {
			return false;
		}
	},

	/**
	 * Test whether the given element's ID matches wpTextboxes and that it's
	 * visible (i.e. display != none).
	 *
	 * @param {Element} el HTML element to test
	 * @return {boolean} True if the element's ID matches, else false
	 */
	editTextareaTest: function ( el ) {
		if ( el.id.match( 'wpTextboxes' ) && ( el.style.display !== 'none' ) ) {
			return true;
		} else {
			return false;
		}
	},

	/**
	 * Test whether the given element's ID matches wpOptionalInput and that
	 * it's visible (i.e. display != none).
	 *
	 * @param {Element} el HTML element to test
	 * @return {boolean} True if the element's ID matches, else false
	 */
	optionalSectionTest: function ( el ) {
		if ( el.id.match( 'wpOptionalInput' ) && ( el.style.display !== 'none' ) ) {
			return true;
		} else {
			return false;
		}
	},

	/**
	 * Test whether the given element's ID matches wpTextboxes.
	 *
	 * @param {Element} el HTML element to test
	 * @return {boolean} True if the element's ID matches, else false
	 */
	optionalContentTest: function ( el ) {
		if ( el.id.match( 'wpTextboxes' ) ) {
			return true;
		} else {
			return false;
		}
	},

	uploadEvent: function ( el ) {
		var j = parseInt( el.id.replace( 'createpage_upload_file_section', '' ) );
		$( '#createpage_upload_file_section' + j ).change( function ( e ) {
			CreateAPage.upload( e, { num: j } );
		} );
	},

	// @todo FIXME: rename to a more descriptive name now that old toolbar stuff is gone
	textareaAddToolbar: function ( el ) {
		var el_id = parseInt( el.id.replace( 'wpTextboxes', '' ) );

		$( '#wpTextIncrease' + el_id ).click( function ( e ) {
			CreateAPage.resizeThisTextarea( e, { textareaId: el_id, numRows: 1 } );
		} );
		$( '#wpTextDecrease' + el_id ).click( function ( e ) {
			CreateAPage.resizeThisTextarea( e, { textareaId: el_id, numRows: -1 } );
		} );
	},

	checkCategoryCloud: function () {
		var cat_textarea = document.getElementById( 'wpCategoryTextarea' );
		if ( !cat_textarea ) {
			return;
		}

		var cat_full_section = document.getElementById( 'createpage_cloud_section' );

		var cloud_num = ( cat_full_section.childNodes.length - 1 ) / 2;
		var n_cat_count = cloud_num;
		var text_categories = [];
		for ( var i = 0; i < cloud_num; i++ ) {
			var cloud_id = 'cloud' + i;
			var found_category = document.getElementById( cloud_id ).innerHTML;
			if ( found_category ) {
				CreateAPage.foundCategories[ i ] = found_category;
			}
		}

		var categories = cat_textarea.value;
		if ( categories === '' ) {
			return;
		}

		categories = categories.split( '|' );
		for ( i = 0; i < categories.length; i++ ) {
			text_categories[ i ] = categories[ i ];
		}

		for ( i = 0; i < text_categories.length; i++ ) {
			var c_found = false;
			var core_cat;
			for ( var j in CreateAPage.foundCategories ) {
				core_cat = text_categories[ i ].replace( /\|.*/, '' );
				if ( CreateAPage.foundCategories[ j ] === core_cat ) {
					var this_button = document.getElementById( 'cloud' + j );
					this_button.onclick = CreateAPage.onclickCategoryFn( text_categories[ i ], j );
					this_button.style.color = '#419636';
					c_found = true;
					break;
				}
			}

			if ( !c_found ) {
				var n_cat = document.createElement( 'a' );
				var s_cat = document.createElement( 'span' );
				n_cat_count++;
				var cat_num = n_cat_count - 1;
				n_cat.setAttribute( 'id', 'cloud' + cat_num );
				n_cat.setAttribute( 'href', '#' );
				n_cat.onclick = CreateAPage.onclickCategoryFn( text_categories[ i ], cat_num );
				n_cat.style.color = '#419636';
				n_cat.style.fontSize = '10pt';
				s_cat.setAttribute( 'id', 'tag' + n_cat_count );
				var t_cat = document.createTextNode( core_cat );
				var space = document.createTextNode( ' ' );
				n_cat.appendChild( t_cat );
				s_cat.appendChild( n_cat );
				s_cat.appendChild( space );
				cat_full_section.appendChild( s_cat );
			}
		}
	},

	/**
	 * Onclick handler for the up/down images, for making the given textarea
	 * either smaller or larger
	 *
	 * @param {jQuery.Event} e
	 * @param {Object} o Object containing a textareaId (numeric ID of a #wpTextboxes<num>
	 *   element) and numRows (amount of rows to increase or decrease)
	 */
	resizeThisTextarea: function ( e, o ) {
		e.preventDefault();
		var r_textarea = $( '#wpTextboxes' + o.textareaId );

		if (
			!( ( r_textarea.prop( 'rows' ) < 4 ) && ( o.numRows < 0 ) ) &&
			!( ( r_textarea.prop( 'rows' ) > 10 ) && ( o.numRows > 0 ) )
		) {
			r_textarea.prop( 'rows', r_textarea.prop( 'rows' ) + o.numRows );
		}
	},

	multiEditSetupOptionalSections: function () {
		var snum = 0;
		if ( document.getElementById( 'createpage_optionals_content' ) ) {
			var optionals = CreateAPage.getElementsBy(
				CreateAPage.optionalSectionTest,
				'input',
				document.getElementById( 'createpage_optionals_content' )
			);
			var optionalsElements = document.getElementById( 'wpOptionals' );
			for ( var i = 0; i < optionals.length; i++ ) {
				snum = optionals[ i ].id.replace( 'wpOptionalInput', '' );
				if ( !document.getElementById( 'wpOptionalInput' + snum ).checked ) {
					optionalsElements.value = CreateAPage.unuseSection(
						document.getElementById( 'createpage_section_' + snum ),
						optionalsElements.value
					);
				}
				$( '#' + optionals[ i ] ).change( function ( e ) {
					CreateAPage.toggleSection( e, { num: snum } );
				} );
			}
		}
	},

	initialRound: function () {
		document.getElementById( 'Createtitle' ).setAttribute( 'autocomplete', 'off' );
		if ( ( CreateAPage.previewMode === 'No' ) && ( CreateAPage.redLinkMode === 'No' ) ) {
			CreateAPage.contentOverlay();
		} else {
			var catlink = document.getElementById( 'catlinks' ); // @todo FIXME/CHECKME: isn't it a class in modern MWs?
			if ( catlink ) {
				var newCatlink = document.createElement( 'div' );
				newCatlink.setAttribute( 'id', 'catlinks' );
				newCatlink.innerHTML = catlink.innerHTML;
				catlink.parentNode.removeChild( catlink );
				var previewArea = document.getElementById( 'createpagepreview' );
				if ( previewArea !== null ) {
					previewArea.insertBefore( newCatlink, document.getElementById( 'createpage_preview_delimiter' ) );
				}
			}
		}

		var edit_textareas = CreateAPage.getElementsBy(
			CreateAPage.editTextareaTest,
			'textarea',
			document.getElementById( 'wpTableMultiEdit' ),
			CreateAPage.textareaAddToolbar
		);

		if ( ( CreateAPage.redLinkMode === 'Yes' ) && ( edit_textareas[ 0 ].id === 'wpTextboxes0' ) ) {
			edit_textareas[ 0 ].focus();
		}

		CreateAPage.multiEditSetupOptionalSections();
		CreateAPage.checkCategoryCloud();
	},

	/**
	 * Render the overlay that hides the editing buttons, the textarea and the
	 * save/preview/view changes buttons until the user supplies a title that
	 * does not exist yet on the wiki.
	 */
	contentOverlay: function () {
		// Based on the MIT-licensed jquery.overlay plugin by Tom McFarlin
		CreateAPage.Overlay = $( '#createpageoverlay' ).css( {
			background: '#000',
			display: 'none',
			// throw in an extra 25px to make sure that we *really* cover all
			// the editing buttons, the whole textarea *and* the buttons
			height: $( '#cp-restricted' ).height() + 25,
			// left: $( '#cp-restricted' ).offset().left, // more harmful than useful
			opacity: 0.5,
			overflow: 'hidden',
			position: 'absolute',
			// top: $( '#cp-restricted' ).offset().top, // more harmful than useful
			width: $( '#cp-restricted' ).width(),
			zIndex: 1000
		} ).show();

		var helperButton = document.getElementById( 'wpRunInitialCheck' );
		$( '#wpRunInitialCheck' ).click( function () {
			CreateAPage.watchTitle();
		} );
		helperButton.style.display = '';
	},

	appendHeight: function ( elem_height, number ) {
		var x_fixed_height = elem_height.replace( 'px', '' );
		x_fixed_height = parseFloat( x_fixed_height ) + number;
		x_fixed_height = x_fixed_height.toString() + 'px';
		return x_fixed_height;
	},

	/**
	 * Resize the overlay which blocks touching the textarea elements before the
	 * title check is complete.
	 *
	 * @param {number} [number] Amount of pixels to resize
	 */
	resizeOverlay: function ( number ) {
		var cont_elem = $( '#cp-restricted' );
		var fixed_height;
		var fixed_width;

		if ( cont_elem.css( 'height' ) === 'auto' ) {
			fixed_height = document.getElementById( 'cp-restricted' ).offsetHeight + number;
			fixed_width = document.getElementById( 'cp-restricted' ).offsetWidth;
		} else {
			fixed_height = cont_elem.css( 'height' );
			fixed_height = CreateAPage.appendHeight( fixed_height, number );
			fixed_width = cont_elem.css( 'width' );
		}

		CreateAPage.Overlay.css( 'height', fixed_height );
		CreateAPage.Overlay.css( 'width', fixed_width );
	},

	/**
	 * Initialize the createplate switcher at the top of the Special:CreatePage page.
	 */
	initializeMultiEdit: function () {
		$( 'div[id^="cp-template-"]' ).click( function ( e ) {
			CreateAPage.switchTemplate( e, $( this ).attr( 'id' ) );
		} );

		// Hide the radio buttons on the left side of each createplate's name,
		// they look ugly in here
		$( 'div[id^="cp-template-"]' ).each( function ( idx ) {
			$( '#' + $( this ).attr( 'id' ) + '-radio' ).hide();
		} );
	},

	/**
	 * Whenever a user clicks on one of the various createplate names, this
	 * function is called.
	 *
	 * @param {jQuery.Event} e
	 * @param {string} [elementId] Name of the createplate template (i.e.
	 * cp-template-Name) for a createplate named "Name"
	 */
	switchTemplate: function ( e, elementId ) {
		CreateAPage.myId = elementId;
		e.preventDefault();

		document.getElementById( 'cp-multiedit' ).innerHTML =
			'<img src="' + mw.config.get( 'wgExtensionAssetsPath' ) + '/CreateAPage/resources/images/progress_bar.gif" width="70" height="11" alt="' +
			mw.msg( 'createpage-please-wait' ) + '" border="0" />';
		if ( CreateAPage.Overlay ) {
			CreateAPage.resizeOverlay( 20 );
		}

		$.ajax( { // using .ajax instead of .get for better flexibility
			url: mw.config.get( 'wgScript' ),
			data: {
				action: 'ajax',
				rs: 'axMultiEditParse',
				template: elementId.replace( 'cp-template-', '' )
			},
			timeout: 50000
		} ).fail( function ( code, result ) {
			document.getElementById( 'cp-multiedit' ).innerHTML = '';
		} ).done( function ( result ) {
			var res = JSON.parse( result );
			$( '#cp-multiedit' ).html( res );

			$( 'div[id^="cp-template-"]' ).each( function ( idx ) {
				$( this ).addClass( 'templateFrame' );
				if ( $( this ).hasClass( 'templateFrameSelected' ) ) {
					$( this ).removeClass( 'templateFrameSelected' );
				}
			} );

			// Make the recently selected createplate active!
			$( '#' + CreateAPage.myId ).addClass( 'templateFrameSelected' );

			// @note infobox_root, infobox_inputs, infobox_uploads and section_uploads
			// _seem_ unused (together with the referenced methods like inputTest etc.)
			// but they are NOT!
			var infobox_root = document.getElementById( 'cp-infobox' );
			var infobox_inputs = CreateAPage.getElementsBy(
				CreateAPageInfobox.inputTest,
				'input',
				infobox_root,
				CreateAPageInfobox.inputEvent
			);
			var infobox_uploads = CreateAPage.getElementsBy(
				CreateAPageInfobox.uploadTest,
				'input',
				infobox_root,
				CreateAPageInfobox.uploadEvent
			);
			var content_root = document.getElementById( 'wpTableMultiEdit' );
			var section_uploads = CreateAPage.getElementsBy(
				CreateAPage.uploadTest,
				'input',
				content_root,
				CreateAPage.uploadEvent
			);

			var cloud_div = document.getElementById( 'createpage_cloud_div' );
			if ( cloud_div !== null ) {
				cloud_div.style.display = 'block';
			}
			CreateAPage.checkCategoryCloud();

			if ( CreateAPage.Overlay && $( '#createpageoverlay' ).is( ':visible' ) ) {
				CreateAPage.resizeOverlay( 20 );
			}

			var edit_textareas = CreateAPage.getElementsBy(
				CreateAPage.editTextareaTest,
				'textarea',
				content_root,
				CreateAPage.textareaAddToolbar
			);

			if ( ( CreateAPage.redLinkMode === 'Yes' ) && ( edit_textareas[ 0 ].id === 'wpTextboxes0' ) ) {
				edit_textareas[ 0 ].focus();
			}

			// Load WikiEditor for the newly created textarea elements, provided that
			// WikiEditor is installed an' all...
			if ( typeof $.wikiEditor === 'object' ) {
				// eslint-disable-next-line no-undef
				loadWikiEditorForTextboxes();
			}

			var edittools_div = document.getElementById( 'createpage_editTools' );
			if ( edittools_div ) {
				if ( CreateAPage.myId !== 'cp-template-Blank' ) {
					edittools_div.style.display = 'none';
				} else {
					edittools_div.style.display = '';
				}
			}

			CreateAPage.multiEditSetupOptionalSections();
		} );
	},

	/**
	 * Checks the user-supplied page title to see if there is such a page already
	 * and thus the user should be asked to either edit that page or choose a different
	 * title for their page, or whether we're good to remove the overlay and enable
	 * the editing areas.
	 *
	 * @param {jQuery.Event} e
	 */
	checkExistingTitle: function ( e ) {
		if ( document.getElementById( 'Createtitle' ).value === '' ) {
			e.preventDefault();
			document.getElementById( 'cp-title-check' ).innerHTML = '<span style="color: red;">' +
				mw.msg( 'createpage-give-title' ) + '</span>';
			window.location.hash = 'title_loc';
			CreateAPage.submitEnabled = false;
		} else if ( CreateAPage.noCanDo === true ) {
			CreateAPage.warningPanel = $( '#dlg' ).dialog( {
				// autoOpen: false,
				draggable: false,
				hide: 'slide',
				modal: true,
				resizable: false,
				title: mw.msg( 'createpage-title-check-header' ),
				text: mw.msg( 'createpage-title-check-text' ),
				// original YUI code used 20em, but I don't think jQuery supports that.
				// So I went to PXtoEM.com to convert 20em to pixels; I used
				// 20.5 as the base font size in pixels, because we set the
				// font size to 127% in Monobook's main.css and according to
				// their conversion tables, 20px is 125%
				width: 410
			} );
			e.preventDefault();
			CreateAPage.submitEnabled = false;
		}
		if (
			( CreateAPage.submitEnabled !== true ) ||
			( CreateAPage.Overlay && ( !$( '#createpageoverlay' ).is( ':hidden' ) ) )
		) {
			e.preventDefault();
		}
	},

	/**
	 * @param {jQuery.Event} e
	 */
	enableSubmit: function ( e ) {
		CreateAPage.submitEnabled = true;
	},

	/**
	 * Copied from the YUI library, version 2.5.2
	 *
	 * Copyright (c) 2008, Yahoo! Inc. All rights reserved.
	 * Code licensed under the BSD License:
	 * http://developer.yahoo.net/yui/license.txt
	 *
	 * @param {Function} method Such as CreateAPage.editTextareaTest,
	 * @param {string} tag e.g. 'textarea'
	 * @param {HTMLElement} root Result of a document.getElementById() call
	 * @param {Function|null} apply If defined, a function to call on matching elements,
	 *   such as CreateAPage.textareaAddToolbar
	 * @return {Object}
	 */
	getElementsBy: function ( method, tag, root, apply ) {
		tag = tag || '*';

		root = ( root ) ? /* $( */root /* ) */ : null || document;

		if ( !root ) {
			return [];
		}

		var nodes = [],
			elements = root.getElementsByTagName( tag );

		for ( var i = 0, len = elements.length; i < len; ++i ) {
			if ( method( elements[ i ] ) ) {
				nodes[ nodes.length ] = elements[ i ];

				if ( apply ) {
					apply( elements[ i ] );
				}
			}
		}

		return nodes;
	}
}; // end of the CreateAPage class

$( function () {
	$( window ).resize( function () {
		if ( CreateAPage.Overlay && !$( 'createpageoverlay' ).is( ':hidden' ) ) {
			CreateAPage.resizeOverlay( 0 );
		}
	} );
} );

/**
 * Class for uploading images from an infobox on the Special:CreatePage page.
 */
var CreateAPageInfobox = {
	failureCallback: function ( response ) {
		response = JSON.parse( response );
		document.getElementById( 'createpage_image_text' + response.argument ).innerHTML = mw.msg( 'createpage-insert-image' );
		document.getElementById( 'createpage_upload_progress' + response.argument ).innerHTML = mw.msg( 'createpage-upload-aborted' );
		document.getElementById( 'createpage_upload_file' + response.argument ).style.display = '';
		document.getElementById( 'createpage_image_text' + response.argument ).style.display = '';
		document.getElementById( 'createpage_image_cancel' + response.argument ).style.display = 'none';
	},

	/**
	 * @param {jQuery.Event} e
	 * @param {Object} o Object containing the number ('num') of the upload field
	 */
	upload: function ( e, o ) {
		var n = o.num;
		var oForm = document.getElementById( 'createpageform' );
		if ( oForm ) {
			e.preventDefault();

			$( '#createpage_upload_progress' + o.num ).show().html( $.createSpinner( 'createpage' ) );

			// Use HTML5 magic to do the file upload without having to resort to super
			// heavy jQuery plugins or anything like that
			var formData = new FormData();
			formData.append( 'wpUploadFile' + o.num, document.getElementById( 'createpage_upload_file' + o.num ).files[ 0 ] );

			var sent_request = $.ajax( { // using .ajax instead of .post for better flexibility
				type: 'POST',
				url: mw.config.get( 'wgScript' ) + '?action=ajax&rs=axMultiEditImageUpload&num=' + n,
				data: formData,
				contentType: false,
				cache: false,
				processData: false,
				timeout: 60000
			} ).done( function ( response ) {
				$.removeSpinner( 'createpage' );
				CreateAPageInfobox.uploadCallback( response );
			} ).fail( function ( code, result ) {
				$.removeSpinner( 'createpage' );
				CreateAPageInfobox.failureCallback( result );
			} );

			document.getElementById( 'createpage_image_cancel' + o.num ).style.display = '';
			document.getElementById( 'createpage_image_text' + o.num ).style.display = 'none';

			$( '#createpage_image_cancel' + o.num ).click( function () {
				sent_request.abort();
			} );

			var neoInput = document.createElement( 'input' );
			var thisInput = document.getElementById( 'createpage_upload_file' + o.num );
			var thisContainer = document.getElementById( 'createpage_image_label' + o.num );
			thisContainer.removeChild( thisInput );

			neoInput.setAttribute( 'type', 'file' );
			neoInput.setAttribute( 'id', 'createpage_upload_file' + o.num );
			neoInput.setAttribute( 'name', 'wpUploadFile' + o.num );
			neoInput.setAttribute( 'tabindex', '-1' );

			thisContainer.appendChild( neoInput );
			$( '#createpage_upload_file' + o.num ).change( function () {
				CreateAPageInfobox.upload( e, { num: o.num } );
			} );

			document.getElementById( 'createpage_upload_file' + o.num ).style.display = 'none';
		}
	},

	uploadCallback: function ( response ) {
		response = JSON.parse( response );
		var ProgressBar = $( '#createpage_upload_progress' + response.num );

		if ( response.error !== 1 ) {
			$( '#wpInfImg' + response.num ).val( response.msg );
			$( '#wpNoUse' + response.num ).val( 'Yes' );

			ProgressBar.html( mw.msg( 'createpage-img-uploaded' ) );
			var ImageThumbnail = $( '#createpage_image_thumb' + response.num );
			var thumb_container = $( '#createpage_main_thumb' + response.num );
			var tempstamp = new Date();
			ImageThumbnail.attr( 'src', response.url + '?' + tempstamp.getTime() );

			if ( $( '#wpLastTimestamp' + response.num ).val() === 'None' ) {
				var break_tag = document.createElement( 'br' );
				thumb_container.css( 'display', '' );
				var label_node = document.getElementById( 'createpage_image_label' + response.num );
				var par_node = label_node.parentNode;
				par_node.insertBefore( break_tag, label_node );
			}

			$( '#wpLastTimestamp' + response.num ).val( response.timestamp );
		} else if ( ( response.error === 1 ) && ( response.msg === 'cp_no_login' ) ) {
			ProgressBar.html(
				'<span style="color: red">' +
				mw.message(
					'createpage-login-required',
					mw.util.getUrl( 'Special:UserLogin', { returnto: 'Special:CreatePage' } ),
					'createpage_login_infobox' + response.num
				).text() +
				'</span>'
			);
		} else {
			ProgressBar.html( '<span style="color: red">' + response.msg + '</span>' );
		}

		document.getElementById( 'createpage_image_text' + response.num ).innerHTML = mw.msg( 'createpage-insert-image' );
		document.getElementById( 'createpage_upload_file' + response.num ).style.display = '';
		document.getElementById( 'createpage_image_text' + response.num ).style.display = '';
		document.getElementById( 'createpage_image_cancel' + response.num ).style.display = 'none';
	},

	/**
	 * Is the supplied HTMLElement an infobox parameter element?
	 *
	 * @param {HTMLElement} el
	 * @return {boolean} True if it is, otherwise false
	 */
	inputTest: function ( el ) {
		return !!el.id.match( 'wpInfoboxPar' );
	},

	inputEvent: function ( el ) {
		var j = parseInt( el.id.replace( 'wpInfoboxPar', '' ) );
		if ( $( '#wpInfoboxPar' + j ).length > 0 ) {
			CreateAPage.clearInput( { num: j } );
		}
	},

	/**
	 * Is the supplied HTMLElement a file upload element?
	 *
	 * @param {HTMLElement} el
	 * @return {boolean} True if it is, otherwise false
	 */
	uploadTest: function ( el ) {
		return !!el.id.match( 'createpage_upload_file' );
	},

	uploadEvent: function ( el ) {
		var j = parseInt( el.id.replace( 'createpage_upload_file', '' ) );
		$( '#createpage_upload_file' + j ).change( function ( e ) {
			CreateAPageInfobox.upload( e, { num: j } );
		} );
	}
};

// Initialize stuff when the DOM is ready
$( function () {
	// This creates the overlay over the editor and effectively blocks the user
	// from typing text on the textarea and thus forces them to supply the page
	// title first.
	// Should be executed when #cp-multiedit exists
	CreateAPage.initialRound();

	CreateAPage.initializeMultiEdit();

	$( '#createpageform' ).submit( function ( e ) {
		CreateAPage.checkExistingTitle( e );
	} );

	// Need to attach the selector on body so that the three buttons will work
	// even after switching to a different createplate
	$( 'body' ).on( 'click', '#wpSave', function ( e ) {
		CreateAPage.enableSubmit( e );
	} );

	$( 'body' ).on( 'click', '#wpPreview', function ( e ) {
		CreateAPage.enableSubmit( e );
		// Even when a CAPTCHA *is* required to _create_ a page, it oughta not be
		// required for merely _previewing_ your changes
		if ( $( '#wpCaptchaWord' ).length > 0 ) {
			$( '#wpCaptchaWord' ).prop( 'required', false );
		}
	} );

	$( 'body' ).on( 'click', '#wpCancel', function ( e ) {
		CreateAPage.enableSubmit( e );
	} );

	// [Hide]/[Show] links for the createplate selector
	$( '#cp-chooser-toggle' ).click( function ( e ) {
		e.preventDefault();
		// Note: I wanted to use .toggle( 500 ) or so for consistency with the old
		// code, but 1) it doesn't actually seem that smooth, and 2) it breaks the
		// code below which updates the hide/show text. With plain .toggle() the text
		// is updated correctly.
		$( '#cp-chooser' ).toggle();
		// Update label accordingly
		if ( $( '#cp-chooser' ).is( ':visible' ) ) {
			$( this ).text( mw.msg( 'createpage-hide' ) );
		} else {
			$( this ).text( mw.msg( 'createpage-show' ) );
		}
	} );

	// [Hide]/[Show] links for a createplate's infobox
	// Using the $( 'body' ) pattern instead of $( '#cp-infobox-toggle' ) b/c switching
	// a createplate using the selector would render those links non-functional
	$( 'body' ).on( 'click', '#cp-infobox-toggle', function ( e ) {
		e.preventDefault();
		$( '#cp-infobox' ).toggle();
		// Update label accordingly
		if ( $( '#cp-infobox' ).is( ':visible' ) ) {
			$( this ).text( mw.msg( 'createpage-hide' ) );
		} else {
			$( this ).text( mw.msg( 'createpage-show' ) );
		}
	} );

	// "Add a category" input (see categorypage.tmpl.php)
	$( '#wpCategoryButton' ).click( function ( e ) {
		e.preventDefault();
		CreateAPageCategoryTagCloud.inputAdd();
	} );

	// Handle clicks on category tags in the cloud
	// Moved from checkCategoryCloud() on 12 December 2019
	$( '#createpage_cloud_section a' ).click( function ( e ) {
		e.preventDefault();
		var tagName = $( this ).text();
		var num = $( this ).parent().attr( 'id' ).replace( /tag/, '' );
		// @todo FIXME: Clicking on a previously added category to remove it does not work (12 December 2019)
		CreateAPageCategoryTagCloud.add( encodeURIComponent( tagName ), num );
	} );

	$( '#Createtitle' ).change( CreateAPage.watchTitle );
	// Clicking on the "Article Title" input clears any "This article already
	// exists" messages
	$( '#Createtitle' ).on( 'focus', function ( e ) {
		CreateAPage.clearTitleMessage( e );
	} );

	// Clicking on the "Advanced Edit" button shows a modal dialog asking the
	// user, "Switching editing modes may break page formatting, do you want to continue?"
	$( '#wpAdvancedEdit' ).on( 'click', function ( e ) {
		// Prevent default action, which would be to follow the link to index.php
		// (which would then likely take the user to the wiki's Main Page)
		e.preventDefault();
		CreateAPage.showWarningPanel( e );
	} );

	// File upload stuff, main infobox image(s) and article sections
	$( 'input[id^="createpage_upload_file"]' ).change( function ( e ) {
		CreateAPageInfobox.upload( e, { num: $( this ).attr( 'id' ).replace( /createpage_upload_file/, '' ) } );
	} );

	$( 'input[id^="createpage_upload_file_section"]' ).change( function ( e ) {
		CreateAPage.upload( e, { num: $( this ).attr( 'id' ).replace( /createpage_upload_file_section/, '' ) } );
	} );

	// Moved from infobox.tmpl.php on 8 December 2019
	var ourInfoboxElement = $( 'input[id^="wpInfoboxPar"]' );
	if ( ourInfoboxElement.length > 0 ) {
		$( function ( e ) {
			CreateAPage.clearInput( { num: ourInfoboxElement.attr( 'id' ).replace( /wpInfoboxPar/, '' ) } );
		} );
	}

	// "or click here to go to the normal editor" link at the start of Special:CreatePage
	// Moved from CreatePageCreateplateForm.php on 8 December 2019
	$( '#createapage-go-to-normal-editor' ).on( 'click', function ( e ) {
		e.preventDefault();
		CreateAPage.goToNormalEditMode();
	} );

	// Login dialog shown to users who try to upload images w/o being logged in
	$( 'body' ).on( 'click', 'a[id^="createpage_login_infobox"]', function ( e ) {
		CreateAPage.showWarningLoginPanel( e );
	} );
} );
