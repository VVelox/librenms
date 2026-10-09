<?php

$link_array = [
    'page' => 'device',
    'device' => $device['device_id'],
    'tab' => 'apps',
    'app' => 'sneck',
];

if (isset($app->data['checks'])) {
    $checks = $app->data['checks'] ?? [];
    $checks_list = array_keys($checks);
    $debugs = $app->data['debugs'] ?? [];
    $debugs_list = array_keys($debugs);
    $restarts = $app->data['restarts'] ?? [];
    $restarts_list = array_keys($restarts);
    if (isset($checks_list[0]) || isset($debugs_list[0]) || isset($restarts_list[0])) {
        print_optionbar_start();
        echo generate_link('Overview', $link_array) . "<br>\n";
        if (isset($checks_list[0])) {
            echo 'Check Info: ';
            foreach ($checks_list as $index => $check) {
                $label = htmlspecialchars((string) $check);
                $link = generate_link($label, $link_array, ['sneck_check' => $check]);

                $link = $vars['sneck_check'] == $check
                    ? '<span class="pagemenu-selected">' . $link . '</span>'
                    : $link;

                echo $link . "\n";

                if ($index < (count($checks_list) - 1)) {
                    echo ', ';
                } else {
                    echo "<br>\n";
                }
            }
        }
        if (isset($debugs_list[0])) {
            echo 'Debug Info: ';
            foreach ($debugs_list as $index => $debug) {
                $label = htmlspecialchars((string) $debug);
                $link = generate_link($label, $link_array, ['sneck_debug' => $debug]);

                $link = $vars['sneck_debug'] == $debug
                    ? '<span class="pagemenu-selected">' . $link . '</span>'
                    : $link;

                echo $link . "\n";

                if ($index < (count($debugs_list) - 1)) {
                    echo ', ';
                } else {
                    echo "<br>\n";
                }
            }
        }
        if (isset($restarts_list[0])) {
            echo 'Restart Info: ';
            foreach ($restarts_list as $index => $restart) {
                $label = htmlspecialchars((string) $restart);
                $link = generate_link($label, $link_array, ['sneck_restart' => $restart]);

                $link = ($vars['sneck_restart'] ?? null) == $restart
                    ? '<span class="pagemenu-selected">' . $link . '</span>'
                    : $link;

                echo $link . "\n";

                if ($index < (count($restarts_list) - 1)) {
                    echo ', ';
                } else {
                    echo "<br>\n";
                }
            }
        }
        // run time
        if (isset($app->data['run_time'])) {
            echo '<b>Runtime:</b> ' . htmlspecialchars($app->data['run_time']) . " seconds<br>\n";
        }
        // number of restarts ran, 1.5.0 and up
        if (isset($app->data['restarted'])) {
            echo '<b>Restarted:</b> ' . htmlspecialchars((string) $app->data['restarted']) . "<br>\n";
        }
        // restart state file could not be read or written
        if (isset($app->data['restart_state_error'])) {
            echo '<b>Restart State Error:</b> ' . htmlspecialchars((string) $app->data['restart_state_error']) . "<br>\n";
        }
        print_optionbar_end();
    }
}

if (isset($vars['sneck_restart']) && isset($app->data['restarts'][$vars['sneck_restart']])) {
    $restart_data = $app->data['restarts'][$vars['sneck_restart']];
    print_optionbar_start();
    // simple values, in display order
    $restart_values = [
        'triggered' => 'Triggered',
        'ran' => 'Ran',
        'reason' => 'Reason',
        'threshold' => 'Threshold',
        'attempts' => 'Attempts',
        'command' => 'Command',
        'ran_command' => 'Ran Command',
        'exit' => 'Exit',
        'error' => 'Error',
    ];
    foreach ($restart_values as $restart_key => $restart_label) {
        if (isset($restart_data[$restart_key])) {
            echo '<b>' . $restart_label . ':</b> ' . htmlspecialchars((string) $restart_data[$restart_key]) . "<br>\n";
        }
    }
    if (isset($restart_data['run_time'])) {
        echo '<b>Runtime:</b> ' . htmlspecialchars((string) $restart_data['run_time']) . " seconds<br>\n";
    }
    // arrays of check names
    if (isset($restart_data['failed_checks']) && is_array($restart_data['failed_checks'])) {
        echo '<b>Failed Checks:</b> ' . htmlspecialchars(implode(', ', $restart_data['failed_checks'])) . "<br>\n";
    }
    if (isset($restart_data['recheck_failed_checks']) && is_array($restart_data['recheck_failed_checks'])) {
        echo '<b>Recheck Failed Checks:</b> ' . htmlspecialchars(implode(', ', $restart_data['recheck_failed_checks'])) . "<br>\n";
    }
    if (isset($restart_data['output'])) {
        echo "<b>Output...</b><br>\n<pre>";
        echo htmlspecialchars((string) $restart_data['output']) . "\n";
        echo "</pre><br>\n";
    }
    echo "<b>Raw JSON:</b><br>\n";
    echo "<pre>\n" . htmlspecialchars(json_encode($restart_data, JSON_PRETTY_PRINT)) . "</pre>\n";
    print_optionbar_end();
} elseif ((isset($vars['sneck_check']) && isset($app->data['checks'][$vars['sneck_check']])) || (isset($vars['sneck_debug']) && isset($app->data['debugs'][$vars['sneck_debug']]))) {
    $type = 'checks';
    $type_name = '';
    if (isset($vars['sneck_debug'])) {
        $type = 'debugs';
        $type_name = $vars['sneck_debug'];
    } else {
        $type_name = $vars['sneck_check'];
    }
    print_optionbar_start();
    // is the template used
    if (isset($app->data[$type][$type_name]['check'])) {
        echo '<b>Check:</b> ' . htmlspecialchars($app->data[$type][$type_name]['check']) . "<br>\n";
    }
    // what was ran post templating
    if (isset($app->data[$type][$type_name]['ran'])) {
        echo '<b>Ran:</b> ' . htmlspecialchars($app->data[$type][$type_name]['ran']) . "<br>\n";
    }
    // run time
    if (isset($app->data[$type][$type_name]['run_time'])) {
        echo '<b>Runtime:</b> ' . htmlspecialchars($app->data[$type][$type_name]['run_time']) . " seconds<br>\n";
    }
    // exit code
    if (isset($app->data[$type][$type_name]['exit'])) {
        echo '<b>Exit:</b> ' . htmlspecialchars($app->data[$type][$type_name]['exit']) . "<br>\n";
    }
    // error non-standard exit info
    if (isset($app->data[$type][$type_name]['error'])) {
        echo '<b>Error:</b> ' . htmlspecialchars($app->data[$type][$type_name]['error']) . "<br>\n";
    }
    // rerun after a restart via check_restart
    if (isset($app->data[$type][$type_name]['rechecked_by'])) {
        echo '<b>Rechecked By Restart:</b> ' . htmlspecialchars((string) $app->data[$type][$type_name]['rechecked_by']) . "<br>\n";
    }
    // output
    if (isset($app->data[$type][$type_name]['output'])) {
        echo "<b>Output...</b><br>\n<pre>";
        echo htmlspecialchars($app->data[$type][$type_name]['output']) . "\n";
        echo "</pre><br>\n";
    }
    echo "<b>Raw JSON:</b><br>\n";
    echo "<pre>\n" . htmlspecialchars(json_encode($app->data[$type][$type_name], JSON_PRETTY_PRINT)) . "</pre>\n";
    print_optionbar_end();
} else {
    $graphs = [
        'sneck_results' => 'Results',
        'sneck_time' => 'Time Difference',
    ];

    if (isset($app->data['run_time'])) {
        $graphs['sneck_runtime'] = 'Runtime';
    }

    if (isset($app->data['restarted'])) {
        $graphs['sneck_restarts'] = 'Restarts';
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

    // overview of all restarts and what they did on the last run
    if (! empty($app->data['restarts']) && is_array($app->data['restarts'])) {
        echo '<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">Restarts</h3>
    </div>
    <div class="panel-body">
    <table class="table table-condensed table-hover">
        <thead><tr><th>Restart</th><th>Triggered</th><th>Ran</th><th>Reason</th><th>Exit</th><th>Error</th></tr></thead>
        <tbody>' . "\n";
        foreach ($app->data['restarts'] as $restart => $restart_data) {
            $label = htmlspecialchars((string) $restart);
            echo '<tr><td>' . generate_link($label, $link_array, ['sneck_restart' => $restart]) . '</td>'
                . '<td>' . (! empty($restart_data['triggered']) ? 'yes' : 'no') . '</td>'
                . '<td>' . (! empty($restart_data['ran']) ? 'yes' : 'no') . '</td>'
                . '<td>' . htmlspecialchars((string) ($restart_data['reason'] ?? '')) . '</td>'
                . '<td>' . htmlspecialchars((string) ($restart_data['exit'] ?? '')) . '</td>'
                . '<td>' . htmlspecialchars((string) ($restart_data['error'] ?? '')) . "</td></tr>\n";
        }
        echo "</tbody>\n</table>\n</div>\n</div>\n";
    }

    // print returned all info from sneck with alert info broken out
    if (isset($app->data)) {
        print_optionbar_start();
        echo 'Last Return...<br>';
        echo "<b>Alert(s):</b><br>\n";
        echo str_replace("\n", "<br>\n", htmlspecialchars((string) $app->data['alertString'])) . "<br><br>\n";
        echo "<b>Raw JSON:</b><br>\n";
        echo "<pre>\n" . htmlspecialchars(json_encode($app->data, JSON_PRETTY_PRINT)) . "</pre>\n";
        print_optionbar_end();
    }
}
