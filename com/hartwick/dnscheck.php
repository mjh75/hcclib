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

class dnscheck {
	private $roots = array(	'a.root-servers.net',
									'b.root-servers.net',
									'c.root-servers.net',
									'd.root-servers.net',
									'e.root-servers.net',
									'f.root-servers.net',
									'g.root-servers.net',
									'h.root-servers.net',
									'i.root-servers.net',
									'j.root-servers.net',
									'k.root-servers.net',
									'l.root-servers.net',
									'm.root-servers.net',
			);
	protected $domain = "";
	protected $splitdomain;
	protected $authoritative;

	public function __construct($domain = "") {
		$this->setDomain($domain);
	}
	
	public function setDomain($domain) {
		$this->domain = $domain;
		$this->splitdomain = explode(".", $domain);
		return $this->domain;
	}
	
	public function getDomain() {
		return $this->domain;
	}
	
	public function checkDelegation($servers = "") {
		if(empty($servers)) {
			$servers = $this->roots;
		}

		/*
		 * Lookup the IP's for the servers provided
		 */
		for($i = 0; $i < count($servers); $i++) {
			$nameserver[] = gethostbyname($servers[$i]);
		}
		$resolver = new \Net_DNS2_Resolver(array('nameservers' => $nameserver));
		try {
			$result = $resolver->query($this->domain, "NS");
		} catch (\Net_DNS2_Exception $e) {
			return \FALSE;
		}
		
		if($result->authority[0]->name === $this->domain) {
			$k = 0;
			foreach($result->authority as $nsrr){
				$newservers[] = $nsrr->nsdname;
				$this->authoritative[$k]['name'] = $nsrr->nsdname;
				$this->authoritative[$k]['failed'] = \FALSE;
				$k++;
			}
		} else {
			foreach($result->authority as $nsrr){
				$newservers[] = $nsrr->nsdname;
			}
			$ret = $this->checkDelegation($newservers);
		}
		return \TRUE;
	}

	public function checkDomain() {
		$resolver = array();
		$result = array();
		$nameserver = array();
		$test = 0;
		$error = 0;
		$warning = 0;
		
		for($i = 0; $i < count($this->authoritative); $i++) {
			$nameserver[$i] = gethostbyname($this->authoritative[$i]['name']);
			$resolver[$i] = new \Net_DNS2_Resolver(array('nameservers' => array($nameserver[$i])));
			try {
				$result[$i] = $resolver[$i]->query($this->domain, "SOA");
				$this->authoritative[$i]['failed'] = \FALSE;
			} catch (\Net_DNS2_Exception $e) {
				$this->authoritative[$i]['failed'] = \TRUE;
			}
		}
		for($j = 0; $j < count($this->authoritative); $j++) {
			if($this->authoritative[$j]['failed'] === \TRUE) {
				$error++;
			} else {
				$this->authoritative[$j]['serial'] = $result[$j]->answer[0]->serial;
				if($test === 0) {
					$test = $result[$j]->answer[0]->serial;
				} else if($test !== $result[$j]->answer[0]->serial) {
					$warning++;
				}
			}
		}
		if($error !== 0 || $warning !== 0) {
			return \FALSE;
		} else {
			return \TRUE;
		}
	}
	
	public function nagiosOutput() {
		$auth = 0;
		$failed = 0;
		$status = 3;
		$faildetails = "";
		$failmessage = "";
		$statusmessage = array(0 => 'OK', 1 => "WARNING", 2 => "CRITICAL", 3 => "UNKNOWN");
		
		for($i = 0; $i < count($this->authoritative); $i++) {
			$auth++;
			if($this->authoritative[$i]['failed'] === \TRUE) {
				$failed++;
				$faildetails .= $this->authoritative[$i]['name']." ";
			}
		}
		
		if($failed !== 0) {
			$status = 2;
			$failmessage = " - $failed Failed; $faildetails";
		} else {
			$status = 0;
		}
		
		return $statusmessage[$status].": Tested $auth$failmessage";
	}
	
}
