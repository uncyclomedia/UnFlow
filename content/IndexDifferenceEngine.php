<?php

define( 'INDEX_DIFF_VERSION', 1 );

class IndexDifferenceEngine extends DifferenceEngine {

	/**
	 * @param IndexContent $old
	 * @param IndexContent $new
	 * @return string
	 */
	function generateContentDiffBody( Content $old, Content $new ) {
		// @fixme only supports adding of one thread
		$newIds = $oldIds = array();
		foreach( $new->getJsonData() as $data ) {
			$newIds[] = $data->threadId;
		}
		foreach( $old->getJsonData() as $data ) {
			$oldIds[] = $data->threadId;
		}
		$html = '';
		$diff = array_values( array_diff( $newIds, $oldIds ) );
		foreach( $diff as $id ) {
			$topic = UnThread::getTopicFor( $id );
			$html .= $this->generateTextDiffBody( '', $topic );

		}
		return $html;
	}



	public function getDiffBodyCacheKey() {
		return parent::getDiffBodyCacheKey() . INDEX_DIFF_VERSION;
	}
}
