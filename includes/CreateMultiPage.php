<?php
/**
 * @file
 * @ingroup Extensions
 * @version 1.0
 * @author Piotr Molski <moli@wikia-inc.com>
 * @author Bartek Łapiński <bartek@wikia-inc.com>
 * @copyright Copyright © 2007 Piotr Molski, Wikia Inc.
 * @license GPL-2.0-or-later
 */

class CreateMultiPage {
	public const SECTION_PARSE = '/\n==[^=]/s';
	public const SPECIAL_TAG_FORMAT = '<!---%s--->';
	public const ADDITIONAL_TAG_PARSE = '/\<!---(.*?)\s*=\s*(&quot;|\'|")*(.*?)(&quot;|\'|")*---\>/is';
	public const SIMPLE_TAG_PARSE = '/\<!---(.*?)---\>/is';
	// @todo FIXME: This should probably be made more i18n-friendly?
	public const CATEGORY_TAG_PARSE = '/\[\[Category:(.*?)\]\]/';
	public const CATEGORY_TAG_SPECIFIC = '/\<!---categories---\>/is';
	// used outside this class in templates/infobox.tmpl.php:
	public const IMAGEUPLOAD_TAG_SPECIFIC = '/\<!---imageupload---\>/is';
	// used _only_ outside this class in templates/infobox.tmpl.php:
	public const INFOBOX_SEPARATOR = '/\<!---separator---\>/is';
	public const ISBLANK_TAG_SPECIFIC = '<!---blanktemplate--->';
	// public const TEMPLATE_INFOBOX_FORMAT = '/\{\{[^\{\}]*Infobox.*\}\}/is'; // replaced by [[MediaWiki:Createpage-template-infobox-format]]
	//public const TEMPLATE_OPENING = '/\{\{[^\{\}]*Infobox[^\|]*/i'; // literally unused
	public const TEMPLATE_CLOSING = '/\}\}/';

	function __construct() {
	}

	public static function unescapeBlankMarker( $text ) {
		$text = str_replace( "\n<!---blanktemplate--->\n", '', $text );
		$text = str_replace( '<!---imageupload--->', '', $text );
		return $text;
	}

	// @todo FIXME: remove $ew parameter, it appears to be always '?' and serves no purpose whatsoever
	public static function multiEditParse( $rows, $cols, $ew, $sourceText, $optional_sections = null ) {
		global $wgExtensionAssetsPath;
		global $wgMultiEditTag;
		global $wgMultiEditPageSimpleTags, $wgMultiEditPageTags;

		$contLang = MediaWiki\MediaWikiServices::getInstance()->getContentLanguage();
		$me_content = '';
		$found_categories = [];

		$is_used_metag = false;
		$is_used_category_cloud = false;
		$wgMultiEditTag = ( empty( $wgMultiEditTag ) ) ? 'useMultiEdit' : $wgMultiEditTag;
		$multiedit_tag = '<!---' . $wgMultiEditTag . '--->';

		# is tag set?
		if ( empty( $wgMultiEditTag ) || ( strpos( $sourceText, $multiedit_tag ) === false ) ) {
			if ( strpos( $sourceText, self::ISBLANK_TAG_SPECIFIC ) !== true ) {
				$sourceText = str_replace( self::ISBLANK_TAG_SPECIFIC . "\n", '', $sourceText );
				$sourceText = str_replace( self::ISBLANK_TAG_SPECIFIC, '', $sourceText );

				// fire off a special one textarea template
				$tmpl = new EasyTemplate( __DIR__ . '/../templates/' );
				$tmpl->set_vars( [
					'box' => $sourceText
				] );
				$me_content .= $tmpl->render( 'bigarea' );

				$cloud = new CAP_TagCloud();
				$tmpl = new EasyTemplate( __DIR__ . '/../templates/' );
				$tmpl->set_vars( [
					'num' => 0,
					'cloud' => $cloud,
					'cols' => $cols,
					'ew' => $ew,
					'text_category' => '' ,
					'array_category' => []
				] );
				$me_content .= $tmpl->render( 'categorypage' );
				return $me_content;
			} else {
				return false;
			}
		} else {
			$sourceText = str_replace( $multiedit_tag, '', $sourceText );
			$is_used_metag = true;
		}

		$category_tags = null;
		preg_match_all( self::CATEGORY_TAG_SPECIFIC, $sourceText, $category_tags );
		if ( is_array( $category_tags ) ) {
			$is_used_category_cloud = true;
			$sourceText = preg_replace( self::CATEGORY_TAG_SPECIFIC, '', $sourceText );
		}

		// get infoboxes out...
		// @todo FIXME: should validate the regex and if it's invalid, use the i18n msg below with ->useDatabase( false )
		// to fetch it from the i18n/en.json file as a fallback. --ashley, 10 December 2019
		preg_match_all(
			wfMessage( 'createpage-template-infobox-format' )->inContentLanguage()->text(),
			$sourceText,
			$infoboxes,
			PREG_OFFSET_CAPTURE
		);

		// new functions to exclude any additional '}}'s from match
		if ( is_array( $infoboxes ) && is_array( $infoboxes[0] ) && !empty( $infoboxes[0][0] ) ) {
			$to_parametrize = $infoboxes[0][0];
			$infobox_start = $to_parametrize[1];
			// take first "}}" here - this should be infoboxes' end
			$infobox_end = strpos( $sourceText, '}}' );
			$to_parametrize = substr( $sourceText, $infobox_start, $infobox_end - $infobox_start + 2 );
			$sourceText = str_replace( $to_parametrize, '', $sourceText );

			$to_parametrize = preg_replace( self::TEMPLATE_CLOSING, '', $to_parametrize );

			// fix issues with |'s given inside the infobox parameters...
			$pre_inf_pars = preg_split( "/\|/", $to_parametrize, -1 );

			$fixed_par_array = [];
			$fix_corrector = 0;

			for ( $i = 0; $i < count( $pre_inf_pars ); $i++ ) {
				// this was cut out from user supplying '|' inside the parameter...
				if ( ( strpos( $pre_inf_pars[$i], '=' ) === false ) && ( $i != 0 ) ) {
					$fixed_par_array[$i - ( 1 + $fix_corrector )] .= '|' . $pre_inf_pars[$i];
					$fix_corrector++;
				} else {
					$fixed_par_array[] = $pre_inf_pars[$i];
				}
			}

			array_shift( $fixed_par_array );
			array_walk( $fixed_par_array, 'CreateAPageUtils::unescapeKnownMarkupTags' );

			$num = 0;
			$tmpl = new EasyTemplate( __DIR__ . '/../templates/' );
			$tmpl->set_vars( [
				'num' => $num,
				'infoboxes' => $to_parametrize,
				'inf_pars' => $fixed_par_array,
			] );

			$me_content .= $tmpl->render( 'infobox' );
		}

		# check sections exist
		$sections = preg_split( self::SECTION_PARSE, $sourceText, -1, PREG_SPLIT_OFFSET_CAPTURE );
		$is_section = ( count( $sections ) > 1 ? true : false );

		$boxes = [];
		$num = 0;
		$loop = 0;
		$optionals = [];

		if ( $is_used_metag ) {
			$boxes[] = [
				'type' => 'text',
				'value' => addslashes( $multiedit_tag ),
				'display' => 0
			];
			$num = 1;
			$loop++;
		}

		$all_image_num = 0;

		/**
		 * Parse sections
		 */
		foreach ( $sections as $section ) {
			# empty section
			$add = '';
			if ( ( $section[1] == 0 ) && ( empty( $section[0] ) ) ) {
				continue;
			} elseif ( intval( $section[1] ) > 0 ) {
				// add last character truncated by preg_split()
				$add = substr( $sourceText, $section[1] - 1, 1 );
			}

			# get section text
			$text = ( ( $num && ( !empty( $add ) ) ) ? '==' : '' ) . $add . $section[0];

			preg_match( '!==(.*?)==!s', $text, $name );
			$section_name = $section_wout_tags = '';
			# section name
			if ( !empty( $name ) ) {
				$section_name = $name[0];
				$section_wout_tags = trim( $name[1] );
			}
			if ( !empty( $section_name ) ) {
				$boxes[] = [
					'type' => 'section_display',
					'value' => '<b>' . $section_wout_tags . '</b>',
					'display' => 1
				];
				$boxes[] = [
					'type' => 'text',
					'value' => addslashes( $section_name ),
					'display' => 0
				];
			} else {
				$boxes[] = [
					'type' => 'section_display',
					'value' => '<b>' . wfMessage( 'createpage-top-of-page' )->escaped() . '</b>',
					'display' => 1
				];
			}

			# text without section name
			if ( strlen( $section_name ) > 0 ) {
				// strip section name
				$text = substr( $text, strlen( $section_name ) + 1 );
			}
			// strip unneeded newlines
			$text = trim( $text );

			/**
			 * <(descr|title|pagetitle)="..."> tag support
			 */
			$main_tags = '';
			$special_tags = [];
			preg_match_all( self::ADDITIONAL_TAG_PARSE, $text, $me_tags );

			if ( isset( $me_tags ) && ( !empty( $me_tags[1] ) ) ) {
				foreach ( $me_tags[1] as $id => $_tag ) {
					$brt = $me_tags[2][$id];
					$correct_brt = ( $brt == '&quot;' ) ? '"' : $brt;
					if ( in_array( $_tag, $wgMultiEditPageTags ) ) {
						switch ( $_tag ) {
							case 'title':
							case 'descr':
							case 'category': {
								if ( empty( $special_tags[$_tag] ) || ( $_tag == 'category' ) ) {
									$special_tags[$_tag] = $me_tags[3][$id];
									if ( $_tag != 'category' ) {
										$format_tag_text = ( $_tag == 'title' ) ? '<b>%s</b>' : '<small>%s</small>';
									} else {
										$format_tag_text = '%s';
									}
									if ( $_tag != 'category' ) {
										$type = '';
										if ( $_tag == 'title' ) {
											$type = 'title';
										}
										# remove special tags
										$text = str_replace( "<!---{$_tag}={$brt}" . $special_tags[$_tag] . "{$brt}--->", '', $text );
										// strip unneeded newlines
										$text = trim( $text );
										# add to display
										$boxes[] = [
											'type' => $type,
											'value' => sprintf( $format_tag_text, $special_tags[$_tag] ),
											'display' => 1
										];
										$main_tags .= "<!---{$_tag}={$correct_brt}" . $special_tags[$_tag] . "{$correct_brt}--->\n";
									} else {
										$text = str_replace(
											"<!---{$_tag}={$brt}" . $special_tags[$_tag] . "{$brt}--->",
											'[[' . $contLang->getNsText( NS_CATEGORY ) .
												':' . sprintf( $format_tag_text, $special_tags[$_tag] ) . ']]',
											// '[[Category:' . sprintf( $format_tag_text, $special_tags[$_tag] ) . ']]',
											$text
										);
									}
								}
								break;
							}
						}
					}
				}
			}

			// parse given categories into an array...
			preg_match_all( self::CATEGORY_TAG_PARSE, $text, $categories, PREG_SET_ORDER );
			// and dispose of them, since they will be in the cloud anyway
			$text = preg_replace( self::CATEGORY_TAG_PARSE, '', $text );
			if ( is_array( $categories ) ) {
				$found_categories = $found_categories + $categories;
			}

			/**
			 * Display section name and additional tags as hidden text
			 */
			if ( !empty( $main_tags ) ) {
				$boxes[] = [
					'type' => 'textarea',
					'value' => $main_tags,
					'toolbar' => '',
					'display' => 0
				];
			}

			/**
			 * other tags - lbl, pagetitle, optional, imageupload
			 */
			preg_match( self::SIMPLE_TAG_PARSE, $text, $other_tags );
			$specialTag = ( isset( $other_tags ) && ( !empty( $other_tags[1] ) ) ) ? $other_tags[1] : 'generic';

			if (
				( !empty( $specialTag ) ) &&
				( !empty( $wgMultiEditPageSimpleTags ) ) &&
				( in_array( $specialTag, $wgMultiEditPageSimpleTags ) )
			) {
				$boxes[] = [
					'type' => 'text',
					'value' => sprintf( self::SPECIAL_TAG_FORMAT, $specialTag ),
					'display' => 0
				];
				switch ( $specialTag ) {
					// <!---lbl---> tag support
					case 'lbl': {
						// strip <!---lbl---> tag
						$text_html = str_replace( $other_tags[0], '', $text );
						// strip unneeded newlines
						$text_html = trim( $text_html );
						// this section type is non-editable, so we just rebuild its contents in JavaScript code
						$boxes[] = [
							'type' => 'textarea',
							'value' => $text_html,
							'toolbar' => '',
							'display' => 0
						];
						$boxes[] = [
							'type' => '',
							'value' => $text_html,
							'display' => 1
						];
						break;
					}
					// <!---pagetitle---> tag support
					case 'pagetitle': {
						// strip <!---pagetitle---> tag
						$text_html = str_replace( $other_tags[0], '', $text );
						// strip unneeded newlines
						$text_html = trim( $text_html );
						// this section type is non-editable, so we just rebuild its contents in JavaScript code
						$boxes[] = [
							'type' => 'text',
							'value' => $text_html,
							'display' => 1
						];
						break;
					}
					// <!---optional---> tag support
					case 'optional': {
						// strip the tag
						$text_html = str_replace( $other_tags[0], '', $text );
						$text_html = trim( $text_html );
						$boxes[] = [
							'type' => 'optional_textarea',
							'value' => $text_html,
							'toolbar' => '',
							'display' => 1
						];

						$optionals[] = count( $boxes ) - 1;
						break;
					}
					// <!---imageupload---> tag support
					case 'imageupload': {
						// do a match here, and for each do the thing, yeah
						preg_match_all( self::IMAGEUPLOAD_TAG_SPECIFIC, $text, $image_tags );

						// one we had already
						$cur_img_count = count( $image_tags ) - 1;
						foreach ( $image_tags[0] as $image_tag ) {
							if ( $cur_img_count > 0 ) {
								$boxes[] = [
									'type' => 'text',
									'value' => sprintf( self::SPECIAL_TAG_FORMAT, 'imageupload' ),
									'display' => 0
								];
							}
							$cur_img_count++;
						}

						$text = str_replace( $other_tags[0], '', $text );

						$boxes[] = [
							'type' => 'textarea',
							'value' => $text,
							'toolbar' => '',
							'display' => 1
						];

						$current = count( $boxes ) - count( $image_tags[0] ) - 1;
						$add_img_num = 0;
						foreach ( $image_tags[0] as $image_tag ) {
							$tmpl = new EasyTemplate( __DIR__ . '/../templates/' );
							$tmpl->set_vars( [
								'imagenum' => $all_image_num,
								'target_tag' => $current + $add_img_num
							] );
							$image_text = $tmpl->render( 'editimage-section' );
							$boxes[] = [
								'type' => 'image',
								'value' => $image_text,
								'display' => 1
							];
							$add_img_num++;
							$all_image_num++;
						}
					}
				}
			} elseif ( $specialTag == 'generic' ) {
				// generic textarea
				$boxes[] = [
					'type' => 'textarea',
					'value' => $text,
					'toolbar' => '',
					'display' => 1
				];
			}

			$boxes[] = [
				'type' => '',
				'value' => '<br/><!--end of section-->',
				'display' => 1
			];
			$num++;
		}

		$tmpl = new EasyTemplate( __DIR__ . '/../templates/' );
		$tmpl->set_vars( [
			'boxes' => $boxes,
			'cols' => $cols,
			'rows' => $rows,
			'ew' => $ew,
			'is_section' => $is_section,
			'imgpath' => $wgExtensionAssetsPath . '/CreateAPage/resources/images/',
			'optional_sections' => $optional_sections
		] );
		$me_content .= $tmpl->render( 'editpage' );

		if ( $is_used_category_cloud ) {
			// categories are generated here... well, except for Blank
			// init some class here to get categories form to display
			$text_category = '';
			$xnum = 0;
			$array_category = [];

			foreach ( $found_categories as $category ) {
				$cat_text = trim( $category[1] );
				// the separator needs to be the pipe and not a comma for the categories
				// to be correctly split; see CreatePageMultiEditor#glueCategories
				// --ashley, 12 December 2019
				$text_category .= ( $xnum ? '|' : '' ) . $cat_text;
				$array_category[$cat_text] = 1;
				$xnum++;
			}

			$cloud = new CAP_TagCloud();

			$tmpl = new EasyTemplate( __DIR__ . '/../templates/' );
			$tmpl->set_vars( [
				'num' => $num,
				'cloud' => $cloud,
				'cols' => $cols,
				'ew' => $ew,
				'text_category' => $text_category,
				'array_category' => $array_category
			] );

			$me_content .= $tmpl->render( 'categorypage' );
		}

		return $me_content;
	}
}
