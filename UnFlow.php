<?php

/**
 * UnFlow extension
 *
 * Enable at your own risk...it might explode.
 *
 * Config options below
 */

/**
 * Full page name of pages to enable UnFlow upon
 * @var array
 */
$wgUnFlowPages = array();

/**
 * Namespaces to enable UnFlow upon
 * @var array
 */
$wgUnFlowNamespaces = array( NS_USER_TALK );

// Useful when screwing with ContentHandler stuff
$wgContentHandlerUseDB = false;

// Extension setup stuff

define( 'NS_POST', 656 );
define( 'NS_POST_TALK', 657 ); // @fixme wtf

define( 'NS_TOPIC', 658 );
define( "NS_TOPIC_TALK", 689 ); // @fixme wtf

$wgContentHandlers['IndexContent'] = 'IndexContentHandler';
$wgContentHandlers['UnPostContent'] = 'UnPostContentHandler';

$wgNamespaceContentModels[NS_POST] = 'UnPostContent';

$wgHooks['CanonicalNamespaces'][] = 'UnHooks::onCanonicalNamespaces';
$wgHooks['BeforePageDisplay'][] = 'UnHooks::onBeforePageDisplay';
$wgHooks['ArticleRevisionVisibilitySet'][] = 'UnHooks::onArticleRevisionVisibilitySet';
$wgHooks['ContentHandlerDefaultModelFor'][] = 'UnHooks::onContentHandlerDefaultModelFor';

$wgResourceModules['ext.UnFlow.indent'] = array(
	'styles' => 'ext.UnFlow.indent.css',
	'localBasePath' => __DIR__ . '/modules',
	'remoteExtPath' => 'UnFlow/modules',
);

$wgExtensionMessagesFiles['UnFlow'] = __DIR__ . '/UnFlow.i18n.php';

$wgAPIModules['newtopic'] = 'ApiNewTopic';
$wgAPIModules['newreply'] = 'ApiNewReply';

$wgSpecialPages['NewReply'] = 'SpecialNewReply';
$wgSpecialPages['NewTopic'] = 'SpecialNewTopic';

$wgAutoloadClasses += array(
	// API modules
	'ApiNewTopic'          => __DIR__ . '/api/ApiNewTopic.php',
	'ApiNewReply'          => __DIR__ . '/api/ApiNewReply.php',

	// Specials
	'SpecialNewReply'      => __DIR__ . '/specials/SpecialNewReply.php',
	'SpecialNewTopic'      => __DIR__ . '/specials/SpecialNewTopic.php',

	// ContentHandler
	'UnPostContent'        => __DIR__ . '/content/UnPostContent.php',
	'UnPostContentHandler' => __DIR__ . '/content/UnPostContentHandler.php',
	'IndexContent'         => __DIR__ . '/content/IndexContent.php',
	'IndexContentHandler'  => __DIR__ . '/content/IndexContentHandler.php',

	// UnThings
	'UnFlow'               => __DIR__ . '/includes/UnFlow.php',
	'UnHooks'              => __DIR__ . '/includes/UnHooks.php',
);
