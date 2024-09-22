<?php

function push($push) {
    global $connect;
    global $sim_table_name;
    global $slot_table_name;
    global $history_table_name;

    $phoneid = -1;
    
    if (@$_GET['phone'] != null) {
        $phoneserial = mysqli_real_escape_string($connect, @$_GET['phone']);

        $query = 'select SimId from ' . $sim_table_name
            . ' where SimSerial=\'' . $phoneserial . '\'';

        if (!(@$resid = mysqli_query($connect, $query)))
            error('select', $connect);

        $resarr = mysqli_fetch_assoc($resid);

        if ($resarr != null)
            $phoneid = $resarr['SimId'];

        mysqli_free_result($resid);
    }

    $serial = mysqli_real_escape_string($connect, $push['serial']);

    $query = 'select SimId from ' . $sim_table_name
        . ' where SimSerial=\'' . $serial . '\'';

    if (!(@$resid = mysqli_query($connect, $query)))
        error('select', $connect);

    $resarr = mysqli_fetch_assoc($resid);

    if ($resarr == null) {
        $query = 'insert into ' . $sim_table_name
            . ' (SimSerial) values (\'' . $serial . '\')';

        if (!@mysqli_query($connect, $query))
            error('insert', $connect);

        $simid = mysqli_insert_id($connect);
    } else {
        $simid = $resarr['SimId'];
    }

    mysqli_free_result($resid);

    $caption = mysqli_real_escape_string($connect, $push['caption']);

    list($lastchange, $lastupdate, $current, $floor,
        $haslimit, $limit, $hasusedwarning, $usedwarning, $usedlastseen,
        $hasremainwarning, $remainwarning, $remainlastseen) = explode(":", $push['data']);

    $query = 'update ' . $sim_table_name . ' set '
        . '`SimCaption` = \'' . $caption . '\', '
        . '`LastChange` = ' . $lastchange . ', '
        . '`LastUpdate` = ' . $lastupdate . ', '
        . '`Current` = ' . $current . ', '
        . '`Floor` = ' . $floor . ', '
        . '`HasLimit` = ' . $haslimit . ', '
        . '`Limit` = ' . $limit . ', '
        . '`HasUsedWarning` = ' . $hasusedwarning . ', '
        . '`UsedWarning`= ' . $usedwarning . ', '
        . '`UsedLastSeen`= ' . $usedlastseen . ', '
        . '`HasRemainWarning` = ' . $hasremainwarning . ', '
        . '`RemainWarning` = ' . $remainwarning . ', '
        . '`RemainLastSeen` = ' . $remainlastseen . ' where SimId = ' . $simid;

    if (!@mysqli_query($connect, $query))
        error('update', $connect);

    $slots = $push['slots'];

    foreach ($slots as $key => $value) {
        list($listid, $slot) = explode(":", $key);
        list($starttime, $rxbytes, $txbytes) = explode(":", $value);

        $query = 'insert ignore into ' . $slot_table_name . ' values ('
            . $simid . ', '
            . $listid . ', '
            . $slot . ', '
            . $starttime . ', '
            . $rxbytes . ', '
            . $txbytes . ')';

        if (!@mysqli_query($connect, $query))
            error('insert', $connect);

        $query = 'update ' . $slot_table_name . ' set '
            . 'StartTime = ' . $starttime
            . ', RxBytes = ' . $rxbytes
            . ', TxBytes = ' . $txbytes
            . ' where SimId = ' . $simid . ' and ListId = ' . $listid
            . ' and Slot = ' . $slot;

        if (!@mysqli_query($connect, $query))
            error('insert', $connect);
    }

    return array(
        'simserial' => $serial
    );
}

function get_slots($simid) {
    global $connect;
    global $slot_table_name;

    $query = 'select ListId, Slot, StartTime, RxBytes, TxBytes from '
        . $slot_table_name . ' where SimId = ' . $simid;

    if (!(@$resid = mysqli_query($connect, $query)))
        error('select', $connect);

    $resarr = mysqli_fetch_assoc($resid);

    while ($resarr != null) {
        $slots[$resarr['ListId']][$resarr['Slot']] = $resarr['StartTime'] . ':'
            . $resarr['RxBytes'] . ':' . $resarr['TxBytes'];

        $resarr = mysqli_fetch_assoc($resid);
    }

    mysqli_free_result($resid);

    return $slots;
}

function pull($pull) {
    global $connect;
    global $sim_table_name;
    global $serial;

    $serial = mysqli_real_escape_string($connect, $pull['serial']);
    $lastupdate = $pull['lastupd'];
    $lastchange = $pull['lastchg'];

    $arr = array(
        'simserial' => $serial
    );

    $query = 'select * from ' . $sim_table_name
        . ' where SimSerial=\'' . $serial . '\'';

    if (!(@$resid = mysqli_query($connect, $query)))
        error('select', $connect);

    $resarr = mysqli_fetch_assoc($resid);

    if ($resarr != null) {
        if ($lastupdate < 0 + $resarr['LastUpdate'] || $lastchange < 0 + $resarr['LastChange']) {
            $arr = $arr + array(
                'simcaption' => $resarr['SimCaption'],
                'lastupdate' => $resarr['LastUpdate'],
                'lastchange' => $resarr['LastChange'],
                'current' => $resarr['Current'],
                'floor' => $resarr['Floor'],
                'haslimit' => $resarr['HasLimit'],
                'limit' => $resarr['Limit'],
                'hasusedwarning' => $resarr['HasUsedWarning'],
                'usedwarning' => $resarr['UsedWarning'],
                'usedlastseen' => $resarr['UsedLastSeen'],
                'hasremainwarning' => $resarr['HasRemainWarning'],
                'remainwarning' => $resarr['RemainWarning'],
                'remainlastseen' => $resarr['RemainLastSeen']
            );

            $arr['slots'] = get_slots($resarr['SimId']);
        }
    }

    mysqli_free_result($resid);

    return $arr;
}

$i = 0;
$req = @$obj[$i++];

$res_arr = array();

while ($req != null) {
    if ($req['type'] == 'push') {
        $res = push($req);
    } else if ($req['type'] == 'pull') {
        $res = pull($req);
    } else
        $res = null;

    if ($res != null) {
        $simid = -1;

        $serial = mysqli_real_escape_string($connect, $req['serial']);

        $query = 'select SimId from ' . $sim_table_name
            . ' where SimSerial=\'' . $serial . '\'';

        if (!(@$resid = mysqli_query($connect, $query)))
            error('select', $connect);

        $resarr = mysqli_fetch_assoc($resid);

        $sync = -1;

        if ($resarr != null) { // should always be true!
            $simid = $resarr['SimId'];
            $sync = 0;

            mysqli_free_result($resid);

            $query = 'select max(SyncId) as Max from ' . $history_table_name
                . ' where SimId = ' . $simid;

            if (!(@$resid = mysqli_query($connect, $query))) {
                error('select', $connect);
            } else {
                $resarr = mysqli_fetch_assoc($resid);

                $max = $resarr['Max'];

                if ($max != null)
                    $sync = $resarr['Max'];
            }
        }

        mysqli_free_result($resid);

        $res['sync'] = $sync;

        $res_arr[] = $res;
    }

    $req = @$obj[$i++];
}

echo json_encode($res_arr);
?>
