<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

if (!chdir("Suspensions")){
	echo "Error: Can't find directory (suspensions)";
	exit;
}

include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');

/* HELP

   This script de-activates members with store-charge account (ar) in arrears.
*/

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - -
 *
 * 17Jun12 EL Fix HELP to make it appropriate to this program.
 *             Was a copy of reactivate.equity.php.
*/

set_time_limit(0);
ini_set('memory_limit','256M');

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
		$FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

$TRANS = $FANNIE_TRANS_DB . ($FANNIE_SERVER_DBMS=="MSSQL" ? 'dbo.' : '.');

$susQ = "INSERT INTO suspensions
	select m.card_no,'I',c.memType,c.Type,'',
	".$sql->now().",m.ads_OK,c.Discount,
	c.memDiscountLimit,1
	from meminfo as m left join
	custdata as c on c.CardNo=m.card_no and c.personNum=1
	left join {$TRANS}newBalanceToday_cust as n on m.card_no=n.memnum
	left join {$TRANS}AR_EOM_Summary AS a ON a.cardno=m.card_no
	where a.twoMonthBalance <= n.balance
	AND a.lastMonthPayments < a.twoMonthBalance
	and c.type='PC' and n.balance > 0
	and c.memtype in (1,3)
	and NOT EXISTS(SELECT NULL FROM suspensions as s
	WHERE s.cardno=m.card_no)";
$sql->query($susQ);

$histQ = "INSERT INTO suspension_history
	    select 'automatic',".$sql->now().",'',
	    m.card_no,1
	    from meminfo as m left join
	    custdata as c on c.CardNo=m.card_no and c.personNum=1
	    left join {$TRANS}newBalanceToday_cust as n on m.card_no=n.memnum
	    left join {$TRANS}AR_EOM_Summary AS a ON a.cardno=m.card_no
	    where a.twoMonthBalance <= n.balance
	    AND a.lastMonthPayments < a.twoMonthBalance
	    and c.type='PC' and n.balance > 0
	    and c.memtype in (1,3)
	    and NOT EXISTS(SELECT NULL FROM suspensions as s
	    WHERE s.cardno=m.card_no)";
$sql->query($histQ);

$custQ = "UPDATE custdata as c LEFT JOIN
	    suspensions as s on c.CardNo=s.cardno
	    SET c.type='INACT',memType=0,c.Discount=0,
	    memDiscountLimit=0
	    where c.type='PC' and s.cardno is not null";
$sql->query($custQ);

$memQ = "UPDATE meminfo as m LEFT JOIN
    suspensions as s ON m.card_no=s.cardno
    SET ads_OK=0
    where s.cardno is not null";
$sql->query($memQ);

?>
