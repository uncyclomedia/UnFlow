<?php

class UnThread {

	protected $id;
	/** @var string $topic */
	protected $topic;
	/** @var string $parsedTopic */
	protected $parsedTopic;
	/** @var UnPost[] $replies */
	protected $replies = array();

	public static function newThread( $topic ) {
		$t = new UnThread;
		$t->id = UnFlow::getNewId( __CLASS__ );
		$t->topic = $topic;
		return $t;
	}

	/*
	 * Assumes a valid title is provided that already exists
	 * @param Title $title
	 * @return UnThread
	 */
	public static function newFromTitle( Title $title ) {
		/** @var UnPostContent $content */
		$content = Revision::newFromTitle( $title )->getContent();
		return $content->getThread();
	}

	/**
	 * @return array
	 */
	public function toJSON() {
		$arr = array(
			'topic' => $this->topic,
			'id' => $this->id,
			'cmts' => array(),
		);

		foreach( $this->getReplies() as $reply ) {
			$arr['cmts'][] = $reply->toJSON();
		}

		return $arr;
	}

	public function newReply( UnPost $post ) {
		$this->replies[] = $post;
	}

	/**
	 * @param stdClass $obj
	 * @return UnThread
	 */
	public static function newFromJSON( $obj ) {
		$t = new UnThread;
		$t->id = $obj->id;
		$t->topic = $obj->topic;
		foreach( $obj->cmts as $cmt ) {
			$t->replies[] = UnPost::newFromJSON( $cmt );
		}

		return $t;
	}

	public function getId() {
		return $this->id;
	}

	public function getReplies() {
		return $this->replies;
	}

	/**
	 * @param UnPost|UnThread $post
	 * @param $id
	 * @return UnPost|bool
	 */
	public static function findPost( $post, $id ) {
		foreach ( $post->getReplies() as $reply ) {
			if ( $reply->getId() === $id ) {
				return $reply;
			}
			$rep = self::findPost( $reply, $id );
			if ( $rep !== false ) {
				return $rep;
			}
		}

		return false;
	}

	/**
	 * @fixme this whole thing is a terrible hack :((((((
	 * @param Title $title
	 * @param ParserOptions $opts
	 * @return string HTML output
	 */
	public function getTopicHtml( Title $title, ParserOptions $opts ) {
		if ( $this->parsedTopic ) {
			return $this->parsedTopic;
		}
		global $wgParser;
		$opts->setEditSection( false );
		$opts->enableLimitReport( false );
		// @fixme this is soooo terrible.
		$text = "== {$this->getTopic()} ==";
		$po = $wgParser->parse( $text, $title, $opts );
		if ( $title->exists() ) {
			// @fixme fix this.
			DeferredUpdates::addUpdate( new LinksUpdate( $title, $po ) );
		}
		return $this->parsedTopic = $po->getText();
	}

	/**
	 * @param UnPost|UnThread $thread
	 * @return int
	 */
	public static function countReplies( $thread ) {
		$count = count( $thread->getReplies() );
		foreach( $thread->getReplies() as $reply ) {
			$count += self::countReplies( $reply );
		}

		return $count;
	}

	/**
	 * @param UnPost|UnThread $thread
	 * @param UnPost|null $post
	 * @return UnPost
	 */
	public static function findLatestReply( $thread, $post = null ) {
		foreach ( $thread->getReplies() as $reply ) {
			if ( !$post ) {
				$post = $reply;
			} elseif ( $reply->getTimestamp() > $post->getTimestamp() ) {
				$post = $reply;
			}

			$post = self::findLatestReply( $reply, $post );
		}


		return $post;
	}

	/**
	 * @return string
	 */
	public function getTopic() {
		return $this->topic;
	}

	public function toHtml( Title $title, ParserOptions $opts ) {
		wfProfileIn( __METHOD__ );
		/** @var Parser $wgParser */
		global $wgParser;

		//$opts = clone $opts;
		$topic = $this->getTopicHtml( $title, $opts );
		$html = "<a name=\"{$this->getId()}\"></a>";
		$html .= '<div class="mw-thread-topic">' . $topic . '</div>';
		foreach( $this->getReplies() as $reply ) {
			$html .= $reply->toHtml( $this, $title, $opts );
		}

		wfProfileOut( __METHOD__ );
		return $html;
	}

}