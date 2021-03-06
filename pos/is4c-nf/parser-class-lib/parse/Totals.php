<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

class Totals extends Parser {

	function check($str){
		if ($str == "FNTL" || $str == "TETL" ||
		    $str == "FTTL" || $str == "TL" ||
		    substr($str,0,2) == "FN")
			return True;
		return False;
	}

	function parse($str){
		global $CORE_LOCAL;
		$ret = $this->default_json();
		if ($str == "FNTL"){
			$ret['main_frame'] = MiscLib::base_url().'gui-modules/fsTotalConfirm.php';
		}
		elseif ($str == "TETL"){
			if ($CORE_LOCAL->get("requestType") == ""){
				$CORE_LOCAL->set("requestType","tax exempt");
				$CORE_LOCAL->set("requestMsg","Enter the tax exempt ID");
				$ret['main_frame'] = MiscLib::base_url().'gui-modules/requestInfo.php';
			}
			else if ($CORE_LOCAL->get("requestType") == "tax exempt"){
				TransRecord::addTaxExempt();
				TransRecord::addcomment("Tax Ex ID# ".$CORE_LOCAL->get("requestMsg"));
				$CORE_LOCAL->set("requestType","");
			}
		}
		elseif ($str == "FTTL")
			PrehLib::finalttl();
		elseif ($str == "TL"){
			$chk = PrehLib::ttl();
			if ($chk !== True)
				$ret['main_frame'] = $chk;
		}

		if (!$ret['main_frame']){
			$ret['output'] = DisplayLib::lastpage();
			$ret['redraw_footer'] = True;
		}
		return $ret;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>FNTL</td>
				<td>Foodstamp eligible total</td>
			</tr>
			<tr>
				<td>TETL</td>
				<td>Tax exempt total</td>
			</tr>
			<tr>
				<td>FTTL</td>
				<td>Final total</td>
			</tr>
			<tr>
				<td>TL</td>
				<td>Re-calculate total</td>
			</tr>
			</table>";
	}
}

?>
