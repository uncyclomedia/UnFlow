<?php

class UnFlow {

	/**
	 * @param string $type
	 * @todo This should check an id isn't currently in use to avoid random errors
	 * @return String
	 */
	public static function getNewId( $type ) {
		return $type . MWCryptRand::generateHex( 16 );
	}

	/**
	 * We need to delay the creation of the LinksUpdate object
	 * since our page might not exist yet
	 * @param Title $title
	 * @param ParserOutput $po
	 */
	public static function addLinksUpdate( Title $title, ParserOutput $po ) {
		DeferredUpdates::addCallableUpdate( function() use ( $title, $po ) {
			$lu = new LinksUpdate( $title, $po );
			$lu->doUpdate();
		});
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
	 * @param User $user
	 * @return string
	 */
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

	public static function isUnFlowEnabled( Title $title ) {
		global $wgUnFlowPages, $wgUnFlowNamespaces;
		return ( in_array( $title->getNamespace(), $wgUnFlowNamespaces )
			|| in_array( $title->getPrefixedText(), $wgUnFlowPages ) );
	}

	public static function batchLoadThread( UnThread $thread ) {
		$dbr = wfGetDB( DB_SLAVE );
		$select = array_merge( Revision::selectFields(), array( 'upa_post' ) );

		$ids = UnThread::getAllIds( $thread );
		$count = count( $ids );
		if ( $count > 1 ) {
			$conds = $dbr->makeList( $ids, LIST_OR );
		} elseif( $count == 1 ) {
			$conds = $ids[0];
		} else {
			return;
		}

		$rows = $dbr->select(
			array( 'revision', 'unpost_revids' ),
			$select,
			array( 'upa_post' => $conds ),
			__METHOD__,
			array(),
			array( 'unpost_revids' => array( 'JOIN', 'upa_revid = rev_id' ) )
		);

		foreach( $rows as $row ) {
			UnThread::findPost( $thread, $row->upa_post )
				->setRev( Revision::newFromRow( $row ) );
		}
	}

}
