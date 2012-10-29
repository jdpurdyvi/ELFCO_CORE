<?php
include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::LoadMap();
include(realpath(dirname(__FILE__).'/../ini.php'));
include('util.php');
?>
<html>
<head>
<title>IT CORE Lane Installation: Scanning options</title>
<style type="text/css">
body {
	line-height: 1.5em;
}
</style>
</head>
<body>
	
<a href="index.php">Necessities</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="extra_config.php">Additional Configuration</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Scanning Options
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="security.php">Security</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="debug.php">Debug</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="plugins.php">Plugins</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="extra_data.php">Sample Data</a>

<h2>IT CORE Lane Installation: Scanning Options</h2>

<?php
check_writeable('../ini.php');
check_writeable('../ini-local.php');
?>
<form action=scanning.php method=post>
Special handling modules for UPCs that aren't
products (e.g., coupons)<br />
<b>Special UPCs</b>:<br />
<select multiple size=10 name=SPECIAL_UPC_MODS[]>
<?php
if (isset($_REQUEST['SPECIAL_UPC_MODS'])) $CORE_LOCAL->set('SpecialUpcClasses',$_REQUEST['SPECIAL_UPC_MODS']);

$mods = AutoLoader::ListModules('SpecialUPC');

foreach($mods as $m){
	$selected = "";
	foreach($CORE_LOCAL->get("SpecialUpcClasses") as $r){
		if ($r == $m){
			$selected = "selected";
			break;
		}
	}
	echo "<option $selected>$m</option>";
}

$saveStr = "array(";
foreach($CORE_LOCAL->get("SpecialUpcClasses") as $r){
	$saveStr .= "'".$r."',";
}
$saveStr = rtrim($saveStr,",").")";
confsave('SpecialUpcClasses',$saveStr);
?>
</select><br />
<hr />
Discount type modules control how sale prices
are calculated.<br />
<b>Number of Discounts</b>:
<?php
if (isset($_REQUEST['DT_COUNT']) && is_numeric($_REQUEST['DT_COUNT'])) $CORE_LOCAL->set('DiscountTypeCount',$_REQUEST['DT_COUNT']);
if ($CORE_LOCAL->get("DiscountTypeCount") == "") $CORE_LOCAL->set("DiscountTypeCount",5);
if ($CORE_LOCAL->get("DiscountTypeCount") <= 0) $CORE_LOCAL->set("DiscountTypeCount",1);
printf("<input type=text size=4 name=DT_COUNT value=\"%d\" />",
	$CORE_LOCAL->get('DiscountTypeCount'));
confsave('DiscountTypeCount',$CORE_LOCAL->get('DiscountTypeCount'));
?>
<br /><b>Discount Module Mapping</b>:<br />
<?php
if (isset($_REQUEST['DT_MODS'])) $CORE_LOCAL->set('DiscountTypeClasses',$_REQUEST['DT_MODS']);
if (!is_array($CORE_LOCAL->get('DiscountTypeClasses'))){
	$CORE_LOCAL->set('DiscountTypeClasses',
		array(
			'NormalPricing',
			'EveryoneSale',
			'MemberSale',
			'CaseDiscount',
			'StaffSale'			
		));
}
$discounts = AutoLoader::ListModules('DiscountType');
$dt_conf = $CORE_LOCAL->get("DiscountTypeClasses");
for($i=0;$i<$CORE_LOCAL->get('DiscountTypeCount');$i++){
	echo "[$i] => ";
	echo "<select name=DT_MODS[]>";
	foreach($discounts as $d) {
		echo "<option";
		if (isset($dt_conf[$i]) && $dt_conf[$i] == $d)
			echo " selected";
		echo ">$d</option>";
	}
	echo "</select><br />";
}
$saveStr = "array(";
$tmp_count = 0;
foreach($CORE_LOCAL->get("DiscountTypeClasses") as $r){
	$saveStr .= "'".$r."',";
	if ($tmp_count == $CORE_LOCAL->get("DiscountTypeCount")-1)
		break;
	$tmp_count++;
}
$saveStr = rtrim($saveStr,",").")";
confsave('DiscountTypeClasses',$saveStr);
?>
<hr />
Price Methods dictate how item prices are calculated.
There's some overlap with Discount Types, but <i>generally</i>
price methods deal with grouped items.<br />
<b>Number of Price Methods</b>:
<?php
if (isset($_REQUEST['PM_COUNT']) && is_numeric($_REQUEST['PM_COUNT'])) $CORE_LOCAL->set('PriceMethodCount',$_REQUEST['PM_COUNT']);
if ($CORE_LOCAL->get("PriceMethodCount") == "") $CORE_LOCAL->set("PriceMethodCount",3);
if ($CORE_LOCAL->get("PriceMethodCount") <= 0) $CORE_LOCAL->set("PriceMethodCount",1);
printf("<input type=text size=4 name=PM_COUNT value=\"%d\" />",
	$CORE_LOCAL->get('PriceMethodCount'));
confsave('PriceMethodCount',$CORE_LOCAL->get('PriceMethodCount'));
?>
<br /><b>Price Method Mapping</b>:<br />
<?php
if (isset($_REQUEST['PM_MODS'])) $CORE_LOCAL->set('PriceMethodClasses',$_REQUEST['PM_MODS']);
if (!is_array($CORE_LOCAL->get('PriceMethodClasses'))){
	$CORE_LOCAL->set('PriceMethodClasses',
		array(
			'BasicPM',
			'GroupPM',
			'QttyEnforcedGroupPM'
		));
}
$pms = AutoLoader::ListModules('PriceMethod');
$pm_conf = $CORE_LOCAL->get("PriceMethodClasses");
for($i=0;$i<$CORE_LOCAL->get('PriceMethodCount');$i++){
	echo "[$i] => ";
	echo "<select name=PM_MODS[]>";
	foreach($pms as $p) {
		echo "<option";
		if (isset($pm_conf[$i]) && $pm_conf[$i] == $p)
			echo " selected";
		echo ">$p</option>";
	}
	echo "</select><br />";
}
$saveStr = "array(";
$tmp_count = 0;
foreach($CORE_LOCAL->get("PriceMethodClasses") as $r){
	$saveStr .= "'".$r."',";
	if ($tmp_count == $CORE_LOCAL->get("PriceMethodCount")-1)
		break;
	$tmp_count++;
}
$saveStr = rtrim($saveStr,",").")";
confsave('PriceMethodClasses',$saveStr);
?>
<hr />
<input type=submit value="Save Changes" />
</form>
</body>
</html>
