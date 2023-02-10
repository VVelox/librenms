<?php

$name = 'lilith';

$filename = Rrd::name($device['hostname'], ['app', $name, $app['app_id'], 'totals-group1']);

$descr = 'Total';
$ds = 'total';

if (! Rrd::checkRrdExists($filename)) {
    d_echo('RRD "' . $filename . '" not found');
}

require 'includes/html/graphs/generic_stats.inc.php';
