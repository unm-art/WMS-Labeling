<?php
namespace WorldShare\WMS;

class Holding {
	protected $category;
	protected $pieceDesignation;
	protected $captions = array();
	protected $notes = array();
	
	// cost/currency
	// cost/amount
	
	public function __construct($doc = null) {
		if (isset($doc)) {
			$this->doc = $doc->asXML();
		}
	}
	
	public function from_doc(){
		$doc = simplexml_load_string($this->doc);
		
		$this->category = (string)$doc[@category];
		$this->pieceDesignation = (string)$doc->pieceDesignation;

		foreach ($doc->caption as $caption){
			$captionObject = new Caption($caption);
			$captionObject->from_doc();
			$this->caption[] = $captionObject;
		}
		
		foreach ($doc->note as $note){
			$noteObject = new Note($note);
			$noteObject->from_doc();
			$this->notes[] = $noteObject;
		}
	}
	
}