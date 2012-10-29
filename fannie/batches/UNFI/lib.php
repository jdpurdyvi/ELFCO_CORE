<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

function getVendorID($scriptName){
	global $dbc;

	$query = sprintf("SELECT vendorID FROM vendorLoadScripts
		WHERE loadScript=%s",$dbc->escape($scriptName));
	$result = $dbc->query($query);

	if (!$result || $dbc->num_rows($result) == 0)
		return False;
	else
		return array_pop($dbc->fetch_row($result));
}

?>
