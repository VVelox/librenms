<?php

use LibreNMS\Exceptions\JsonAppException;
use LibreNMS\RRD\RrdDefinition;

$name = 'lilith';

try {
    $returned = json_app_get($device, $name, 1)['data'];
} catch (JsonAppException $e) {
    echo PHP_EOL . $name . ':' . $e->getCode() . ':' . $e->getMessage() . PHP_EOL;
    update_application($app, $e->getCode() . ':' . $e->getMessage(), []); // Set empty metrics and error message

    return;
}

// find group1 for each sagan and suricata instances
$current=['suricata'=>[], 'sagan'=>[]];
$suricata_found_rrds=Rrd::getRrdApplicationArrays($device, $app->app_id, $name, 'suricata-group1');
$sagan_found_rrds=Rrd::getRrdApplicationArrays($device, $app->app_id, $name, 'sagan-group1');
foreach ($suricata_found_rrds as $value) {
    $value = str_replace('suricata-group1___-___', '', $value);
    $current['suricata'][$value]=1;
}
foreach ($sagan_found_rrds as $value) {
    $value = str_replace('sagan-group1___-___', '', $value);
    $current['sagan'][$value]=1;
}

$fields_base = [
    'total' => 0,
    'ADoS' => 0,
    'AUPG' => 0,
    'C2domain' => 0,
    'ConfChg' => 0,
    'ConfErr' => 0,
    'CredTheft' => 0,
    'DOS' => 0,
    'DRPCQ' => 0,
    'DoS' => 0,
    'ExeCode' => 0,
    'ExpAtmp' => 0,
    'ExpKit' => 0,
    'FoDAccAtmp' => 0,
    'GPCD' => 0,
    'GenICMP' => 0,
    'HWevent' => 0,
    'IL' => 0,
    'LrgSclIL' => 0,
    'MalC2act' => 0,
    'Mining' => 0,
    'MiscActivity' => 0,
    'MiscAtk' => 0,
    'NS_PoE' => 0,
    'NetEvent' => 0,
    'NetScan' => 0,
    'NetTrojan' => 0,
    'OddClntPrt' => 0,
    'PosSocEng' => 0,
    'PotBadTraf' => 0,
    'PotCorpPriVio' => 0,
    'PotUnwantedProg' => 0,
    'PotVulWebApp' => 0,
    'ProgErr' => 0,
    'RetrExtIP' => 0,
    'Spam' => 0,
    'SucAdmPG' => 0,
    'SucAdmPG' => 0,
    'SucUsrPG' => 0,
    'SusFilename' => 0,
    'SusProgExec' => 0,
    'SusString' => 0,
    'SusT' => 0,
    'SysEvent' => 0,
    'Syscall' => 0,
    'TCPconn' => 0,
    'TargetedMalAct' => 0,
    'UnknownT' => 0,
    'WebAppAtk' => 0,
    'blankC' => 0,
    'not_AppCont' => 0,
    'not_DefUserPass' => 0,
    'not_IL' => 0,
    'not_LoginUser' => 0,
    'not_SucAdmPG' => 0,
    'not_SucUsrPG' => 0,
    'not_SusT' => 0,
    'undefC' => 0,
    'unknownC' => 0,
];

$rrd_name = ['app', $name, $app->app_id];
$rrd_def = RrdDefinition::make()
    ->addDataset('total', 'GAUGE', 0)
    ->addDataset('ADoS', 'GAUGE', 0)
    ->addDataset('AUPG', 'GAUGE', 0)
    ->addDataset('C2domain', 'GAUGE', 0)
    ->addDataset('ConfChg', 'GAUGE', 0)
    ->addDataset('ConfErr', 'GAUGE', 0)
    ->addDataset('CredTheft', 'GAUGE', 0)
    ->addDataset('DOS', 'GAUGE', 0)
    ->addDataset('DRPCQ', 'GAUGE', 0)
    ->addDataset('DoS', 'GAUGE', 0)
    ->addDataset('ExeCode', 'GAUGE', 0)
    ->addDataset('ExpAtmp', 'GAUGE', 0)
    ->addDataset('ExpKit', 'GAUGE', 0)
    ->addDataset('FoDAccAtmp', 'GAUGE', 0)
    ->addDataset('GPCD', 'GAUGE', 0)
    ->addDataset('GenICMP', 'GAUGE', 0)
    ->addDataset('HWevent', 'GAUGE', 0)
    ->addDataset('IL', 'GAUGE', 0)
    ->addDataset('LrgSclIL', 'GAUGE', 0)
    ->addDataset('MalC2act', 'GAUGE', 0)
    ->addDataset('Mining', 'GAUGE', 0)
    ->addDataset('MiscActivity', 'GAUGE', 0)
    ->addDataset('MiscAtk', 'GAUGE', 0)
    ->addDataset('NS_PoE', 'GAUGE', 0)
    ->addDataset('NetEvent', 'GAUGE', 0)
    ->addDataset('NetScan', 'GAUGE', 0)
    ->addDataset('NetTrojan', 'GAUGE', 0)
    ->addDataset('OddClntPrt', 'GAUGE', 0)
    ->addDataset('PosSocEng', 'GAUGE', 0)
    ->addDataset('PotBadTraf', 'GAUGE', 0)
    ->addDataset('PotCorpPriVio', 'GAUGE', 0)
    ->addDataset('PotUnwantedProg', 'GAUGE', 0)
    ->addDataset('PotVulWebApp', 'GAUGE', 0)
    ->addDataset('ProgErr', 'GAUGE', 0)
    ->addDataset('RetrExtIP', 'GAUGE', 0)
    ->addDataset('Spam', 'GAUGE', 0)
    ->addDataset('SucAdmPG', 'GAUGE', 0)
    ->addDataset('SucAdmPG', 'GAUGE', 0)
    ->addDataset('SucUsrPG', 'GAUGE', 0)
    ->addDataset('SusFilename', 'GAUGE', 0)
    ->addDataset('SusProgExec', 'GAUGE', 0)
    ->addDataset('SusString', 'GAUGE', 0)
    ->addDataset('SusT', 'GAUGE', 0)
    ->addDataset('SysEvent', 'GAUGE', 0)
    ->addDataset('Syscall', 'GAUGE', 0)
    ->addDataset('TCPconn', 'GAUGE', 0)
    ->addDataset('TargetedMalAct', 'GAUGE', 0)
    ->addDataset('UnknownT', 'GAUGE', 0)
    ->addDataset('WebAppAtk', 'GAUGE', 0)
    ->addDataset('blankC', 'GAUGE', 0)
    ->addDataset('not_AppCont', 'GAUGE', 0)
    ->addDataset('not_DefUserPass', 'GAUGE', 0)
    ->addDataset('not_IL', 'GAUGE', 0)
    ->addDataset('not_LoginUser', 'GAUGE', 0)
    ->addDataset('not_SucAdmPG', 'GAUGE', 0)
    ->addDataset('not_SucUsrPG', 'GAUGE', 0)
    ->addDataset('not_SusT', 'GAUGE', 0)
    ->addDataset('undefC', 'GAUGE', 0)
    ->addDataset('unknownC', 'GAUGE', 0);

//
// process the totals
//
$total_keys=['totals', 'suricata_totals', 'sagan_totals'];
foreach ($total_keys as $total_key) {
    $fields=$fields_base;
    foreach ($returned[$total_key] as $key => $value){
        // ignore unknown classes
        if (isset($fields[$key])) {
            $fields[$key]=$value;
        }
    }
    $rrd_name = ['app', $name, $app->app_id, $total_key . '-group1'];
    $tags = ['name' => $name, 'app_id' => $app->app_id, 'rrd_def' => $rrd_def, 'rrd_name' => $rrd_name];
    data_update($device, 'app', $tags, $fields);
}

//
// process instances
//
$instance_keys=array('suricata_instances', 'sagan_instances');
$instance_type=[
    'suricata_instances' => 'suricata',
    'sagan_instances' => 'sagan'
 ];
foreach ($instance_keys as $instance_key) {
    $type=$instance_type[$instance_key];
    foreach ($returned[$instance_key] as $instance => $instance_data){
        print "Processing $type instance $instance ...\n";
        $fields=$fields_base;
        foreach ($returned[$instance_key][$instance] as $key => $value){
            // ignore unknown classes
            if (isset($fields[$key])) {
                $fields[$key]=$value;
            }
        }
        $rrd_name = ['app', $name, $app->app_id, $type.'-group1___-___'.$instance];
        $tags = ['name' => $name, 'app_id' => $app->app_id, 'rrd_def' => $rrd_def, 'rrd_name' => $rrd_name];
        data_update($device, 'app', $tags, $fields);

        if (isset($current[$type][$instance])){
            unset($current[$type][$instance]);
        }
    }
}

//
// process instances that did not generate any alerts this period
//
foreach ($instance_keys as $instance_key) {
    $type=$instance_type[$instance_key];
    foreach ($current[$type] as $instance => $instance_data){
        print "$type instance $instance had no data, zeroing this period\n";
        $rrd_name = ['app', $name, $app->app_id, $type.'-group1___-___'.$instance];
        $tags = ['name' => $name, 'app_id' => $app->app_id, 'rrd_def' => $rrd_def, 'rrd_name' => $rrd_name];
        data_update($device, 'app', $tags, $fields_base);
    }
}

update_application($app, 'OK', $returned);
