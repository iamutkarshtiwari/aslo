<?php
/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is addons.mozilla.org site.
 *
 * The Initial Developer of the Original Code is
 * The Mozilla Foundation.
 * Portions created by the Initial Developer are Copyright (C) 2007
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *   Justin Scott <fligtar@mozilla.com> (Original Author)
 *   l.m.orchard <lorchard@mozilla.com>
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */
class StatsComponent extends Object {
    var $controller;

    var $date_group_column_map = array(
        'date'  => '', 
        'month' => 'date_format(`date`, "month:%Y-%m") as `date_month`,',
        'week'  => 'date_format(`date`, "week:%X-%V") as `date_week`,'
    );
    
    var $date_group_by_map = array(
        'date'  => 'date', 
        'month' => 'date_month',
        'week'  => 'date_week'
    );
    
    /**
     * Save a reference to the controller on startup
     * @param object &$controller the controller using this component
     */
    function startup(&$controller) {
        $this->controller =& $controller;
    }

    function historicalPlot($addon_id, $plot, $date_grouping='date') {

        // Constrain the date grouping choice to valid set
        if (!array_key_exists($date_grouping, $this->date_group_column_map)) {
            $date_grouping = 'date';
        }
        $date_group_column = $this->date_group_column_map[$date_grouping];
        $date_group_by     = $this->date_group_by_map[$date_grouping];

        $model =& $this->controller->Addon;
        
        $csv = array();
        $totalCounts = array();
        $dynamicFields = array();
        $dates = array();
        
        // Warning: extensive use of ` ahead. We keenly named columns after
        // MySQL functions
        
        switch ($plot) {
            // Summary of downloads and update pings per day
            case 'summary':
                if ($data = $model->query("
                    SELECT
                        `download_counts`.`date`,
                        `download_counts`.`count`,
                        `update_counts`.`count`
                    FROM
                        `download_counts`
                            LEFT JOIN `update_counts` ON
                                `download_counts`.`date`=`update_counts`.`date` AND
                                `download_counts`.`addon_id`=`update_counts`.`addon_id`
                    WHERE
                        `download_counts`.`addon_id`={$addon_id} AND
                        `download_counts`.`date` >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    ORDER BY
                        `download_counts`.`date`
                ", true)) {
                    foreach ($data as $date) {
                        $csv[] = array('date' => $date['download_counts']['date'],
                                       'downloads' => $date['download_counts']['count'],
                                       'updatepings' => $date['update_counts']['count']
                                   );
                    }
                }
                
                break;
            
            // Downloads per day
            case 'downloads':
                if ($data = $model->query("
                    SELECT `date`, {$date_group_column} sum(`count`) as `count` 
                    FROM `download_counts` 
                    WHERE `addon_id`={$addon_id} AND date != '0000-00-00' 
                    GROUP BY `{$date_group_by}`
                    ORDER BY `date`
                ", true)) {
                    foreach ($data as $download) {
                        $csv[] = array('date' => $download['download_counts']['date'],
                                       'count' => $download['0']['count']);
                    }
                }
                break;
            
            // Update pings per day
            case 'updatepings':
                // Since summing update pings per day isn't as useful as summing
                // the other data points, we're taking an average here instead.
                if ($data = $model->query("
                    SELECT `date`, {$date_group_column} AVG(`count`) as `count`
                    FROM `update_counts` 
                    WHERE `addon_id`={$addon_id} AND date != '0000-00-00' 
                    GROUP BY `{$date_group_by}`
                    ORDER BY `date`
                ", true)) {
                    foreach ($data as $download) {
                        $csv[] = array('date' => $download['update_counts']['date'],
                                       'count' => $download['0']['count']);
                    }
                }
                break;
            
            // Add-on versions in use per day
            case 'version':
                if ($data = $model->query("
                    SELECT `date`, {$date_group_column} `count`, `version` 
                    FROM `update_counts` 
                    WHERE `addon_id`={$addon_id} AND date != '0000-00-00' 
                    ORDER BY `date`
                ", true)) {
                    foreach ($data as $download) {
                        $date = $download['update_counts']['date'];
                        
                        // Add static fields for CSV
                        $dates[$date] = array(
                            'date'  => $date,
                            'count' => $download['update_counts']['count']
                        );

                        if ($date_group_by != 'date')
                            $dates[$date]['group_by'] = $download['0'][$date_group_by];
                        
                        // Loop through dynamic fields
                        $items = unserialize($download['update_counts']['version']);
                        if (!empty($items)) {
                            foreach ($items as $item => $count) {
                                // Update total count for sorting
                                if (empty($dynamicFields[$item]))
                                    $dynamicFields[$item] = $count;
                                else
                                    $dynamicFields[$item] += $count;
                                
                                $dates[$date]['dynamic'][$item] = $count;
                            }
                        }
                    }

                    if ($date_group_by != 'date')
                        $dates = $this->_groupAndSumDynamicFields($dates);

                }
                break;
            
            // Application versions in use per day
            case 'application':
                if ($data = $model->query("
                    SELECT `date`, {$date_group_column} `count`, `application` 
                    FROM `update_counts` 
                    WHERE `addon_id`={$addon_id} AND date != '0000-00-00' 
                    ORDER BY `date`
                ", true)) {
                    foreach ($data as $download) {
                        $date = $download['update_counts']['date'];
                        
                        // Add static fields for CSV
                        $dates[$date] = array(
                            'date'  => $date,
                            'count' => $download['update_counts']['count']
                        );

                        if ($date_group_by != 'date')
                            $dates[$date]['group_by'] = $download['0'][$date_group_by];
                        
                        // Loop through dynamic fields
                        $items = unserialize($download['update_counts']['application']);
                        if (!empty($items)) {
                            foreach ($items as $app => $versions) {
                                foreach ($versions as $version => $count) {
                                    // Combine app GUID with version
                                    $item = "{$app}/{$version}";
                                    
                                    // Update total count for sorting
                                    if (empty($dynamicFields[$item]))
                                        $dynamicFields[$item] = $count;
                                    else
                                        $dynamicFields[$item] += $count;
                                    
                                    $dates[$date]['dynamic'][$item] = $count;
                                }
                            }
                        }
                    }

                    if ($date_group_by != 'date')
                        $dates = $this->_groupAndSumDynamicFields($dates);

                }
                break;
            
            // Status of add-ons in use per day
            case 'status':
                if ($data = $model->query("
                    SELECT `date`, {$date_group_column} `count`, `status` 
                    FROM `update_counts` 
                    WHERE `addon_id`={$addon_id} AND date != '0000-00-00' 
                    ORDER BY `date`
                ", true)) {
                    foreach ($data as $download) {
                        $date = $download['update_counts']['date'];
                        
                        // Add static fields for CSV
                        $dates[$date] = array(
                            'date'  => $date,
                            'count' => $download['update_counts']['count']
                        );

                        if ($date_group_by != 'date')
                            $dates[$date]['group_by'] = $download['0'][$date_group_by];
                        
                        // Loop through dynamic fields
                        $items = unserialize($download['update_counts']['status']);
                        if (!empty($items)) {
                            foreach ($items as $item => $count) {
                                // Update total count for sorting
                                if (empty($dynamicFields[$item]))
                                    $dynamicFields[$item] = $count;
                                else
                                    $dynamicFields[$item] += $count;
                                
                                $dates[$date]['dynamic'][$item] = $count;
                            }
                        }
                    }

                    if ($date_group_by != 'date')
                        $dates = $this->_groupAndSumDynamicFields($dates);

                }
                break;
            
            // Operating Systems in use per day
            case 'os':
                if ($data = $model->query("
                    SELECT `date`, {$date_group_column} `count`, `os` 
                    FROM `update_counts` 
                    WHERE `addon_id`={$addon_id} AND date != '0000-00-00' 
                    ORDER BY `date`
                ", true)) {
                    foreach ($data as $download) {
                        $date = $download['update_counts']['date'];
                        
                        // Add static fields for CSV
                        $dates[$date] = array(
                            'date'  => $date,
                            'count' => $download['update_counts']['count']
                        );

                        if ($date_group_by != 'date')
                            $dates[$date]['group_by'] = $download['0'][$date_group_by];
                        
                        // Loop through dynamic fields
                        $items = unserialize($download['update_counts']['os']);
                        if (!empty($items)) {
                            foreach ($items as $item => $count) {
                                // Update total count for sorting
                                if (empty($dynamicFields[$item]))
                                    $dynamicFields[$item] = $count;
                                else
                                    $dynamicFields[$item] += $count;
                                    
                                $dates[$date]['dynamic'][$item] = $count;
                            }
                        }
                    }

                    if ($date_group_by != 'date')
                        $dates = $this->_groupAndSumDynamicFields($dates);

                }
                break;
        }
        
        arsort($dynamicFields);
        
        // Loop through dates and add each field to the CSV array
        foreach ($dates as $date => $items) {
            $record = array();
            
            foreach ($items as $item => $value) {                
                // If item is a "normal" field like date or count, add it
                if ($item != 'dynamic') {
                    $record[$item] = $value;
                }
                // If it's the array of dynamic fields, go in pre-determined order
                else {
                    foreach ($dynamicFields as $dynamicField => $count) {
                        if (!empty($value[$dynamicField]))
                            $record[$dynamicField] = $value[$dynamicField];
                        else
                            $record[$dynamicField] = 0;
                    }
                }
            }
            
            $csv[] = $record;
        }
        
        return $csv;
    }

    /**
     * Aggregate all counts and dynamic field counts using the value of 
     * group by fields.
     */
    function _groupAndSumDynamicFields($dates) {

        // Run through all the flat date-indexed results and aggregate the 
        // counts by the group_by field
        $agg = array();
        foreach ($dates as $date=>$stats) {
            $group_by = $stats['group_by'];

            if (!isset($agg[$group_by])) {
                // Empty aggregate, so start off with the current stats row.
                $agg[$group_by] = $stats;
            } else {
                // Add the current count to aggregate.
                $agg[$group_by]['count'] += $stats['count'];
                // Add all the dynamic field values to the aggregate.
                foreach ($stats['dynamic'] as $key=>$value) {
                    if (!isset($agg[$group_by]['dynamic'][$key])) {
                        // Empty aggregate value, use current value.
                        $agg[$group_by]['dynamic'][$key] = $value;
                    } else {
                        // Add current value to aggregate.
                        $agg[$group_by]['dynamic'][$key] += $value;
                    }
                }
            }
        }

        // Re-index the aggregates by the date field, return results.
        $new_dates = array();
        foreach ($agg as $group_by=>$stats) {
            unset($stats['group_by']);
            $new_dates[$stats['date']] = $stats;
        }
        return $new_dates;
    }
    
    function getSummary($addon_id) {
        $json = array(
                      'downloads' => array(
                                'availableDates' => array()
                                             ),
                      'updatepings' => array(
                                'availableDates' => array(),
                                'plotFields' => array(
                                                      'version' => array(),
                                                      'application' => array(),
                                                      'status' => array(),
                                                      'os' => array()
                                                      )
                                             ),
                      'prettyNames' => array(
                                             'version' => _('statistics_longnames_version'),
                                             'application' => _('statistics_longnames_application'),
                                             'status' => _('statistics_longnames_status'),
                                             'os' => _('statistics_longnames_os'),
                                             'unknown' => _('statistics_longnames_unknown')
                                             ),
                      'shortNames' => array(
                                            'version' => _('statistics_shortnames_version'),
                                            'application' => _('statistics_shortnames_application'),
                                            'status' => _('statistics_shortnames_status'),
                                            'os' => _('statistics_shortnames_os'),
                                            'unknown' => _('statistics_shortnames_unknown'),
                                            )
                      );
        $appShortnames = $this->controller->Amo->getApplicationName(null, true);
        foreach ($appShortnames as $appShortname) {
            $json['shortNames'][$appShortname['name']] = ucwords($appShortname['shortname']);
        }
        
        $appNames = $this->controller->Application->getGUIDList();
        $json['prettyNames'] = array_merge($json['prettyNames'], $appNames);
        
        // Downloads
        if ($data = $this->controller->Addon->query("SELECT date FROM `download_counts` WHERE `addon_id`={$addon_id} AND date != '0000-00-00' ORDER BY `date`", true)) {
            foreach ($data as $date) {
                // if date isn't recorded, record it
                if (!in_array($date['download_counts']['date'], $json['downloads']['availableDates']))
                    $json['downloads']['availableDates'][] = $date['download_counts']['date'];
            }
        }
        
        // Update pings
        if ($data = $this->controller->Addon->query("SELECT * FROM `update_counts` WHERE `addon_id`={$addon_id} AND date != '0000-00-00' ORDER BY `date`", true)) {
            foreach ($data as $date) {
                // if date isn't recorded, record it
                if (!in_array($date['update_counts']['date'], $json['updatepings']['availableDates']))
                    $json['updatepings']['availableDates'][] = $date['update_counts']['date'];
                
                // record dynamic field names
                foreach (array_keys($json['updatepings']['plotFields']) as $dynamicField) {
                    $field = unserialize($date['update_counts'][$dynamicField]);
                    
                    if (!empty($field)) {
                        foreach ($field as $name => $count) {
                            if (!is_array($count)) {
                                // fields that are only 1 level deep
                                if (empty($json['updatepings']['plotFields'][$dynamicField][$name]))
                                    $json['updatepings']['plotFields'][$dynamicField][$name] = $count;
                                else
                                    $json['updatepings']['plotFields'][$dynamicField][$name] += $count;
                            }
                            else {
                                // applications are 2 levels deep
                                foreach ($count as $appVersion => $appcount) {
                                    // $name == app GUID
                                    // make sure app is added
                                    if (!array_key_exists($name, $json['updatepings']['plotFields'][$dynamicField]))
                                        $json['updatepings']['plotFields'][$dynamicField][$name] = array();
                                    
                                    // make sure app version is added
                                    if (empty($json['updatepings']['plotFields'][$dynamicField][$name][$appVersion]))
                                        $json['updatepings']['plotFields'][$dynamicField][$name][$appVersion] = $appcount;
                                    else
                                        $json['updatepings']['plotFields'][$dynamicField][$name][$appVersion] += $appcount;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // sort each field by total count
        foreach (array_keys($json['updatepings']['plotFields']) as $dynamicField) {
            if ($dynamicField == 'application') {
                foreach ($json['updatepings']['plotFields'][$dynamicField] as $app => $appversions) {
                    // Causing a warning because one of the elements is 0=>false
                    if (is_array($json['updatepings']['plotFields'][$dynamicField][$app]))
                        arsort($json['updatepings']['plotFields'][$dynamicField][$app]);
                }
            }
            else
                arsort($json['updatepings']['plotFields'][$dynamicField]);
        }
        
        return $json;
    }
    
    function getEventsApp($app) {
        if ($app == 'firefox') {
            vendor('product-details/history/firefoxHistory.class');
            $history = new firefoxHistory();
        }
        elseif ($app == 'thunderbird') {
            vendor('product-details/history/thunderbirdHistory.class');
            $history = new thunderbirdHistory();
        }
        
        $releases = $history->getReleaseDates();
        $xml = array();
        
        if (!empty($releases)) {
            foreach ($releases as $version => $date) {
                $title = sprintf(_('statistics_events_app_released'), ucwords($app)." {$version}");
                $xml[] = '<event start="'.date('M d Y H:i:s \G\M\TO', strtotime($date)).'" title="'.$title.'" />';
            }
        }
        
        return $xml;
    }
    
    function getEventsAddon($addon_id) {
        $xml = array();
        
        $name = $this->controller->Addon->getAddonName($addon_id);
        
        $versions = $this->controller->Version->findAll(
                                "Version.addon_id={$addon_id}",
                                array('Version.version', 'Version.created'));
        
        if (!empty($versions)) {
            foreach ($versions as $version) {
                $title = sprintf(_('statistics_events_addon_created'), "{$name} {$version['Version']['version']}");
                $xml[] = '<event start="'.date('M d Y H:i:s \G\M\TO', strtotime($version['Version']['created'])).'" title="'.$title.'" />';
            }
        }
        
        return $xml;
    }

    /**
     * @return array Array of count results for the current day.
     */
    function getDailyStats() {

        $yesterday = date('Y-m-d', strtotime('yesterday'));
        $after = $yesterday.' 00:00:00';
        $before = $yesterday.' 23:59:59';

        $ret = array(
            'nomination' => '',
            'pending' => '',
            'flagged' => '',
            'reviews' => '',
            'dailyAddons' => '',
            'totalAddons' => '',
            'dailyVersions' => '',
            'dailyUsers' => '',
            'dailyImages' => '',
            'dailyDownloads' => '',
            'after' => $after,
            'before' => $before
        );

        $model =& $this->controller->Addon;

        if ($buf = $model->query("SELECT COUNT(*) as nomination FROM addons WHERE status=3", true)) {
            $ret['nomination'] = $buf[0][0]['nomination'];
            unset($buf);
        }

        if ($buf = $model->query("SELECT COUNT(*) as pending FROM files WHERE status=2", true)) {
            $ret['pending'] = $buf[0][0]['pending'];
            unset($buf);
        }

        if ($buf = $model->query("SELECT COUNT(*) as flagged FROM addons WHERE adminreview=1", true)) {
            $ret['flagged'] = $buf[0][0]['flagged'];
            unset($buf);
        }

        if ($buf = $model->query("SELECT COUNT(*) as reviews FROM reviews WHERE editorreview=1", true)) {
            $ret['reviews'] = $buf[0][0]['reviews'];
            unset($buf);
        }

        if ($buf = $model->query("SELECT COUNT(*) as total FROM addons WHERE created >= '{$after}' AND created <= '{$before}'", true)) {
            $ret['dailyAddons'] = $buf[0][0]['total'];
            unset($buf);
        }

        if ($buf = $model->query("SELECT COUNT(*) as total FROM addons", true)) {
            $ret['totalAddons'] = $buf[0][0]['total'];
            unset($buf);
        }

        if ($buf = $model->query("SELECT COUNT(*) as total FROM versions WHERE created >= '{$after}' AND created <= '{$before}'", true)) {
            $ret['dailyVersions'] = $buf[0][0]['total'];
            unset($buf);
        }

        if ($buf = $model->query("SELECT COUNT(*) as total FROM users WHERE created >= '{$after}' AND created <= '{$before}'", true)) {
            $ret['dailyUsers'] = $buf[0][0]['total'];
            unset($buf);
        }

        if ($buf = $model->query("SELECT COUNT(*) as total FROM reviews WHERE created >= '{$after}' AND created <= '{$before}'", true)) {
            $ret['dailyReviews'] = $buf[0][0]['total'];
            unset($buf);
        }

        if ($buf = $model->query("SELECT COUNT(*) as total FROM previews WHERE created >= '{$after}' AND created <= '{$before}'", true)) {
            $ret['dailyImages'] = $buf[0][0]['total'];
            unset($buf);
        }

        if ($buf = $model->query("SELECT SUM(count) as count FROM download_counts WHERE date = '{$yesterday}'", true)) {
            $ret['dailyDownloads'] = $buf[0][0]['count'];
            unset($buf);
        }

        return $ret;
    }
}
?>
