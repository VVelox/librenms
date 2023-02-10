<?php

$name = 'lilith';
$app_id = $app['app_id'];
$unit_text = 'Alerts';
$colours = 'psychedelic';
$dostack = 0;
$printtotal = 1;
$addarea = 0;
$transparency = 15;
$float_precision = 3;

$rrd_filename = Rrd::name($device['hostname'], ['app', $name, $app['app_id'], 'suricata_totals-group1']);

$rrd_list = [];
if (Rrd::checkRrdExists($rrd_filename)) {
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'ADoS',
        'ds'       => 'ADoS',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'AUPG',
        'ds'       => 'AUPG',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'C2domain',
        'ds'       => 'C2domain',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'ConfChg',
        'ds'       => 'ConfChg',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'ConfErr',
        'ds'       => 'ConfErr',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'CredTheft',
        'ds'       => 'CredTheft',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'DOS',
        'ds'       => 'DOS',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'DRPCQ',
        'ds'       => 'DRPCQ',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'DoS',
        'ds'       => 'DoS',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'ExeCode',
        'ds'       => 'ExeCode',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'ExpAtmp',
        'ds'       => 'ExpAtmp',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'ExpKit',
        'ds'       => 'ExpKit',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'FoDAccAtmp',
        'ds'       => 'FoDAccAtmp',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'GPCD',
        'ds'       => 'GPCD',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'GenICMP',
        'ds'       => 'GenICMP',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'HWevent',
        'ds'       => 'HWevent',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'IL',
        'ds'       => 'IL',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'LrgSclIL',
        'ds'       => 'LrgSclIL',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'MalC2act',
        'ds'       => 'MalC2act',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'Mining',
        'ds'       => 'Mining',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'MiscActivity',
        'ds'       => 'MiscActivity',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'MiscAtk',
        'ds'       => 'MiscAtk',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'NS_PoE',
        'ds'       => 'NS_PoE',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'NetEvent',
        'ds'       => 'NetEvent',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'NetScan',
        'ds'       => 'NetScan',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'NetTrojan',
        'ds'       => 'NetTrojan',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'OddClntPrt',
        'ds'       => 'OddClntPrt',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'PosSocEng',
        'ds'       => 'PosSocEng',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'PotBadTraf',
        'ds'       => 'PotBadTraf',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'PotCorpPriVio',
        'ds'       => 'PotCorpPriVio',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'PotUnwantedProg',
        'ds'       => 'PotUnwantedProg',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'PotVulWebApp',
        'ds'       => 'PotVulWebApp',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'ProgErr',
        'ds'       => 'ProgErr',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'RetrExtIP',
        'ds'       => 'RetrExtIP',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'Spam',
        'ds'       => 'Spam',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'SucAdmPG',
        'ds'       => 'SucAdmPG',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'SucAdmPG',
        'ds'       => 'SucAdmPG',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'SucUsrPG',
        'ds'       => 'SucUsrPG',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'SusFilename',
        'ds'       => 'SusFilename',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'SusProgExec',
        'ds'       => 'SusProgExec',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'SusString',
        'ds'       => 'SusString',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'SusT',
        'ds'       => 'SusT',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'SysEvent',
        'ds'       => 'SysEvent',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'Syscall',
        'ds'       => 'Syscall',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'TCPconn',
        'ds'       => 'TCPconn',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'TargetedMalAct',
        'ds'       => 'TargetedMalAct',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'UnknownT',
        'ds'       => 'UnknownT',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'WebAppAtk',
        'ds'       => 'WebAppAtk',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'blankC',
        'ds'       => 'blankC',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'not_AppCont',
        'ds'       => 'not_AppCont',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'not_DefUserPass',
        'ds'       => 'not_DefUserPass',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'not_IL',
        'ds'       => 'not_IL',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'not_LoginUser',
        'ds'       => 'not_LoginUser',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'not_SucAdmPG',
        'ds'       => 'not_SucAdmPG',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'not_SucUsrPG',
        'ds'       => 'not_SucUsrPG',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'not_SusT',
        'ds'       => 'not_SusT',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'undefC',
        'ds'       => 'undefC',
    ];
    $rrd_list[] = [
        'filename' => $rrd_filename,
        'descr'    => 'unknownC',
        'ds'       => 'unknownC',
    ];
} else {
    d_echo('RRD "' . $rrd_filename . '" not found');
}

require 'includes/html/graphs/generic_multi_line.inc.php';
