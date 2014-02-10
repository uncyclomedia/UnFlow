<?php

class UnPost {
	protected $id;
	/** @var User $user */
	protected $user = false;
	protected $text;
	protected $ts;
	/** @var Revision $rev */
	protected $rev;
	/** @var UnPost[] $replies */
	protected $replies = array();

	public static function newPost( $text ) {
		$p = new UnPost;
		$p->id = UnFlow::getNewId( __CLASS__ );
		$p->text = $text;
		$p->ts = wfTimestampNow();
		return $p;
	}

	public function getText() {
		return $this->text;
	}

	/**
	 * @param Revision|int $id
	 */
	public function setRev( $id ) {
		if ( $id instanceof Revision ) {
			$this->rev = $id;
		} else {
			$this->rev = Revision::newFromId( $id );
		}
	}

	/**
	 * @todo load from the db if not set
	 * @return Revision
	 */
	public function getRev() {
		return $this->rev;
	}

	public function newReply( UnPost $post ) {
		$this->replies[] = $post;
	}

	public function setText( $text ) {
		$this->text = $text;
	}

	/**
	 * Recursively export to an array
	 * @return array
	 */
	public function toJSON() {
		$arr = array(
			'id' => $this->id,
			'text' => $this->text,
			'ts' => $this->ts,
			'cmts' => array(),
		);
		foreach( $this->getReplies() as $reply ) {
			$arr['cmts'][] = $reply->toJSON();
		}

		return $arr;
	}

	/**
	 * @param stdClass $obj
	 * @return UnPost
	 */
	public static function newFromJSON( $obj ) {
		$p = new UnPost;
		$p->id = $obj->id;
		$p->text = $obj->text;
		$p->ts = $obj->ts;

		foreach( $obj->cmts as $cmt ) {
			$p->replies[] = UnPost::newFromJSON( $cmt );
		}

		return $p;
	}

	public function getId() {
		return $this->id;
	}

	public function getReplies() {
		return $this->replies;
	}

	/**
	 * @fixme implement proper revdel
	 * @param int $vis Revision visibility constant
	 * @param User|null $user
	 * @return User|null
	 */
	public function getUser( $vis = Revision::FOR_PUBLIC, $user = null ) {
		if ( $this->user === false ) {
			if ( !$this->getRev() ) {
				// ??? We're probably trying to save the page...
				// something something globals are evil meh
				return $this->user = $GLOBALS['wgUser'];
			}
			$name = $this->getRev()->getUserText( $vis, $user );
			$this->user = $name ? User::newFromName( $name, false ) : null;
		}
		return $this->user;
	}

	protected function getReplyLink( UnThread $thread, Title $title ) {
		$subpage = $thread->getId() . '/' . $this->getId();
		return Linker::link(
			SpecialPage::getTitleFor( 'NewReply', $subpage ),
			wfMessage( 'unflow-reply' )->escaped(),
			array(
				'data-thread-id' => $thread->getId(),
				'data-post-id' => $this->getId(),
				'class' => 'mw-unflow-reply-link',
			),
			array( 'returnto' => $title->getPrefixedText() )
		);
	}

	public function getTimestamp() {
		return $this->ts;
	}

	protected function getUserToolLinks() {
		$user = $this->getUser();
		if ( $user ) {
			$text = UnFlow::userToolLinks( $user );
		} else {
			$text = wfMessage( 'rev-deleted-user' )->escaped();
		}

		return $text;
	}

	/**
	 * @param UnThread $thread
	 * @param Title $title
	 * @param ParserOptions $opts
	 * @return mixed
	 */
	public function toHtml( UnThread $thread, Title $title, ParserOptions $opts ) {
		wfProfileIn( __METHOD__ );
		/** @var Parser $wgParser */
		global $wgParser;
		$lang = $opts->getUserLangObj();

		$po = $wgParser->parse( $this->text, $title, $opts );
		// Because we don't use the ParserOutput past this function,
		// we need to do the LinksUpdates semi-manually
		UnFlow::addLinksUpdate( $title, $po );

		$html = "<div class=\"mw-unpost-comment-container\">";
		$html .= "<a name=\"{$this->getId()}\"></a><div class=\"mw-unpost-comment\">{$po->getText()}</div>";
		$html .= '<div class="mw-posted-by">' . wfMessage( 'unflow-posted-by' )
				->rawParams( $this->getUserToolLinks() )
				->params( $lang->formatExpiry( $this->getTimestamp() ) )
				->rawParams( $this->getReplyLink( $thread, $title ) )
				->parse() . '</div></div>' ;
		// Now the replies!!
		foreach( $this->getReplies() as $reply ) {
			$html .= '<div class="mw-unpost-reply">' . $reply->toHtml( $thread, $title, $opts ) . '</div>';
		}
		// Something something parsercache???!!
		wfProfileOut( __METHOD__ );
		return $html;
	}

}