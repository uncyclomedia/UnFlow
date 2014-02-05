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

		$thread = UnThread::newThread( $params['topic'] );
		$post = UnPost::newPost( $params['text'], $this->getUser() );
		$thread->newReply( $post );

		$flags = $user->isAllowed( 'bot' ) ? EDIT_FORCE_BOT : 0;

		$content = UnPostContent::newFromThread( $thread );
		$postTitle = Title::makeTitle( NS_POST, $thread->getId() );
		$stat = UnFlow::makeEdit( $this, $postTitle, $content, '', $flags | EDIT_NEW );
		if ( !$stat->isOK() ) {
			$this->dieStatus( $stat );
		}

		$json[] = array(
			'threadId' => $thread->getId(),
			'user' => $user->getName(),
			'source' => 'local',
			'ts' => wfTimestampNow(),
		);

		$indexContent = new IndexContent( FormatJson::encode( $json ) );

		$stat = UnFlow::makeEdit( $this, $title, $indexContent, 'summary', $flags );
		if ( !$stat->isOK() ) {
			$this->dieStatus( $stat );
		}

		$this->getResult()->addValue(
			null,
			$this->getModuleName(),
			array(
				'result' => 'success',
				'thread-id' => $thread->getId(),
				'post-id' => $post->getId(),
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
			// @todo use an autosummary
			'summary' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_DFLT => '',
			),
			'token' => null,
		);
	}

	public function getParamDescription() {
		return array(
			'topic' => 'Header of topic',
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
