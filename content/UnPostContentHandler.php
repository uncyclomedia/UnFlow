<?php

class UnPostContentHandler extends TextContentHandler {

	public function __construct( $modelId = 'UnPost', $format = 'text/unpost' ) {
		parent::__construct( $modelId, array( $format ) );
	}

	public function unserializeContent( $text, $format = null ) {
		$this->checkFormat( $format );

		return new UnPostContent( $text );
	}

	/**
	 * @see ContentHandler::makeEmptyContent
	 *
	 * @return Content
	 */
	public function makeEmptyContent() {
		return new UnPostContent( '' );
	}

	public function supportsRedirects() {
		return false;
	}

	public function supportsSections() {
		return false;
	}

	public function makeParserOptions( $context ) {
		if ( $context === 'canonical' ) {
			$context = RequestContext::getMain();
		}

		$options = parent::makeParserOptions( $context );
		$options->getUserLangObj(); // Split parser cache by user language
		return $options;
	}

	public function isParserCacheSupported() {
		// @todo does it?
		return true;
	}

	protected function getDiffEngineClass() {
		return 'UnDifferenceEngine';
	}

}
