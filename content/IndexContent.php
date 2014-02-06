
<?php
/**
 * Represents the content of a JSON Schema article.
 *
 * Schema looks something like:
 * [
 *  {
 *      'threadId': $id,
 *      'ts': timestamp
 *      'user': $username,
 *      'source': one of 'local', 'db', or 'api'
 *      'remotedb': 'metawiki'  # Optional
 *      'remoteurl': 'https://en.wikipedia.org/w/api.php'  # Also optional.
 *  },
 * ];
 */
class IndexContent extends TextContent {

	function __construct( $text ) {
		parent::__construct( $text, 'IndexContent' );
	}

	/**
	 * Decodes the JSON schema into a PHP associative array.
	 * @return array: Schema array.
	 */
	function getJsonData() {
		return FormatJson::decode( $this->getNativeData() );
	}

	/**
	 * @throws MWException: If invalid.
	 * @return bool: True if valid.
	 */
	function validate() {
		// @todo implement this
		return true;
	}

	/**
	 * @return bool: Whether content is valid JSON Schema.
	 */
	function isValid() {
		// @todo implement this
		return true;
	}

	/**
	 * @todo implement this
	 * @return string
	 */
	function getTextForSearchIndex() {
		return '';
	}

	/*
	function getSize() {
		$json = $this->getJsonData();
		return strlen( $json['text'] );
	}*/

	function getTextForSummary( $maxlength = 250 ) {
		// @todo This could be better...
		return substr( $this->getNativeData(), 0, 250 );
	}

	/**
	 * New "empty" content
	 * @return IndexContent
	 */
	static function newEmptyContent() {
		return new IndexContent( FormatJson::encode( array() ) );
	}

	/**
	 * Returns a generic ParserOutput object, wrapping the HTML returned by
	 * getHtml().
	 *
	 * @param $title Title Context title for parsing
	 * @param int|null $revId Revision ID (for {{REVISIONID}})
	 * @param $options ParserOptions|null Parser options
	 * @param bool $generateHtml Whether or not to generate HTML
	 *
	 * @return ParserOutput representing the HTML form of the text
	 */
	public function getParserOutput( Title $title,
	                                 $revId = null,
	                                 ParserOptions $options = null, $generateHtml = true
	) {

		if ( !$options ) {
			//NOTE: use canonical options per default to produce cacheable output
			$options = $this->getContentHandler()->makeParserOptions( 'canonical' );
		}

		$html = '';
		$toc = UnTOC::getHeader();
		$po = new ParserOutput();

		if ( $generateHtml ) {
			foreach ( $this->getJsonData() as $threadInfo ) {
				$threadTitle = Title::makeTitle( NS_POST, $threadInfo->threadId );
				$thread = UnThread::newFromTitle( $threadTitle );
				$html .= $thread->toHtml( $title, $options );
				$topicHtml = $thread->getTopicHtml( $title, $options );
				$toc .= UnTOC::formatRow( $threadInfo, $thread, $topicHtml, $options->getUserLangObj() );
				$po->addTemplate( $threadTitle, $threadTitle->getArticleID(), $threadTitle->getLatestRevID() );
				// @TODO add this in somehow
/*				$html .= '<div class=mw-thread-topic">' . $po->getText() . '</div>';
				$link = Linker::link(
					SpecialPage::getTitleFor( 'NewReply', $thread['thread-id'] ),
					wfMessage( 'unflow-reply' )->escaped()
				);

				$html .= '<div class="mw-thread-posted-by">' . wfMessage( 'unflow-thread-posted-by' )
					->rawParams( UnFlow::userToolLinks( User::newFromName( $thread['user'] ) ) )
					->params( $wgLang->formatExpiry( $thread['ts'] ) )
					->rawParams( $link )
					->parse() . '</div>';
*/
			}
		}

		$po->setText( $toc . $html );
		$po->recordOption( 'userlang' );

		return $po;
	}



}
