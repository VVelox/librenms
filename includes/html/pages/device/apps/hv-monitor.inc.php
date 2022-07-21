<?php

$app_data = Application::find($app['app_id'])->data;

$link_array = [
    'page'   => 'device',
    'device' => $device['device_id'],
    'tab'    => 'apps',
    'app'    => 'hv-monitor',
];

$graphs = [];
if (!isset($vars['vm'])) {
    $graphs['hv-monitor_status'] = 'VM Statuses Count';
}

$graphs['hv-monitor_memory'] = 'VM Memmory Usage';
$graphs['hv-monitor_time'] = 'VM CPU Time';
$graphs['hv-monitor_pmem'] = 'Memory Percent';
$graphs['hv-monitor_pcpu'] = 'CPU Percent';
$graphs['hv-monitor_flt'] = 'Faults';
$graphs['hv-monitor_cow'] = 'COWs';
$graphs['hv-monitor_csw'] = 'Context Switches';
$graphs['hv-monitor_etimes.inc'] = 'Etimes';
$graphs['hv-monitor_disk-rw-blocks'] = 'Disk RW, Blocks';
$graphs['hv-monitor_disk-rw-bytes'] = 'Disk RW, Bytes';
$graphs['hv-monitor_disk-rw-reqs'] = 'Disk RW, Requests';
$graphs['hv-monitor_disk-rw-time'] = 'Disk RW, Time';
$graphs['hv-monitor_snaps'] = 'Snapshots';
$graphs['hv-monitor_snaps_size'] = 'Snapshots Size';


foreach ($graphs as $key => $text) {
    $graph_type = $key;
    $graph_array['height'] = '100';
    $graph_array['width'] = '215';
    $graph_array['to'] = \LibreNMS\Config::get('time.now');
    $graph_array['id'] = $app['app_id'];
    $graph_array['type'] = 'application_' . $key;

    if (isset($vars['vm'])) {
        $graph_array['vm'] = $vars['vm'];
    }

    echo '<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">' . $text . '</h3>
    </div>
    <div class="panel-body">
    <div class="row">';
    include 'includes/html/print-graphrow.inc.php';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}
