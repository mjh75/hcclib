<?php namespace com\hartwick;
/* 
 * Copyright (C) 2016 Michael J. Hartwick <hartwick at hartwick.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

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

	/**
	 * Load the HTML document into a DOM, normalize it and then parse it
	 * 
	 * @param string $html The document to load
	 * @return boolean TRUE on success, FALSE on fail
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

	/**
	 * Get the text version of an HTML document optionally word wrapped
	 * 
	 * @param integer $wrap The number of lines to wrap the text
	 * @return string The text verson of the HTML, word wrapped at $wrap
	 */
	public function getText($wrap = 0) {
    if($wrap > 0) {
      return wordwrap($this->text, $wrap);
    } else {
      return $this->text;
    }
  }

	/**
	 * Parse the DOM storing the results in the text property
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
