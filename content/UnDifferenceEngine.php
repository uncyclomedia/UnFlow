<?php

/**
 * Manage our own version too
 * @var int
 */
define( 'UNDIFF_VERSION', 1 );

class UnDifferenceEngine extends DifferenceEngine {

	public $enableDebugComment = true;

	protected $mReducedLineNumbers = true;

	/**
	 * @param UnPostContent $old
	 * @param UnPostContent $new
	 * @return bool|string
	 */
	function generateContentDiffBody( Content $old, Content $new ) {
		// @FIXME This only handles the addition of new posts right now
		// and will probably fail on everything else.
		$oldThread = $old->getThread();
		$newThread = $new->getThread();
		$newIds = array_values( array_diff(
			UnThread::getAllIds( $newThread ), UnThread::getAllIds( $oldThread )
		) );
		// $newIds will only have one id, since you can only add one reply
		// at a time.
		$newPost = UnThread::findPost( $newThread, $newIds[0] );
		// @todo We should add custom headers here.
		return $this->generateTextDiffBody( '', $newPost->getText() );
	}

	protected function getDiffBodyCacheKey() {
		return parent::getDiffBodyCacheKey() . ':' . UNDIFF_VERSION;
	}
}
