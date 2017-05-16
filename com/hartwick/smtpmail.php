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
define("__SMTPMAIL_VERSION__", "2.1.1");

/*! \brief SMTP Mailing class
 * smtpmail supports SMTP authentication, attachments and multiple recipients.
 *
 * \author Michael J. Hartwick <hartwick@hartwick.com>
 * \version 2.1.0
 */
class smtpmail {
  private $server;
  private $port;
  private $username;
  private $password;
  private $newline;
  private $recipients;
  private $attachments;
  private $headers;
  private $senderaddress;
  private $sendername;
  private $logArray;
  private $randomhash;

  /*! \brief Setup the class with sane initial values.
   *
   */
  function __construct() {
    $this->server = "localhost";
    $this->port = 25;
    $this->newline = "\r\n";
    $this->recipients = array();
    $this->attachments = array();
    $this->headers = array();
    $this->headers[] = "MIME-Version: 1.0" . $this->newline;
    $this->headers[] = "X-Mailer: smtpmail/".__SMTPMAIL_VERSION__." PHP/".\phpversion().$this->newline;
    $this->logArray = array();
    $this->randomhash = \md5(\date('r', \time()));
  }

  /*! \brief Set the SMTP server and port to use
   * \param $server The hostname of the SMTP server to use
   * \param $port The TCP port to connect to (optional).
   */
  function setServer($server, $port = 0) {
    $this->server = $server;
    if($port != 0) {
      $this->setPort($port);
    }
  }

  /*! \brief Set the TCP port to connect to
   * \param $port The TCP port to connect to
   */
  function setPort($port) {
    $this->port = $port;
  }

  /*! \brief Set the Sender address
   * \param $address The email address to use as the sender
   * \param $name The name of the sender (optional)
   */
  function setSender($address, $name = "") {
    $this->senderaddress = $address;
    if(!empty($name)) {
      $this->sendername = $name;
    }
  }

  /*! \brief Set the username and password for SMTP authentication
   * \param $username The username
   * \param $password The password
   */
  function setUsername($username, $password = "") {
    $this->username = $username;
    if(!empty($password)) {
      $this->password = $password;
    }
  }

  /*! \brief Add a recipient
   * \param $address The email address of the new recipient
   * \param $name The name of the new recipient (optional)
   */
  function addRecipient($address, $name = "") {
    $this->recipients[] = array("address"=>$address, "name"=>$name);
  }

  /*! \brief Remove the recipients
   *  Remove all recipients, this allows the reuse of the class to send the
   *  same message to multiple recipients.
   */
  function clearRecipients() {
    unset($this->recipients);
    $this->recipients = array();
  }

  /*! \brief Remove the headers
   *  Remove all headers. This allows us to reuse the class.
   */
  function clearHeaders() {
    unset($this->headers);
    $this->headers = array();
    $this->headers[] = "MIME-Version: 1.0" . $this->newline;
    $this->headers[] = "X-Mailer: smtpmail/".__SMTPMAIL_VERSION__." PHP/".\phpversion().$this->newline;
  }

  /*! \brief Add an attachment to the email
   * Generate the appropriate content to add an attachment to an email, base64
   * encode the file and wrap it appropriately.
   *
   * \param $data A binary chunk of data that is the file to be sent as an attachment
   * \param $type The MIME type of the attachment
   * \param $name
   * \param $id
   */
  function addAttachment($data, $type, $name = "", $id = "") {
    if(empty($data)) {
      return false;
    }
    $attachment = "--PHP-mixed-".$this->randomhash.$this->newline;
    if(!empty($type)) {
      $attachment .= "Content-Type: \"$type\"; name=\"$name\"".$this->newline;
    }
    $attachment .= "Content-Transfer-Encoding: base64".$this->newline;
    $attachment .= "Content-Disposition: attachment; filename=\"$name\"".$this->newline;
    if(!empty($id)) {
      $attachment .= "Content-ID: <$id>".$this->newline;
    }
    $attachment .= $this->newline;
    $attachment .= \chunk_split(\base64_encode($data));
    $attachment .= $this->newline;
    $this->attachments[] = $attachment;
  }

  /*! \brief Add an arbitrary email header
   * Add an arbitrary email header to the message, the new line character is added
   * \param $header The header to add.
   */
  function addHeader($header) {
    if(!empty($header)) {
      $this->headers[] = $header.$this->newline;
    }
  }

  /*! \brief Send the email
   * \param $text The text version of the email
   * \param $html The HTML version of the email
   */
  function send($text, $html = "") {
    //connect to the host and port
    $errno = 0;
    $errstr = "";
    $timeout = 10;
    $x = 0;
    $this->logArray['trying'][$x] = "Trying ".$this->server.":".$this->port;
    $smtpConnect = fsockopen($this->server, $this->port, $errno, $errstr, $timeout);
    $this->logArray['connect'][$x] = "Error [$errno]: $errstr";
    if($smtpConnect === FALSE) {
      return false;
    }

    $smtpResponse = \fgets($smtpConnect, 4096);
    if(empty($smtpConnect)) {
      return FALSE;
    } else {
      $this->logArray['connection'] = "Connected to: $smtpResponse";
    }

    //say HELO to our little friend
    \fputs($smtpConnect, "HELO ".gethostname(). $this->newline);
    $smtpResponse = \fgets($smtpConnect, 4096);
    $this->logArray['heloresponse'] = "$smtpResponse";

    if(!empty($this->username) && !empty($this->password)) {
      //request for auth login
      \fputs($smtpConnect,"AUTH LOGIN" . $this->newline);
      $smtpResponse = \fgets($smtpConnect, 4096);
      $this->logArray['authrequest'] = $smtpResponse;

      if(\substr($this->logArray['authrequest'], 0, 3) != "503") {
        //send the username
        \fputs($smtpConnect, base64_encode($this->username) . $this->newline);
        $smtpResponse = \fgets($smtpConnect, 4096);
        $this->logArray['authusername'] = $smtpResponse;

        //send the password
        \fputs($smtpConnect, base64_encode($this->password) . $this->newline);
        $smtpResponse = \fgets($smtpConnect, 4096);
        $this->logArray['authpassword'] = $smtpResponse;
      }
    }

    //email from
    \fputs($smtpConnect, "MAIL FROM: <".$this->senderaddress.">" . $this->newline);
    $smtpResponse = \fgets($smtpConnect, 4096);
    $this->logArray['mailfromresponse'] = $smtpResponse;

    //email to
    foreach($this->recipients as $value) {
      if(!is_array($value)) {
        return FALSE;
      }
      $name = "";
      if(!empty($value['address'])) {
        $address = $value['address'];
      } else {
        continue;
      }
      if(!empty($value['name'])) {
        $name = $value['name'];
      }
      \fputs($smtpConnect, "RCPT TO: <$address>" . $this->newline);
      $smtpResponse = \fgets($smtpConnect, 4096);
      $this->logArray['mailtoresponse'] = "$smtpResponse";
    }

    //the email
    \fputs($smtpConnect, "DATA" . $this->newline);
    $smtpResponse = \fgets($smtpConnect, 4096);
    $this->logArray['data1response'] = "$smtpResponse";

    //observe the . after the newline, it signals the end of message
    \fputs($smtpConnect, "From: \"".$this->sendername."\" <".$this->senderaddress.">". $this->newline);

    foreach($this->recipients as $value) {
      if(!is_array($value)) {
        return FALSE;
      }
      $name = "";
      if(!empty($value['address'])) {
        $address = $value['address'];
      } else {
        continue;
      }
      if(!empty($value['name'])) {
        $name = $value['name'];
      }
			if(!empty($name)) {
				\fputs($smtpConnect, "To: \"$name\" <$address>".$this->newline);
			} else {
				\fputs($smtpConnect, "To: <$address>".$this->newline);
			}
    }
    
    foreach($this->headers as $header) {
      \fputs($smtpConnect, $header);
    }
    if(!empty($this->attachments) || !empty($html)) {
      \fputs($smtpConnect, "Content-Type: multipart/mixed; boundary=\"PHP-mixed-".$this->randomhash."\"".$this->newline);
    }

    \fputs($smtpConnect, $this->newline);
    if(!empty($html)) {
      \fputs($smtpConnect, "This is a message with multiple parts in MIME format.".$this->newline);
      \fputs($smtpConnect, "--PHP-mixed-".$this->randomhash.$this->newline);
      \fputs($smtpConnect, "Content-Type: multipart/alternative; boundary=\"PHP-alt-".$this->randomhash."\"".$this->newline.$this->newline);
      \fputs($smtpConnect, "--PHP-alt-".$this->randomhash.$this->newline);
      \fputs($smtpConnect, "Content-Type: text/plain; charset=\"iso-8859-1\"".$this->newline);
      \fputs($smtpConnect, "Content-Transfer-Encoding: 7bit".$this->newline);
      \fputs($smtpConnect, $this->newline.$text.$this->newline);
      \fputs($smtpConnect, $this->newline);
      \fputs($smtpConnect, "--PHP-alt-".$this->randomhash.$this->newline);
      \fputs($smtpConnect, "Content-Type: text/html; charset=\"iso-8859-1\"".$this->newline);
      \fputs($smtpConnect, "Content-Transfer-Encoding: 7bit".$this->newline);
      \fputs($smtpConnect, $this->newline.$html.$this->newline);
      \fputs($smtpConnect, $this->newline);
      \fputs($smtpConnect, "--PHP-alt-".$this->randomhash."--".$this->newline);
    } else {
      \fputs($smtpConnect, $text.$this->newline);
    }
    \fputs($smtpConnect, $this->newline);

    foreach($this->attachments as $attachment) {
      \fputs($smtpConnect, $attachment);
    }

    if(!empty($this->attachments) || !empty($html)) {
      \fputs($smtpConnect, "--PHP-mixed-".$this->randomhash."--".$this->newline);
    }

    \fputs($smtpConnect, ".".$this->newline);
    $smtpResponse = \fgets($smtpConnect, 4096);
    $this->logArray['data2response'] = $smtpResponse;

    // say goodbye
    \fputs($smtpConnect,"QUIT" . $this->newline);
    $smtpResponse = \fgets($smtpConnect, 4096);
    $this->logArray['quitresponse'] = $smtpResponse;
    $this->logArray['quitcode'] = \substr($smtpResponse, 0, 3);
    \fclose($smtpConnect);

    //a return value of 221 in $retVal["quitcode"] is a success
    return TRUE;
  }

  /*! \brief Display the generated log
   * 
   */
  function showLog() {
    print_r($this->logArray);
  }
}
