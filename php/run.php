<?php

require "vendor/autoload.php";
use PHPHtmlParser\Dom;

require __DIR__ . "/powercalc.php";
require __DIR__ . "/../data.php";

function resolve() {
    global $bike_data;

    // Number of fields
    $num = 10;

    $source = '';
    $lastdistance = 0;
    $lastramp = 0;
    $lasthigheralt = 0;
    $lastloweralt = 0;
    $lasttime = 0;
    $lastvel = 0;
    $lastpower = 0;
    $lastkcal = 0;
    $startdiff = false;
    // =====
    $totaldist = 0;
    $totaltime = 0;
    $totalpwr = 0;
    $totalkcal = 0;
    // =====
    $colsize = 3;
    for ($i=0; $i < count($bike_data); $i++) {
        $data = $bike_data[$i];
        $pdata = calc($data);
        $t = $data["time"];
        // =====
        $totaldist += $data["distance"];
        $totaltime += $data["time"];
        $totalpwr += $pdata["power"];
        $totalkcal += $pdata["kcal"];
        // ======
        $source .= '<div class="col-md-'.$colsize.' item">';
        $placeholder = array(
            "DISTANCE" => $data["distance"],
            "RETURNABLE" => $data["returnable"] ? "Sí" : "No",
            "RAMP" => $data["ramp"],
            "HIGHER_ALT" => $data["higher_alt"],
            "LOWER_ALT" => $data["lower_alt"],
            "DIFFICULTY" => $data["difficulty"],
            "POINTS" => base64_decode(file_get_contents(__DIR__ . "/polys/poly".$i.".txt")),
            "LEN1" => round($data["distance"] * (1 / 4), 2),
            "LEN2" => round($data["distance"] * (2 / 4), 2),
            "LEN3" => round($data["distance"] * (3 / 4), 2),
            "TIME" => toTime($t),
            "VELOCITY" => $pdata["velocity"],
            "POWER" => $pdata["power"],
            "KCALS" => $pdata["kcal"],
            "JOULES" => $pdata["joules"],
            "WATTPERKG" => $pdata["wperkg"],
        );

        $source .= "<h3>Día ".($i + 1)."</h3>";
        $source .= file_get_contents(__DIR__ . "/templates/template.txt");
        foreach ($placeholder as $key => $item) {
            $source = str_replace("[".$key."]", $item, $source);
        }
        $source .= '</div>';
        if($i == 1) {
            $source .= '<div class="col-md-3" style="height: 408px;"></div>';
            $source .= '<div class="col-md-3" style="height: 408px;"></div>';
        }

        if($startdiff === true) {
            $dom = new Dom;
            $dom->load($source);

            // DISTANCE
            $dom = modifySpan($dom, $i * $num + 0, $data["distance"] - $lastdistance);

            // RAMP
            $dom = modifySpan($dom, $i * $num + 2, $data["ramp"] - $lastramp);
            $dom = modifySpan($dom, $i * $num + 4, $data["ramp"] - $lastramp);

            // HIGHER_ALT
            $dom = modifySpan($dom, $i * $num + 3, $data["higher_alt"] - $lasthigheralt);

            // LOWER_ALT
            $dom = modifySpan($dom, $i * $num + 5, $lastloweralt - $data["lower_alt"]);

            // TIME
            $dom = modifySpan($dom, $i * $num + 7, $data["time"] - $lasttime, 'time');

            // POWER
            $dom = modifySpan($dom, $i * $num + 8, array($pdata["power"] - $lastpower, $pdata["kcal"] - $lastkcal), 'power');

            // VELOCITY
            $dom = modifySpan($dom, $i * $num + 9, $pdata["velocity"] - $lastvel);

            $source = $dom->outerHtml;
        }
        // echo "curlower: ".$data["lower_alt"]."; lastlower: ".$lastloweralt;

        $lastdistance = $data["distance"];
        $lastramp = $data["ramp"];
        $lasthigheralt = $data["higher_alt"];
        $lastloweralt = $data["lower_alt"];
        $lasttime = $data["time"];
        $lastvel = $pdata["velocity"];
        $lastpower = $pdata["power"];
        $lastkcal = $pdata["kcal"];
        if($startdiff === false)
            $startdiff = true;
    }
    // ==========
    // ADD TOTALS
    // ==========
    $source .= file_get_contents(__DIR__ . "/templates/total-template.txt");
    $totalholders = array(
        "TOTAL_DIST" => $totaldist,
        "TOTAL_TIME" => toTime($totaltime),
        "TOTAL_PWR" => $totalpwr,
        "TOTAL_KCALS" => $totalkcal,
        "COLSIZE" => $colsize
    );
    foreach ($totalholders as $key => $item) {
        $source = str_replace("[".$key."]", $item, $source);
    }
    $source .= '<div class="col-md-3" style="height: 408px;"></div>';
    $source .= '<div class="col-md-3" style="height: 408px;"></div>';

    // =======
    $source = '<div class="container-fluid days"><div class="row">'.$source.'</div></div>';
    return $source;
}

function getSpan($dom, $i) {
    $j = 1;
    $ditem = $dom->find('.d-item')[$i];
    if(strpos($ditem->getAttribute('class'), 'special') !== false)
        $j = 0;
    return $ditem->find('span')[$j];
}

function modifySpan($dom, $i, $d, $type = '') {
    $diff = is_array($d) ? null : $d;
    $span = getSpan($dom, $i);
    $oldtext = $span->firstChild()->text;
    $fdiff = null;
    if($type == '') {
        $fdiff = findDiff($diff);
    } else if($type == 'time') {
        $fdiff = findDiffFormatted($diff, ($diff < 0 ? "-" : "").str_replace("-", "", toTime($diff)), true);
    } else if($type == 'power') {
        $fdiff = findDiffFormatted($d[0], $d[0].'/').findDiff($d[1]);
        $oldtext = "<span>".$oldtext."</span>";
    } 
    $span->firstChild()->setText($oldtext.$fdiff);
    return $dom;
}

function findDiff($diff) {
    if($diff > 0) {
        return "<span class='up'>+ ".$diff."</span>";
    } else if($diff < 0) {
        return "<span class='down'>".$diff."</span>";
    } else {
        return "<span>0.00</span>";
    }
}

function findDiffFormatted($diff, $text, $inverse = false) {
    if($diff > 0) {
        return "<span class='".($inverse ? "down" : "up")."'>+ ".$text."</span>";
    } else if($diff < 0) {
        return "<span class='".($inverse ? "up" : "down")."'>".$text."</span>";
    } else {
        return "<span>0.00</span>";
    }
}

function toTime($t) {
    return sprintf('%02d:%02d:%02d', ($t/3600),($t/60%60), $t%60);
}