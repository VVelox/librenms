<?php

use Illuminate\Support\Str;

echo ' ENTITY-SENSOR: ';
echo 'Caching OIDs:';
if (empty($entity_array)) {
    $entity_array = [];
    echo ' entPhysicalDescr';
    $entity_array = snmpwalk_cache_multi_oid($device, 'entPhysicalDescr', $entity_array, 'ENTITY-MIB');
    if (! empty($entity_array)) {
        echo ' entPhysicalName';
        $entity_array = snmpwalk_cache_multi_oid($device, 'entPhysicalName', $entity_array, 'ENTITY-MIB');
    }
}

if (! empty($entity_array)) {
    echo ' entPhySensorType';
    $entity_oids = snmpwalk_cache_multi_oid($device, 'entPhySensorType', [], 'ENTITY-SENSOR-MIB');
    echo ' entPhySensorScale';
    $entity_oids = snmpwalk_cache_multi_oid($device, 'entPhySensorScale', $entity_oids, 'ENTITY-SENSOR-MIB');
    echo ' entPhySensorPrecision';
    $entity_oids = snmpwalk_cache_multi_oid($device, 'entPhySensorPrecision', $entity_oids, 'ENTITY-SENSOR-MIB');
    echo ' entPhySensorValue';
    $entity_oids = snmpwalk_cache_multi_oid($device, 'entPhySensorValue', $entity_oids, 'ENTITY-SENSOR-MIB');
    if ($device['os'] === 'arista_eos') {
        $entity_oids = snmpwalk_cache_oid($device, 'aristaEntSensorThresholdTable', $entity_oids, 'ARISTA-ENTITY-SENSOR-MIB');
    }
    if ($device['os'] === 'xos') {
        echo ' XOS:entPhysicalContainedIn';
        $entity_oids = snmpwalk_cache_oid($device, 'entPhysicalContainedIn', $entity_oids, 'ENTITY-MIB');
        echo ' XOS:entAliasMappingIdentifier';
        $entity_oids = snmpwalk_cache_oid($device, 'entAliasMappingIdentifier', $entity_oids, 'ENTITY-MIB');
        echo ' XOS:ifName';
        $xos_ifname = snmpwalk_cache_oid($device, 'ifName', [], 'IF-MIB');
    }
    echo ' entPhySensorOperStatus';
    $entity_oids = snmpwalk_cache_multi_oid($device, 'entPhySensorOperStatus', $entity_oids, 'ENTITY-SENSOR-MIB');
}

if (! empty($entity_oids)) {
    $entitysensor = [
        'voltsDC' => 'voltage',
        'voltsAC' => 'voltage',
        'amperes' => 'current',
        'watts' => 'power',
        'hertz' => 'freq',
        'percentRH' => 'humidity',
        'rpm' => 'fanspeed',
        'celsius' => 'temperature',
        'dBm' => 'dbm',
    ];

    foreach ($entity_oids as $index => $entry) {
        $low_limit = null;
        $low_warn_limit = null;
        $warn_limit = null;
        $high_limit = null;
        $group = null;
        $type = null;
        $oid = null;
        $descr = null;
        $divisor = null;
        $multiplier = null;
        $current = null;
        $entPhysicalIndex = null;

        // Fix for Cisco ASR920, 15.5(2)S
        if ($entry['entPhySensorType'] == 'other' && Str::contains($entity_array[$index]['entPhysicalName'], ['Rx Power Sensor', 'Tx Power Sensor'])) {
            $entitysensor['other'] = 'dbm';
        }
        if (isset($entitysensor[$entry['entPhySensorType']]) && is_numeric($entry['entPhySensorValue']) && is_numeric($index)) {
            $entPhysicalIndex = $index;
            $oid = '.1.3.6.1.2.1.99.1.1.1.4.' . $index;
            $current = $entry['entPhySensorValue'];
            if ($device['os'] === 'arris-d5') {
                $card = str_split($index);
                if (count($card) === 3) {
                    $card = $card[0] . '00';
                } elseif (count($card) === 4) {
                    $card = $card[0] . $card[1] . '00';
                }
                $descr = ucwords($entity_array[$card]['entPhysicalName'] ?? '') . ' ' . ucwords($entity_array[$index]['entPhysicalDescr'] ?? '');
            } elseif ($device['os'] === 'xos' && str_starts_with($entity_oids[$entity_oids[$index]['entPhysicalContainedIn'] . '.0']['entAliasMappingIdentifier'], 'mib-2.2.2.1.1.')) {
                $xos_ifindex = end(explode('.', $entity_oids[$entity_oids[$index]['entPhysicalContainedIn'] . '.0']['entAliasMappingIdentifier']));
                $xos_portname = $xos_ifname[$xos_ifindex]['ifName'];
                $descr = ucwords($xos_portname . ' ' . str_replace(' Sensor', '', $entity_array[$index]['entPhysicalDescr']));
            } elseif (isset($entity_array[$index]['entPhysicalName'])) {
                $descr = ucwords($entity_array[$index]['entPhysicalName']);
            }
            if ($descr) {
                $descr = rewrite_entity_descr($descr);
            } else {
                // Better sensor names for Arista EOS. Remove some redundancy and improve them so they reflect to which unit they belong.
                if ($device['os'] === 'arista_eos') {
                    $descr = $entity_array[$index]['entPhysicalDescr'];
                    if (preg_match('/(Input|Output) (voltage|current) sensor/i', $descr) || Str::startsWith($descr, 'Power supply') || preg_match('/^(Power Supply|Hotspot|Inlet|Board)/i', $descr)) {
                        $descr = ucwords($entity_array[substr_replace($index, '000', -3)]['entPhysicalDescr'] ?? '')
                                 . ' '
                                 . preg_replace(
                                     '/(Voltage|Current|Power Supply) Sensor$/i',
                                     '',
                                     ucwords($entity_array[$index]['entPhysicalDescr'])
                                 );
                    }
                    if (preg_match('/(temp|temperature) sensor$/i', $descr)) {
                        $descr = preg_replace('/(temp|temperature) sensor$/i', '', $descr);
                    }
                }
                // End better sensor names for Arista EOS.
                $descr = rewrite_entity_descr($descr);
            }
            $valid_sensor = check_entity_sensor($descr, $device);
            $type = $entitysensor[$entry['entPhySensorType']];

            // Try to handle the scale
            [$divisor, $multiplier] = match ($entry['entPhySensorScale']) {
                'zepto' => [1000000000000000000, 1],
                'nano' => [1000000000, 1],
                'micro' => [1000000, 1],
                'milli' => [1000, 1],
                'units' => [1, 1],
                'kilo' => [1, 1000],
                'mega' => [1, 1000000],
                'giga' => [1, 1000000000],
                'yocto' => [1, 1],
                default => [1, 1],
            };

            if (is_numeric($entry['entPhySensorPrecision']) && $entry['entPhySensorPrecision'] > 0) {
                $divisor .= str_pad('', $entry['entPhySensorPrecision'], '0');
            }

            $current = ($current * $multiplier / $divisor);
            if ($type == 'temperature') {
                if ($current > '200') {
                    $valid_sensor = false;
                }
                $descr = preg_replace('/[T|t]emperature[|s]/', '', $descr);
            }

            // Fix for FortiSwitch - ALL FortiSwitches as of 14/2/2024 output fan speeds as percentages while entPhySensorType is RPM.
            if ($device['os'] == 'fortiswitch' && $entry['entPhySensorType'] == 'rpm') {
                $type = 'percent';
                $divisor = 1;
                $current = $current * 10;
            }

            if ($device['os'] == 'rittal-lcp') {
                if ($type == 'voltage') {
                    $divisor = 1000;
                }
                if ($descr == 'Temperature.Value') {
                    $divisor = 1000;
                }
                if ($descr == 'System.Temperature.Value') {
                    $divisor = 1000;
                }
                if ($type == 'humidity' && $current == '0') {
                    $valid_sensor = false;
                }
            }
            if ($current == '-127' || ($device['os'] == 'asa' && is_string($device['hardware']) && Str::endsWith($device['hardware'], 'sc'))) {
                $valid_sensor = false;
            }
            // Check for valid sensors
            if (isset($entry['entPhySensorOperStatus']) && ($entry['entPhySensorOperStatus'] === 'unavailable' || $entry['entPhySensorOperStatus'] === 'nonoperational')) {
                $valid_sensor = false;
            }

            if ($entry['entPhySensorValue'] == '-1000000000') {
                $valid_sensor = false;
            }

            if ($valid_sensor && dbFetchCell("SELECT COUNT(*) FROM `sensors` WHERE device_id = ? AND `sensor_class` = ? AND `sensor_type` = 'cisco-entity-sensor' AND `sensor_index` = ?", [$device['device_id'], $type, $index]) == '0') {
                // Check to make sure we've not already seen this sensor via cisco's entity sensor mib
                if ($type == 'power' && $device['os'] == 'arista_eos' && preg_match('/DOM (R|T)x Power/i', $descr)) {
                    $type = 'dbm';
                    $current = round(10 * log10($entry['entPhySensorValue'] / 10000), 3);
                    $multiplier = 1;
                    $divisor = 1;
                }

                if ($device['os'] === 'arista_eos') {
                    if (isset($entry['aristaEntSensorThresholdLowWarning']) && $entry['aristaEntSensorThresholdLowWarning'] != '-1000000000') {
                        if ($entry['entPhySensorScale'] == 'milli' && $entry['entPhySensorType'] == 'watts') {
                            $temp_low_warn_limit = $entry['aristaEntSensorThresholdLowWarning'] / 10000;
                            $low_warn_limit = round(10 * log10($temp_low_warn_limit), 2);
                        } else {
                            $low_warn_limit = $entry['aristaEntSensorThresholdLowWarning'] / $divisor;
                        }
                    }
                    if (isset($entry['aristaEntSensorThresholdLowCritical']) && $entry['aristaEntSensorThresholdLowCritical'] != '-1000000000') {
                        if ($entry['entPhySensorScale'] == 'milli' && $entry['entPhySensorType'] == 'watts') {
                            $temp_low_limit = $entry['aristaEntSensorThresholdLowCritical'] / 10000;
                            $low_limit = round(10 * log10($temp_low_limit), 2);
                        } else {
                            $low_limit = $entry['aristaEntSensorThresholdLowCritical'] / $divisor;
                        }
                    }
                    if (isset($entry['aristaEntSensorThresholdHighWarning']) && $entry['aristaEntSensorThresholdHighWarning'] != '1000000000') {
                        if ($entry['entPhySensorScale'] == 'milli' && $entry['entPhySensorType'] == 'watts') {
                            $temp_warn_limit = $entry['aristaEntSensorThresholdHighWarning'] / 10000;
                            $warn_limit = round(10 * log10($temp_warn_limit), 2);
                        } else {
                            $warn_limit = $entry['aristaEntSensorThresholdHighWarning'] / $divisor;
                        }
                    }
                    if (isset($entry['aristaEntSensorThresholdHighCritical']) && $entry['aristaEntSensorThresholdHighCritical'] != '1000000000') {
                        if ($entry['entPhySensorScale'] == 'milli' && $entry['entPhySensorType'] == 'watts') {
                            $temp_high_limit = $entry['aristaEntSensorThresholdHighCritical'] / 10000;
                            $high_limit = round(10 * log10($temp_high_limit), 2);
                        } else {
                            $high_limit = $entry['aristaEntSensorThresholdHighCritical'] / $divisor;
                        }
                    }
                    // Grouping sensors
                    $group = null;
                    if (preg_match('/DOM /i', $descr)) {
                        $group = 'SFPs';
                    } elseif (preg_match('/PwrCon/', $descr)) {
                        $string = explode(' ', $descr);
                        if (preg_match('/PwrCon[0-9]/', $string[0])) {
                            $group = $string[0];
                        } else {
                            $group = preg_replace('/PwrCon/i', '', $string[0]);
                        }
                        $descr = preg_replace('/^.*?(PwrCon)[0-9]*/i', '', $descr);
                    } elseif (preg_match('/^(Trident.*|Jericho[0-9]|FM6000)/i', $descr)) {
                        // I only know replies for Trident|Jericho|FM6000 platform. If you have another please add to the preg_match
                        $group = 'Platform';
                    } elseif (preg_match('/^(Power|PSU)/i', $descr)) {
                        $group = 'PSUs';
                    } else {
                        $group = 'System';
                        $descr = Str::replaceLast('temp sensor', '', $descr);
                    }
                    // End grouping sensors
                }
                $descr = trim($descr);
                discover_sensor(null, $type, $device, $oid, $index, 'entity-sensor', $descr, $divisor, $multiplier, $low_limit, $low_warn_limit, $warn_limit, $high_limit, $current, 'snmp', $entPhysicalIndex, $entry['entSensorMeasuredEntity'] ?? null, null, $group);
            }
        }//end if
    }//end foreach
    unset(
        $entity_array
    );
}//end if
echo "\n";
