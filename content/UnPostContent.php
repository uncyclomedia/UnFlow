<?php

/**
 * Schema looks something like:
 * {
 *  'topic': topic text
 *  'id': UnThread####
 *  'cmts': [
 *      {
 *          'id': UnPost####,
 *          'text': text,
 *          'user': username/IP,
 *          'ts': db timestamp,
 *          'cmts': [ ... ]
 *      },
 *  ],
 * }
 *
 */
class UnPostContent extends TextContent {

	/**
	 * @var bool|stdClass
	 */
	protected $json = false;

	public function __construct( $text = '' ) {
		parent::__construct( $text, 'UnPostContent' );
	}

	/**
	 * Returns JSON representation, lazy-loaded
	 * @return stdClass
	 */
	protected function getJsonData() {
		if ( $this->json === false ) {
			$this->json = FormatJson::decode( $this->getNativeData() );
		}
		return $this->json;
	}

	/**
	 * @return UnThread
	 */
	public function getThread() {
		return UnThread::newFromJSON( $this->getJsonData() );
	}

	/**
	 * @todo should this be in the UnThread class?
	 * @param UnThread $thread
	 * @return UnPostContent
	 */
	public static function newFromThread( UnThread $thread ) {
		return new UnPostContent( FormatJson::encode( $thread->toJSON() ) );
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

		$thread = $this->getThread();
		$html = $thread->toHtml( $title, $options );
		$po = new ParserOutput();
		$po->setText( $html );
		$po->recordOption( 'userlang' );

		return $po;
	}

	public function getSection( $section ) {
		return false;
	}

}