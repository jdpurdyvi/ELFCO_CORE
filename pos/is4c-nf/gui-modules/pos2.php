<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	18Sep2012 Eric Lee Added CORE_LOCAL[store]-based option to change style of items.
	                    Needed for expanded comments for scaled items.

*/

ini_set('display_errors','1');
 
session_cache_limiter('nocache');

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class pos2 extends BasicPage {

	var $display;

	function preprocess(){
		global $CORE_LOCAL;
		$this->display = "";

		$sd = MiscLib::scaleObject();
		//$st = MiscLib::sigTermObject();

		$entered = "";
		if (isset($_REQUEST["reginput"])) {
			$entered = strtoupper(trim($_REQUEST["reginput"]));
		}

		if (substr($entered, -2) == "CL") $entered = "CL";

		if ($entered == "RI") $entered = $CORE_LOCAL->get("strEntered");

		if ($CORE_LOCAL->get("msgrepeat") == 1 && $entered != "CL") {
			$entered = $CORE_LOCAL->get("strRemembered");
		}
		$CORE_LOCAL->set("strEntered",$entered);

		$json = array();
		if ($entered != ""){
			/* this breaks the model a bit, but I'm putting
			 * putting the CC parser first manually to minimize
			 * code that potentially handles the PAN */
			include_once(realpath(dirname(__FILE__)."/../cc-modules/lib/paycardEntered.php"));
			$pe = new paycardEntered();
			if ($pe->check($entered)){
				$valid = $pe->parse($entered);
				$entered = "PAYCARD";
				$CORE_LOCAL->set("strEntered","");
				$json = $valid;
			}

			$CORE_LOCAL->set("quantity",0);
			$CORE_LOCAL->set("multiple",0);

			/* FIRST PARSE CHAIN:
			 * Objects belong in the first parse chain if they
			 * modify the entered string, but do not process it
			 * This chain should be used for checking prefixes/suffixes
			 * to set up appropriate $CORE_LOCAL variables.
			 */
			$parser_lib_path = $this->page_url."parser-class-lib/";
			if (!is_array($CORE_LOCAL->get("preparse_chain")))
				$CORE_LOCAL->set("preparse_chain",PreParser::get_preparse_chain());

			foreach ($CORE_LOCAL->get("preparse_chain") as $cn){
				$p = new $cn();
				if ($p->check($entered))
					$entered = $p->parse($entered);
					if (!$entered || $entered == "")
						break;
			}

			if ($entered != "" && $entered != "PAYCARD"){
				/* 
				 * SECOND PARSE CHAIN
				 * these parser objects should process any input
				 * completely. The return value of parse() determines
				 * whether to call lastpage() [list the items on screen]
				 */
				if (!is_array($CORE_LOCAL->get("parse_chain")))
					$CORE_LOCAL->set("parse_chain",Parser::get_parse_chain());

				$result = False;
				foreach ($CORE_LOCAL->get("parse_chain") as $cn){
					$p = new $cn();
					if ($p->check($entered)){
						$result = $p->parse($entered);
						break;
					}
				}
				if ($result && is_array($result)){
					$json = $result;
					if (isset($result['udpmsg']) && $result['udpmsg'] !== False){
						if (is_object($sd))
							$sd->WriteToScale($result['udpmsg']);
						/*
						if (is_object($st))
							$st->WriteToScale($result['udpmsg']);
						*/
					}
				}
				else {
					$arr = array(
						'main_frame'=>false,
						'target'=>'.baseHeight',
						'output'=>DisplayLib::inputUnknown());
					$json = $arr;
					if (is_object($sd)){
						$sd->WriteToScale('errorBeep');
					}
				}
			}
		}
		$CORE_LOCAL->set("msgrepeat",0);
		if (isset($json['main_frame']) && $json['main_frame'] != False){
			$this->change_page($json['main_frame']);
			return False;
		}
		if (isset($json['output']) && !empty($json['output']))
			$this->display = $json['output'];

		if (isset($json['retry']) && $json['retry'] != False){
			$this->add_onload_command("setTimeout(\"inputRetry('".$json['retry']."');\", 700);\n");
		}

		if (isset($json['receipt']) && $json['receipt'] != False){
			$this->add_onload_command("receiptFetch('".$json['receipt']."');\n");
		}

		return True;
	}

	function head_content(){
		global $CORE_LOCAL;
		?>
		<script type="text/javascript" src="<?php echo $this->page_url; ?>js/ajax-parser.js"></script>
		<script type="text/javascript">
		function submitWrapper(){
			var str = $('#reginput').val();
			if (str.indexOf("tw") != -1 || str.indexOf("TW") != -1 || (str.search(/^[0-9]+$/) == 0 && str.length <= 13) || str=='TFS'){
				$('#reginput').val('');
				clearTimeout(screenLockVar);
				runParser(str,'<?php echo $this->page_url; ?>');
				enableScreenLock();
				return false;
			}
			return true;
		}
		function parseWrapper(str){
			$('#reginput').val(str);
			$('#formlocal').submit();
		}
		var screenLockVar;
		function enableScreenLock(){
			screenLockVar = setTimeout('lockScreen()', <?php echo $CORE_LOCAL->get("timeout") ?>);
		}
		function lockScreen(){
			$.ajax({
				'url': '<?php echo $this->page_url; ?>ajax-callbacks/ajax-lock.php',
				'type': 'get',
				'cache': false,
				'success': function(){
					location = '<?php echo $this->page_url; ?>gui-modules/login3.php';
				}
			});
		}
		function receiptFetch(r_type){
			$.ajax({
				url: '<?php echo $this->page_url; ?>ajax-callbacks/ajax-end.php',
				type: 'get',
				data: 'receiptType='+r_type,
				cache: false,
				success: function(data){
				},
				error: function(e1){
				}
			});
		}
		function inputRetry(str){
			parseWrapper(str);
		}
		</script>
		<?php
	}

	function body_content(){
		global $CORE_LOCAL;
		$this->input_header('action="pos2.php" onsubmit="return submitWrapper();"');
		if ($CORE_LOCAL->get("timeout") != "")
			$this->add_onload_command("enableScreenLock();\n");
		$this->add_onload_command("\$('#reginput').keydown(function(ev){
					switch(ev.which){
					case 33:
						parseWrapper('U11');
						break;
					case 38:
						parseWrapper('U');
						break;
					case 34:
						parseWrapper('D11');
						break;
					case 40:
						parseWrapper('D');
						break;
					case 9:
						parseWrapper('TFS');
						return false;
					}
				});\n");
		/*
		if ($CORE_LOCAL->get("msgrepeat") == 1)
			$this->add_onload_command("submitWrapper();");
		*/
		?>
		<div class="baseHeight">
		<?php

		$CORE_LOCAL->set("quantity",0);
		$CORE_LOCAL->set("multiple",0);
		$CORE_LOCAL->set("casediscount",0);
		$CORE_LOCAL->set("away",0);

		// set memberID if not set already
		if (!$CORE_LOCAL->get("memberID")) {
			$CORE_LOCAL->set("memberID","0");
		}

		// handle messages
		if ( $CORE_LOCAL->get("msg") == "0") {
			$CORE_LOCAL->set("msg",99);
			$CORE_LOCAL->set("unlock",0);
		}

		if ($CORE_LOCAL->get("plainmsg") && strlen($CORE_LOCAL->get("plainmsg")) > 0) {
			echo DisplayLib::printheaderb();
			echo "<div class=\"centerOffset\">";
			echo DisplayLib::plainmsg($CORE_LOCAL->get("plainmsg"));
			$CORE_LOCAL->set("plainmsg",0);
			$CORE_LOCAL->set("msg",99);
			echo "</div>";
		}
		elseif (!empty($this->display))
			echo $this->display;
		else
			echo DisplayLib::lastpage();

		echo "</div>"; // end base height

		echo "<div id=\"footer\">";
		if ($CORE_LOCAL->get("away") == 1)
			echo DisplayLib::printfooterb();
		else
			echo DisplayLib::printfooter();
		echo "</div>";

		$CORE_LOCAL->set("away",0);
	} // END body_content() FUNCTION
}

new pos2();

?>
