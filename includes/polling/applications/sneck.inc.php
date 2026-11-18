<?php

use App\Facades\LibrenmsConfig;
use App\Models\Eventlog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
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

// used for only logging restarts once per sneck run and skipped restarts once per reason
$old_time = $app->data['time'] ?? null;
$old_restarts = $app->data['restarts'] ?? [];

if (LibrenmsConfig::has('apps.sneck.polling_time_diff')) {
    $compute_time_diff = LibrenmsConfig::get('apps.sneck.polling_time_diff');
} else {
    $compute_time_diff = false;
}

try {
    $json_return = json_app_get($device, $name, 1);
} catch (JsonAppException $e) {
    Log::info($name . ':' . $e->getCode() . ':' . $e->getMessage());
    // Set empty metrics and error message
    update_application($app, $e->getCode() . ':' . $e->getMessage(), []);

    return;
}

$app->data = $json_return['data'];

$new_checks = [];
if (isset($json_return['data']) and isset($json_return['data']['checks'])) {
    $new_checks = array_keys($json_return['data']['checks']);
}

$new_debugs = [];
if (isset($json_return['data']) and isset($json_return['data']['debugs'])) {
    $new_debugs = array_keys($json_return['data']['debugs']);
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

// restarted is only present in version 1.5.0 and up, so only save it if it is there
if (isset($json_return['data']['restarted']) && is_numeric($json_return['data']['restarted'])) {
    $rrd_def = RrdDefinition::make()
        ->addDataset('data', 'GAUGE', 0);

    $rrd_name = ['app', $name, $app->app_id, 'restarted'];

    $restarted_fields = [
        'data' => $json_return['data']['restarted'],
    ];
    $fields['restarted'] = $json_return['data']['restarted'];

    $tags = ['name' => $name, 'app_id' => $app->app_id, 'rrd_def' => $rrd_def, 'rrd_name' => $rrd_name];
    app('Datastore')->put($device, 'app', $tags, $restarted_fields);
}

// Save perfdata for checks whose name matches one of the regexps in apps.sneck.perfdata_save.
// Each check/label pair gets its own RRD, so labels coming and going never breaks one.
// Perfdata is present in version 1.8.0 and up. Debugs and compliance checks are not saved.
$perfdata_save_regexps = LibrenmsConfig::get('apps.sneck.perfdata_save', []);
if (is_array($perfdata_save_regexps) && count($perfdata_save_regexps) > 0) {
    foreach ($json_return['data']['checks'] ?? [] as $check_name => $check_info) {
        if (! isset($check_info['perfdata']) || ! is_array($check_info['perfdata'])) {
            continue;
        }

        $save_check_perfdata = false;
        foreach ($perfdata_save_regexps as $regexp) {
            $match_result = @preg_match($regexp, (string) $check_name);
            if ($match_result === false) {
                Log::warning($name . ': invalid regexp in apps.sneck.perfdata_save: ' . $regexp);
            } elseif ($match_result === 1) {
                $save_check_perfdata = true;
                break;
            }
        }
        if (! $save_check_perfdata) {
            continue;
        }

        foreach ($check_info['perfdata'] as $perf_label => $perf_item) {
            // c is the nagios plugin unit for a continuous counter
            if (($perf_item['unit'] ?? '') == 'c') {
                $rrd_def = RrdDefinition::make()->addDataset('data', 'COUNTER', 0);
            } else {
                $rrd_def = RrdDefinition::make()->addDataset('data', 'GAUGE');
            }

            $rrd_name = ['app', $name, $app->app_id, 'perf', $check_name, $perf_label];

            // a value of U in the perfdata comes through as null, which is stored as unknown
            $perf_value = $perf_item['value'] ?? null;
            if (! is_numeric($perf_value)) {
                $perf_value = null;
            }

            $tags = [
                'name' => $name,
                'app_id' => $app->app_id,
                'check' => (string) $check_name,
                'label' => (string) $perf_label,
                'rrd_def' => $rrd_def,
                'rrd_name' => $rrd_name,
            ];
            app('Datastore')->put($device, 'app', $tags, ['data' => $perf_value]);

            // update_application casts metrics to float, which would turn unknown into 0, so leave it out
            if ($perf_value !== null) {
                $fields['perf_' . $check_name . '_' . $perf_label] = $perf_value;
            }
        }
    }
}

// save the return status for each alerting possibilities
foreach ($json_return['data']['checks'] as $key => $value) {
    $fields['check_' . $key] = $value['exit'];
}

$fields['time_to_polling_abs'] = abs($time_to_polling);

if (abs($time_to_polling) > 540) {
    $json_return['data']['alertString'] .= "\nGreater than 540 seconds since the polled data was generated";
    $json_return['data']['alert'] = 1;
}

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

// Log restarts that ran or were triggered but skipped. Restarts are present in version 1.5.0 and up.
// Only done when time changed, so a stale cache from sneck no longer being ran does not log the same
// restart every poll. Skipped ones are only logged when the reason differs from the last poll, with the
// seconds left removed from cooldown reasons, so one sitting in cooldown is not logged every run.
if (isset($json_return['data']['restarts']) && is_array($json_return['data']['restarts']) && $json_return['data']['time'] != $old_time) {
    foreach ($json_return['data']['restarts'] as $restart_name => $restart) {
        $failed_checks = implode(',', $restart['failed_checks'] ?? []);
        $reason = (string) ($restart['reason'] ?? '');

        if (! empty($restart['ran'])) {
            $log_message = $restart_name . ' (' . $reason . '), failed checks: ' . $failed_checks;
            $restart_failed = ($restart['exit'] ?? 0) != 0 || isset($restart['error']);
            if ($restart_failed) {
                $log_message = 'Sneck Restart Failed: ' . $log_message . ', exit: ' . ($restart['exit'] ?? '') . ', error: ' . ($restart['error'] ?? '');
                Eventlog::log($log_message, $device['device_id'], 'application', Severity::Error);
            } else {
                Eventlog::log('Sneck Restart Ran: ' . $log_message, $device['device_id'], 'application', Severity::Warning);
            }
            continue;
        }

        if (empty($restart['triggered']) || $reason == 'restarts disabled') {
            continue;
        }

        $old_reason = '';
        if (isset($old_restarts[$restart_name]) && empty($old_restarts[$restart_name]['ran']) && ! empty($old_restarts[$restart_name]['triggered'])) {
            $old_reason = (string) ($old_restarts[$restart_name]['reason'] ?? '');
        }
        if (preg_replace('/^cooldown,.*$/', 'cooldown', $reason) != preg_replace('/^cooldown,.*$/', 'cooldown', $old_reason)) {
            $log_message = 'Sneck Restart Skipped: ' . $restart_name . ', ' . $reason . ', failed checks: ' . $failed_checks;
            Eventlog::log($log_message, $device['device_id'], 'application', Severity::Notice);
        }
    }
}

// update it here as we are done with this mostly
update_application($app, 'OK', $fields);
