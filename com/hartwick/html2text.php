<?php namespace com\hartwick;
define("__HTTP2TEXT_VERSION__", "2.1.0");
/*! \brief This class takes an HTML document and does its best to "render" it as
 * plain text suitable for a text version of a HTML email.
 *
 * \author Michael J. Hartwick <hartwick@hartwick.com>
 * \version 2.1.0
 */

class html2text {
  private $DOM;
  private $text;

  /*! \brief Load an HTML document into a DOM object
   * \param $html The HTML code that we are to parse and convert to text
   */
  function __construct($html) {
    $this->DOM = new \DOMDocument();
    if(!$this->DOM->loadHTML($html)) {
      return \FALSE;
    }
    $this->DOM->normalizeDocument();
    $this->parse();
		return \TRUE;
  }

  /*! \brief Get the "rendered" text version of the HTML handed to us
   * \param $wrap The number of characters to wrap the returned text to
   * \return A string containing the rendered text version of the HTML
   */
  public function getText($wrap = 0) {
    if($wrap > 0) {
      return wordwrap($this->text, $wrap);
    } else {
      return $this->text;
    }
  }

  /*! \brief Start the rendering process at the top
   *
   */
  private function parse() {
    $this->text = $this->iterate_over_node($this->DOM);
  }

  private function next_child_name($node) {
    $nextNode = $node->nextSibling;
    while($nextNode != NULL) {
      if($nextNode instanceof \DOMElement) {
        break;
      }
      $nextNode = $nextNode->nextSibling;
    }
    $nextName = NULL;
    if($nextNode instanceof \DOMElement && $nextNode != NULL) {
      $nextName = \strtolower($nextNode->nodeName);
    }
    return $nextName;
  }

  private function prev_child_name($node) {
    $prevNode = $node->previousSibling;
    while($prevNode != NULL) {
      if($prevNode instanceof \DOMElement) {
        break;
      }
      $prevNode = $prevNode->previousSibling;
    }
    $prevName = NULL;
    if($prevNode instanceof \DOMElement && $prevNode != NULL) {
      $prevName = \strtolower($prevNode->nodeName);
    }
    return $prevName;
  }

  private function iterate_over_node($node) {
    if($node instanceof \DOMText) {
      return \preg_replace("/\\s+/im", " ", $node->wholeText);
    }
    if($node instanceof \DOMDocumentType) {
      return "";
    }

    $nextName = $this->next_child_name($node);
    $prevName = $this->prev_child_name($node);

    $name = \strtolower($node->nodeName);

    switch($name) {
      case "hr":
        return "------\r\n";

      case "style":
      case "head":
      case "title":
      case "meta":
      case "script":
        return "";

      case "h1":
      case "h2":
      case "h3":
      case "h4":
      case "h5":
      case "h6":
        $output = "\r\n\r\n";
        break;

      case "p":
      case "div":
        $output = "\r\n";
        break;

      default:
        $output = "";
        break;
    }

    for($i = 0; $i < $node->childNodes->length; $i++) {
      $n = $node->childNodes->item($i);
      $text = $this->iterate_over_node($n);
      $output .= $text;
    }

    switch($name) {
      case "style":
      case "head":
      case "title":
      case "meta":
      case "script":
        return "";

      case "h1":
      case "h2":
      case "h3":
      case "h4":
      case "h5":
      case "h6":
        $output .= "\r\n";
        break;

      case "p":
      case "br":
        if($nextName != "div") {
          $output .= "\r\n";
        }
        break;

      case "div":
        if($nextName != "div" && $nextName != NULL) {
          $output .= "\r\n";
        }
        break;

      case "img":
        $alt = $node->getAttribute("alt");
        if(!empty($alt)) {
          $output = "[image:$alt]";
        } else {
          $output = "[image]";
        }
        break;

      case "a":
        $href = $node->getAttribute("href");
        if($href == NULL) {
          if($node->getAttribute("name") != NULL) {
            $output = "[$output]";
          }
        } else {
          if($href != $output) {
            $output = "[$output]($href)";
          }
        }
        switch($nextName) {
          case "h1":
          case "h2":
          case "h3":
          case "h4":
          case "h5":
          case "h6":
            $output .= "\r\n";
            break;
        }
      default:
    }
    return $output;
  }

}
