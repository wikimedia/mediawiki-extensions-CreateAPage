<?php

class CreateAPageUtils {
	/**
	 * Restore what we temporarily encoded
	 * moved from CreateMultiPage.php
	 *
	 * @param string &$text
	 */
	public static function unescapeKnownMarkupTags( &$text ) {
		$text = str_replace( '<!---pipe--->', '|', $text );
	}
}
