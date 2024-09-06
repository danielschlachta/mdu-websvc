<?php
ini_set('display_errors', 1); 
error_reporting(E_ALL);

include_once('./config.php');
include_once('common.php');

header("Content-type: image/png");

$font = getcwd() . '/fonts/Roboto-Regular.ttf';
$font_cond = getcwd() . '/fonts/RobotoCondensed-Regular.ttf';

function fmtnumnum($bytes) {
	$divisor = 1024;
	$unit = "KB";

    while ($bytes / $divisor >= 1000.0) {		
		$divisor *= 1024;
	}

    $number = number_format($bytes / $divisor, 2, '.', '');
    return $number;
}

function fmtnumun($bytes) {
	$divisor = 1024;
	$unit = "KB";

    while ($bytes / $divisor >= 1000.0) {
		switch ($unit) {
			case "KB":
				$unit = "MB";
				break;
			case "MB":
				$unit = "GB";
				break;
			case "GB":
				$unit = "TB";
				break;
			default:
				return;
		}
		
		$divisor *= 1024;
	}

    return $unit;
}

function fmtnum($bytes) {
	return fmtnumnum($bytes) . ' ' . fmtnumun($bytes);
}


if (@!($serial = $_GET['serial']))
	exit;

$sql = "select * from `$sim_table_name` " .
	"where SimSerial='$serial'";

if (!(@$resid = mysqli_query($connect, $sql)))
	error('select', $connect);

if (!@$resarr = mysqli_fetch_assoc($resid)) 
	exit;

mysqli_free_result($resid);

$simid = $resarr['SimId'];

$used = $resarr['Current'] - $resarr['Floor'];

if ($used < 0) {
	$used = 0;
}

$limit = $resarr['HasLimit'] ? $resarr['Limit'] : 0;

if ($limit < 0) {
	$limit = 0;
}

$percent = 0;

if ($limit > 0 && $used > 0) {
	$percent = $used / $limit;
}

$width = 800;
$height = 400;

$fontsize = 24;
$smallfontsize = 20;

$im = imagecreatetruecolor($width, $height);

$transp = imagecolorallocate ($im, 0xFF, 0xFF, 0xFF);
imagecolortransparent($im, $transp);
imagefilledrectangle($im, 0, 0, $width - 1, $height - 1, $transp);

if (@$_GET['secret'] != $mdu_secret) {
	imagepng($im);
	imagedestroy($im);
	exit;
}

$black = imagecolorallocate ($im, 0, 0, 0);

$green = imagecolorallocate($im, 0x6A, 0x9B, 0x93);
$blue = imagecolorallocate($im, 0x3D, 0x63, 0x6F);
$grey = imagecolorallocate($im, 0xC0, 0xC0, 0xC0);

imagefilledarc($im, 120, 120, 200, 200, 0, 360, $grey, IMG_ARC_PIE);

if ($limit > 0) {
	imagefilledarc($im, 120, 120, 200, 200, -90, -90 + $percent * 360, $green, 
		IMG_ARC_PIE);
}

imagefilledarc($im, 120, 120, 90, 90, 0, 360, $transp, IMG_ARC_PIE);

$ofsX = 450;

$ftsize = 32;

$text = fmtnumun($used);

$dimensions = imagettfbbox($ftsize, 0, $font_cond, $text);
$textWidthU = abs($dimensions[4] - $dimensions[0]);
$x = $ofsX - $textWidthU;

imagefttext($im, $ftsize, 0, $x, 218, $black, $font_cond, $text);

$ftsize = 42;

$text = fmtnumnum($used);

$dimensions = imagettfbbox($ftsize, 0, $font_cond, $text);
$textWidth = abs($dimensions[4] - $dimensions[0]);
$x = $ofsX - $textWidth - $textWidthU - 7;

imagefttext($im, $ftsize, 0, $x, 218, $black, $font_cond, $text);


$ftsize = 20;

$text = 'used ' . floor($used / 1024 / 1024) . ' MB';

if ($limit > 0) {
	$text = floor(($limit - $used) / 1024 / 1024) . ' MB left, ' . $text;
} 

$dimensions = imagettfbbox($ftsize, 0, $font, $text);
$textWidth = abs($dimensions[4] - $dimensions[0]);
$x = $ofsX - $textWidth;

imagefttext($im, $ftsize, 0, $x, 320, $black, $font, $text);

if ($limit > 0) {
	$text = 'limit ' . floor($limit / 1024 / 1024) . ' MB';
} else {
	$text = 'no limit';
}

$dimensions = imagettfbbox($ftsize, 0, $font, $text);
$textWidth = abs($dimensions[4] - $dimensions[0]);
$x = $ofsX - $textWidth;

imagefttext($im, $ftsize, 0, $x, 365, $black, $font, $text);


function drawcirc($x, $y, $bperc, $gperc, $text, $bnum, $gnum) {
	global $im;
	global $grey;
	global $green;
	global $blue;
	global $black;
	global $transp;
	global $font;
	
	$s = 100;
	$c = floor($s * 0.45);
	
	imagefilledarc($im, $x, $y, $s, $s, 0, 360, $grey, IMG_ARC_PIE);

	if ($bperc > 0) {
		imagefilledarc($im, $x, $y, $s, $s, -90, -90 + $bperc * 360, 
			$blue, IMG_ARC_PIE);
	}
	
	if ($gperc > 0) {
		imagefilledarc($im, $x, $y, $s, $s, -90 + $bperc * 360, 
			-90 + ($gperc + $bperc) * 360, 	$green, IMG_ARC_PIE);
	}	
		
	imagefilledarc($im, $x, $y, $c, $c, 0, 360, $transp, IMG_ARC_PIE);
	
	imagefttext($im, 16, 0, $x - $s/2, $y + $s/2 + 28, $black, $font, $text);
			
	imagefilledrectangle($im, $x - $s/2, $y + $s/2 + 40,
		$x - $s/2 + 10, $y + $s/2 + 40 + 10, $green);
		
	imagefttext($im, 12, 0, $x - $s/2 + 15, $y + $s/2 + 51, $black, $font, 
		'RX = ' . fmtnum($gnum));
		
	imagefilledrectangle($im, $x - $s/2, $y + $s/2 + 60,
		$x - $s/2 + 10, $y + $s/2 + 60 + 10, $blue);
		
	imagefttext($im, 12, 0, $x - $s/2 + 15, $y + $s/2 + 71, $black, $font, 
		'TX = ' . fmtnum($bnum));

}

$starttime = mktime(0, 0, 0, $month, $day, $year) * 1000;

$sql = "select sum(RxBytes) as Rx, sum(TxBytes) as Tx from $history_table_name" 
	. " where SimId = $simid and StartTime >= $starttime and StartTime < " 
	. ($starttime + 86400 * 1000);

if (!(@$resid = mysqli_query($connect, $sql)))
	error('select', $connect);

if (!@$resarr = mysqli_fetch_assoc($resid)) 
	exit;

mysqli_free_result($resid);

$ttx = $resarr['Tx'];
$trx = $resarr['Rx'];
$today = $ttx + $trx;

$starttime = $starttime - 86400 * 1000;

$sql = "select sum(RxBytes) as Rx, sum(TxBytes) as Tx from $history_table_name" 
	. " where SimId = $simid and StartTime >= $starttime and StartTime < " 
	. ($starttime + 86400 * 1000);

if (!(@$resid = mysqli_query($connect, $sql)))
	error('select', $connect);

if (!@$resarr = mysqli_fetch_assoc($resid)) 
	exit;

mysqli_free_result($resid);

$ytx = $resarr['Tx'];
$yrx = $resarr['Rx'];
$yesterday = $ytx + $yrx;

if ($yesterday == 0) {
	if ($today > 0) {
		$trp = ($trx / $today);
		$ttp = 1 - $trp;
	} else {
		$trp = 0;
		$ttp = 0;
	}
	$yrp = 0;
	$ytp = 0;
} else if ($today < $yesterday) {
	$trp = ($trx / $yesterday);
	$ttp = ($ttx / $yesterday);
	$yrp = ($yrx / $yesterday);
	$ytp = 1 - $yrp;
} else {
	$trp = ($trx / $today);
	$ttp = 1 - $trp;
	$yrp = ($yrx / $today);
	$ytp = ($ytx / $today);
}

drawcirc(550, 60, $ttp, $trp, "today", $ttx, $trx);
drawcirc(550, 255, $ytp, $yrp, "yesterday", $ytx, $yrx);

$starttime = strtotime('first day of this month', $time) * 1000;
$endtime = (strtotime('last day of this month', $time) + 86400) * 1000;

$sql = "select sum(RxBytes) as Rx, sum(TxBytes) as Tx from $history_table_name" 
	. " where SimId = $simid and StartTime >= $starttime and StartTime < " 
	. $endtime;

if (!(@$resid = mysqli_query($connect, $sql)))
	error('select', $connect);

if (!@$resarr = mysqli_fetch_assoc($resid)) 
	exit;

mysqli_free_result($resid);

$ttx = $resarr['Tx'];
$trx = $resarr['Rx'];
$today = $ttx + $trx;

$starttime = strtotime('first day of last month', $time) * 1000;
$endtime = (strtotime('last day of last month', $time) + 86400) * 1000;

$sql = "select sum(RxBytes) as Rx, sum(TxBytes) as Tx from $history_table_name" 
	. " where SimId = $simid and StartTime >= $starttime and StartTime < " 
	. $endtime;

if (!(@$resid = mysqli_query($connect, $sql)))
	error('select', $connect);

if (!@$resarr = mysqli_fetch_assoc($resid)) 
	exit;
	
mysqli_free_result($resid);

$ytx = $resarr['Tx'];
$yrx = $resarr['Rx'];
$yesterday = $ytx + $yrx;

if ($yesterday == 0) {
	if ($today > 0) {
		$trp = ($trx / $today);
		$ttp = 1 - $trp;
	} else {
		$trp = 0;
		$ttp = 0;
	}
	$yrp = 0;
	$ytp = 0;
} else if ($today < $yesterday) {
	$trp = ($trx / $yesterday);
	$ttp = ($ttx / $yesterday);
	$yrp = ($yrx / $yesterday);
	$ytp = 1 - $yrp;
} else {
	$trp = ($trx / $today);
	$ttp = 1 - $trp;
	$yrp = ($yrx / $today);
	$ytp = ($ytx / $today);
}

drawcirc(710, 60, $ttp, $trp, "this month", $ttx, $trx);
drawcirc(710, 255, $ytp, $yrp, "last month", $ytx, $yrx);

imagepng($im);
imagedestroy($im);

?>
