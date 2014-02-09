<?php
/**
 * API module to create new replies or edit existing ones
 */
class ApiNewReply extends ApiBase {
	public function execute() {
		$params = $this->extractRequestParams();

		$title = Title::makeTitleSafe( NS_POST, $params['threadId'] );
		if ( !$title || !$title->exists() ) {
			$this->dieUsage( 'Invalid threadId provided', 'invalid-thread' );
		}

		$thread = UnThread::newFromTitle( $title );
		$post = UnThread::findPost( $thread, $params['postId'] );
		if ( !$post ) {
			$this->dieUsage( 'Invalid postId provided', 'invalid-postId' );
		}

		if ( !$params['edit'] ) {
			$reply = UnPost::newPost( $params['text'] );
			$post->newReply( $reply );
			$postId = $reply->getId();
		} else {
			$post->setText( $params['text'] );
			$postId = $post->getId();
		}

		// Now saveeeeee
		$flags = $this->getUser()->isAllowed( 'bot' ) ? EDIT_FORCE_BOT : 0;
		$content = UnPostContent::newFromThread( $thread );
		$stat = UnFlow::makeEdit( $this, $title, $content, '', $flags | EDIT_UPDATE );
		if ( !$stat->isOK() ) {
			$this->dieStatus( $stat );
		}

		$this->getResult()->addValue(
			null,
			$this->getModuleName(),
			array(
				'result' => 'success',
				'post-id' => $postId,
				'thread-id' => $params['threadId'],
			)
		);
	}

	public function getDescription() {
		return 'Create a new post or edit one';
	}

	public function getAllowedParams() {
		return array(
			'threadId' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'postId' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'text' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'edit' => array(
				ApiBase::PARAM_TYPE => 'boolean',
				ApiBase::PARAM_DFLT => false,
			),
			'token' => null,
		);
	}

	public function getParamDescription() {
		return array(
			'threadId' => 'Thread Id',
			'postId' => 'Post Id to reply to or edit',
			'text' => 'Text of the message',
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
