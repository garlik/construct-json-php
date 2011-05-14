<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Jochen Rau <jochen.rau@typoplanet.de>, typoplanet
 *  			
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/*
 * Grossly hacked about by Steve Harris in 2011, go get the original from
http://forge.typo3.org/projects/extension-semantic/repository/raw/trunk/Classes/Domain/Model/Sparql/QueryResultParser.php if you want a working version */

/**
 * QueryResultParser
 *
 * @version $Id$
 * @copyright Copyright belongs to the respective authors
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SparqlResultParser {

	/**
	 * A resource handler reference to the PHP Extpat parser
	 *
	 * @var resource
	 **/
	protected $parser;

	/**
	 * An array of results
	 *
	 * @var array
	 **/
	protected $results = array();

	/**
	 * The current result
	 *
	 * @var array
	 **/
	protected $currentResult = array();

	/**
	 * @var string
	 **/
	protected $currentName = '';

	/**
	 * @var string
	 **/
	protected $currentCharacterData = '';

	/**
	 * @var string
	 **/
	protected $currentType = '';

	/**
	 * @var string
	 **/
	protected $currentDataType;

	/**
	 * @var string
	 **/
	protected $currentLanguage;

	/**
	 * A flag indicating, if the character data of the current node should be processed.
	 *
	 * @var bool
	 **/
	protected $processCharacterData = FALSE;

	/**
	 * Sets up the PHP parser.
	 *
	 * @return void
	 **/
	public function __construct() {
		$this->parser = xml_parser_create();
		xml_set_object($this->parser, $this);
		xml_set_element_handler($this->parser, 'handleElementStart', 'handleElementStop');
		xml_set_character_data_handler($this->parser, 'handleCharacterData');
	}

	/**
	 * Frees the memory of the PHP parser.
	 *
	 * @return void
	 **/
	public function __destruct() {
		xml_parser_free($this->parser);
	}
	
	/**
	 * Parses the given XML document. Returns and array or arrays, with the
	 * inner array have the var name as a key, and the lexical value of the
	 * binding as a value.
	 *
	 * @return void
	 * @api
	 **/
	public function parse($document) {
		$status = xml_parse($this->parser, $document);
		if ($status === 1) {
			return $this->results;
		} else {
			throw new Sparql_Exception_QueryResultParserException('Parser Error: "' . xml_error_string(xml_get_error_code($this->parser)) . '".', 1296481762);
		}
	}

	/**
	 * Handles an event fired by an opening element.
	 *
	 * @return void
	 **/
	protected function handleElementStart($parser, $elementName, $elementAttributes) {
		switch ($elementName) {
			case 'BINDING':
				$this->currentName = $elementAttributes['NAME'];
				$this->processCharacterData = FALSE;
				break;
			case 'LITERAL':
				$this->currentType = 'literal';
				$this->processCharacterData = TRUE;
				break;
			case 'BNODE':
				$this->currentType = 'bnode';
				$this->processCharacterData = TRUE;
				break;
			case 'URI':
				$this->currentType = 'literal';
				$this->processCharacterData = TRUE;
				break;
		}
	}

	/**
	 * Handles an event fired by a closing element.
	 *
	 * @return void
	 **/
	protected function handleElementStop($parser, $elementName) {
		switch ($elementName) {
			case 'BINDING':
				$this->currentResult[$this->currentName] =
					$this->currentCharacterData;
				$this->currentCharacterData = '';
				break;
			case 'LITERAL':
				$this->processCharacterData = FALSE;
				break;
			case 'BNODE':
				$this->processCharacterData = FALSE;
				break;
			case 'URI':
				$this->processCharacterData = FALSE;
				break;
			case 'RESULT':
				$this->results[] = $this->currentResult;
				$this->currentResult = array();
				break;
		}
	}

	/**
	 * Handles character data.
	 *
	 * @return void
	 **/
	protected function handleCharacterData($parser, $characterData) {
		if ($this->processCharacterData === TRUE) {
			$this->currentCharacterData .= $characterData;
		}
	}

}
?>
