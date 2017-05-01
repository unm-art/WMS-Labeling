<?php
namespace WorldShare\WMS;

class Chronology {
	protected $label;
	protected $value;

	public function __construct($doc = null) {
		if (isset($doc)) {
			$this->doc = $doc->asXML();
		}
	}

	public function from_doc(){
		$doc = simplexml_load_string($this->doc);

		$this->label = (string)$doc[@label];
		$this->value = (string)$doc;
	}
}