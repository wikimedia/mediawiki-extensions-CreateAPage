/*
 * JavaScript for WikiEditor toolbars on Special:CreatePage
 */
window.loadWikiEditorForTextboxes = function () {
	// Replace icons
	$( 'textarea[id^="wpTextboxes"]' ).each( function () {
		$.wikiEditor.modules.dialogs.config.replaceIcons( $( this ) );

		// Add dialogs module
		$( this ).wikiEditor( 'addModule', $.wikiEditor.modules.dialogs.config.getDefaultConfig() );
		$( this ).wikiEditor(
			'addModule',
			$.wikiEditor.modules.toolbar.config.getDefaultConfig()
		);
	} );
};

// eslint-disable-next-line no-undef
$( loadWikiEditorForTextboxes );
