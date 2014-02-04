<?php

class SpecialNewReply extends FormSpecialPage {

	/** @var array $result */
	protected $result;

	public function __construct() {
		parent::__construct( 'NewReply' );
	}

	protected function getFormFields() {
		return array(
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
		$req = new DerivativeRequest(
			$this->getRequest(),
			array(
				'action' => 'newreply',
				'replyto' => $data['replyto'],
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
		$title = Title::newFromText( $this->getRequest()->getVal( 'returnto' ) );
		if ( $title ) {
			$title->setFragment( $postId ); // @todo avoid using setFragment
		} else {
			$title = Title::makeTitle( NS_POST, $postId );
		}
		$this->getOutput()->redirect( $title->getFullURL() );

	}

	public function isListed() {
		return false;
	}
}