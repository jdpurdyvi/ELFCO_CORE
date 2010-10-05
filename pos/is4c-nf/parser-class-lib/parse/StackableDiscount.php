<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

if (!class_exists("Parser")) include_once($_SESSION["INCLUDE_PATH"]."/parser-class-lib/Parser.php");
if (!function_exists("percentDiscount")) include_once($_SESSION["INCLUDE_PATH"]."/lib/prehkeys.php");
if (!function_exists("boxMsg")) include_once($_SESSION["INCLUDE_PATH"]."/lib/drawscreen.php");
if (!isset($IS4C_LOCAL)) include($_SESSION["INCLUDE_PATH"]."/lib/LocalStorage/conf.php");

class StackableDiscount extends Parser {
	function check($str){
		global $IS4C_LOCAL;
		if (substr($str,-2) == "SD"){
			$strl = substr($str,0,strlen($str)-2);
			if (!is_numeric($strl)) 
				return False;
			elseif ($IS4C_LOCAL->get("tenderTotal") != 0) 
				boxMsg("discount not applicable after tender");
			elseif ($strl > 50) 
				boxMsg("discount exceeds maximum");
			elseif ($strl <= 0) 
				boxMsg("discount must be greater than zero");
			elseif ($strl <= 50 and $strl > 0) {
				$existingPD = $IS4C_LOCAL->get("percentDiscount");
				$stackablePD = $strl;
				$equivalentPD = ($existingPD + $stackablePD);								//	sum discounts
				percentDiscount($equivalentPD);
			}
			else 
				return False;
			return True;
		}
		return False;
	}

	function parse($str){
		return False;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td><i>number</i>SD</td>
				<td>Add percent discount in amount
				<i>number</i></td>
			</tr>
			</table>";
	}
}

?>
