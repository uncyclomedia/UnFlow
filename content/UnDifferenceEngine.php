<?php

/**
 * Manage our own version too
 * @var int
 */
define( 'UNDIFF_VERSION', 2 );

class UnDifferenceEngine extends DifferenceEngine {

	public $enableDebugComment = true;

	/**
	 * @param UnPostContent $old
	 * @param UnPostContent $new
	 * @return bool|string
	 */
	function generateContentDiffBody( Content $old, Content $new ) {
		// @FIXME This only handles the addition of new posts right now
		// and will probably fail on everything else.
		$oldThread = $old->getThread();
		$allOldIds = UnThread::getAllIds( $oldThread );
		$newThread = $new->getThread();
		$allNewIds = UnThread::getAllIds( $newThread );
		$newIds = array_values( array_diff(
			$allNewIds, $allOldIds
		) );
		$commonIds = array_values( array_intersect(
			$allNewIds, $allOldIds
		) );
		$html = '';
		$html .= $this->getNewReplyPosts( $newIds, $newThread );
		$html .= $this->getEdittedPosts( $commonIds, $oldThread, $newThread );

		return $html;
	}

	protected function getNewReplyPosts( array $newIds, UnThread $newThread ) {
		$html = '';
		foreach( $newIds as $newId ) {
			$newPost = UnThread::findPost( $newThread, $newId );
			$text = $this->generateTextDiffBody( '', $newPost->getText() );
			$html .= $this->getNewReplyDiffHeader( $text, $newPost );
		}
		return $html;
	}

	/**
	 * @param string $text
	 * @param UnPost $unPost
	 * @return string
	 */
	protected function getNewReplyDiffHeader( $text, UnPost $unPost ) {
		$us = $this;
		return preg_replace_callback(
			'/<!--LINE (\d+)-->/',
			function ( $matches ) use ( $us, $unPost ) {
				static $first = null;
				if ( is_null( $first ) ) {
					// Ignore the first one, which is on the left side.
					$first = true;
					return '';
				} else {
					$user = $unPost->getUser();
					if ( $user ) {
						$userName = $user->getName();
					} else {
						$userName = $us->msg( 'rev-deleted-user' )->text();
					}
					$msg = $us->msg( 'unflow-diff-new-reply' )->params( $userName )->escaped();
					return Html::rawElement( 'a', array( 'href' => '#' . $unPost->getId() ),  $msg );
				}
			},
			$text
		);
	}

	protected function getEdittedPosts( array $commonIds, UnThread $old, UnThread $new ) {
		$html = '';
		foreach( $commonIds as $id ) {
			$oldPost = UnThread::findPost( $old, $id );
			$newPost = UnThread::findPost( $new, $id );
			if ( $newPost->getText() !== $oldPost->getText() ) {
				$text = $this->generateTextDiffBody( $oldPost->getText(), $newPost->getText() );
				// @fixme set a custom header here.
				$html .= $this->localiseLineNumbers( $text );
			}
		}

		return $html;
	}

	/**
	 * Add our own diff versioning scheme, plus split
	 * cache by language code
	 * @return string
	 */
	protected function getDiffBodyCacheKey() {
		return parent::getDiffBodyCacheKey()
		. ':' . UNDIFF_VERSION . ':' . $this->getLanguage()->getCode();
	}
}
