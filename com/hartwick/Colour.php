<?php
namespace com\hartwick;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Colour
 *
 * @author hartwick
 */
class Colour {
	private $red = 0;
	private $green = 0;
	private $blue = 0;
	private $h = 0;
	private $s = 0;
	private $l = 0;
	private $complementaryh2 = 0;
	private $complementaryred = 0;
	private $complementarygreen = 0;
	private $complementaryblue = 0;
	public $format;
	public $colour;
	
	public function __construct($colour) {
		$this->colour = $colour;
		if(is_array($colour)) {
			$this->format = "array";
			$this->red = $colour['r'];
			$this->green = $colour['g'];
			$this->blue = $colour['b'];
		} else if(strncasecmp("rgb(", $colour, 4) == 0) {
			$this->format = "rgb";
			$temp = explode(',', substr($colour, 4, -1));
			$this->red = $temp[0];
			$this->green = $temp[1];
			$this->blue = $temp[2];
		} else if(strncasecmp("#", $colour, 1) == 0) {
			$this->format = "hex";
			$this->red = hexdec(substr($colour, 1, 2));
			$this->green = hexdec(substr($colour, 3, 2));
			$this->blue = hexdec(substr($colour, 5, 2));
		} else {
			$this->format = "unsupported";
		}
		$this->toHSL();
	}
	
	public function getHex($colour = "original") {
		if($colour === "complementary") {
			return sprintf("#%02X%02X%02X", $this->complementaryred, $this->complementarygreen, $this->complementaryblue);
		} else { 
			return sprintf("#%02X%02X%02X", $this->red, $this->green, $this->blue);
		}
	}
	
	public function getHSL() {
		return array(number_format($this->h, 4), $this->s, $this->l);
	}
	
	private function toHSL() {
		$r = $this->red / 255;
		$g = $this->green / 255;
		$b = $this->blue / 255;
		$min = min($r, $g, $b);
		$max = max($r, $g, $b);
		$delta = $max - $min;
		$l = ($max + $min) / 2;
		
		if($delta === 0) {
			$h = 0;
			$s = 0;
		} else {
			if($l < 0.5) {
				$s = $delta / ($max + $min);
			} else {
				$s = $delta / (2 - $max - $min);
			}
		}
		$delta_r = ((($max - $r) / 6) + ($max / 2)) / $max;
		$delta_g = ((($max - $g) / 6) + ($max / 2)) / $max;
		$delta_b = ((($max - $b) / 6) + ($max / 2)) / $max;
		
		if($r === $max) {
			$h = $delta_b - $delta_g;
		} else if($g === $max) {
			$h = (1 / 3) + $delta_r - $delta_b;
		} else if($b === $max) {
			$h = (2 / 3) + $delta_g - $delta_r;
		}
		if($h < 0) {
			$h += 1;
		}
		if($h > 1) {
			$h -= 1;
		}
		$this->h = $h;
		$this->s = $s;
		$this->l = $l;
	}

	private function fromHSL() {
		if($this->s === 0) {
			$this->red = $l * 255;
			$this->green = $l * 255;
			$this->blue = $l * 255;
		} else {
			if($l < 0.5) {
				$v2 = $l * (1 + $s);
			} else {
				$v2 = ($l + $s) - ($s * $l);
			}
			$v1 = 2 * $l - $v2;
			$this->complementaryred = 255 * $this->hue2rgb($v1, $v2, $this->complementaryh2 + (1 / 3));
			$this->complementarygreen = 255 * $his->hue2rgb($v1, $v2, $this->complementaryh2);
			$this->complementaryblue = 255 * $this->hue2rgb($v1, $v2, $this->complementaryh2 - (1 / 3));
		}
	}
	
	private function hue2rgb($v1, $v2, $vh) {
		if($vh < 0) {
			$vh += 1;
		}
		if($vh > 1) {
			$$vh -= 1;
		}
		if((6 * $vh) < 1) {
			return ($v1 + ($v2 - $v1) * 6 * $vh);
		}
		if((2 * $vh) < 1) {
			return ($v2);
		}
		if((3 * $vh) < 2) {
			return ($v1 + ($v2 - $v1) * ((2 / 3 - $vh) * 6));
		}
		return ($v1);
	}
	
	public function complementary() {
		$h2 = $this->h + 0.5;
		if($h2 > 1) {
			$h2 -= 1;
		}
		$this->complementaryh2 = $h2;
		return array(number_format($this->complementaryh2, 4), $this->s, $this->l);
	}
}
