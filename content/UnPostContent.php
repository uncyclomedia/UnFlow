<?php

/**
 * Just raw text, but we parse it differently
 *
 */
class UnPostContent extends TextContent {

	public function __construct( $text = '' ) {
		parent::__construct( $text, 'UnPostContent' );
	}

	/**
	 * @param Title $title
	 * @return string HTML text
	 */
	protected function getCreator( Title $title ) {
		// @todo We have a hook clearing memcache, make sure that's good enough for revdel
		$postId = $title->getRootText();
		$cache = UnFlow::getCache();
		$key = wfMemcKey( 'unflow', 'creator', $postId );
		$data = $cache->get( $key );
		if ( $data === false ) {
			$wp = WikiPage::factory( $title );
			if ( $wp->getOldestRevision() ) {
				$creator = $wp->getCreator();
			} else {
				// Post is currently being created...
				$creator = RequestContext::getMain()->getUser();
			}
			if ( $creator ) {
				$data = UnFlow::userToolLinks( $creator );
			} else {
				$data = wfMessage( 'rev-deleted-user' )->parse();
			}
			$cache->set( $key, $data );
		}
		return $data;
	}

	/**
	 * @param Title $title
	 * @return string database timestamp
	 */
	protected function getTimestamp( Title $title ) {
		$cache = UnFlow::getCache();
		$postId = $title->getRootText();
		$key = wfMemcKey( 'unflow', 'ts', $postId );
		$ts = $cache->get( $key );
		if ( $ts === false ) {
			$oldRev = WikiPage::factory( $title )->getOldestRevision();
			if ( $oldRev ) {
				$ts = $oldRev->getTimestamp();
			} else {
				$ts = wfTimestampNow(); // Page is being created right now
			}
			$cache->set( $key, $ts );
		}
		return $ts;

	}

	/**
	 * @param Title $title
	 * @param null|int $revId
	 * @param ParserOptions $options
	 * @param bool $generateHtml
	 * @return ParserOutput
	 */
	public function getParserOutput( Title $title, $revId = null,
		ParserOptions $options = null, $generateHtml = true
	) {

		if ( !$options ) {
			//NOTE: use canonical options per default to produce cacheable output
			$options = $this->getContentHandler()->makeParserOptions( 'canonical' );
		}

		/** @var Parser $wgParser */
		/** @var Language $wgLang */
		global $wgParser, $wgLang;
		$po = $wgParser->parse( $this->getNativeData(), $title, $options, true, true, $revId );

		$html = '<a name="' . $title->getRootText() . '"></a><div class="mw-unpost-comment">'. $po->getText() . '</div>';
		$link = Linker::link(
			SpecialPage::getTitleFor( 'NewReply', $title->getRootText() ),
			wfMessage( 'unflow-reply' )->escaped()
		);
		$html .= '<div class="mw-posted-by">' . wfMessage( 'unflow-posted-by' )
			->rawParams( $this->getCreator( $title ) )
			->params( $wgLang->formatExpiry( $this->getTimestamp( $title ) ) )
			->rawParams( $link )
			->parse() . '</div>';
		$html .= UnFlow::getChildrenHtml(
			$title->getRootText(),
			$po,
			$title,
			$options,
			$generateHtml
		);
		$po->setText( $html );
		$po->recordOption( 'userlang' );
		return $po;
	}

	// Now disable some stuff that wikitext supports but we don't want to
	public function getSection( $section ) {
		return false;
	}

}