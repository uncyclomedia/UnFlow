<?php
/**
 * API module to create new replies or edit them
 */
class ApiNewReply extends ApiBase {
	public function execute() {
		$params = $this->extractRequestParams();

		$replyTo = Title::makeTitle( NS_POST, $params['replyto'] );
		if ( !$replyTo || !$replyTo->exists() ) {
			// It could be a top level reply?
			$replyTo2 = Title::makeTitle( NS_TOPIC, $params['replyto'] );
			if ( !$replyTo2 || !$replyTo2->exists() ) {
				$this->dieUsage( 'Invalid reply-to', 'invalid-reply-to' );
			}
		}

		$postId = UnFlow::getNewId( 'UnPost' );
		$postTitle = Title::makeTitle( NS_POST, $postId );
		$cont = new UnPostContent( $params['text'] );
		$stat = UnFlow::makeEdit( $this, $postTitle, $cont, '', EDIT_NEW );
		if ( !$stat->isOK() ) {
			$this->dieStatus( $stat );
		}

		// Woo! Update the db...
		UnFlow::registerNewReply( $params['replyto'], $postId );

		$this->getResult()->addValue(
			null,
			$this->getModuleName(),
			array(
				'result' => 'success',
				'post-id' => $postId,
			)
		);
	}

	public function getDescription() {
		return 'Create a new post or edit one';
	}

	public function getAllowedParams() {
		return array(
			'replyto' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'text' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'token' => null,
		);
	}

	public function getParamDescription() {
		return array(
			'replyto' => 'Post Id to reply to',
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
