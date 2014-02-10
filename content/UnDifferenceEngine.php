<?php

class UnDifferenceEngine extends DifferenceEngine {
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
}
