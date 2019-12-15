<?php
/**
 * A special page to create a new article using the easy-to-use interface at
 * Special:CreatePage.
 *
 * @file
 * @ingroup Extensions
 * @version 4.00 (Based on Wikia SVN r15554)
 * @author Bartek Łapiński <bartek@wikia-inc.com>
 * @author Jack Phoenix
 * @copyright Copyright © 2007-2008 Wikia Inc.
 * @copyright Copyright © 2009-2011, 2019 Jack Phoenix
 * @license GPL-2.0-or-later
 * @link https://www.mediawiki.org/wiki/Extension:CreateAPage Documentation
 * @note Removed from Wikia's repository in February 2012 after having been unused for two years, see https://github.com/Wikia/app/commit/e52d845350c8c49c6b39a4399b3d6e8f91e0461f
 */

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = [
	'name' => 'CreateAPage',
	'license-name' => 'GPL-2.0-or-later',
	'author' => [
		'Bartek Łapiński', 'Piotr Molski', 'Łukasz Garczewski', 'Przemek Piotrowski',
		'Jack Phoenix'
	],
	'version' => '4.00-super-alpha',
	'descriptionmsg' => 'createpage-desc',
	'url' => 'https://www.mediawiki.org/wiki/Extension:CreateAPage',
];

$wgMultiEditPageTags = [ 'title', 'descr', 'category' ];
$wgMultiEditPageSimpleTags = [ 'lbl', 'categories', 'pagetitle', 'imageupload', 'optional' ];

// Autoload classes and set up the new special page(s)
$wgMessagesDirs['CreateAPage'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['CreatePageAliases'] = __DIR__ . '/CreatePage.alias.php';

$wgAutoloadClasses['CreateAPageUtils'] = __DIR__ . '/includes/CreateAPageUtils.php';
$wgAutoloadClasses['CreateAPageHooks'] = __DIR__ . '/includes/CreateAPageHooks.php';
$wgAutoloadClasses['EasyTemplate'] = __DIR__ . '/includes/EasyTemplate.php'; // @todo FIXME: kill templates and remove this class
$wgAutoloadClasses['CAP_TagCloud'] = __DIR__ . '/includes/CAP_TagCloud.php';
$wgAutoloadClasses['CreateMultiPage'] = __DIR__ . '/includes/CreateMultiPage.php';
$wgAutoloadClasses['CreatePage'] = __DIR__ . '/includes/specials/SpecialCreatePage.body.php';
$wgAutoloadClasses['CreatePageEditor'] = __DIR__ . '/includes/CreatePageEditor.php';
$wgAutoloadClasses['CreatePageMultiEditor'] = __DIR__ . '/includes/CreatePageMultiEditor.php';
$wgAutoloadClasses['CreatePageCreateplateForm'] = __DIR__ . '/includes/CreatePageCreateplateForm.php';
$wgAutoloadClasses['CreatePageImageUploadForm'] = __DIR__ . '/includes/CreatePageImageUploadForm.php';

$wgSpecialPages['CreatePage'] = 'CreatePage';

// Load AJAX functions, too
require_once __DIR__ . '/includes/specials/SpecialCreatePage_ajax.php';

// ResourceLoader support for MediaWiki 1.17+
$wgResourceModules['ext.createAPage'] = [
	'scripts' => 'js/CreateAPage.js',
	'messages' => [
		'createpage-insert-image', 'createpage-upload-aborted',
		'createpage-img-uploaded', 'createpage-login-required',
		'createpage-login-href', 'createpage-login-required2',
		'createpage-give-title', 'createpage-img-uploaded',
		'createpage-article-exists', 'createpage-article-exists2',
		'createpage-title-invalid', 'createpage-please-wait',
		'createpage-show', 'createpage-hide',
		'createpage-must-specify-title', 'createpage-unsaved-changes',
		'createpage-unsaved-changes-details',
		'createpage-edit-normal',
		'createpage-advanced-warning',
		'createpage-yes', 'createpage-no'
	],
	'dependencies' => [ 'jquery.ui', 'jquery.spinner', 'mediawiki.util' ],
	'localBasePath' => __DIR__ . '/resources',
	'remoteExtPath' => 'CreateAPage/resources'
];

$wgResourceModules['ext.createAPage.wikiEditor'] = [
	'scripts' => 'js/WikiEditorIntegration.js',
	'dependencies' => 'ext.wikiEditor',
	'localBasePath' => __DIR__ . '/resources',
	'remoteExtPath' => 'CreateAPage/resources'
];

$wgResourceModules['ext.createAPage.styles'] = [
	'styles' => 'css/CreatePage.css',
	'localBasePath' => __DIR__ . '/resources',
	'remoteExtPath' => 'CreateAPage/resources'
];

// Our only configuration setting -- use CreateAPage on redlinks (i.e. clicking
// on a redlink takes you to index.php?title=Special:CreatePage&Createtitle=Title_of_our_page
// instead of taking you to index.php?title=Title_of_our_page&action=edit&redlink=1)
$wgCreatePageCoverRedLinks = false;

// Hooked functions
$wgHooks['EditPage::showEditForm:initial'][] = 'CreateAPageHooks::preloadContent';
$wgHooks['CustomEditor'][] = 'CreateAPageHooks::onCustomEditor';
$wgHooks['GetPreferences'][] = 'CreateAPageHooks::onGetPreferences';
