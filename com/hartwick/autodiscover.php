<?php namespace com\hartwick;

class autodiscover {
  private $server;
  private $port;
  private $srv;
  private $proto;
  private $host;
	private $depth;
	private $maxdepth;

  /*! \brief Setup the class with sane initial values.
   *
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
	
	public function setHost($host) {
		$this->host = $host;
		$this->hostarray = explode(".", $host);
		$this->depth = 0;
		$this->maxdepth = count($this->hostarray);
	}
	
	public function setSRV($srv) {
		$this->srv = $srv;
	}
	
	public function setProto($proto) {
		$this->proto = $proto;
	}
	
	public function getHost() {
		return $this->host;
	}
	
	public function getSRV() {
		return $this->srv;
	}
	
	public function getProto() {
		return $this->proto;
	}

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
