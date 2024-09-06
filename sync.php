<?php

$serial = mysqli_real_escape_string($connect, $_GET['sync']);

$query = 'select SimId from ' . $sim_table_name
    . ' where SimSerial=\'' . $serial . '\'';

if (!(@$resid = mysqli_query($connect, $query)))
    error('select', $connect);

$resarr = mysqli_fetch_assoc($resid);
$simid = $resarr['SimId'];
mysqli_free_result($resid);

$res_arr = array();

if (array_key_exists('new', $obj)) {
    $arr = $obj['new'];

    $i = 0;
    $req = @$arr[$i++];

    $id_arr = array();

    while ($req != null) {
        list($entryid, $starttime, $rxbytes, $txbytes) = explode(":", $req);

        $id_arr[$entryid] = -1; // must return something, else endless loop!

        $query = 'insert into ' . $history_table_name
            . ' (`SimId`, `StartTime`, `RxBytes`, `TxBytes`) values ('
            . $simid . ',' . $starttime . ',' . $rxbytes . ',' . $txbytes . ')';

        if (@mysqli_query($connect, $query)) {
            $id_arr[$entryid] = mysqli_insert_id($connect);
        } else {
            $query = 'select SyncId from ' . $history_table_name
                . ' where SimId = ' . $simid . ' and StartTime = ' . $starttime;

            if ($resid = @mysqli_query($connect, $query)) {
                $resarr = mysqli_fetch_assoc($resid);

                if ($resarr != null)
                    $id_arr[$entryid] = $resarr['SyncId'];

                mysqli_free_result($resid);
            }
        }

        $req = @$arr[$i++];
    }

    $res_arr[] = array('sync' => $id_arr);
}

if (array_key_exists('req', $obj)) {
    $req = $obj['req'];

    $query = 'select SyncId, StartTime, RxBytes, TxBytes from '
        . $history_table_name . ' where SimId = ' . $simid
        . ' and SyncId >= ' . $req . ' order by SyncId limit 20';

    if (!(@$resid = mysqli_query($connect, $query)))
        error('select', $connect);

    $resarr = mysqli_fetch_assoc($resid);

    $req_arr = array();

    while ($resarr != null) {
        $req_arr[$resarr['SyncId']] = $resarr['StartTime'] . ':'
            . $resarr['RxBytes'] . ':' . $resarr['TxBytes'];

        $resarr = mysqli_fetch_assoc($resid);
    }

    mysqli_free_result($resid);

    $res_arr[] = array('req' => $req_arr);
}

echo json_encode($res_arr);
?>
