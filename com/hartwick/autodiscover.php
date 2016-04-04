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
 * This class takes a host, srv and protocol and attempts to autodiscover
 * the server target and port.
 */
class autodiscover {
	/**
	 *
	 * @var string $srv The service that we are autodiscovering
	 */
	private $srv;
	/**
	 *
	 * @var string $proto The protocol of the service we are autodiscovering
	 */
  private $proto;
	/**
	 *
	 * @var string $host The hostname we want to start with to autodiscover
	 */
  private $host;
	/**
	 *
	 * @var int $depth The depth of the recursion.
	 */
	private $depth;
	/**
	 *
	 * @var int $maxdepth The maximum depth to recurse.
	 */
	private $maxdepth;

  /** 
	 * Setup the class with sane initial values.
   * @param string $host The hostname, if not supplied call gethostname()
	 * @param string $srv The service, defaults to _submission
	 * @param string $proto The protocol, defaults to _tcp
   */
  public function __construct($host = "", $srv = "_submission", $proto = "_tcp") {
		if($host === "") {
			$this->setHost(gethostname());
		} else {
			$this->setHost($host);
		}
		$this->setSRV($srv);
		$this->setProto($proto);
  }
	
	/**
	 * Set the host property. Also explode the hostname into components
	 * @param string $host The hostname to set
	 */
	public function setHost($host) {
		$this->host = $host;
		$this->hostarray = explode(".", $host);
		$this->depth = 0;
		$this->maxdepth = count($this->hostarray);
	}
	
	/**
	 * Set the srv property
	 * @param string $srv The service
	 */
	public function setSRV($srv) {
		$this->srv = $srv;
	}
	
	/**
	 * Set the proto property
	 * @param string $proto The protocol
	 */
	public function setProto($proto) {
		$this->proto = $proto;
	}
	
	/**
	 * Get the hostname
	 * @return string The hostname
	 */
	public function getHost() {
		return $this->host;
	}
	
	/**
	 * Get the service
	 * @return string The service
	 */
	public function getSRV() {
		return $this->srv;
	}
	
	/**
	 * Get the protocol
	 * @return string The protocol
	 */
	public function getProto() {
		return $this->proto;
	}

	/**
	 * Use DNS to attempt to autodiscover the target and port for the
	 * host, proto and srv as set in the properties. Will recurse through
	 * the elements of the hostname until it finds an autodiscover or 
	 * runs out of elements.
	 * 
	 * @return mixed Returns an array of the target and port on success
	 * @todo Consider restricting to at least 2 levels of depth to never
	 * try to autodiscover a TLD.
	 */
	public function CheckRR() {
		$check = $this->srv.".".$this->proto.".";
		for($i = $this->depth; $i < $this->maxdepth; $i++) {
			$check .= $this->hostarray[$i].".";
		}
		$res = dns_get_record($check, DNS_SRV);
		if(count($res) == 0 || $res === FALSE) {
			$this->depth++;
			if($this->maxdepth < $this->depth) {
				return FALSE;
			}
			$result = $this->CheckRR();
			if($result === FALSE) {
				return FALSE;
			} else {
				return $result;
			}
			$this->depth--;
		} else {
			return array($res[0]['target'], $res[0]['port']);
		}
	}
}
