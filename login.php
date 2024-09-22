<?php

function isMobile() {
    return preg_match('/\b(?:a(?:ndroid|vantgo)|b(?:lackberry|olt|o?ost)'
        . '|cricket|docomo|hiptop|i(?:emobile|p[ao]d)|kitkat|m(?:ini|obi)'
        . '|palm|(?:i|smart|windows )phone|symbian|up\.(?:browser|link)|tablet'
        . '(?: browser| pc)|(?:hp-|rim |sony )tablet|w(?:ebos|indows ce|os))/i',
        $_SERVER["HTTP_USER_AGENT"]);
}

function fmtime($timestamp) {
    if ($timestamp == 0)
        return 'never';

    $dateTime = date('Y-m-d H:i:s', floor($timestamp / 1000));
    return$dateTime;
}

$cancel = "";

if (@($_SERVER['PHP_AUTH_PW'] != $mdu_secret)) {
    header('WWW-Authenticate: Basic realm="Mobile Data Usage"');
    header('HTTP/1.0 401 Unauthorized');
    $cancel = "Authentication cancelled. Reload this page to try again.";
}

$serial = @$_GET['serial'];

if (!$serial) {
    $serial = @$_COOKIE['serial'];
}

if ($serial) {
    $sql = "select SimId, SimCaption, LastUpdate from `$sim_table_name` "
        . "where SimSerial='$serial'";

    if (!(@$resid = mysqli_query($connect, $sql)))
        error('select', $connect);

    if (@$resarr = mysqli_fetch_assoc($resid)) {
        $simid = $resarr['SimId'];
        $simcaption = $resarr['SimCaption'];
        $lastupdate = $resarr['LastUpdate'];

        setcookie("serial", $serial);
    }

    mysqli_free_result($resid);
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <link rel="stylesheet" href="login.css" type="text/css" />
        <link rel="icon" href="images/favicon.png" />
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Tangerine">
<?php
$title = "";

if (@$simcaption)
    $title = "$simcaption - ";

$title = $title . 'Mobile Data Usage';

echo "	<title>$title</title>";

if (isMobile()) {
    echo '	<style type="text/css">body { font-size: 250%; }</style>' . "\n";
} else {
    echo '	<style type="text/css">body { font-size: 150%; }</style>' . "\n";
}
?>	
    </head>
    <body>
        <div class="topheader">
            <h1>
                <img src="images/mdu.png" alt="Logo" class="logo" />
                Mobile Data Usage
            </h1>
        </div>
        <div id="leftcol">
<?php
if ($cancel != "") {
    echo "	<h2>$cancel</h2>\n	</div>\n</body>\n</html>\n";
    exit;
}

$sql = "select count(*) as Count from `$sim_table_name`;";

if (!(@$resid = mysqli_query($connect, $sql)))
    error('select', $connect);

$resarr = mysqli_fetch_assoc($resid);
mysqli_free_result($resid);

if ($resarr['Count'] == 0) {
    echo "		<h2>There is nothing in the database. Sync something!</h2>\n";
    echo "	</div>\n</body>\n</html>\n";
    exit;
}
?>
            <form method="get" action="" id="card">
                <input name="action" value="show" type="submit" />
                <select name="serial" onchange="this.form.submit();">
            <?php
            $sql = "select SimCaption, SimSerial from `$sim_table_name`;";

            if (!(@$resid = mysqli_query($connect, $sql)))
                error('select', $connect);

            while ($resarr = mysqli_fetch_assoc($resid)) {
                echo '				<option value="' . $resarr['SimSerial'] . '"';

                if ($serial == $resarr['SimSerial']) {
                    echo ' selected="selected"';
                    $caption = $resarr['SimCaption'];
                }

                echo '>' . $resarr['SimCaption'] . "</option>\n";
            }

            mysqli_free_result($resid);
            ?>
                </select>
            </form>
            <div id="summary">
                    <?php
                    if (!@$simid) {
                        echo "		<h2>Please choose a card or phone.</h2>\n";
                        echo "	</div>\n</body>\n</html>\n";
                        exit;
                    }
                    
                    $lup = fmtime($lastupdate);
                   // echo "			<h2>$simcaption</h2>\n";
                    echo "          <div id=\"lastupdate\">" .
                        "<div id=\"simid\">" .
                        (strlen($serial) == 19 || strlen($serial) == 16 ? 
                        'SIM ' . (strlen($serial) == 19 ? "serial" : "ID") : 'Phone ID') 
                        . ": $serial</div><br>Last update: $lup</div>\n";
                    /* echo "				<table>\n";
                    echo '					<tr><td class="cell_odd">'
                    . (
                    . '</td><td class="cell_odd">' . "$serial</td></tr>\n";
                    echo '					<tr><td class="cell">'
                    . 'Last update</td><td class="cell">'
                    . fmtime($lastupdate) . "</td></tr>\n";

                    $sql = "select count(*) as count from `$history_table_name` "
                        . "where SimId = $simid";

                    if (!(@$resid = mysqli_query($connect, $sql)))
                        error('select', $connect);

                    $resarr_h = mysqli_fetch_assoc($resid);

                    mysqli_free_result($resid);

                    $count = $resarr_h['count'];

                    echo '					<tr><td class="cell_odd">'
                    . '#Records</td>'
                    . "<td class=\"cell_odd\">$count</td></tr>\n";
                    echo "				</table>\n";
                     */
                    ?> 
                
                <p>
                    <img src="donut.php?serial=<?php
                echo "$serial&amp;secret=$mdu_secret";
                ?>" id="donut" alt="" /> 
                </p>

            </div>
        </div>

        <div id="graph">
            <form method="post" action="">
            <p>
                <input name="page" value="show" type="submit" class="form" id="submit" />
            </p>

            <p>
                <select name="interval" class="form" id="interval">
        <?php
        foreach ($intervals as $key => $value)
            if ($key == $intsel)
                echo '<option value="' . $key . '" selected="selected">'
                . $value . '</option>';
            else
                echo '<option value="' . $key . '">'
                . $value . '</option>';
        ?>
                </select>
            </p>

            <div id="of">of</div>

            <p>
                <select name="year" class="form">
                    <?php
                    for ($i = $year; $i > $year - 10; $i--)
                        if ($i == $yearsel)
                            echo '<option selected="selected">' . $i . '</option>';
                        else
                            echo "<option>$i</option>";
                    ?>
                </select>
            </p>

            <p>
                <select name="month" class="form">
                    <?php
                    foreach ($months as $key => $value)
                        if ($key == $monthsel)
                            echo '<option value="' . $key . '" selected="selected">' . $value . '</option>';
                        else
                            echo '<option value="' . $key . '">' . $value . '</option>';
                    ?>
                </select>
            </p>

            <p>
                <select name="day" class="form">
                    <?php
                    for ($i = 1; $i <= 31; $i++)
                        if ($i == $daysel)
                            echo '<option selected="selected">' . $i . '</option>';
                        else
                            echo "<option>$i</option>";
                    ?>
                </select>
            </p>
        </form>

            
<?php
//echo "		<h2>$cal_caption</h2>\n";
echo "		<img src=\"bargraph.php?start=$starttime&amp;"
 . "end=$endtime&amp;view=$intsel&amp;simid=$simid&amp;"
 . "secret=$mdu_secret\" id=\"bargraph\" alt=\"\" />";
?>
        </div>
        <div style="clear: both;"></div>	
                    <?php
                    echo "	</div>\n";

                    echo '	<p class="copyright">' . "\n";
                    echo '		Copyright &copy; 2024 Daniel Schlachta' . "\n";
                    echo '	</p>' . "\n";
                    ?>
    </body>
</html>
