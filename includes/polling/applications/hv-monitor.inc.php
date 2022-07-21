<?php

use LibreNMS\Exceptions\JsonAppException;
use LibreNMS\RRD\RrdDefinition;

$name = 'hv-monitor';
$app_id = $app['app_id'];

try {
    $return_data = json_app_get($device, 'hv-monitor')['data'];
} catch (JsonAppException $e) {
    echo PHP_EOL . $name . ':' . $e->getCode() . ':' . $e->getMessage() . PHP_EOL;
    update_application($app, $e->getCode() . ':' . $e->getMessage(), []); // Set empty metrics and error message

    return;
}

//
// totals graph stuff
//
$rrd_def = RrdDefinition::make()
    ->addDataset('usertime', 'DDERIVE', 0)
    ->addDataset('pmem', 'GAUGE', 0)
    ->addDataset('oublk', 'DERIVE', 0)
    ->addDataset('minflt', 'DERIVE', 0)
    ->addDataset('pcpu', 'GAUGE', 0)
    ->addDataset('mem_alloc', 'GAUGE', 0)
    ->addDataset('nvcsw', 'DERIVE', 0)
    ->addDataset('snaps', 'GAUGE', 0)
    ->addDataset('rss', 'GAUGE', 0)
    ->addDataset('snaps_size', 'GAUGE', 0)
    ->addDataset('cpus', 'GAUGE', 0)
    ->addDataset('cow', 'DERIVE', 0)
    ->addDataset('nivcsw', 'DERIVE', 0)
    ->addDataset('systime', 'DDERIVE', 0)
    ->addDataset('vsz', 'GAUGE', 0)
    ->addDataset('etimes', 'DERIVE', 0)
    ->addDataset('majflt', 'GAUGE', 0)
    ->addDataset('inblk', 'DERIVE', 0)
    ->addDataset('nswap', 'GAUGE', 0)
    ->addDataset('on', 'GAUGE', 0)
    ->addDataset('off', 'GAUGE', 0)
    ->addDataset('off_hard', 'GAUGE', 0)
    ->addDataset('off_soft', 'GAUGE', 0)
    ->addDataset('unknown', 'GAUGE', 0)
    ->addDataset('paused', 'GAUGE', 0)
    ->addDataset('crashed', 'GAUGE', 0)
    ->addDataset('blocked', 'GAUGE', 0)
    ->addDataset('nostate', 'GAUGE', 0)
    ->addDataset('pmsuspended', 'GAUGE', 0);

$totals_fields = [
    'usertime' => $return_data['totals']['usertime'],
    'pmem' => $return_data['totals']['pmem'],
    'oublk' => $return_data['totals']['oublk'],
    'minflt' => $return_data['totals']['minflt'],
    'pcpu' => $return_data['totals']['pcpu'],
    'mem_alloc' => $return_data['totals']['mem_alloc'],
    'nvcsw' => $return_data['totals']['nvcsw'],
    'snaps' => $return_data['totals']['snaps'],
    'rss' => $return_data['totals']['rss'],
    'snaps_size' => $return_data['totals']['snaps_size'],
    'cpus' => $return_data['totals']['cpus'],
    'cow' => $return_data['totals']['cow'],
    'nivcsw' => $return_data['totals']['nivcsw'],
    'systime' => $return_data['totals']['systime'],
    'vsz' => $return_data['totals']['vsz'],
    'etimes' => $return_data['totals']['etimes'],
    'majflt' => $return_data['totals']['majflt'],
    'inblk' => $return_data['totals']['inblk'],
    'nswap' => $return_data['totals']['nswap'],
    'on' => $return_data['totals']['on'],
    'off' => $return_data['totals']['off'],
    'off_hard' => $return_data['totals']['off_hard'],
    'off_soft' => $return_data['totals']['off_soft'],
    'unknown' => $return_data['totals']['unknown'],
    'paused' => $return_data['totals']['paused'],
    'crashed' => $return_data['totals']['crashed'],
    'blocked' => $return_data['totals']['blocked'],
    'nostate' => $return_data['totals']['nostate'],
    'pmsuspended' => $return_data['totals']['pmsuspended'],
];

$rrd_name = ['app', $name, $app_id];
$tags = ['name' => $name, 'app_id' => $app_id, 'rrd_def' => $rrd_def, 'rrd_name' => $rrd_name];
data_update($device, 'app', $tags, $totals_fields);

//
// handle each VM
//


//
// all done so update the app metrics
//
delete($return_data['hv']);
update_application($app, 'OK', $return_data);
