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
	 * Occurs after the save page request has been processed.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageContentSaveComplete
	 *
	 * @param WikiPage $article
	 * @param User $user
	 * @param Content $content
	 * @param string $summary
	 * @param boolean $isMinor
	 * @param boolean $isWatch
	 * @param $section
	 * @param integer $flags
	 * @param Revision $revision
	 * @param Status $status
	 * @param integer $baseRevId
	 * @return boolean
	 */
	public static function onPageContentSaveComplete( $article, $user, $content, $summary,
	                                                  $isMinor, $isWatch, $section, $flags, $revision, $status, $baseRevId ) {
		if ( !$article->getTitle()->inNamespace( NS_POST ) ) {
			return true;
		}

		/** @var UnPostContent $content */
		$thread = $content->getThread();
		$newIds = UnThread::getAllIds( $thread );
		$parent = $revision->getParentId();
		if ( $parent ) {
			/** @var UnPostContent $oldContent */
			$oldContent = Revision::newFromId( $parent )->getContent( Revision::RAW );
			$oldIds = UnThread::getAllIds( $oldContent->getThread() );
		} else {
			$oldIds = array();
		}

		$reallyNewIds = array_diff( $newIds, $oldIds );
		if ( $reallyNewIds ) {
			$rows = array();
			foreach( $reallyNewIds as $id ) {
				$rows[] = array(
					'upa_post' => $id,
					'upa_revid' => $revision->getId(),
				);
			}
			wfGetDB( DB_MASTER )->insert(
				'unpost_revids',
				$rows,
				__METHOD__
			);
		}

		return true;
	}

}
