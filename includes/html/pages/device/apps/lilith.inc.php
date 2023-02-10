<?php

if (! isset($vars['lilithType'])) {
    $vars['lilithType'] = 'combined';
}else{
    if ( $vars['lilithType'] != 'general' &&
         $vars['lilithType'] != 'sagan' &&
         $vars['lilithType'] != 'suricata'
        ){
        $vars['lilithType'] = 'combined';
    }
}

$categories=array(
    'ADoS',
    'AUPG',
    'C2domain',
    'ConfChg',
    'ConfErr',
    'CredTheft',
    'DOS',
    'DRPCQ',
    'DoS',
    'ExeCode',
    'ExpAtmp',
    'ExpKit',
    'FoDAccAtmp',
    'GPCD',
    'GenICMP',
    'HWevent',
    'IL',
    'LrgSclIL',
    'MalC2act',
    'Mining',
    'MiscActivity',
    'MiscAtk',
    'NS_PoE',
    'NetEvent',
    'NetScan',
    'NetTrojan',
    'OddClntPrt',
    'PosSocEng',
    'PotBadTraf',
    'PotCorpPriVio',
    'PotUnwantedProg',
    'PotVulWebApp',
    'ProgErr',
    'RetrExtIP',
    'Spam',
    'SucAdmPG',
    'SucAdmPG',
    'SucUsrPG',
    'SusFilename',
    'SusProgExec',
    'SusString',
    'SusT',
    'SysEvent',
    'Syscall',
    'TCPconn',
    'TargetedMalAct',
    'UnknownT',
    'WebAppAtk',
    'blankC',
    'not_AppCont',
    'not_DefUserPass',
    'not_IL',
    'not_LoginUser',
    'not_SucAdmPG',
    'not_SucUsrPG',
    'not_SusT',
    'undefC',
    'unknownC',
);

// find group1 for each sagan and suricata instances
$current=['suricata'=>[], 'sagan'=>[]];
$suricata_found_rrds=Rrd::getRrdApplicationArrays($device, $app->app_id, 'lilith', 'suricata-group1');
$sagan_found_rrds=Rrd::getRrdApplicationArrays($device, $app->app_id, 'lilith', 'sagan-group1');
foreach ($suricata_found_rrds as $value) {
    $value = str_replace('suricata-group1___-___', '', $value);
    $current['suricata'][$value]=1;
}
foreach ($sagan_found_rrds as $value) {
    $value = str_replace('sagan-group1___-___', '', $value);
    $current['sagan'][$value]=1;
}

print_optionbar_start();

    $link_tmp = generate_link('Combined', $link_array, ['app'=>'lilith', 'lilithType'=>'combined']);
    if (isset($vars['lilithType']) && $vars['lilithType'] == 'combined'){
        $link_tmp = '<span class="pagemenu-selected">' . $link_tmp . '</span>';
    }
    echo $link_tmp.' | ';

    $link_tmp = generate_link('Sagan', $link_array, ['app'=>'lilith', 'lilithType'=>'sagan']);
    if (isset($vars['lilithType']) && $vars['lilithType'] == 'sagan'){
        $link_tmp = '<span class="pagemenu-selected">' . $link_tmp . '</span>';
    }
    echo $link_tmp.' | ';

    $link_tmp = generate_link('Suricata', $link_array, ['app'=>'lilith', 'lilithType'=>'suricata']);
    if (isset($vars['lilithType']) && $vars['lilithType'] == 'suricata'){
        $link_tmp = '<span class="pagemenu-selected">' . $link_tmp . '</span>';
    }
    echo $link_tmp;


    $category_array=array();
    echo '<br><b>Categorties</b>:';
    foreach ($categories as $value) {
        $link_switches=['app'=>'lilith', 'lilithType' => $vars['lilithType'], 'lilithCat' => $value];
        if (isset($vars['lilithInstance'])) {
            $link_switches['lilithInstance']=$vars['lilithInstance'];
        }
        $link_tmp = generate_link($value, $link_array, $link_switches);
        if (isset($vars['lilithCat']) && $vars['lilithCat'] == $value){
            $link_tmp = '<span class="pagemenu-selected">' . $link_tmp . '</span>';
        }
        array_push($category_array, $link_tmp);
    }
    echo join(', ', $category_array);

    if (sizeof($current['sagan']) > 0) {
        echo "<br>\n<b>Sagan Instances:</b> ";
        $sagan_array=array();
        foreach($current['sagan'] as $key => $value){
            $link_tmp = generate_link($key, $link_array, ['app'=>'lilith', 'lilithType'=>'sagan', 'lilithInstance'=>$key]);
            if (isset($vars['lilithInstance']) && isset($vars['lilithType']) && $vars['lilithType'] == 'sagan' && $vars['lilithInstance'] == $key){
                $link_tmp = '<span class="pagemenu-selected">' . $link_tmp . '</span>';
            }
            array_push($sagan_array, $link_tmp);
        }
        echo join(', ', $sagan_array);
    }

    if (sizeof($current['suricata']) > 0) {
        echo "<br>\n<b>Suricata Instances:</b> ";
        $suricata_array=array();
        foreach($current['suricata'] as $key => $value){
            $link_tmp=generate_link($key, $link_array, ['app'=>'lilith', 'lilithType'=>'suricata', 'lilithInstance'=>$key]);
            if (isset($vars['lilithInstance']) && isset($vars['lilithType']) && $vars['lilithType'] == 'suricata' && $vars['lilithInstance'] == $key){
                $link_tmp = '<span class="pagemenu-selected">' . $link_tmp . '</span>';
            }
            array_push($suricata_array, $link_tmp);
        }
        echo join(', ', $suricata_array);
    }

print_optionbar_end();


if (isset($vars['lilithType']) && $vars['lilithType'] == 'sagan'){
    $graphs = [
        'lilith_sagan_total' => ['text'=>'Sagan Total'],
        'lilith_sagan_totals' => ['text'=>'Sagan Totals Per Category']
    ];
}elseif(isset($vars['lilithType']) && $vars['lilithType'] == 'suricata'){
    $graphs = [
        'lilith_suricata_total' => ['text'=>'Suricata Total'],
        'lilith_suricata_totals' => ['text'=>'Suricata Totals Per Category']
    ];
}else{
    $graphs = [
        'lilith_total' => ['text'=>'Combined Total'],
        'lilith_totals' => ['text'=>'Combined Totals Per Category']
    ];
}

foreach ($graphs as $key => $details) {
    $text=$details['text'];
    $graph_type = $key;
    $graph_array['height'] = '100';
    $graph_array['width'] = '215';
    $graph_array['to'] = time();
    $graph_array['id'] = $app['app_id'];
    $graph_array['type'] = 'application_' . $key;

    if(isset($vars['lilithType'])){
        $graph_array['lilithType']=$vars['lilithType'];
    }

    if(isset($vars['lilithCat'])){
        $graph_array['lilithCat']=$vars['lilithCat'];
    }

    if(isset($vars['lilithInstance'])){
        $graph_array['lilithInstance']=$vars['lilithInstance'];
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
