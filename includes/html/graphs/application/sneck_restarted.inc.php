<?php

$name = 'sneck';
$unit_text = 'Restarts';
$colours = 'psychedelic';
$dostack = 0;
$printtotal = 0;
$addarea = 0;
$transparency = 15;
$float_precision = 0;

$rrd_filename = Rrd::name($device['hostname'], ['app', $name, $app->app_id, 'restarted']);

$rrd_list = [];

if (Rrd::checkRrdExists($rrd_filename)) {
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr' => 'Restarted',
        'ds' => 'data',
    ];
}

require 'includes/html/graphs/generic_multi_line.inc.php';
