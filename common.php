<?php

$sim_table_name = 'sims';
$slot_table_name = 'slots';
$history_table_name = 'history';

date_default_timezone_set($timezone);

function error($text, $conn) {
    echo $text;

    if ($conn)
        echo ": " . mysqli_error($conn);
    exit;
}

$connect = @mysqli_connect($db_host, $db_user, $db_pass);

if (!$connect) {
    echo 'Unable to connect to mysql';
    exit;
}

if (!@mysqli_select_db($connect, $db_name))
    error('Unable to select database', $connect);


$time = time();

$intsel = @$_POST['interval'];

$yearsel = @$_POST['year'];
$monthsel = @$_POST['month'];
$daysel = @$_POST['day'];

$year = date('Y', $time);
$month = date('n', $time);
$day = date('j', $time);

$months = array(
    1 => 'January',
    2 => 'February',
    3 => 'March',
    4 => 'April',
    5 => 'May',
    6 => 'June',
    7 => 'July',
    8 => 'August',
    9 => 'September',
    10 => 'October',
    11 => 'November',
    12 => 'December'
);

$intervals = array(
    1 => "the day",
    2 => "the week",
    3 => "the month",
    4 => "the year"
);

$intsel = $intsel == '' ? '1' : $intsel;
$yearsel = $yearsel == '' ? $year : $yearsel;
$monthsel = $monthsel == '' ? $month : $monthsel;
$daysel = $daysel == '' ? $day : $daysel;

function dateref($interval, $year, $month, $day) {
    return '<a href="' . "?page=graph&amp;interval=$interval&amp;year=$year&amp;month=$month&amp;day=$day" . '">';
}

function timeref($interval, $time) {
    $year = date('Y', $time);
    $month = date('n', $time);
    $day = date('j', $time);

    return dateref($interval, $year, $month, $day);
}

$starttime = mktime(0, 0, 0, $monthsel, $daysel, $yearsel);
$endtime = $starttime + 86400;
$cal_caption = date('l, F j Y', $starttime);

if ($intsel == 2) {
    $starttime = strtotime('this week', $starttime);
    $endtime = $starttime + 86400 * 7;
    $cal_caption = 'Week of ' . date('F j, Y', $starttime);
} else if ($intsel == 3) {
    $starttime = strtotime('first day of this month', $starttime);
    $endtime = strtotime('first day of next month', $starttime);
    $cal_caption = date('F Y', $starttime);
} else if ($intsel == 4) {
    $starttime = strtotime('first day of january', $starttime);
    $endtime = strtotime('last day of december', $starttime);
    $cal_caption = date('Y', $starttime);
}
?>
