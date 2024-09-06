<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once('./config.php');
include_once('common.php');

$obj = null;

function do_json() {
    global $mdu_secret;
    global $obj;

    $data = file_get_contents('php://input');
    $obj = json_decode($data, true, 512, JSON_BIGINT_AS_STRING);

    if (@$obj['secret'] != $mdu_secret) {
        die('Wrong secret');
    }
}

if (@$_GET['update'] != null) {
    do_json();
    include('update.php');
} else if (@$_GET['sync'] != null) {
    do_json();
    include('sync.php');
} else if (@$_GET['list'] != null) {
    do_json();
    include('list.php');
} else
    include('login.php');

mysqli_close($connect);
?>
