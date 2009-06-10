<?php
/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/e
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is addons.mozilla.org site.
 *
 * The Initial Developer of the Original Code is
 * Justin Scott <fligtar@gmail.com>.
 * Portions created by the Initial Developer are Copyright (C) 2007
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

class LocalizersController extends AppController
{
    var $name = 'Localizers';
    var $uses = array('Addon', 'Addontype', 'Application', 'Approval', 'Appversion', 'Eventlog', 'Platform','Tag', 'Translation', 'User', 'Version');
    var $components = array('Amo', 'Audit', 'Error', 'Pagination');
    var $helpers = array('Html', 'Javascript', 'Pagination');
    
    var $writeAccess = false;

   /**
    * Require login for all actions
    */
    function beforeFilter() {
        // Disable ACLs because permissions are assigned per locale here
        $this->SimpleAuth->enabled = false;
        $this->SimpleAcl->enabled = false;
        
        //beforeFilter() is apparently called before components are initialized. Cake++
        $this->Amo->startup($this);
        $this->SimpleAuth->startup($this);
        $this->SimpleAcl->startup($this);
        
        $session = $this->Session->read('User');
        
        //Make sure user is a localizer of SOMETHING
        if (!$this->SimpleAcl->actionAllowed('Localizers', '%', $session)) {
            $this->Amo->accessDenied();
        }
        
        $this->Amo->checkLoggedIn();
        
        $this->Amo->clean($this->data);
        
        $this->layout = 'mozilla';
        $this->pageTitle = 'Localizer Control Panel :: '.sprintf(_('addons_home_pagetitle'), APP_PRETTYNAME);
        
        $this->cssAdd = array('localizers');
        $this->publish('cssAdd', $this->cssAdd);

        $this->breadcrumbs = array('Localizer Control Panel' => '/localizers/index');
        $this->publish('breadcrumbs', $this->breadcrumbs);
	
        $this->publish('subpagetitle', 'Localizer Control Panel');

        // disable query caching so devcp changes are visible immediately
        foreach ($this->uses as $_model) {
            $this->$_model->caching = false;
        }
        
        global $valid_languages;
        //First, see if locale was submitted by form
        if (!empty($_GET['userlang']) && array_key_exists($_GET['userlang'], $valid_languages)) {
            $this->Session->write('Localizers', array('userlang' => $_GET['userlang']));
            define('USERLANG', $_GET['userlang']);
        }
        //Next, try looking in the session
        elseif ($lsession = $this->Session->read('Localizers')) {
            define('USERLANG', $lsession['userlang']);
        }
        
        //Determine language access based on group membership
        if ($this->SimpleAcl->actionAllowed('Admin', 'EditAnyLocale', $session)) {
            $this->writeAccess = true;
        }
        else {
            foreach ($session['Group'] as $group) {
                if (strpos($group['rules'], 'Localizers') !== false) {
                    $rules = explode(':', $group['rules']);
                    
                    //If user doesn't have a lang set yet, we set this one
                    if (!defined('USERLANG')) {
                        define('USERLANG', $rules[1]);
                    }
                    
                    //If user in group, can write
                    if (USERLANG == $rules[1]) {
                        $this->writeAccess = true;
                    }
                }
            }
        }
        
        //If user is not in any locale groups, use current locale
        if (!defined('USERLANG')) {
            define('USERLANG', LANG);
        }
        
        $this->set('writeAccess', $this->writeAccess);
    }
    
   /**
    * Index - summary
    */
    function index() {
        $this->summary();
    }
    
   /**
    * Summary
    */
    function summary() {
        $this->cssAdd = array('summary', 'localizers');
        $this->publish('cssAdd', $this->cssAdd);
        
        $ids = array();
        
        //Pull application translation ids
        if ($applications = $this->Application->query("SELECT name, shortname FROM applications")) {
            foreach ($applications as $application) {
                if (!empty($application['applications']['name'])) {
                    $ids[] = $application['applications']['name'];
                }
                if (!empty($application['applications']['shortname'])) {
                    $ids[] = $application['applications']['shortname'];
                }
            }
        }
        
        //Pull tag translation ids
        if ($tags = $this->Tag->query("SELECT name, description FROM tags")) {
            foreach ($tags as $tag) {
                if (!empty($tag['tags']['name'])) {
                    $ids[] = $tag['tags']['name'];
                }
                if (!empty($tag['tags']['description'])) {
                    $ids[] = $tag['tags']['description'];
                }
            }
        }
        
        //Pull platform translation ids
        if ($platforms = $this->Platform->query("SELECT name, shortname FROM platforms")) {
            foreach ($platforms as $platform) {
                if (!empty($platform['platforms']['name'])) {
                    $ids[] = $platform['platforms']['name'];
                }
                if (!empty($platform['platforms']['shortname'])) {
                    $ids[] = $platform['platforms']['shortname'];
                }
            }
        }
        
        //Locale stats
        $ids = implode(', ', $ids);
        global $valid_languages;
        foreach ($valid_languages as $lang => $language) {
            $localeStats[$lang] = $this->Translation->query("SELECT COUNT(*) FROM translations WHERE id IN ({$ids}) AND locale='{$lang}'");
        }

        $this->set('localeStats', $localeStats);
        
        //Recent activity
        $logs = $this->Eventlog->findAll(array('type' => 'l10n'), null, 'Eventlog.created DESC', 5);
        $logs = $this->Audit->explainLog($logs);
        $this->set('logs', $logs);
        
        $this->set('page', 'summary');
        $this->render('summary');
    }
    
   /**
    * Application Localization
    */
    function applications() {
        $this->breadcrumbs['Application Localization'] = '/localizers/application';
        $this->set('breadcrumbs', $this->breadcrumbs);
        
        if (!empty($this->data['Application'])) {
            //Make sure user has write access
            if (!$this->writeAccess)  {
                //Log
                $this->Eventlog->log($this, 'security', 'modify_other_locale', null, 1, null, null, USERLANG);
                
                $this->flash('You do not have permission to modify this locale!', '/localizers/applications');
                return;
            }
            
            $this->Application->setLang(USERLANG, $this);
            foreach ($this->data['Application'] as $id => $data) {
                $this->Application->id = $id;
                $this->Application->save($data);
            }
            
            //Log l10n action
            $this->Eventlog->log($this, 'l10n', 'update_applications', null, 1, null, null, USERLANG);
            
            $this->flash('Translations updated!', '/localizers/applications');
            return;
        }
        
        $this->Application->setLang(USERLANG, $this);
        $applications[USERLANG] = $this->Application->findAll(null, null, null, null, null, 0);
        
        $this->Application->setLang('en-US', $this);
        $applications['en-US'] = $this->Application->findAll(null, null, null, null, null, 0);
        
        $this->set('applications', $applications);
        $this->set('page', 'applications');
        $this->render('applications');
    }
    
   /**
    * Category Localization
    */
    function tags() {
        $this->breadcrumbs['Tag Localization'] = '/localizers/tags';
        $this->set('breadcrumbs', $this->breadcrumbs);
        
        if (!empty($this->data['Tag'])) {
            //Make sure user has write access
            if (!$this->writeAccess)  {
                //Log
                $this->Eventlog->log($this, 'security', 'modify_other_locale', null, 1, null, null, USERLANG);
                
                $this->flash('You do not have permission to modify this locale!', '/localizers/tags');
                return;
            }
            
            $this->Tag->setLang(USERLANG, $this);
            foreach ($this->data['Tag'] as $id => $data) {
                $this->Tag->id = $id;
                $this->Tag->save($data);
            }
            
            //Log l10n action
            $this->Eventlog->log($this, 'l10n', 'update_tags', null, 1, null, null, USERLANG);
            
            $this->flash('Translations updated!', '/localizers/tags');
            return;
        }
        
        $this->Tag->setLang(USERLANG, $this);
        $tags[USERLANG] = $this->Tag->findAll(null, null, null, null, null, 0);
        
        $this->Tag->setLang('en-US', $this);
        $tags['en-US'] = $this->Tag->findAll(null, null, null, null, null, 0);
        
        $this->set('tags', $tags);
        $this->set('page', 'tags');
        $this->render('tags');
    }
    
   /**
    * Platform Localization
    */
    function platforms() {
        //Only admins can modify platforms
        if (!$this->SimpleAcl->actionAllowed('Admin', 'lists', $this->Session->read('User'))) {
            $this->Amo->accessDenied();
            return;
        }
        
        $this->breadcrumbs['Platform Localization'] = '/localizers/platforms';
        $this->set('breadcrumbs', $this->breadcrumbs);
        
        if (!empty($this->data['Platform'])) {
            //Make sure user has write access
            if (!$this->writeAccess)  {
                //Log
                $this->Eventlog->log($this, 'security', 'modify_other_locale', null, 1, null, null, USERLANG);
                
                $this->flash('You do not have permission to modify this locale!', '/localizers/platforms');
                return;
            }
            
            $this->Platform->setLang(USERLANG, $this);
            foreach ($this->data['Platform'] as $id => $data) {
                $this->Platform->id = $id;
                $this->Platform->save($data);
            }
            
            //Log l10n action
            $this->Eventlog->log($this, 'l10n', 'update_platforms', null, 1, null, null, USERLANG);

            $this->flash('Translations updated!', '/localizers/platforms');
            return;
        }
        
        $this->Platform->setLang(USERLANG, $this);
        $platforms[USERLANG] = $this->Platform->findAll(null, null, null, null, null, 0);
        
        $this->Platform->setLang('en-US', $this);
        $platforms['en-US'] = $this->Platform->findAll(null, null, null, null, null, 0);
        
        $this->set('platforms', $platforms);
        $this->set('page', 'platforms');
        $this->render('platforms');
    }
    
   /**
    * Display log for locale
    */
    function logs() {
        $this->breadcrumbs['Log'] = '/localizers/logs';
        $this->set('breadcrumbs', $this->breadcrumbs);
        
        $logs = $this->Eventlog->findAll(array('notes' => USERLANG), null, 'Eventlog.created DESC');
        
        $logs = $this->Audit->explainLog($logs);
        
        $this->set('logs', $logs);
        
        $this->set('page', 'logs');
        $this->render('logs');
    }
    
   /**
    * Pages
    */
    function pages() {
        $this->breadcrumbs['Pages'] = '/localizers/pages';
        $this->set('breadcrumbs', $this->breadcrumbs);
        
        //This is not automatic because not all pages should be translated, yo
        $pages = array(
                       0 => array('page' => 'developer_agreement.thtml',
                                  'url' => '/developers/add'
                                 ),
                       1 => array('page' => 'error404.thtml',
                                  'url' => '/thispage/doesnotexist'
                                 ),
                       2 => array('page' => 'nomination.thtml',
                                  'url' => ''
                                 ),
                       3 => array('page' => 'policy.thtml',
                                  'url' => '/pages/policy'
                                 ),
                       4 => array('page' => 'sandbox.thtml',
                                  'url' => '/pages/sandbox'
                                 ),
                       5 => array('page' => 'submission_help.thtml',
                                  'url' => '/pages/submissionhelp'
                                 )
                      );
        
        $images = array(
                        0 => array('image' => 'sandbox-review.png',
                                   'url' => '/pages/sandbox'
                                  )
                       );
        
        $userlang = str_replace('-', '_', USERLANG);
        
        //Check for page existence
        foreach ($pages as $k => $page) {
            if (file_exists(APP.'locale'.DS.$userlang.DS.'pages'.DS.$page['page'])) {
                $pages[$k]['exists'] = true;
            }
            else {
                $pages[$k]['exists'] = false;
            }
        }
        
        //Check for image existence
        foreach ($images as $k => $image) {
            if (file_exists(APP.'locale'.DS.$userlang.DS.'images'.DS.$image['image'])) {
                $images[$k]['exists'] = true;
            }
            else {
                $images[$k]['exists'] = false;
            }
        }
        
        $this->set('pages', $pages);
        $this->set('images', $images);
        
        $this->set('page', 'pages');
        $this->render('pages'); 
    }
    
   /**
    * Gettext
    */
    function gettext() {
        $this->breadcrumbs['Gettext'] = '/localizers/gettext';
        $this->set('breadcrumbs', $this->breadcrumbs);
        
        $userlang = str_replace('-', '_', USERLANG);
        
        //Get .po files
        $enus = file_get_contents(APP.'locale'.DS.'en_US'.DS.'LC_MESSAGES'.DS.'messages.po');
        $locale = file_get_contents(APP.'locale'.DS.$userlang.DS.'LC_MESSAGES'.DS.'messages.po');
        
        //Remove header information
        $enus = substr($enus, strpos($enus, "\n\n"));
        $locale = substr($locale, strpos($locale, "\n\n"));
        
        //Parse strings
        preg_match_all('/msgid\s+"(.+?)"\s*(msgid_plural\s+"(.+?)"\s*)?(msgstr(\[\d+\])? "(.+?)"\s*)+(#|msg)/is', $enus, $enusMatches, PREG_SET_ORDER);
        preg_match_all('/msgid\s+"(.+?)"\s*(msgid_plural\s+"(.+?)"\s*)?(msgstr(\[\d+\])? "(.+?)"\s*)+(#|msg)/is', $locale, $localeMatches, PREG_SET_ORDER);
        
        //Make pretty key => value arrays
        foreach ($enusMatches as $enusMatch) {
            $enusStrings[$enusMatch[1]] = $enusMatch[6];
        }
        foreach ($localeMatches as $localeMatch) {
            $localeStrings[$localeMatch[1]] = $localeMatch[6];
        }
        
        //Compare
        $untranslated = array_intersect_assoc($enusStrings, $localeStrings);
        
        $this->set('untranslated', $untranslated);
        $this->set('total', count($enusStrings));
        $this->set('userlang', $userlang);
        
        $this->set('page', 'gettext');
        $this->render('gettext');
    }
}

?>
