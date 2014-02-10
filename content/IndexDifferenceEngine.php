<?php

define( 'INDEX_DIFF_VERSION', 1 );

class IndexDifferenceEngine extends DifferenceEngine {

	public function getDiffBodyCacheKey() {
		return parent::getDiffBodyCacheKey() . INDEX_DIFF_VERSION;
	}
}
