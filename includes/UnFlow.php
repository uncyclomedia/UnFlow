<?php

class UnFlow {

	/**
	 * @param string $type
	 * @todo This should check an id isn't currently in use
	 * @return String
	 */
	public static function getNewId( $type ) {
		return $type . MWCryptRand::generateHex( 16 );
	}

	/**
	 * @todo Do something else here.
	 * @return BagOStuff
	 */
	public static function getCache() {
		global $wgMemc;
		return $wgMemc;
	}

	/**
	 * This function is a bit silly...
	 * @param ParserOutput $po
	 * @param Title $title
	 * @param Title $pageTitle ParserOutput is for
	 */
	public static function addTemplateLink( ParserOutput $po, Title $title, Title $pageTitle ) {
		$po->addTemplate( $title, $title->getArticleID(), $title->getLatestRevID() );
		//DeferredUpdates::addUpdate( new LinksUpdate( $pageTitle, $po ) );
	}

	public static function registerNewReply( $postId, $newPostId ) {
		wfGetDB( DB_MASTER )->insert(
			'un_posts',
			array( array(
				'un_id' => $postId,
				'un_child' => $newPostId,
			) )
		);

		// Invalidate the cache
		self::getCache()->delete( wfMemcKey( 'unflow', $postId ) );
	}

	public static function userToolLinks( User $user ) {
		$altname = $user->getName() === 'Wctaiwan' ? 'wctaiwan' : false;
		return
			Linker::userLink( $user->getId(), $user->getName(), $altname )
			. Linker::userToolLinks( $user->getId(), $user->getName() );
	}

	/**
	 * @param IContextSource $ctx
	 * @param Title $title
	 * @param Content $content
	 * @param string $summary
	 * @param int $flags
	 * @param bool $revid
	 * @return Status
	 */
	public static function makeEdit( IContextSource $ctx, Title $title,
	                                 Content $content, $summary = '',
	                                 $flags = 0, $revid = false
	) {
		if ( !$title->userCan( 'edit', $ctx->getUser() ) ) {
			// @todo make this better
			return Status::newFatal( 'permissiondenied' );
		}

		$wp = WikiPage::factory( $title );
		return $wp->doEditContent( $content, $summary, $flags, $revid, $ctx->getUser() );
	}

	/**
	 * @param string $id
	 * @param ParserOutput $po
	 * @param Title $title
	 * @param ParserOptions|null $options
	 * @param bool $generateHtml
	 * @return string
	 */
	public static function getChildrenHtml( $id, ParserOutput $po, Title $title, ParserOptions $options, $generateHtml = true ) {
		$cache = self::getCache();
		$key = wfMemcKey( 'unflow', $id );
		$childs = $cache->get( $key );
		if ( $childs === false ) {
			$rows = wfGetDB( DB_SLAVE )->select(
				array( 'un_posts' ),
				array( 'un_child' ),
				array( 'un_id' => $id ),
				__METHOD__
			);

			$childs = array();
			foreach( $rows as $row ) {
				$childs[] = $row->un_child;
			}
			$cache->set( $key, $childs );
		}

		$html = '';
		foreach( $childs as $child ) {
			$postTitle = Title::makeTitle( NS_POST, $child );
			self::addTemplateLink( $po, $postTitle, $title );
			$post = Revision::newFromTitle( $postTitle );
			/** @var UnPostContent $content */
			$content = $post->getContent();
			//$options->enableLimitReport( false ); // @todo figure out how to do this properly
			$postHTML = $content->getParserOutput( $postTitle, null, $options, $generateHtml )->getText();
			// Now wrap it in the div
			if ( $title->inNamespace( NS_POST ) ) {
				$postHTML = '<div class="mw-unpost-reply">' . $postHTML . '</div>';
			}
			$html .= $postHTML;
		}

		return $html;
	}

	public static function isUnFlowEnabled( Title $title ) {
		global $wgUnFlowPages, $wgUnFlowNamespaces;
		return ( in_array( $title->getNamespace(), $wgUnFlowNamespaces )
			|| in_array( $title->getPrefixedText(), $wgUnFlowPages ) );
	}

}
