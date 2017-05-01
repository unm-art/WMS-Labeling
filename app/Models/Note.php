<?php
namespace WorldShare\WMS;

class Note {
	protected $type;
	protected $text;

	public function __construct($doc = null) {
		if (isset($doc)) {
			$this->doc = $doc->asXML();
		}
	}

	public function from_doc(){
		$doc = simplexml_load_string($this->doc);

		$this->type = (string)$doc[@type];
		$this->text = (string)$doc;
	}
}