<?php

ini_set('display_errors', 1); 
error_reporting(E_ALL);

include_once('./config.php');
include_once('common.php');

$width = 1000;
$height = 622;

$font = getcwd() . '/fonts/Roboto-Regular.ttf';
$fontsize = 24;
$smallfontsize = 20;

header("Content-type: image/png");

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

$dark = $black;
$light = $black;

$green = imagecolorallocate($im, 0x6A, 0x9B, 0x93);
$blue = imagecolorallocate($im, 0x3D, 0x63, 0x6F);

$marginTop = $fontsize * 2;
$marginBottom = round($fontsize * 1.9);

$starttime = $_GET['start'] + 0;
$endtime = $_GET['end'] + 0;
$view = $_GET['view'] + 0;
$simid = $_GET['simid'] + 0;

if ($view == 2)
{
	$slotInterval = 86400 * 1000;
	$labelCount = 7;
} else if ($view == 3)
{
	$slotInterval = 86400 * 1000;
	$labelCount = date('j', $endtime / 1000);
} else if ($view == 4)
{
	$slotInterval = 0;
	$labelCount = 12;
} else {
	$view = 1;
	$slotInterval = 3600 * 1000;
	$labelCount = 4;
}

$maxBytes = 0;
$slotCount = 0;

$currtime = $starttime * 1000;

while ($currtime < $endtime * 1000)
{
	if ($view == 4)
		$nexttime = strtotime('first day of next month', $currtime / 1000) * 1000;
	else
		$nexttime = $currtime + $slotInterval;
	
	$query = 'select sum(RxBytes), sum(TxBytes) from ' 
	. $history_table_name . ' where SimId = ' . $simid 
	. ' and StartTime >= ' . $currtime
	. ' and StartTime < ' . $nexttime;
	
	if (!(@$resid = mysqli_query($connect, $query)))
		error('select', $connect);
	
	$row = mysqli_fetch_array($resid, MYSQLI_NUM);
	
	$rxBytes = $row[0];
	$txBytes = $row[1];
	
	$slots[] = array('rxBytes' => $rxBytes, 'txBytes' => $txBytes,
	  'timeStamp' => round(floor($currtime / 1000)));
	
	if ($maxBytes < $rxBytes)
		$maxBytes = $rxBytes;
		
	if ($maxBytes < $txBytes)
		$maxBytes = $txBytes;

	$currtime = $nexttime;
	$slotCount++;
}

if ($view == 3)
	$labelCount = $slotCount;

function getLabel($index) {
	global $slots;
	global $view;
	global $slotCount;
	
	$time = $slots[$index]['timeStamp'];
	
	if ($view == 1)
		return date('H:i:s', $time);
	else if ($view == 2)
		return date('D j', $time);
	else if ($view == 3)
		return date('j', $time);
	else if ($view == 4)
		return date('M', $time);
}

$plotHeight = $height - $marginBottom - $marginTop - 2;
$colWidth = $width / $slotCount;
$colStep = $colWidth / 10;

$maxData = $maxBytes;

$dimString = "Byte";
$dim = 1;

if ($maxData > 1024) {
	$dimString = "Kilobyte";
	$dim = 1024;
}

if ($maxData > 1024 * 1024) {
	$dimString = "Megabyte";
	$dim = 1024 * 1024;
}

if ($maxData > 1024 * 1024 * 1024) {
	$dimString = "Gigabyte";
	$dim = 1024 * 1024 * 1024;
}

$lineStep = $fontsize / 3;

function drawVertLine($x, $color) {
	global $im;
	global $lineStep;
	global $height;
	
	$i = $lineStep * 2;

	while ($i <= $height) {
		imagerectangle($im, 
			$x - 1, $i - $lineStep, $x, $i - $lineStep + 1, $color);
		$i = $i + $lineStep * 2;
	}

	imagerectangle($im, $x - 1, $i - $lineStep * 2 + 5, $x, $height, $color);
}

function drawHorizLine($y, $color) {
	global $im;
	global $lineStep;
	global $width;

	$i = $lineStep * 2;

	while ($i - $lineStep <= $width) {
		imagerectangle($im,
			$i - $lineStep * 2, $y, $i - $lineStep, $y, $color);
		$i = $i + $lineStep * 2;
	}

	imagerectangle($im, $i - $lineStep * 2, $y, $width - 1, $y + 1, $color);
}

function drawText($text, $fontsize, $x, $y, $color) {
	global $im;
	global $font;
	
	imagefttext($im, $fontsize, 0, $x, $y, $color, $font, $text);
}

for ($cap = $dim < 1000 ? 1 : 2; $cap * $dim < $maxData; $cap++);

$factor = 1.0 * $plotHeight / ($cap * $dim);

for ($i = 0; $i < $labelCount; $i++) {

	$posX = round($i * $colWidth * $slotCount / $labelCount + 1);

	if ($view != 3 || $i % 2 == 0) {
		drawVertLine($posX, $light);
		drawText(getLabel(round(floor($slotCount / $labelCount)) * $i), 
			$view == 3 ? $smallfontsize : $fontsize, 
			$posX + ($view != 3 ? $fontsize - 14 : 4), $height - $fontsize / 2 + 4, $light);
	}
}

$lines = 4; // excluding top

while (round($fontsize * 1.8) > 
	round($dim * ($cap / $lines) * $factor))
		$lines--;

$frac = 0;

for ($l = $lines; $l > 2; $l--) {
	$maxfrac = 0;

	for ($i = 1; $i < $l; $i++)
		if (($cap / $l * $i) % 10 > $maxfrac)
			$maxfrac = ($cap / $l * $i) % 10;

	if ($frac == 0 || $maxfrac < $frac) {
		$lines = $l;
		$frac = $maxfrac;
	}
}

$step = round(floor($cap / $lines));

for ($i = $cap; $i > 0; $i -= $step > 0 ? $step : 1) {
	$y = $height - round($dim * $i * $factor) - $marginBottom - 1 + 3;

	drawHorizLine($y, $dark);
	drawText("" . round($i) . ' ' . $dimString . (round($i) > 1 ? "s" : ""), 
		$fontsize, $fontsize - 10, $y - 10, $dark);
}

imagerectangle($im, 0, 1, 1, $height - $marginBottom + 4, $dark);
imagerectangle($im, 0, $height - $marginBottom + 3, $width, $height - $marginBottom + 4, $dark);

for ($i = 0; $i < $slotCount; $i++) {
	$slot = $slots[$i];

	$ofs = $i > 0 ? ($i < $slotCount / 4 * 3 ? 1 : 0) : -1;

	$txData = round($slot['txBytes'] * $factor);

	if ($txData > 0) {
		$x1 = $colWidth * $i + $colStep * 3 + 1 + $ofs;
		$y1 = $height - $txData - $marginBottom;
		$x2 = $colWidth * ($i + 1) - $colStep + $ofs;
		$y2 = $height - $marginBottom + 2;
		imagefilledrectangle($im, $x1, $y1, $x2, $y2, $blue);
	}

	$rxData = round($slot['rxBytes'] * $factor);

	if ($rxData > 0) {
		$x1 = $colWidth * $i + $colStep + 1 + $ofs;
		$y1 = $height - $rxData - $marginBottom;
		$x2 = $colWidth * ($i + 1) - $colStep * 3 + $ofs;
		$y2 = $height - $marginBottom + 2;

		imagefilledrectangle($im, $x1, $y1, $x2, $y2, $green);
	} 
}

imagepng($im);
imagedestroy($im);

?>
