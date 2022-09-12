<?php
/**
 * @copyright Copyright © 2007, Wikia Inc.
 * @license GPL-2.0-or-later
 */

use Wikimedia\AtEase\AtEase;

class CAP_TagCloud {
	public $tags_min_pts = 8;
	public $tags_max_pts = 32;
	public $tags_highest_count = 0;
	public $tags_size_type = 'pt';
	/** @var int */
	public $limit;
	public $tags = [];

	/**
	 * @param int $limit
	 */
	public function __construct( $limit = 10 ) {
		$this->limit = $limit;
		$this->initialize();
	}

	public function initialize() {
		$dbr = wfGetDB( DB_PRIMARY );
		$res = $dbr->select(
			'categorylinks',
			[ 'cl_to', 'COUNT(*) AS count' ],
			[],
			__METHOD__,
			[
				'GROUP BY' => 'cl_to',
				'ORDER BY' => 'count DESC',
				'LIMIT' => $this->limit
			]
		);

		// prevent PHP from bitching about strtotime()
		AtEase::suppressWarnings();

		foreach ( $res as $row ) {
			$tag_name = Title::makeTitle( NS_CATEGORY, $row->cl_to );
			$tag_text = $tag_name->getText();

			// don't want dates to show up
			if ( strtotime( $tag_text ) == '' ) {
				if ( $row->count > $this->tags_highest_count ) {
					$this->tags_highest_count = $row->count;
				}
				$this->tags[$tag_text] = [
					'count' => $row->count
				];
			}
		}

		AtEase::restoreWarnings();

		// sort tag array by key (tag name)
		if ( $this->tags_highest_count == 0 ) {
			return;
		}
		ksort( $this->tags );
		/* and what if we have _1_ category? like on a new wiki with nteen articles, mhm? */
		if ( $this->tags_highest_count == 1 ) {
			$coef = $this->tags_max_pts - $this->tags_min_pts;
		} else {
			$coef = ( $this->tags_max_pts - $this->tags_min_pts ) / ( ( $this->tags_highest_count - 1 ) * 2 );
		}
		foreach ( $this->tags as $tag => $att ) {
			$this->tags[$tag]['size'] = $this->tags_min_pts + ( $this->tags[$tag]['count'] - 1 ) * $coef;
		}
	}
}
