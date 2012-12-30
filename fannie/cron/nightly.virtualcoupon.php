<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

/* HELP

   nightly.virtualcoupon.php

   Track virtual coupon usage
   Adjust custdata settings to match
*/

include('../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'src/cron_msg.php');

set_time_limit(0);

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
		$FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

$TRANS = ($FANNIE_SERVER_DBMS == "MSSQL") ? $FANNIE_TRANS_DB.".dbo." : $FANNIE_TRANS_DB.".";

// create a temp table to hold info about recently used coupons
if (!$sql->table_exists("TempVirtCoupon")){
	$sql->query("CREATE TABLE TempVirtCoupon (card_no int, coupID int, quantity double,PRIMARY KEY(card_no,coupID))");
}
else
	$sql->query("TRUNCATE TABLE TempVirtCoupon");

echo cron_msg("Create / Truncate table TempVirtCoupon");

// select number of coupons used by each member in
// the applicable period
$insQ = "INSERT INTO TempVirtCoupon
	select d.card_no, h.coupID, sum(quantity) as quantity
	from {$TRANS}dlog_90_view as d
	INNER JOIN houseVirtualCoupons as h
	ON d.upc=".$sql->concat("'00499999'",'RIGHT('.$sql->concat("'00000'",$sql->convert('h.coupID','CHAR'),'').',5)','')."
	AND d.card_no=h.card_no
	where d.tdate >= h.start_date and d.tdate <= h.end_date";
$insR = $sql->query($insQ);

// remove expired or already-used coupons
$sqlQ = "DELETE h FROM houseVirtualCoupons AS h
	LEFT JOIN TempVirtCoupon AS t ON
	h.card_no=t.card_no AND h.coupID=t.coupID
	WHERE ".$sql->now()." > h.end_date
	OR (t.card_no IS NOT NULL AND t.coupID IS NOT NULL)";
if ($FANNIE_SERVER_DBMS == "MSSQL"){
	$sqlQ = "DELETE FROM houseVirtualCoupons 
		FROM houseVirtualCoupons AS h
		LEFT JOIN TempVirtCoupon AS t ON
		h.card_no=t.card_no AND h.coupID=t.coupID
		WHERE ".$sql->now()." > h.end_date
		OR (t.card_no IS NOT NULL AND t.coupID IS NOT NULL)";
}
$delR = $sql->query($sqlQ);
if ($delR === false) 
	echo cron_msg("DELETE query failed");
else
	echo cron_msg("Successfully removed redeemed houseVirtualCoupons (" . num_rows($sqlR) . ")");

// set custdata.memcoupons equal to the number
// of available coupons (in theory)
$upQ = "UPDATE $FANNIE_OP_DB.custdata AS c, houseVirtualCoupons AS h
	SET c.memCoupons=1
	WHERE c.CardNo=h.card_no 
	AND ".$sql->now()." >= h.start_date 
	AND ".$sql->now()."<= h.end_date";

if ($FANNIE_SERVER_DBMS == "MSSQL"){
	$upQ = "UPDATE custdata SET 
		c.memCoupons=SUM(CASE WHEN c.personNum=1 THEN 1 ELSE 0)
		FROM custdata AS c LEFT JOIN
		houseVirtualCoupons AS h ON c.CardNo=h.card_no
		WHERE ".$sql->now()." >= h.start_date 
		AND ".$sql->now()."<= h.end_date
		GROUP BY c.CardNo";
}
$upR = $sql->query($upQ);

if ($upR === false)
	echo cron_msg("Failed to update custdata field: memCoupons");
else
	echo cron_msg("Successfully updated custdata field: memCoupons");

// update blueline to match memcoupons
$blueLineQ = "UPDATE $FANNIE_OP_DB.custdata SET blueLine="
	.$sql->concat($sql->convert('CardNo','CHAR'),"' '",'LastName',"' Coup('",
		$sql->convert('memCoupons','CHAR'),"')'",'');
$blR = $sql->query($blueLineQ);

if ($blR === false)
	echo cron_msg("Failed to update custdata field: blueLine");
else
	echo cron_msg("Successfully updated custdata field: blueLine");

$sql->query("DROP TABLE TempVirtCoupon");

?>
