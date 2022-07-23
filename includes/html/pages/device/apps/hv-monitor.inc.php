<?php

$app_data = json_decode($app['data'], true);

sort($app_data['VMs']);

$link_array = [
    'page'   => 'device',
    'device' => $device['device_id'],
    'tab'    => 'apps',
    'app'    => 'hv-monitor',
];

print_optionbar_start();

if (!isset($vars['vm'])){
    echo generate_link('<span class="pagemenu-selected"><b>Totals</b></span>', $link_array);
}else{
    echo generate_link('<b>Totals</b>', $link_array);
}
echo '<b> | VMs: </b>';
$vm_int = 0;
while (isset($app_data['VMs'][$vm_int])) {
    $vm = $app_data['VMs'][$vm_int];
    $label = $vm;

    if ($vars['vm'] == $vm) {
        $label = '<span class="pagemenu-selected">' . $vm . '</span>';
    }

    $vm_int++;

    $append = '';
    if (isset($app_data['VMs'][$vm_int])) {
        $append = ', ';
    }

    echo generate_link($label, $link_array, ['vm'=>$vm]) . $append;
}

if (isset($vars['vm'])){
    echo "<hr><b>Interfaces:</b> ";
    $if_links=[];
    foreach ($app_data['VMifs'][$vars['vm']] as $vmif => $if_info) {
        $label = $vmif;

        if ($vars['vmif'] == $vmif) {
            $if_links[]='<span class="pagemenu-selected">' . $vmif . '</span>';
        } else {
            $if_links[]=generate_link($label, $link_array, ['vm' => $vars['vm'],'vmif'=>$vmif]);
       }
    }
    echo implode(', ', $if_links);

    echo "<br><b>Disks:</b> ";
    $disk_int = 0;
    while (isset($app_data['VMdisks'][$vars['vm']][$disk_int])) {
            $disk = $app_data['VMdisks'][$vars['vm']][$disk_int];
            $label = $disk;

            if ($vars['vmdisk'] == $disk) {
                $label = '<span class="pagemenu-selected">' . $disk . '</span>';
            }

            $disk_int++;

            $append = '';
            if (isset($app_data['VMdisks'][$vars['vm']][$vm_int])) {
                $append = ', ';
            }

            if ($vars['vmdisk'] == $disk) {
                echo $label . $append;
            } else {
                echo generate_link($label, $link_array, ['vm' => $vars['vm'],'vmdisk'=>$disk]) . $append;
            }
    }
}

if (isset($vars['vmif']) and isset($vars['vm'])) {
    echo '<br>' .
        '<b>MAC:</b> '.$app_data['VMifs'][$vars['vm']][$vars['vmif']]['mac'] .
        '<br><b>HV if:</b> '.$app_data['VMifs'][$vars['vm']][$vars['vmif']]['if'];
    if ($app_data['VMifs'][$vars['vm']][$vars['vmif']]['parent'] != '') {
        echo '<br><b>HV parrent if:</b> '.$app_data['VMifs'][$vars['vm']][$vars['vmif']]['parent'];
    }
}

print_optionbar_end();


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

if (!isset($vars['vmdisk']) and !isset($vars['vmif'])) {
    $graphs['hv-monitor_memory'] = 'VM Memmory Usage';
    $graphs['hv-monitor_time'] = 'VM CPU Time';
    $graphs['hv-monitor_pmem'] = 'Memory Percent';
    $graphs['hv-monitor_pcpu'] = 'CPU Percent';
    $graphs['hv-monitor_flt'] = 'Faults';
    $graphs['hv-monitor_cow'] = 'COWs';
    $graphs['hv-monitor_csw'] = 'Context Switches';
    $graphs['hv-monitor_etimes'] = 'Etimes';
    $graphs['hv-monitor_disk-rw-blocks'] = 'Disk RW, Blocks';
    $graphs['hv-monitor_disk-rw-bytes'] = 'Disk RW, Bytes';
    $graphs['hv-monitor_disk-rw-reqs'] = 'Disk RW, Requests';
    if($app_data['hv'] != 'CBSD'){
        $graphs['hv-monitor_disk-rw-time'] = 'Disk RW, Time';
    }
    if($app_data['hv'] == 'libvirt'){
        $graphs['hv-monitor_disk-ftime'] = 'Disk Flush, Time';
        $graphs['hv-monitor_disk-freq'] = 'Disk Flush, Requests';
    }
    $graphs['hv-monitor_snaps'] = 'Snapshots';
    $graphs['hv-monitor_snaps_size'] = 'Snapshots Size';
}


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

    if (isset($vars['vmdisk'])) {
        $graph_array['vmdisk'] = $vars['vmdisk'];
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
