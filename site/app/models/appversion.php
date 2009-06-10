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
 * Portions created by the Initial Developer are Copyright (C) 2006
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *   Andrei Hajdukewycz <sancus@off.net> (Original Author)
 *   Justin Scott <fligtar@mozilla.com>
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

class Appversion extends AppModel
{
    var $name = 'Appversion';
    var $belongsTo = 'Application';
    
    /**
     * Retrieves all appversions for all applications
     */
    function getAllVersions() {
        $_versionlist = $this->findAll(null, null, null, null, null, -1);
        
        $versions = array();
        
        if (!empty($_versionlist)) {
            foreach ($_versionlist as $version) {
                $versions[$version['Appversion']['application_id']][$version['Appversion']['id']] = $version['Appversion']['version'];
            }
        }
        
        return $versions;
    }
    
    /**
     * Retrieves all appversions that start with the passed version.
     * For example, passing 3.1 would return 3.1a1pre, 3.1a1, 3.1b1, 3.1.*
     */
    function getRelatedVersions($version) {
        $_versionlist = $this->findAll("Appversion.version LIKE '{$version}%'", null, null, null, null, -1);
        
        $versions = array();
        
        if (!empty($_versionlist)) {
            foreach ($_versionlist as $version) {
                $versions[$version['Appversion']['application_id']][$version['Appversion']['id']] = $version['Appversion']['version'];
            }
        }
        
        return $versions;
    }
}
?>
