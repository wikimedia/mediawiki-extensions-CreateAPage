<?php

class CreateAPageUtils {
	// Restore what we temporarily encoded
	// moved from CreateMultiPage.php
	public static function unescapeKnownMarkupTags( &$text ) {
		$text = str_replace( '<!---pipe--->', '|', $text );
	}
}