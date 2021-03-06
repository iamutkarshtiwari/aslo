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
   * Portions created by the Initial Developer are Copyright (C) 2009
   * the Initial Developer. All Rights Reserved.
   *
   * Contributor(s):
   *   RJ Walsh <rwalsh@mozilla.com>
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

class TestCase extends AppModel
{
    var $name = 'TestCase';
    var $belongsTo = array('TestGroup');
    var $hasMany_full = array(
        'TestResult' =>
        array(
            'className'   => 'TestResult',
            'conditions'  => '',
            'order'       => '',
            'limit'       => '',
            'foreignKey'  => 'test_case_id',
            'dependent'   => true,
            'exclusive'   => false,
            'finderSql'   => ''
        )
    );

    /**
     * Tie into Cake's callback to populate the array with localized strings
     * @param array $results the results of the find operation
     * @return array our modified results
     */
    function afterFind($results) {

        // Don't modify these arrays, as they are used
        // as a translation mechanism to localize test names
        $names = array(
            '11' => ___('General Verification Tests'),
            '12' => ___('install.rdf Verification Tests'),
            '13' => ___('File Type Verification', 'test_case_name_all_general_verifyFileTypes'),
            '14' => ___('JavaScript Namespace Pollution Tests'),
            '21' => ___('Unsafe JavaScript Tests'),
            '22' => ___('Unsafe Settings Tests'),
            '23' => ___('Remote JavaScript Tests'),
            '24' => ___('Common Library Checksum Tests'),
            '31' => ___('L10n Completeness Check'),
            '121' => ___('Geolocation Check'),
            '122' => ___('Conduit Toolbar Check'),
            '211' => ___('File Layout Check', 'test_case_name_dictionary_general_verifyFileLayout'),
            '212' => ___('File Type Verification', 'test_case_name_all_general_verifyFileTypes'),
            '213' => ___('SeaMonkey Valid Files Check'),
            '221' => ___('install.js Verification'),
            '311' => ___('File Layout Check', 'test_case_name_dictionary_general_verifyFileLayout'),
            '312' => ___('File Type Verification', 'test_case_name_all_general_verifyFileTypes'),
            '321' => ___('Unsafe HTML Check'),
            '322' => ___('Remote Loading Check'),
            '323' => ___('chrome.manifest Check', 'test_case_name_langpack_security_checkChromeManifest'),
            '411' => ___('File Layout Check', 'test_case_name_dictionary_general_verifyFileLayout'),
            '421' => ___('chrome.manifest Check', 'test_case_name_langpack_security_checkChromeManifest'),
            '511' => ___('Opensearch Format Check'),
            '521' => ___('UpdateURL Check')
        );

        // Might not even need this ...
        $descriptions = array(
            '11' => ___('General Verification Tests'),
            '12' => ___('install.rdf Verification Tests'),
            '13' => ___('File Type Verification', 'test_case_name_all_general_verifyFileTypes'),
            '14' => ___('JavaScript Namespace Pollution Tests'),
            '21' => ___('Unsafe JavaScript Tests'),
            '22' => ___('Unsafe Settings Tests'),
            '23' => ___('Remote JavaScript Tests'),
            '24' => ___('Common Library Checksum Tests'),
            '31' => ___('L10n Completeness Check'),
            '121' => ___('Geolocation Check'),
            '122' => ___('Conduit Toolbar Check'),
            '211' => ___('File Layout Check', 'test_case_name_dictionary_general_verifyFileLayout'),
            '212' => ___('File Type Verification', 'test_case_name_all_general_verifyFileTypes'),
            '213' => ___('SeaMonkey Valid Files Check'),
            '221' => ___('install.js Verification'),
            '311' => ___('File Layout Check', 'test_case_name_dictionary_general_verifyFileLayout'),
            '312' => ___('File Type Verification', 'test_case_name_all_general_verifyFileTypes'),
            '321' => ___('Unsafe HTML Check'),
            '322' => ___('Remote Loading Check'),
            '323' => ___('chrome.manifest Check', 'test_case_name_langpack_security_checkChromeManifest'),
            '411' => ___('File Layout Check', 'test_case_name_dictionary_general_verifyFileLayout'),
            '421' => ___('chrome.manifest Check', 'test_case_name_langpack_security_checkChromeManifest'),
            '511' => ___('Opensearch Format Check'),
            '521' => ___('UpdateURL Check')
        );


        foreach ($results as $key => $result) {
            if (!empty($result['TestCase'][0])) { // Doing a find all...
                foreach ($result['TestCase'] as $idx => $data) {
                    $id = $result['TestCase'][$idx]['id'];
                    $results[$key]['TestCase'][$idx]['name'] = $names[$id];
                    $results[$key]['TestCase'][$idx]['description'] = $descriptions[$id];
                }
            } else {
                // Temporary hook for missing data
                if (empty($result['TestCase'])) return $results;

                $id = $result['TestCase']['id'];
                $results[$key]['TestCase']['name'] = $names[$id];
                $results[$key]['TestCase']['description'] = $descriptions[$id];
            }
        }
        return $results;
    }
}
?>
