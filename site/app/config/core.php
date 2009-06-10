<?php
/* SVN FILE: $Id: core.php,v 1.1.1.1 2006/08/14 23:54:56 sancus%off.net Exp $ */
/**
 * This is core configuration file.
 *
 * Use it to configure core behaviour ofCake.
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2006, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.app.config
 * @since			CakePHP v 0.2.9
 * @version			$Revision: 1.1.1.1 $
 * @modifiedby		$LastChangedBy: phpnut $
 * @lastmodified	$Date: 2006/08/14 23:54:56 $
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Include non-default defines from config-local.php.
 * If you want to set global variables just for your install, use this method.
 */
if (file_exists(ROOT.DS.APP_DIR.DS.'config'.DS.'config-local.php')) {
    require_once('config-local.php');
}

/**
 * If you do not have mod rewrite on your system
 * or if you prefer to use CakePHP pretty urls.
 * uncomment the line below.
 * Note: If you do have mod rewrite but prefer the
 * CakePHP pretty urls, you also have to remove the
 * .htaccess files
 * release/.htaccess
 * release/app/.htaccess
 * release/app/webroot/.htaccess
 */
    //define ('BASE_URL', env('SCRIPT_NAME'));
/**
 * Set debug level here:
 * - 0: production
 * - 1: development
 * - 2: full debug with sql
 * - 3: full debug with sql and dump of the current object
 *
 * In production, the "flash messages" redirect after a time interval.
 * With the other debug levels you get to click the "flash message" to continue.
 *
 */
if (!defined('DEBUG'))
  define('DEBUG', 0);

/**
 * Development mode
 * If this is off, you will not see links to not-yet-released features and such.
 */
if (!defined('DEV'))
    define('DEV', false);

/**
 * Turn of caching checking wide.
 * You must still use the controller var cacheAction inside you controller class.
 * You can either set it controller wide, or in each controller method.
 * use var $cacheAction = true; or in the controller method $this->cacheAction = true;
 */
	define('CACHE_CHECK', false);

/**
 * Error constant. Used for differentiating error logging and debugging.
 * Currently PHP supports LOG_DEBUG
 */
	define('LOG_ERROR', 2);
/**
 * CakePHP includes 3 types of session saves
 * database or file. Set this to your preferred method.
 * If you want to use your own save handler place it in
 * app/config/name.php DO NOT USE file or database as the name.
 * and use just the name portion below.
 *
 * Setting this to cake will save files to /cakedistro/tmp directory
 * Setting it to php will use the php default save path
 * Setting it to database will use the database
 *
 *
 */
	define('CAKE_SESSION_SAVE', 'database');
/**
 * If using you own table name for storing sessions
 * set the table name here.
 * DO NOT INCLUDE PREFIX IF YOU HAVE SET ONE IN database.php
 *
 */
	define('CAKE_SESSION_TABLE', 'cake_sessions');
/**
 * Set a random string of used in session.
 *
 */
	define('CAKE_SESSION_STRING', 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi');
/**
 * Set the name of session cookie
 *
 */
	define('CAKE_SESSION_COOKIE', 'AMOv3');
/**
 * Set level of Cake security.  This is now set in the app_controller, because it
 * needs to be dynamic based on the page we are on.
 *
 */
	//define('CAKE_SECURITY', 'high');
/**
 * Set Cake Session time out.
 * If CAKE_SECURITY define is set
 * high: multiplied by 10
 * medium: is multiplied by 100
 * low is: multiplied by 300
 *
 *  Number below is seconds.
 */
	define('CAKE_SESSION_TIMEOUT', '8640');
/**
 * Uncomment the define below to use cake built in admin routes.
 * You can set this value to anything you want.
 * All methods related to the admin route should be prefixed with the
 * name you set CAKE_ADMIN to.
 * For example: admin_index, admin_edit
 */
//	define('CAKE_ADMIN', 'admin');
/**
 *  The define below is used to turn cake built webservices
 *  on or off. Default setting is off.
 */
	define('WEBSERVICES', 'off');
/**
 * Compress output CSS (removing comments, whitespace, repeating tags etc.)
 * This requires a/var/cache directory to be writable by the web server (caching).
 * To use, prefix the CSS link URL with '/ccss/' instead of '/css/' or use Controller::cssTag().
 */
	define('COMPRESS_CSS', false);
/**
 * If set to true, helpers would output data instead of returning it.
 */
	define('AUTO_OUTPUT', false);
/**
 * If set to false, session would not automatically be started.
 */
	define('AUTO_SESSION', false);
/**
 * Set the max size of file to use md5() .
 */
	define('MAX_MD5SIZE', (5 * 1024) * 1024);
/**
 * To use Access Control Lists with Cake...
 */
	define('ACL_CLASSNAME', 'DB_ACL');
	define('ACL_FILENAME', 'dbacl' . DS . 'db_acl');

/** modified gettext
 *
 *  @param $to_translate -- token to look for locale translation
 *  @param $fall_translation -- if no translation for current locale output this
 *  
 *  @return translation -- if no fallback and no translation then uses English
 *
 */
function ___($to_translate, $fallback_translation ="") {
    if(_($to_translate) != $to_translate) return _($to_translate);
    if($fallback_translation != "") return $fallback_translation;

    global $valid_languages;

    putenv('LANG=en_US');
    setlocale(LC_COLLATE, 'en_US');
    setlocale(LC_MONETARY, 'en_US');
    setlocale(LC_NUMERIC, 'en_US');
    setlocale(LC_TIME, 'en_US');
    if (defined('LC_MESSAGES')) {
        setlocale(LC_MESSAGES, 'en_US');
    }
    $output = _($to_translate);

    $lang = $valid_languages[LANG];
    putenv("LANG=".$lang);
    setlocale(LC_COLLATE, $lang);
    setlocale(LC_MONETARY, $lang);
    setlocale(LC_NUMERIC, $lang);
    setlocale(LC_TIME, $lang);
    if (defined('LC_MESSAGES')) {
        setlocale(LC_MESSAGES, $lang);
    }

    return $output;	
}	

/** modified ngettext
 *
 *  @param $to_translate -- token to look for locale translation
 *  @param $plural_to_translate -- plural token; useless for AMO, used merely so xgettext works 
 *  @param $fall_translation -- if no translation for current locale output this
 *  
 *  @return translation -- if no fallback and no translation then uses English
 *
 */
function n___($to_translate, $plural_to_translate, $num, $fallback_translation ="") {
    $translation = ngettext($to_translate, $plural_to_translate, $num);
    if($translation != $to_translate) return $translation;
    if($fallback_translation != "") return $fallback_translation;

    global $valid_languages;

    putenv('LANG=en_US');
    setlocale(LC_COLLATE, 'en_US');
    setlocale(LC_MONETARY, 'en_US');
    setlocale(LC_NUMERIC, 'en_US');
    setlocale(LC_TIME, 'en_US');
    if (defined('LC_MESSAGES')) {
        setlocale(LC_MESSAGES, 'en_US');
    }
    $output = ngettext($to_translate, $plural_to_translate, $num);

    $lang = $valid_languages[LANG];
    putenv("LANG=".$lang);
    setlocale(LC_COLLATE, $lang);
    setlocale(LC_MONETARY, $lang);
    setlocale(LC_NUMERIC, $lang);
    setlocale(LC_TIME, $lang);
    if (defined('LC_MESSAGES')) {
        setlocale(LC_MESSAGES, $lang);
    }

    return $output;	
}	
?>
