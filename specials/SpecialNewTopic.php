<?php

class SpecialNewTopic extends FormSpecialPage {

	/** @var array $result */
	protected $result;
	/** @var Title $title */
	protected $title;

	public function __construct() {
		parent::__construct( 'NewTopic' );
	}

	protected function getFormFields() {
		return array(
			'page' => array(
				'id' => 'mw-unflow-page',
				'name' => 'page',
				'type' => 'text',
				'label-message' => 'unflow-newtopic-page',
				'default' => $this->par,
			),
			'topic' => array(
				'id' => 'mw-unflow-topic',
				'name' => 'topic',
				'type' => 'text',
				'label-message' => 'unflow-newtopic-topic',
			),
			'text' => array(
				'id' => 'mw-unflow-text',
				'name' => 'text',
				'type' => 'textarea',
				'label-message' => 'unflow-newtopic-text',
			),
		);
	}

	public function onSubmit( array $data ) {
		$this->title = Title::newFromText( $data['page'] );
		if ( !$this->title ) {
			return Status::newFatal( 'unflow-newtopic-invalidtitle' );
		}
		$req = new DerivativeRequest(
			$this->getRequest(),
			array(
				'action' => 'newtopic',
				'title' => $this->title->getFullText(),
				'topic' => $data['topic'],
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
		$threadId = $this->result['newtopic']['thread-id'];
		$title = Title::makeTitle(
			$this->title->getNamespace(),
			$this->title->getText(),
			$threadId // anchor
		);
		$this->getOutput()->redirect( $title->getFullURL() );

	}

	public function isListed() {
		return false;
	}
}