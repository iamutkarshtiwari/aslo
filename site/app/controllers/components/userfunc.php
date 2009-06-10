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
 * Frederic Wenzel <fwenzel@mozilla.com>
 * Portions created by the Initial Developer are Copyright (C) 2008
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
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

/**
 * The User Functions Component provides helper functions for user/login related stuff.
 */
class UserfuncComponent extends Object {
    var $components = array('Session');
    var $controller;

   /**
    * Save a reference to the controller on startup
    * @param object &$controller the controller using this component
    */
    function startup(&$controller) {
        $this->controller =& $controller;
    }
    
   /**
    * Toggles the currently logged in user's sandbox preference in the DB
    * if necessary.
    * @param bool sandbox enabled?
    * @return bool success
    */
    function toggleSandboxPref($enable = false) {
        if (!$this->Session->check('User'))
            return;
        
        $session = $this->Session->read('User');
        if ($session['sandboxshown'] == $enable)
            return;
        
        /* store new preference */
        if (!isset($this->controller->User)) {
            loadModel('User');
            $this->controller->User =& new User();
        }
        $newpref = array('User' => array(
            'id'            => $session['id'],
            'sandboxshown'  => ($enable?'1':'0')
            ));
        if (!$this->controller->User->save($newpref))
            return false;
        
        /* re-fetch session information */
        $newprofile = $this->controller->User->findById($session['id']);
        if (!empty($newprofile)) {
            $this->Session->write('User', $newprofile['User']);
            return true;
        } else {
            return false;
        }
    }
    
}
?>
