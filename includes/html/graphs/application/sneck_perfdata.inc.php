<?php

// Graphs a single perfdata item for a check, as saved via apps.sneck.perfdata_save.
// Requires the vars sneck_check and sneck_perf_label.

$name = 'sneck';
$colours = 'psychedelic';
$dostack = 0;
$printtotal = 0;
$addarea = 0;
$transparency = 15;

$check_name = (string) ($vars['sneck_check'] ?? '');
$perf_label = (string) ($vars['sneck_perf_label'] ?? '');

// use the unit of measure from the last poll if there is one
$unit_text = $app->data['checks'][$check_name]['perfdata'][$perf_label]['unit'] ?? '';
if ($unit_text == '') {
    $unit_text = 'Value';
}

$rrd_filename = Rrd::name($device['hostname'], ['app', $name, $app->app_id, 'perf', $check_name, $perf_label]);

$rrd_list = [];

if ($check_name != '' && $perf_label != '' && Rrd::checkRrdExists($rrd_filename)) {
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr' => $perf_label,
        'ds' => 'data',
    ];
}

require 'includes/html/graphs/generic_multi_line.inc.php';
