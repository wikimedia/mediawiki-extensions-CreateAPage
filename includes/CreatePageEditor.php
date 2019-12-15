<?php
/**
 * @file
 * @ingroup Extensions
 * @author Bartek Łapiński <bartek@wikia-inc.com>
 * @copyright Copyright © 2007 Bartek Łapiński, Wikia Inc.
 * @license GPL-2.0-or-later
 */

// all editor-related functions will go there
abstract class CreatePageEditor {
	public $mTemplate;

	function __construct( $template ) {
		$this->mTemplate = $template;
	}

	abstract public function generateForm();

	abstract public function glueArticle();
}
