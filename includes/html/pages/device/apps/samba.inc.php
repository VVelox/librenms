<?php

use App\Models\Device;
use App\Models\Ipv4Address;
use App\Models\Ipv6Address;
use App\Models\Port;

$link_array = [
    'page' => 'device',
    'device' => $device['device_id'],
    'tab' => 'apps',
    'app' => 'samba',
];

$app_data = $app->data;

print_optionbar_start();

if (! isset($vars['app_page'])) {
    echo generate_link('<span class="pagemenu-selected"><b>General</b></span>', $link_array);
} else {
    echo generate_link('<b>General</b>', $link_array);
}

echo ', ';

if (isset($vars['app_page']) && $vars['app_page'] == 'details') {
    echo generate_link('<span class="pagemenu-selected"><b>Details</b></span>', $link_array, ['app_page'=> 'details']);
} else {
    echo generate_link('<b>Details</b>', $link_array, ['app_page'=> 'details']);
}

echo ', ';

if (isset($vars['app_page']) && $vars['app_page'] == 'syscall') {
    echo generate_link('<span class="pagemenu-selected"><b>Syscall</b></span>', $link_array, ['app_page'=> 'syscall']);
} else {
    echo generate_link('<b>Syscall</b>', $link_array, ['app_page'=> 'syscall']);
}

echo ', ';

if (isset($vars['app_page']) && $vars['app_page'] == 'smb') {
    echo generate_link('<span class="pagemenu-selected"><b>SMB</b></span>', $link_array, ['app_page'=> 'smb']);
} else {
    echo generate_link('<b>SMB</b>', $link_array, ['app_page'=> 'smb']);
}

echo ', ';

if (isset($vars['app_page']) && $vars['app_page'] == 'smb2') {
    echo generate_link('<span class="pagemenu-selected"><b>SMB2</b></span>', $link_array, ['app_page'=> 'smb2']);
} else {
    echo generate_link('<b>SMB2</b>', $link_array, ['app_page'=> 'smb2']);
}

echo ', ';

if (isset($vars['app_page']) && $vars['app_page'] == 'misc') {
    echo generate_link('<span class="pagemenu-selected"><b>Misc</b></span>', $link_array, ['app_page'=> 'misc']);
} else {
    echo generate_link('<b>Misc</b>', $link_array, ['app_page'=> 'misc']);
}

print_optionbar_end();

$graphs = [];

if (isset($vars['app_page']) and $vars['app_page'] == 'details') {
    if (isset($app_data['procs'])) {
        $table_info = [
            'headers' => [
                'PID',
                'User',
                'Group',
                'Machine',
                'IP',
                'Port',
                'Version',
                'Encryption',
                'Signing',
            ],
            'rows' => [],
        ];
        foreach ($app_data['procs'] as $key => $samba_proc) {
            $ip_found = false;
            $samba_client_ip_raw = false;
            $samba_client_ip = $samba_proc['ip'];
            if (preg_match('/^[\:A-Fa-f0-9]+$/', $samba_client_ip)) {
                $ip_info = Ipv6Address::firstWhere(['ipv6_address' => $samba_client_ip]);
                if (isset($ip_info)) {
                    $ip_found = true;
                }
            } elseif (preg_match('/^[\.0-9]+$/', $samba_client_ip)) {
                $ip_info = Ipv4Address::firstWhere(['ipv4_address' => $samba_client_ip]);
                if (isset($ip_info)) {
                    $ip_found = true;
                }
            }
            if ($ip_found) {
                $port = Port::with('device')->firstWhere(['port_id' => $ip_info->port_id]);
                $samba_client_ip = $samba_proc['ip'].' ('.generate_device_link(['device_id' => $port->device_id], $allowed_ip) . ', ' .
                    generate_port_link([
                        'label' => $port->label,
                        'port_id' => $port->port_id,
                        'ifName' => $port->ifName,
                        'device_id' => $port->device_id,
                    ]) . ')';
                $samba_client_ip_raw = true;
            }

            $row = [
                ['data' => $samba_proc['pid']],
                ['data' => $samba_proc['user']],
                ['data' => $samba_proc['group']],
                ['data' => $samba_proc['machine']],
                ['data' => $samba_client_ip, 'raw' => $samba_client_ip_raw],
                ['data' => $samba_proc['port']],
                ['data' => $samba_proc['version']],
                ['data' => $samba_proc['encryption']],
                ['data' => $samba_proc['signing']],
            ];
            $table_info['rows'][] = $row;
        }
        print_optionbar_start();
        echo "<center><b>Samba Procs</b></center><br>\n" . view('widgets/sortable_table', $table_info);
        print_optionbar_end();
    }

    if (isset($app_data['shares'])) {
        $table_info = [
            'headers' => [
                'PID',
                'Service',
                'Machine',
                'Connected At',
                'Encryption',
                'Signing',
            ],
            'rows' => [],
        ];
        foreach ($app_data['shares'] as $key => $share) {
            $row = [
                ['data' => $share['pid']],
                ['data' => $share['service']],
                ['data' => $share['machine']],
                ['data' => $share['connected_at']],
                ['data' => $share['encryption']],
                ['data' => $share['signing']],
            ];
            $table_info['rows'][] = $row;
        }
        print_optionbar_start();
        echo "<center><b>Shares</b></center><br>\n" . view('widgets/sortable_table', $table_info);
        print_optionbar_end();
    }
}


foreach ($graphs as $key => $text) {
    $graph_type = $key;
    $graph_array['height'] = '100';
    $graph_array['width'] = '215';
    $graph_array['to'] = \LibreNMS\Config::get('time.now');
    $graph_array['id'] = $app['app_id'];
    $graph_array['type'] = 'application_' . $key;

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
