<?php

$name = 'hv-monitor';
$app_id = $app['app_id'];
$unit_text = 'VM Statuses';
$colours = 'psychedelic';
$dostack = 0;
$printtotal = 0;
$addarea = 0;
$transparency = 15;

if (isset($vars['vm'])) {
    $rrd_filename = Rrd::name($device['hostname'], ['app', $name, $app['app_id'], 'vm', $vars['vm']]);
} else {
    $rrd_filename = Rrd::name($device['hostname'], ['app', $name, $app['app_id']]);
}

$rrd_list = [];
if (Rrd::checkRrdExists($rrd_filename)) {
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'On',
        'ds'       => 'on',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'Off',
        'ds'       => 'off',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'off_hard',
        'ds'       => 'off_hard',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'off_soft',
        'ds'       => 'off_soft',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'unknown',
        'ds'       => 'unknown',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'paused',
        'ds'       => 'paused',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'crashed',
        'ds'       => 'crashed',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'blocked',
        'ds'       => 'blocked',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'nostate',
        'ds'       => 'nostate',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'pmsuspended',
        'ds'       => 'pmsuspended',
    ];
} else {
    d_echo('RRD "' . $rrd_filename . '" not found');
}

require 'includes/html/graphs/generic_multi_line.inc.php';
