<?php namespace com\hartwick;
define("__HCCXML_VERSION__", "2.1.0");

class XML {
  private $type;
  private $document;
  private $root;
  private $reply;
  private $id;

  private $commands;
  private $data;
  private $fields;
  private $response;
  private $version;
	private $dtdpath;
  public $debug;


  /*! Create a new DOMImplementation with the project DTD file, then create a document.
   */
  function __construct($type = "reply", $source = null, $dtdpath = "/hcc.dtd", $dtdroot = "hcc") {
    $this->type = $type;
		$this->dtdpath = $dtdpath;
    $implementation = new \DOMImplementation();
    $dtd = $implementation->createDocumentType($dtdroot, '', $this->dtdpath);
    $this->document = $implementation->createDocument('', '', $dtd);
    $this->root = $this->document->createElement($dtdroot);
    $this->reply = $this->document->createElement($this->type);
  }

  /**
	 * Add a debug field to an array for later adding to the reply. This is primarily
   * used for debugging purposes and should not be called in a live situation.
	 * 
   * @param value The value to return -- usually a SQL query
   */
  public function addDebug($value) {
    if($this->debug != true) {
      return;
    }
		$data = [];
		if(!empty($value)) {
			$data['query'] = $value;
		}
		$this->addData("debug", $data);
  }

  /**
	 * Add an error field to the list for returning. Since only one error is permitted
   * if an error has already been set unset it and replace it.
	 * 
	 * @deprecated This has been rewritten to use the addData method
   * @param mixed $message The message to be displayed
   * @param mixed @errno The error number to report
	 * @param mixed $error The error message to report
   */
  public function addError($message = null, $errno = null, $error = null) {
    if(!$message) {
      return;
    }
		$data = [];
    if($message) {
      $data['value'] = $message;
    }
    if(!empty($errno)) {
      $data['errno'] = $errno;
    }
    if(!empty($error)) {
      $data['error'] = $error;
    }
    $element = $this->addData('error', $data);
  }

  /**
	 * Add an id field to the list for returning. Since only one id is permitted
   * if an id has already been set unset it and replace it.
	 * 
	 * @deprecated This has been rewritten to use the addData method
   * @param string $id The ID value to send back
	 * @param string $readonly If this is readonly
   */
  public function addId($id, $readonly = "false") {
    if(!isset($id)) {
			return;
		}
    if(is_array($id)) {
      report_error($id);
      return;
    }
    if($this->id) {
			unset($this->id);
		}
		$data = [];
		$data['value'] = $id;
		if(!empty($readonly)) {
			$data['readonly'] = $readonly;
		}
    $element = $this->addData("id", $data);
    $this->id = $element;
  }

	/**
	 * Add a version element with software and schema attributes
	 * 
	 * @deprecated This function has been rewritten to call the addData method
	 * @param string $software The software version
	 * @param string $schema The schema version
	 */
  public function addVersion($software = "", $schema = "") {
		$data = [];
		if(!empty($software)) {
			$data['software'] = $software;
		}
		if(!empty($schema)) {
			$data['schema'] = $schema;
		}
		$this->addData("version", $data);
  }

	/**
	 * Set the parent element
	 * 
	 * @param \DOMElelment $parent The parent object to set
	 * @return \DOMElement The parent element that was set
	 */
	public function setParent($parent) {
		$this->parent = $parent;
		return $this->parent;
	}
	
	/**
	 * Unset the parent element
	 */
	public function unsetParent() {
		unset($this->parent);
	}
	
	/**
	 * 
	 * @param string $elementname The element name to add
	 * @param array $data The attributes in an associative array
	 * @param mixed $textvalue If a string a text value to add as a text node, if an array sub elements
	 * @return object The element object that was created
	 */
  public function addData($elementname, $data = NULL, $textvalue = NULL) {
    $element = $this->document->createElement($elementname);
    if(is_array($data)) {
      foreach($data as $key=>$value) {
        $element->setAttribute($key, $value);
      }
    }
    if(isset($textvalue) && !is_array($textvalue)) {
      $text = $this->document->createTextNode($textvalue);
      $element->appendChild($text);
    }
    if(isset($textvalue) && is_array($textvalue)) {
      for($i = 0; $i < count($textvalue); $i++) {
        $ename = $textvalue[$i]['element'];
        unset($textvalue[$i]['element']);
        $e = $this->document->createElement($ename);
        foreach($textvalue[$i] as $key=>$value) {
          if($key == 'element') {
            continue;
          }
          $e->setAttribute($key, $value);
        }
        $element->appendChild($e);
      }
    }
		if(isset($this->parent)) {
			$this->parent->appendChild($element);
		} else {
			$this->reply->appendChild($element);
		}
		return $element;
  }

  /*! Gather the arrays and add the elements to the reply, making sure
   * the reply and root are added and if valid XML return the resulting XML
   * otherwise return nothing.
   */
  public function assemble() {
    /* Add the Child nodes in the order we want them in the output */
    if($this->type != "reply") {
      return;
    }

    // Enable user error handling
    libxml_use_internal_errors(true);

    $this->root->appendChild($this->reply);
    $this->document->appendChild($this->root);
    $this->document->resolveExternals = true;
    $this->document->formatOutput = true;
    $this->document->encoding = "utf-8";
		if(empty($this->dtdpath)) {
			return $this->document->saveXML();
		} else {
			if($this->document->validate()) {
	      return $this->document->saveXML();
		  } else {
	      $this->libxmlDisplayErrors();
		    return "Invalid XML";
			}
    }
  }

	/**
	 * Get the detailed error information
	 * 
	 * @param object $error The error object
	 * @return string The detailed error message
	 */
  private function libxmlDisplayError($error) {
    $return = "";
    switch ($error->level) {
      case LIBXML_ERR_WARNING:
        $return .= "<b>Warning $error->code</b>: ";
        break;
      case LIBXML_ERR_ERROR:
        $return .= "<b>Error $error->code</b>: ";
        break;
      case LIBXML_ERR_FATAL:
        $return .= "<b>Fatal Error $error->code</b>: ";
        break;
    }
    $return .= trim($error->message);
    $return .= " in $error->file ";
    $return .= " on line $error->line\n";
    return $return;
  }

	/**
	 * Get the errors from the XML parsing
	 * 
	 * @return string $ferror The errors as a string
	 */
  private function libxmlDisplayErrors() {
    $ferror = "";
    $errors = libxml_get_errors();
    foreach ($errors as $error) {
      $ferror .= $this->libxmlDisplayError($error);
    }
    libxml_clear_errors();
    return $ferror;
  }
}
