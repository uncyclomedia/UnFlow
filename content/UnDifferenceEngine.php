<?php

/**
 * Manage our own version too
 * @var int
 */
define( 'UNDIFF_VERSION', 3 );

class UnDifferenceEngine extends DifferenceEngine {

	public $enableDebugComment = true;

	/**
	 * @param UnPostContent $old
	 * @param UnPostContent $new
	 * @return string
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
		// @todo need getRemovedPosts
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
				$user = $newPost->getUser();
				if ( $user ) {
					$userName = $user->getName();
				} else {
					$userName = $this->msg( 'rev-deleted-user' )->text();
				}
				$link = Html::element( 'a', array( 'href' => '#' . $newPost->getId() ),
					$this->msg( 'unflow-diff-edit' )->params( $userName )->text()
				);
				$html .= $this->getCustomHeader( $link );
				// Reduce the line numbers no matter what it's configured to
				$old = wfSetVar( $this->mReducedLineNumbers, true );
				$html .= $this->removeEmptyRows( $this->localiseLineNumbers( $text ) );
				$this->mReducedLineNumbers = $old;
			}
		}

		return $html;
	}

	/**
	 * @fixme Ugh, this is a total hack.
	 * @param string $text
	 * @return string
	 */
	protected function removeEmptyRows( $text ) {
		return str_replace( '<td colspan="2" class="diff-lineno"></td>', '', $text );
	}

	/**
	 * @param string $header html
	 * @return string
	 */
	protected function getCustomHeader( $header ) {
		return '<tr><td colspan="2" class="diff-lineno">' . $header . '</td><td colspan="2" class="diff-lineno">' . $header . '</td></tr>';
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
