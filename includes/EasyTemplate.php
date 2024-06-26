<?php
/**
 * @file
 * @author Krzysztof Krzyżaniak <eloy@wikia-inc.com>
 * @version: $Id: EasyTemplate.php 9427 2008-02-15 11:31:05Z eloy $
 *
 * EasyTemplate class for easy mixing HTML/JavaScript/CSS/PHP code
 * ideas taken from Template class by
 * Copyright © 2003 Brian E. Lozier (brian@massassi.net)
 *
 * setVars() method contributed by Ricardo Garcia (Thanks!)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

class EasyTemplate {

	/** @var string */
	public $mPath;
	/** @var array */
	public $mVars;

	/**
	 * Public constructor
	 * Example:
	 * @code
	 * new EasyTemplate( __DIR__ . '/templates/' );
	 * @endcode
	 * @param string $path
	 */
	public function __construct( $path ) {
		$this->mPath = rtrim( $path, '/' );
		$this->mVars = [];
	}

	/**
	 * Set a bunch of variables at once using an associative array.
	 *
	 * @param array $vars Variables to set
	 * @param bool $clear Whether to completely overwrite the existing vars
	 */
	public function setVars( $vars, $clear = false ) {
		if ( $clear ) {
			$this->mVars = $vars;
		} else {
			$this->mVars = is_array( $vars )
				? array_merge( $this->mVars, $vars )
				: array_merge( $this->mVars, [] );
		}
	}

	/**
	 * Set a variable
	 *
	 * @param string $name Variable name
	 * @param mixed $value Variable value
	 */
	public function set( $name, $value ) {
		$this->mVars[$name] = $value;
	}

	/**
	 * Open, parse, and return the template file.
	 *
	 * @param string $file The template file name
	 * @return string
	 */
	public function render( $file ) {
		if ( !strstr( $file, '.tmpl.php' ) ) {
			$file .= '.tmpl.php';
		}

		// phpcs:ignore MediaWiki.Usage.ForbiddenFunctions.extract
		extract( $this->mVars );
		ob_start();
		include $this->mPath . '/' . $file;
		$contents = ob_get_clean();

		return $contents;
	}

	/**
	 * Check if template file exists
	 *
	 * @param string $file path to file with template
	 * @return bool
	 */
	public function templateExists( $file ) {
		if ( !strstr( $file, '.tmpl.php' ) ) {
			$file .= '.tmpl.php';
		}
		return file_exists( $this->mPath . '/' . $file );
	}

	/**
	 * Reset variables array
	 */
	public function reset() {
		$this->mVars = [];
	}
}
