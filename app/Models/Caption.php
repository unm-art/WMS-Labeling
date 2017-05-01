<?php
namespace WorldShare\WMS;

class Caption {
	protected $id;
	protected $sequence;
	protected $enumerations = array();
	protected $chronologies = array();

	public function __construct($doc = null) {
		if (isset($doc)) {
			$this->doc = $doc->asXML();
		}
	}
	
	public function from_doc(){
		$doc = simplexml_load_string($this->doc);

		$this->id = (string)$doc[@id];
		$this->sequence = (string)$doc[@sequence];
		
		foreach ($doc->enumeration as $enumeration) {
			$enumerationObject = new Enumeration($enumeration);
			$enumerationObject->from_doc();
			$this->enumerations[] = $enumerationObject;
		}
		foreach ($doc->chronology as $chronology) {
			$chronologyObject = new Chronology($chronology);
			$chronologyObject->from_doc();
			$this->chronologies[] = $chronologyObject;
		}
	}
}