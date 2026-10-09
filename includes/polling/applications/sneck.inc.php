<?php

use App\Facades\LibrenmsConfig;
use App\Models\Eventlog;
use Carbon\Carbon;
use LibreNMS\Enum\Severity;
use LibreNMS\Exceptions\JsonAppException;
use LibreNMS\RRD\RrdDefinition;

$name = 'sneck';

$old_checks = [];
$old_checks_data = [];
if (isset($app->data['checks'])) {
    $old_checks = array_keys($app->data['checks']);
    $old_checks_data = $app->data['checks'];
}

$old_debugs = [];
if (isset($app->data['debugs'])) {
    $old_debugs = array_keys($app->data['debugs']);
}

$old_restarts = [];
if (isset($app->data['restarts'])) {
    $old_restarts = array_keys($app->data['restarts']);
}
$old_restart_state_error = $app->data['restart_state_error'] ?? null;

// used to avoid logging the same restarts twice if the cache was not updated between polls
$old_time = $app->data['time'] ?? null;

if (LibrenmsConfig::has('apps.sneck.polling_time_diff')) {
    $compute_time_diff = LibrenmsConfig::get('apps.sneck.polling_time_diff');
} else {
    $compute_time_diff = false;
}

try {
    $json_return = json_app_get($device, $name, 1);
} catch (JsonAppException $e) {
    echo PHP_EOL . $name . ':' . $e->getCode() . ':' . $e->getMessage() . PHP_EOL;
    // Set empty metrics and error message
    update_application($app, $e->getCode() . ':' . $e->getMessage(), []);

    return;
}

$new_checks = [];
if (isset($json_return['data']) and isset($json_return['data']['checks'])) {
    $new_checks = array_keys($json_return['data']['checks']);
}

$new_debugs = [];
if (isset($json_return['data']) and isset($json_return['data']['debugs'])) {
    $new_debugs = array_keys($json_return['data']['debugs']);
}

// restarts are only present in version 1.5.0 and up
$new_restarts = [];
if (isset($json_return['data']['restarts']) && is_array($json_return['data']['restarts'])) {
    $new_restarts = array_keys($json_return['data']['restarts']);
}

$rrd_name = ['app', $name, $app->app_id];
$rrd_def = RrdDefinition::make()
    ->addDataset('time', 'DERIVE', 0)
    ->addDataset('time_to_polling', 'GAUGE', 0)
    ->addDataset('ok', 'GAUGE', 0)
    ->addDataset('warning', 'GAUGE', 0)
    ->addDataset('critical', 'GAUGE', 0)
    ->addDataset('unknown', 'GAUGE', 0)
    ->addDataset('errored', 'GAUGE', 0);

// epoch off set between poller and when the when the JSON was generated
// only compueted if
if ($compute_time_diff) {
    $time_to_polling = Carbon::now()->timestamp - $json_return['data']['time'];
} else {
    $time_to_polling = 0;
}

$fields = [
    'time' => $json_return['data']['time'],
    'time_to_polling' => $time_to_polling,
    'ok' => $json_return['data']['ok'],
    'warning' => $json_return['data']['warning'],
    'critical' => $json_return['data']['critical'],
    'unknown' => $json_return['data']['unknown'],
    'errored' => $json_return['data']['errored'],
];

$tags = ['name' => $name, 'app_id' => $app->app_id, 'rrd_def' => $rrd_def, 'rrd_name' => $rrd_name];
app('Datastore')->put($device, 'app', $tags, $fields);

// run_time is only present in version 1.1.0 and up
if (isset($json_return['data']) and isset($json_return['data']['run_time']) and is_numeric($json_return['data']['run_time'])) {
    $rrd_def = RrdDefinition::make()
        ->addDataset('data', 'GAUGE', 0);

    $rrd_name = ['app', $name, $app->app_id, 'run_time'];

    // $fields is also used for storing metrics, so don't stomp it
    $run_time_fields = [
        'data' => $json_return['data']['run_time'],
    ];
    $fields['run_time'] = $json_return['data']['run_time'];

    $tags = ['name' => $name, 'app_id' => $app->app_id, 'rrd_def' => $rrd_def, 'rrd_name' => $rrd_name];
    app('Datastore')->put($device, 'app', $tags, $run_time_fields);

    // if this is over 300, it took 300+ seconds to run, meaning we are currently ingesting data for a previous time slot
    if ($json_return['data']['run_time'] >= 300) {
        $log_message = 'Sneck took ' . $json_return['data']['run_time'] . ' seconds to run';
        Eventlog::log($log_message, $device['device_id'], 'application', Severity::Error);
    }
}

// save the return status for each alerting possibilities
foreach ($json_return['data']['checks'] as $key => $value) {
    $fields['check_' . $key] = $value['exit'];
}

// restarts are only present in version 1.5.0 and up
$restarts_ran = [];
$restarts_failed = [];
if (isset($json_return['data']['restarted'])) {
    $restarts_triggered_count = 0;
    foreach ($new_restarts as $restart) {
        $restart_data = $json_return['data']['restarts'][$restart];
        $restart_ran = ! empty($restart_data['ran']) ? 1 : 0;
        $restart_triggered = ! empty($restart_data['triggered']) ? 1 : 0;
        // ran and either did not exit 0 or had a error, such as check_restart finding its checks still failing
        $restart_failed = $restart_ran && ((isset($restart_data['exit']) && $restart_data['exit'] != 0) || isset($restart_data['error'])) ? 1 : 0;

        $fields['restart_ran_' . $restart] = $restart_ran;
        $fields['restart_triggered_' . $restart] = $restart_triggered;
        $fields['restart_failed_' . $restart] = $restart_failed;

        $restarts_triggered_count += $restart_triggered;
        if ($restart_ran) {
            $restarts_ran[$restart] = [
                'reason' => $restart_data['reason'] ?? null,
                'exit' => $restart_data['exit'] ?? null,
            ];
        }
        if ($restart_failed) {
            $restarts_failed[$restart] = [
                'exit' => $restart_data['exit'] ?? null,
                'error' => $restart_data['error'] ?? null,
                'output' => $restart_data['output'] ?? null,
            ];
        }
    }

    $fields['restarted'] = $json_return['data']['restarted'];
    $fields['restarts_failed'] = count($restarts_failed);
    $fields['restart_state_error'] = isset($json_return['data']['restart_state_error']) ? 1 : 0;

    $rrd_def = RrdDefinition::make()
        ->addDataset('restarted', 'GAUGE', 0)
        ->addDataset('triggered', 'GAUGE', 0)
        ->addDataset('failed', 'GAUGE', 0);

    $rrd_name = ['app', $name, $app->app_id, 'restarts'];

    // $fields is also used for storing metrics, so don't stomp it
    $restarts_fields = [
        'restarted' => $json_return['data']['restarted'],
        'triggered' => $restarts_triggered_count,
        'failed' => count($restarts_failed),
    ];

    $tags = ['name' => $name, 'app_id' => $app->app_id, 'rrd_def' => $rrd_def, 'rrd_name' => $rrd_name];
    app('Datastore')->put($device, 'app', $tags, $restarts_fields);
}

$fields['time_to_polling_abs'] = abs($time_to_polling);

if (abs($time_to_polling) > 540) {
    $json_return['data']['alertString'] .= "\nGreater than 540 seconds since the polled data was generated";
    $json_return['data']['alert'] = 1;
}

// set after the alert check above so it includes any time_to_polling alert
$app->data = $json_return['data'];

//check for added checks/debugs
$added_checks = array_values(array_diff($new_checks, $old_checks));
$added_debugs = array_values(array_diff($new_debugs, $old_debugs));

//check for removed checks/debugs
$removed_checks = array_values(array_diff($old_checks, $new_checks));
$removed_debugs = array_values(array_diff($old_debugs, $new_debugs));

// if we have any check changes, log it
if (count($added_checks) > 0 || count($removed_checks) > 0) {
    $log_message = 'Sneck Check Change:';
    $log_message .= count($added_checks) > 0 ? ' Added ' . json_encode($added_checks) : '';
    $log_message .= count($removed_checks) > 0 ? ' Removed ' . json_encode($removed_checks) : '';
    Eventlog::log($log_message, $device['device_id'], 'application');
}

// if we have any debug changes, log it
if (count($added_debugs) > 0 || count($removed_debugs) > 0) {
    $log_message = 'Sneck Debugs Change:';
    $log_message .= count($added_debugs) > 0 ? ' Added ' . json_encode($added_debugs) : '';
    $log_message .= count($removed_debugs) > 0 ? ' Removed ' . json_encode($removed_debugs) : '';
    Eventlog::log($log_message, $device['device_id'], 'application');
}

//check for added/removed restarts
$added_restarts = array_values(array_diff($new_restarts, $old_restarts));
$removed_restarts = array_values(array_diff($old_restarts, $new_restarts));

// if we have any restart changes, log it
if (count($added_restarts) > 0 || count($removed_restarts) > 0) {
    $log_message = 'Sneck Restarts Change:';
    $log_message .= count($added_restarts) > 0 ? ' Added ' . json_encode($added_restarts) : '';
    $log_message .= count($removed_restarts) > 0 ? ' Removed ' . json_encode($removed_restarts) : '';
    Eventlog::log($log_message, $device['device_id'], 'application');
}

// only log restarts that ran if this is a new sneck run, not the same cache polled again
if ($old_time != $json_return['data']['time']) {
    // failed ones are logged separately below
    $restarts_ran_okay = array_diff_key($restarts_ran, $restarts_failed);
    if (count($restarts_ran_okay) > 0) {
        $log_message = 'Sneck Restarts Ran: ' . json_encode($restarts_ran_okay);
        Eventlog::log($log_message, $device['device_id'], 'application', Severity::Warning);
    }

    if (count($restarts_failed) > 0) {
        $log_message = 'Sneck Restarts Failed: ' . json_encode($restarts_failed);
        Eventlog::log($log_message, $device['device_id'], 'application', Severity::Error);
    }
}

// log restart state file errors when they appear or change
if (isset($json_return['data']['restart_state_error']) && $json_return['data']['restart_state_error'] != $old_restart_state_error) {
    $log_message = 'Sneck Restart State Error: ' . $json_return['data']['restart_state_error'];
    Eventlog::log($log_message, $device['device_id'], 'application', Severity::Error);
}

// go through and looking for status changes
$cleared = [];
$warned = [];
$alerted = [];
$unknowned = [];
foreach ($new_checks as $check) {
    if (isset($old_checks_data[$check]) && isset($old_checks_data[$check]['exit']) && isset($old_checks_data[$check]['output'])) {
        if ($json_return['data']['checks'][$check]['exit'] != $old_checks_data[$check]['exit']) {
            $check_output = $json_return['data']['checks'][$check]['output'];
            $exit_code = $json_return['data']['checks'][$check]['exit'];

            if ($exit_code == 1) {
                $warned[$check] = $check_output;
            } elseif ($exit_code == 2) {
                $alerted[$check] = $check_output;
            } elseif ($exit_code >= 3) {
                $unknowned[$check] = $check_output;
            } elseif ($exit_code == 0) {
                $cleared[$check] = $check_output;
            }
        }
    } else {
        if (isset($json_return['data']['checks'][$check]['exit']) && isset($json_return['data']['checks'][$check]['output'])) {
            $check_output = $json_return['data']['checks'][$check]['output'];
            $exit_code = $json_return['data']['checks'][$check]['exit'];

            if ($exit_code == 1) {
                $warned[$check] = $check_output;
            } elseif ($exit_code == 2) {
                $alerted[$check] = $check_output;
            } elseif ($exit_code >= 3) {
                $unknowned[$check] = $check_output;
            }
        }
    }
}

// log any clears
if (count($cleared) > 0) {
    $log_message = 'Sneck Check Clears: ' . json_encode($cleared);
    Eventlog::log($log_message, $device['device_id'], 'application', Severity::Ok);
}

// log any warnings
if (count($warned) > 0) {
    $log_message = 'Sneck Check Warns: ' . json_encode($warned);
    Eventlog::log($log_message, $device['device_id'], 'application', Severity::Warning);
}

// log any alerts
if (count($alerted) > 0) {
    $log_message = 'Sneck Check Alerts: ' . json_encode($alerted);
    Eventlog::log($log_message, $device['device_id'], 'application', Severity::Error);
}

// log any unknowns
if (count($unknowned) > 0) {
    $log_message = 'Sneck Check Unknowns: ' . json_encode($unknowned);
    Eventlog::log($log_message, $device['device_id'], 'application', Severity::Unknown);
}

// update it here as we are done with this mostly
update_application($app, 'OK', $fields);
