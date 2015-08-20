<?php

/**
 * Project:     Address Parser
 * File:        address.class.php
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @link http://www.rob-barry.com/
 * @copyright 2007-2010 Rob Barry
 * @author Rob Barry <rob.barry at gmail dot com>
 * @version 1.00
 */

/**
 * Usage Instructions
 *  
 * This class is designed to crush U.S. postal addresses into a standard
 * format.
 *
 * Make sure this file and address.idx.php are in the same directory.
 * 
 * Example:
 *
 * <?php 
 *
 *		require_once('address.class.php'); 		
 *		$addressParser = new AddressParser();
 *
 *		echo $addressParser->parseAddress("123 Biltmore Boulevard #A-101") . "\n";
 *		//Output is: 123BILTMOREBLVD101A
 *
 *		echo $addressParser->parseAddress("One Two Three Biltmore Boulv Unit 101A") . "\n";
 *		//Output is the same: 123BILTMOREBLVD101A
 *
 * ?>
 *
 */

	class AddressParser {

		/**
		 * array containing common street endings and a few other housekeeping items
		 *
		 * @var array
		 */		
		var $streetIndex 			= array();

		/**
		 * array containing common abbreviations and a few other housekeeping items
		 *
		 * @var array
		 */		
		var $abbreviationIndex 		= array();

		/**
		 * The class constructor.
		 */
		function AddressParser() {
			$this->initializeIndices();
		}

		/**
		 * crushes addresses to a standard format
		 *
		 * @param string $address_string the address to be crushed
		 */		
		function parseAddress($address_string) {			
		 	
		 	$address_string = strtoupper($address_string);
		 	
			$address_string = $this->truncateZipCode($address_string);			
			$address_string = $this->cleanText($address_string);			
			$address_string = $this->cleanSuffixes($address_string);			
			$address_string = $this->applyStreetIndex($address_string);
			$address_string = $this->applyAbbreviationIndex($address_string);
			
			while (strstr($address_string, "  ")) $address_string = str_replace("  ", " ", $address_string);
			$address_string = trim($address_string);

			$address_string = $this->applyUnitFix($address_string);

		 	return $address_string;

		}
		
		/**
		 * splits addresses into multiple parts if split string present
		 * i.e., 110-120 Mayberry Way becomes 110 Mayberry Way, 111 Mayberry Way, etc.
		 *
		 * @param string $address_string the address to be split
		 */		
		function splitAddress($address_string) {	
			$split_string[] = "-";
			$split_string[] = "/";
			foreach ($split_string as $split) {
				$address_string = str_replace("{$split} ", "{$split}", $address_string);
				$address_string = str_replace(" {$split}", "{$split}", $address_string);
			}			
			$parts = explode(" ", $address_string);
			foreach ($split_string as $split) {
				if (strstr($parts[0], $split)) {					
					$set = explode($split, $parts[0]);
					unset($parts[0]);
					$set[0] = $set[0] * 1;
					$set[1] = $set[1] * 1;
					for($i=$set[0];$i<=$set[1];$i++) {												
						$address = $this->parseAddress($i . " " . implode(" ", $parts));
						$return_address[$address] = $address;
					}					
				} else {
					$address = $this->parseAddress($address_string);				
					$return_address[$address] = $address;
				}				
			}
			return $return_address;
		}
		
		/**
		 * loads street and abbreviation indices
		 */		
		private function initializeIndices() {
			require_once(dirname(__FILE__) . "/address.idx.php");
		}
		
		/**
		 * removes suffixes from numbers (1st, 2nd, 3rd, 4th, etc.)
		 *
		 * @param string $address_string the address to be crushed
		 */		
		private function cleanSuffixes($address_string) {
			$address_string = str_replace(" TH ", "TH ", $address_string);
			
			$address_string = preg_replace("/([0-9])ND/", "$1", $address_string);
			$address_string = preg_replace("/([0-9])ST/", "$1", $address_string);
			$address_string = preg_replace("/([0-9])RD/", "$1", $address_string);
			$address_string = preg_replace("/([0-9])TH/", "$1", $address_string);
			return $address_string;
		}
		
		/**
		 * applies common street index in address.idx.php
		 *
		 * @param string $address_string the address to be crushed
		 */		
		private function applyStreetIndex($address_string) {
		 	$address_words = explode(" ", $address_string);
		 	foreach ($address_words as $key => $word) if (isset($this->streetIndex[$word])) $address_words[$key] = $this->streetIndex[$word];
		 	return implode(" ", $address_words);
		}
		
		/**
		 * applies common abbreviations in address.idx.php
		 *
		 * @param string $address_string the address to be crushed
		 */		
		private function applyAbbreviationIndex($address_string) {
			foreach ($this->abbreviationIndex as $old_value => $new_value) $address_string = str_replace($old_value, $new_value, $address_string);
			return $address_string;
		}

		/**
		 * prepares address for unit separation (i.e., 101-A and A-101 both become 101A)
		 *
		 * @param string $address_string the address to be crushed
		 */				
		private function applyUnitFix($address_string) {
			$address_words = explode(" ", $address_string);
			foreach ($address_words as $key => $value) {
				$next_key = $key + 1;
				if (strlen($address_words[$key]) == 1) {
					@$address_words[$next_key] = $address_words[$key] . $address_words[$next_key];
					unset($address_words[$key]);
				} else {
					$address_words[$key] = $this->unitFix($address_words[$key]);
				}
			}
			foreach ($address_words as $key => $value) {
				$next_key = $key + 1;
				if (strlen($address_words[$key]) == 1) {
					@$address_words[$next_key] = $address_words[$key] . $address_words[$next_key];
					unset($address_words[$key]);
				} else {
					$address_words[$key] = $this->unitFix($value);
				}
			}	
			return implode("", $address_words);	
			
		}
		
		/**
		 * separates numbers and text for standardization of unit numbers
		 *
		 * @param string $unit assume every word in address is a unit
		 */		
		private function unitFix($unit) {
			$numeric_part = "";
			$string_part = "";
			for($i=0;$i<strlen($unit);$i++) (is_numeric($unit[$i])) ? $numeric_part .= $unit[$i] : $string_part .= $unit[$i];
			return $numeric_part . $string_part;
		}
		
		/**
		 * truncates zipcode to the first 5 numbers
		 *
		 * @param string $address_string the address to be crushed
		 */		
		private function truncateZipCode($address_string) {
			$address_string = preg_replace("/([0-9]{5})([0-9]{4})/", "$1", $address_string);
			$address_string = preg_replace("/([0-9]{5})-([0-9]{4})/", "$1", $address_string);
			return $address_string;
		}
	
		/**
		 * generic: filters non-letters and numbers out of string
		 *
		 * @param string $text text to be filtered
		 */		
		private function cleanText($text) {
			$cleaned_text = "";
			for($i=0;$i<strlen($text);$i++) if ((($text[$i] >= "A") && ($text[$i] <= "Z")) || (is_numeric($text[$i])) || ($text[$i] == " ")) $cleaned_text .= $text[$i];
			return $cleaned_text;
		}	

		/**
		 * generic: checks to see if one string is contained in another
		 *
		 * @param string $string1
		 * @param string $string2
		 */		
		 public function contained($string1, $string2, $min_length = 0) {
		 	$string1 = strtolower($string1);
		 	$string2 = strtolower($string2);
			if ((strlen($string1) < $min_length) || (strlen($string2) < $min_length)) return false;
		 	if ($string1 == $string2) return true;
		 	if (strlen($string1) > strlen($string2)) if (substr($string1, 0, strlen($string2)) == $string2) return true;
		 	if (strlen($string2) > strlen($string1)) if (substr($string2, 0, strlen($string1)) == $string1) return true;
		 	return false;
		 }
	}
		
?>