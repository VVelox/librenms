<?php

$name = 'sagan';
$unit_text = 'Threshold';
$colours = 'psychedelic';
$dostack = 0;
$printtotal = 0;
$addarea = 0;
$transparency = 15;

if (isset($vars['sinstance'])) {
    $rrd_filename = Rrd::name($device['hostname'], ['app', $name, $app->app_id, $vars['sinstance']]);
} else {
    $rrd_filename = Rrd::name($device['hostname'], ['app', $name, $app->app_id]);
}

$rrd_list = [
    [
        'filename' => $rrd_filename,
        'descr' => 'Threshold',
        'ds' => 'threshold',
    ],
];

require 'includes/html/graphs/generic_multi_line.inc.php';
