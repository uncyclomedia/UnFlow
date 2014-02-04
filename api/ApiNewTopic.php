<?php
/**
 * API module to create new topics
 */
class ApiNewTopic extends ApiBase {
	public function execute() {
		$params = $this->extractRequestParams();
		$user = $this->getUser();
		$title = Title::newFromText( $params['title'] );
		if ( !$title ) {
			$this->dieUsage( 'Invalid title provided', 'invalid-title' );
		}

		$threadId = UnFlow::getNewId( 'UnThread' );

		// Create the topic page
		$topicTitle = Title::makeTitle( NS_TOPIC, $threadId );
		$topicContent = new WikitextContent( $params['topic'] );
		$stat = UnFlow::makeEdit( $this, $topicTitle, $topicContent, 'summary', EDIT_NEW );
		if ( !$stat->isOK() ) {
			$this->dieStatus( $stat );
		}

		// Create the inital post...
		$postId = UnFlow::getNewId( 'UnPost' );
		$postTitle = Title::makeTitle( NS_POST, $postId );
		$postContent = new UnPostContent( $params['text'] );
		$stat = UnFlow::makeEdit( $this, $postTitle, $postContent, 'summary', EDIT_NEW );
		if ( !$stat->isOK() ) {
			$this->dieStatus( $stat );
		}

		// Now add it to the initial index...
		if ( $title->exists() ) {
			$rev = Revision::newFromTitle( $title );
			/** @var IndexContent $content */
			$content = $rev->getContent();
			$json = $content->getJsonData();
			$revid = $rev->getId();
			$flags = EDIT_UPDATE;
		} else {
			$json = array();
			$revid = false;
			$flags = EDIT_NEW;
		}

		if ( $user->isAllowed( 'bot' ) ) {
			$flags |= EDIT_FORCE_BOT;
		}

		$json[] = array(
			'thread-id' => $threadId,
			'user' => $user->getName(),
			'source' => 'local',
			'ts' => wfTimestampNow(),
		);

		$indexContent = new IndexContent( FormatJson::encode( $json ) );

		$stat = UnFlow::makeEdit( $this, $title, $indexContent, 'summary', $flags, $revid );
		if ( !$stat->isOK() ) {
			$this->dieStatus( $stat );
		}

		// Wooo!!! Now update the table thingy.
		UnFlow::registerNewReply( $threadId, $postId );

		$this->getResult()->addValue(
			null,
			$this->getModuleName(),
			array(
				'result' => 'success',
				'thread-id' => $threadId,
				'post-id' => $postId,
			)
		);
	}

	public function getDescription() {
		return 'Create a new topic';
	}

	public function getAllowedParams() {
		return array(
			'topic' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			),
			'text' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			),
			'title' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			),
			'summary' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_DFLT => '',
			),
			'token' => null,
		);
	}

	public function getParamDescription() {
		return array(
			'header' => 'Header of topic',
			'text' => 'Text of the message',
			'page' => 'Page the topic is for',
			'token' => 'An edit token from action=tokens'
		);
	}

	public function mustBePosted() {
		return true;
	}

/*
	public function needsToken() {
		return true;
	}

	public function getTokenSalt() {
		return '';
	}*/

	public function isWriteMode() {
		return true;
	}

	public function getExamples() {
		return array(
			'api.php?action=newtopic&spamlist=Signpost%20Spamlist&subject=New%20Signpost&message=Please%20read%20it&token=TOKEN'
		);
	}

}
