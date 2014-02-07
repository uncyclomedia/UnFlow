<?php

class UnHooks {

	/**
	 * @param array $ns
	 * @return bool
	 */
	public static function onCanonicalNamespaces( array &$ns ) {
		$ns[NS_POST] = 'Post';
		$ns[NS_POST_TALK] = 'Post_talk';
		$ns[NS_TOPIC] = 'Topic';
		$ns[NS_TOPIC_TALK] = 'Topic_talk';
		return true;
	}

	/**
	 * @param Title $title
	 * @param string|null $model
	 * @return bool
	 */
	public static function onContentHandlerDefaultModelFor( Title $title, &$model ) {
		if ( UnFlow::isUnFlowEnabled( $title ) ) {
			$model = 'IndexContent';
			return false;
		}

		return true;
	}

	/**
	 * @param OutputPage $out
	 * @param Skin $skin
	 * @return bool
	 */
	public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ) {
		if ( UnFlow::isUnFlowEnabled( $out->getTitle() )
			|| $out->getTitle()->inNamespace( NS_POST )
		) {
			$out->addModuleStyles( 'ext.UnFlow.indent' );
			$out->addModules( 'ext.UnFlow.reply' );
		}

		return true;
	}

	/**
	 * This function is a bit over-agressive in clearing the cache, but
	 * will have to do until bug 58596 is fixed.
	 * @param Title $title
	 * @return bool
	 */
	public static function onArticleRevisionVisibilitySet( Title &$title ) {
		if ( $title->inNamespace( NS_POST ) ) {
			UnFlow::getCache()->delete( wfMemcKey( 'unflow', 'creator', $title->getRootText() ) );
		}

		return true;
	}
}