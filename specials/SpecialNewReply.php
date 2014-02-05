<?php

class SpecialNewReply extends FormSpecialPage {

	/** @var array $result */
	protected $result;

	public function __construct() {
		parent::__construct( 'NewReply' );
	}

	protected function getFormFields() {
		return array(
			// Format is $threadId/$postId
			// @todo in the future we should make this hidden
			// and display the post the user is responding to
			'replyto' => array(
				'id' => 'mw-unflow-replyto',
				'name' => 'replyTo',
				'type' => 'text',
				'label-message' => 'unflow-newreply-replyto',
				'default' => $this->par,
			),
			'text' => array(
				'id' => 'mw-unflow-text',
				'name' => 'text',
				'type' => 'textarea',
				'label-message' => 'unflow-newreply-text',
			),
			'returnto' => array(
				'id' => 'mw-unflow-returnto',
				'name' => 'returnto',
				'type' => 'hidden',
				'default' => $this->getRequest()->getVal( 'returnto' ),
			)
		);
	}

	public function onSubmit( array $data ) {
		$split = explode( '/', $data['replyto'] );
		// @todo validate $split
		$req = new DerivativeRequest(
			$this->getRequest(),
			array(
				'action' => 'newreply',
				'threadId' => $split[0],
				'postId' => $split[1],
				'text' => $data['text'],
				'token' => $this->getUser()->getEditToken()
			),
			true
		);

		$main = new ApiMain( $req, true );
		$main->execute(); // @todo catch exceptions here
		$this->result = $main->getResult()->getData();

		return true;
	}

	public function onSuccess() {
		$postId = $this->result['newreply']['post-id'];
		$threadId = $this->result['newreply']['thread-id'];
		$title = Title::newFromText( $this->getRequest()->getVal( 'returnto' ) );
		if ( $title ) {
			$title->setFragment( $postId ); // @todo avoid using setFragment
		} else {
			$title = Title::makeTitle( NS_POST, $threadId, $postId );
		}
		$this->getOutput()->redirect( $title->getFullURL() );

	}

	public function isListed() {
		return false;
	}
}