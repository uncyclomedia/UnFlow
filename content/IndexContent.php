
<?php
/**
 * Represents the content of a JSON Schema article.
 *
 * Schema looks something like:
 * [
 *  {
 *      'thread-id': $id,
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
		/** @var Parser $wgParser */
		/** @var Language $wgLang */
		global $wgParser, $wgLang;

		if ( !$options ) {
			//NOTE: use canonical options per default to produce cacheable output
			$options = $this->getContentHandler()->makeParserOptions( 'canonical' );
		}

		$html = '';
		$parserOutput = new ParserOutput();

		if ( $generateHtml ) {
			foreach ( $this->getJsonData() as $thread ) {
				$thread = (array)$thread;
				$html .= "<a name=\"{$thread['thread-id']}\"></a>";
				$topicTitle = Title::makeTitle( NS_TOPIC, $thread['thread-id'] );
				UnFlow::addTemplateLink( $parserOutput, $topicTitle, $title );
				$content = Revision::newFromTitle( $topicTitle )->getContent();
				$text = "== {$content->getNativeData()} =="; // @fixme find a better way to do this
				$options->setEditSection( false ); // Don't create edit section links that don't work
				$options->enableLimitReport( false );
				$po = $wgParser->parse( $text, $title, $options, true, true );
				$html .= '<div class=mw-thread-topic">' . $po->getText() . '</div>';
				$link = Linker::link(
					SpecialPage::getTitleFor( 'NewReply', $thread['thread-id'] ),
					wfMessage( 'unflow-reply' )->escaped()
				);

				$html .= '<div class="mw-thread-posted-by">' . wfMessage( 'unflow-thread-posted-by' )
					->rawParams( UnFlow::userToolLinks( User::newFromName( $thread['user'] ) ) )
					->params( $wgLang->formatExpiry( $thread['ts'] ) )
					->rawParams( $link )
					->parse() . '</div>';
				// Fetch the replies...
				$html .= UnFlow::getChildrenHtml(
					$thread['thread-id'],
					$parserOutput,
					$title,
					$options,
					$generateHtml
				);
			}

		}

		$parserOutput->setText( $html );
		$parserOutput->recordOption( 'userlang' );

		return $parserOutput;
	}



}
