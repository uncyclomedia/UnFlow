<?php

class IndexContentHandler extends TextContentHandler {

	public function __construct( $modelId = 'IndexContent' ) {
		parent::__construct( $modelId, array( CONTENT_FORMAT_JSON ) );
	}

	public function unserializeContent( $text, $format = null ) {
		$this->checkFormat( $format );
		return new IndexContent( $text );
	}

	public function makeEmptyContent() {
		return new IndexContent( '[]' );
	}

	public function isParserCacheSupported() {
		return true;
	}

	public function makeParserOptions( $context ) {
		if ( $context === 'canonical' ) {
			$context = RequestContext::getMain();
		}

		$options = parent::makeParserOptions( $context );
		$options->getUserLangObj(); // Split parser cache by user language
		return $options;
	}

	protected function getDiffEngineClass() {
		return 'IndexDifferenceEngine';
	}
}
