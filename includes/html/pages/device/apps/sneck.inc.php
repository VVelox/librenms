<?php

$link_array = [
    'page' => 'device',
    'device' => $device['device_id'],
    'tab' => 'apps',
    'app' => 'sneck',
];

// The types of checks sneck returns, keyed by the key in the returned data.
// var is the URL var used for selecting one and label is what is shown in the link row.
$check_types = [
    'checks' => ['var' => 'sneck_check', 'label' => 'Check Info'],
    'debugs' => ['var' => 'sneck_debug', 'label' => 'Debug Info'],
    'compliance' => ['var' => 'sneck_compliance', 'label' => 'Compliance Info'],
];

// one link row for each check type that has any checks
$link_rows = [];
foreach ($check_types as $type => $type_info) {
    $names = array_keys($app->data[$type] ?? []);
    if (count($names) == 0) {
        continue;
    }

    $links = [];
    foreach ($names as $check_name) {
        $link = generate_link(htmlspecialchars((string) $check_name), $link_array, [$type_info['var'] => $check_name]);
        if (($vars[$type_info['var']] ?? null) == $check_name) {
            $link = '<span class="pagemenu-selected">' . $link . '</span>';
        }
        $links[] = $link;
    }
    $link_rows[] = $type_info['label'] . ': ' . implode(",\n", $links) . "<br>\n";
}

if (count($link_rows) > 0) {
    print_optionbar_start();
    echo generate_link('Overview', $link_array) . "<br>\n";
    echo implode('', $link_rows);
    if (isset($app->data['run_time'])) {
        echo '<b>Runtime:</b> ' . htmlspecialchars((string) $app->data['run_time']) . " seconds<br>\n";
    }
    print_optionbar_end();
}

// find the selected check, if any
$selected_type = null;
$selected_name = null;
foreach ($check_types as $type => $type_info) {
    if (isset($vars[$type_info['var']]) && isset($app->data[$type][$vars[$type_info['var']]])) {
        $selected_type = $type;
        $selected_name = $vars[$type_info['var']];
        break;
    }
}

if ($selected_type !== null) {
    $check_info = $app->data[$selected_type][$selected_name];

    print_optionbar_start();
    // field in the check info => label shown for it
    $info_fields = [
        'check' => 'Check',
        'ran' => 'Ran',
        'run_time' => 'Runtime',
        'exit' => 'Exit',
        'error' => 'Error',
        'perfdata_error' => 'Perfdata Error',
    ];
    foreach ($info_fields as $field => $field_label) {
        if (isset($check_info[$field])) {
            $suffix = $field == 'run_time' ? ' seconds' : '';
            echo '<b>' . $field_label . ':</b> ' . htmlspecialchars((string) $check_info[$field]) . $suffix . "<br>\n";
        }
    }
    if (isset($check_info['output'])) {
        echo "<b>Output...</b><br>\n<pre>";
        echo htmlspecialchars((string) $check_info['output']) . "\n";
        echo "</pre><br>\n";
    }

    if (isset($check_info['perfdata']) && is_array($check_info['perfdata']) && count($check_info['perfdata']) > 0) {
        echo "<b>Perfdata:</b><br>\n";
        echo '<table class="table table-condensed table-hover table-striped">' . "\n";
        echo '<thead><tr><th>Label</th><th>Value</th><th>Unit</th><th>Warning</th><th>Critical</th><th>Min</th><th>Max</th></tr></thead>' . "\n";
        echo "<tbody>\n";
        foreach ($check_info['perfdata'] as $perf_label => $perf_item) {
            echo '<tr><td>' . htmlspecialchars((string) $perf_label) . '</td>';
            foreach (['value', 'unit', 'warning', 'critical', 'min', 'max'] as $perf_field) {
                echo '<td>' . htmlspecialchars((string) ($perf_item[$perf_field] ?? '')) . '</td>';
            }
            echo "</tr>\n";
        }
        echo "</tbody>\n</table>\n";
    }

    echo "<b>Raw JSON:</b><br>\n";
    echo "<pre>\n" . htmlspecialchars(json_encode($check_info, JSON_PRETTY_PRINT)) . "</pre>\n";
    print_optionbar_end();

    // perfdata is only saved for checks, and only ones matching apps.sneck.perfdata_save
    if ($selected_type == 'checks') {
        foreach (array_keys($check_info['perfdata'] ?? []) as $perf_label) {
            $rrd_filename = Rrd::name($device['hostname'], ['app', 'sneck', $app->app_id, 'perf', $selected_name, $perf_label]);
            if (! Rrd::checkRrdExists($rrd_filename)) {
                continue;
            }

            $graph_array = [
                'height' => '100',
                'width' => '215',
                'to' => \App\Facades\LibrenmsConfig::get('time.now'),
                'id' => $app->app_id,
                'type' => 'application_sneck_perfdata',
                'sneck_check' => $selected_name,
                'sneck_perf_label' => $perf_label,
            ];

            echo '<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">Perfdata: ' . htmlspecialchars((string) $perf_label) . '</h3>
    </div>
    <div class="panel-body">
    <div class="row">';
            include 'includes/html/print-graphrow.inc.php';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
    }
} else {
    $graphs = [
        'sneck_results' => 'Results',
        'sneck_time' => 'Time Difference',
    ];

    if (isset($app->data['run_time'])) {
        $graphs['sneck_runtime'] = 'Runtime';
    }

    // only present in sneck 1.5.0 and up
    if (isset($app->data['restarted'])) {
        $graphs['sneck_restarted'] = 'Restarts';
    }

    foreach ($graphs as $key => $text) {
        $graph_type = $key;
        $graph_array['height'] = '100';
        $graph_array['width'] = '215';
        $graph_array['to'] = \App\Facades\LibrenmsConfig::get('time.now');
        $graph_array['id'] = $app->app_id;
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

    // print returned all info from sneck with alert info broken out
    if (isset($app->data)) {
        print_optionbar_start();
        echo 'Last Return...<br>';
        echo "<b>Alert(s):</b><br>\n";
        echo str_replace("\n", "<br>\n", htmlspecialchars((string) ($app->data['alertString'] ?? ''))) . "<br><br>\n";
        echo "<b>Raw JSON:</b><br>\n";
        echo "<pre>\n" . htmlspecialchars(json_encode($app->data, JSON_PRETTY_PRINT)) . "</pre>\n";
        print_optionbar_end();
    }
}
