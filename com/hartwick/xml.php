<?php namespace com\hartwick;
define("__HCCXML_VERSION__", "2.1.0");

class hccxml {
  private $type;
  private $document;
  private $root;
  private $reply;
  private $id;

  private $commands;
  private $data;
  private $debugs;
  private $error;
  private $fields;
  private $response;
  private $version;
  public $debug;


  /*! Create a new DOMImplementation with the project DTD file, then create a document.
   */
  function __construct($type = "reply", $source = null, $dtdpath = "/hcc.dtd", $dtdroot = "hcc") {
    $this->type = $type;
    $this->type = "reply";
    $implementation = new DOMImplementation();
    $dtd = $implementation->createDocumentType($dtdroot, '', $dtdpath);
    $this->document = $implementation->createDocument('', '', $dtd);
    $this->root = $this->document->createElement($dtdroot);
    $this->reply = $this->document->createElement($this->type);
  }

  /*! Add a debug field to an array for later adding to the reply. This is primaryily
   * used for debugging purposes and should not be called in a live situation.
   * @param value The value to return -- usually a SQL query
   */
  public function addDebug($value) {
    if($this->debug != true) {
      return;
    }
    $element = $this->document->createElement('debug');
    $element->setAttribute('query', trimText($value));
    $this->debugs[] = $element;
  }

  /*! Add an error field to the list for returning. Since only one error is permitted
   * if an error has already been set unset it and replace it.
   * @param message The message to be displayed
   * @param redirect The URL to redirect to
   */
  public function addError($message = null, $errno = null, $error = null) {
    if(!$message) {
      return;
    }
    if($this->error) {
      unset($this->error);
    }
    $element = $this->document->createElement('error');
    if($message) {
      $element->setAttribute('value', $message);
    }
    if(!empty($errno)) {
      $element->setAttribute('errno', $errno);
    }
    if(!empty($error)) {
      $element->setAttribute('error', $error);
    }
    $this->error = $element;
  }

  /*! Add an id field to the list for returning. Since only one error is permitted
   * if an error has already been set unset it and replace it.
   * @param id The ID value to send back
   */
  public function addId($id, $readonly = "false") {
    if(!isset($id)) return;
    if(is_array($id)) {
      report_error($id);
      return;
    }
    if($this->id) unset($this->id);
    $element = $this->document->createElement('id');
    $element->setAttribute('value', $id);
    $element->setAttribute('readonly', $readonly);
    $this->id = $element;
  }

  public function addVersion($software, $schema) {
    $element = $this->document->createElement("version");
    $element->setAttribute('software', $software);
    $element->setAttribute('schema', $schema);
    $this->version = $element;
  }

  public function addData($attribute, $data, $textvalue = NULL) {
    $element = $this->document->createElement($attribute);
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
    $this->data[] = $element;
  }

  /*! Gather the arrays and add the elements to the reply, making sure
   * the reply and root are added and if valid XML return the resulting XML
   * otherwise return nothing.
   */
  public function assemble() {
    /* Add the Child nodes in the order we want them in the output */
    if($this->type != "reply") return;

    /* Start with the id */
    if(isset($this->id)) $this->reply->appendChild($this->id);

    /* Add the version */
    if(isset($this->version)) $this->reply->appendChild($this->version);

    /* Error.... */
    if(isset($this->error)) $this->reply->appendChild($this->error);

    /* Debugs.... */
    if(is_array($this->debugs)) {
      foreach($this->debugs as $element) {
        $this->reply->appendChild($element);
      }
    }

    /* Data.... */
    if(is_array($this->data)) {
      foreach($this->data as $element) {
        $this->reply->appendChild($element);
      }
    }

    // Enable user error handling
    libxml_use_internal_errors(true);

    $this->root->appendChild($this->reply);
    $this->document->appendChild($this->root);
    $this->document->resolveExternals = true;
    $this->document->formatOutput = true;
    $this->document->encoding = "utf-8";
    if($this->document->validate()) return $this->document->saveXML();
    else {
      $error = $this->libxml_display_errors();
      report_error($error);
      return "Invalid XML";
    }
  }

  private function libxml_display_error($error) {
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

  private function libxml_display_errors() {
    $ferror = "";
    $errors = libxml_get_errors();
    foreach ($errors as $error) {
      $ferror .= $this->libxml_display_error($error);
    }
    libxml_clear_errors();
    return $ferror;
  }
}