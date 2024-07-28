<?php

use LibreNMS\Exceptions\JsonAppException;
use LibreNMS\RRD\RrdDefinition;

$name = 'samba';

try {
    $returned = json_app_get($device, $name, 1);
} catch (JsonAppException $e) {
    echo PHP_EOL . $name . ':' . $e->getCode() . ':' . $e->getMessage() . PHP_EOL;
    update_application($app, $e->getCode() . ':' . $e->getMessage(), []); // Set empty metrics and error message

    return;
}

$stat_vars = [
    'acl_count',
    'acl_get_count',
    'acl_get_time',
    'acl_set_count',
    'acl_set_time',
    'acl_time',
    'connect_count',
    'cpu_system_time',
    'cpu_user_time',
    'disconnect_count',
    'idle_count',
    'idle_time',
    'lock_count',
    'nt_transact_count',
    'nt_transact_time',
    'pop_sec_ctx_count',
    'pop_sec_ctx_time',
    'push_sec_ctx_count',
    'push_sec_ctx_time',
    'request_count',
    'set_root_sec_ctx_count',
    'set_root_sec_ctx_time',
    'set_sec_ctx_count',
    'set_sec_ctx_time',
    'smb2_bytes',
    'smb2_count',
    'smb2_idle',
    'smb2_other_count',
    'smb2_other_time',
    'smb2_read_bytes',
    'smb2_read_count',
    'smb2_read_idle',
    'smb2_read_time',
    'smb2_time',
    'smb2_write_bytes',
    'smb2_write_count',
    'smb2_write_idle',
    'smb2_write_time',
    'smb_count',
    'smb_other_count',
    'smb_other_time',
    'smb_read_count',
    'smb_read_time',
    'smb_time',
    'smb_write_count',
    'smb_write_time',
    'statcache_hits_count',
    'statcache_lookups_count',
    'statcache_misses_count',
    'syscall_bytes',
    'syscall_count',
    'syscall_idle',
    'syscall_other_count',
    'syscall_other_time',
    'syscall_read_bytes',
    'syscall_read_count',
    'syscall_read_idle',
    'syscall_read_time',
    'syscall_time',
    'syscall_write_bytes',
    'syscall_write_count',
    'syscall_write_idle',
    'syscall_write_time',
    'trans2_count',
    'trans2_time',
];

$gauges=[
    'lock_count'=>1,
];

$metrics = [];
$new_data = [
    'shares' => $returned['data']['shares'],
    'procs' => $returned['data']['procs'],
];

$data = $returned['data'];

$gauge_rrd_def = RrdDefinition::make()
    ->addDataset('data', 'GAUGE', 0);

$counter_rrd_def = RrdDefinition::make()
    ->addDataset('data', 'COUNTER', 0);

// process the stats under .data.general
foreach ($stat_vars as $key => $stat) {
    $value = $data['general'][$stat];
    $rrd_name = ['app', $name, $app->app_id, $stat];
    $fields = ['data' => $value];
    $metrics[$stat] = $value;
    if (isset($gauges[$stat])) {
        $rrd_def= $gauge_rrd_def;
    } else {
        $rrd_def = $counter_rrd_def;
    }
    $tags = ['name' => $name, 'app_id' => $app->app_id, 'rrd_def' => $rrd_def, 'rrd_name' => $rrd_name];
    data_update($device, 'app', $tags, $fields);
}

$app->data = $new_data;

// all done so update the app metrics
update_application($app, 'OK', $metrics);
