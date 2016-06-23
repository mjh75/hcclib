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

/**
 * Description of xmlcreds
 *
 * @author hartwick
 */
class xmlcreds {
  public $loaded;
  private $doc;
  private $users;
  private $filename;
  private $allowed;

  public function __construct($file) {
    $this->loaded = 0;
    if(!file_exists($file)) return;
    $this->filename = $file;
    $this->doc = new \DOMDocument();
    $this->doc->formatOutput = true;
    $this->doc->preserveWhiteSpace = false;
    if($this->doc->load($this->filename) == false) return;
    $this->users = $this->doc->getElementsByTagName('users')->item(0);
    $this->loaded = 1;
    $this->allowed = array("username", "password", "expiry");
  }

  public function save() {
    if($this->loaded == 0) return(-1);
    $tmpfname = tempnam(".", "XCR");
    $written = $this->doc->save($tmpfname);
    if($written == false) return(-2);
    if(rename($this->filename, $this->filename.".old") == false) {
      error_log("Error creating backup file.");
      return(-3);
      }
    if(rename($tmpfname, $this->filename) == false) {
      error_log("Error renaming temp file.");
      return(-4);
    }
    unlink($this->filename.".old");
  }

  public function __destruct() {
  }

  public function getCreds($username) {
    if($this->loaded == 0) return false;
    $users = $this->doc->getElementsByTagName('user');
    foreach ($users as $user) {
      if($user->getAttribute("username") == $username) {
        foreach($this->allowed as $key) {
          $retval[$key] = $user->getAttribute($key);
        }
        return($retval);
      }
    }
    return false;
  }

  public function setCreds($data) {
    if(!isset($data['username'])) return -1;
    $users = $this->doc->getElementsByTagName('user');
    foreach ($users as $user) {
      if($user->getAttribute("username") == $data['username']) {
        foreach($data as $key=>$piece) {
          $user->setAttribute($key, $piece);
        }
        return true;
      }
    }
    $el = $this->doc->createElement('user');
    foreach($data as $key=>$piece) {
      if(in_array($key, $this->allowed)) $el->setAttribute($key, $piece);
    }
    $this->users->appendChild($el);
    return true;
  }
}
?>
