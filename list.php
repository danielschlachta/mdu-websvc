<?php
	$query = 'select SimSerial, SimCaption, LastUpdate from ' . $sim_table_name;

	$res_arr = array();
    
    if (!(@$resid = mysqli_query($connect, $query)))
        error('select', $connect);
    
    while (@($resarr = mysqli_fetch_assoc($resid)) != null) {
		$res_arr[] = array(
			'serial' => $resarr['SimSerial'],
			'caption' => $resarr['SimCaption'],
			'lastupdate' => $resarr['LastUpdate'],
		);
	}

	mysqli_free_result($resid);
	
	echo json_encode($res_arr);
?>
