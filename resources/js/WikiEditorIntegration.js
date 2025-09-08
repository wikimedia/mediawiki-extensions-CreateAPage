/*
 * JavaScript for WikiEditor toolbars on Special:CreatePage
 */
window.loadWikiEditorForTextboxes = function () {
	// Replace icons
	$( 'textarea[id^="wpTextboxes"]' ).each( function () {
		// @todo FIXME: using mw.loader.moduleRegistry like this feels like a filthy hack
		// *but* it works, unlike anything related to require()...
		const dialogsConfig = mw.loader.moduleRegistry[ 'ext.wikiEditor' ].packageExports[ 'jquery.wikiEditor.dialogs.config.js' ];

		dialogsConfig.replaceIcons( $( this ) );

		// Add dialogs module
		$( this ).wikiEditor(
			'addModule',
			dialogsConfig.getDefaultConfig()
		);
		$( this ).wikiEditor(
			'addModule',
			mw.loader.moduleRegistry[ 'ext.wikiEditor' ].packageExports[ 'jquery.wikiEditor.toolbar.config.js' ]
		);
	} );
};

// eslint-disable-next-line no-undef
$( loadWikiEditorForTextboxes );
