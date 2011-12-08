<?php
/**
 * File List extension.
 *
 * Author: Jens Nyman <nymanjens.nj@gmail.com> (VTK Ghent)
 *
 * This extension implements a new tag, <filelist>, which generates a list of
 * all images or other media that was uploaded to the page. Also, the tag adds
 * an input field to add a new file.
 *
 * Usage:
 *     <filelist/>
 *
 */

if (!defined('MEDIAWIKI')) die("Mediawiki not set");

/****************** EXTENSION SETTINGS ******************/
// configuration array of extension
$wgFileListConfig = array(
    'upload_anonymously' => false,
    'defaultdeleteperm'  => true,
    'hideForm'           => true,
);

$wgExtensionCredits['parserhook'][] = array(
    'name'           => 'FileList',
    'author'         => 'Jens Nyman, Simon Peeters (VTK Ghent)',
	'descriptionmsg' => 'fl_credits_desc',
	'url'            => 'https://github.com/SimonPe/FileList',
);

$wgAutoloadClasses['FileList'] = dirname(__FILE__) . '/FileList.body.php';
$wgAutoloadClasses['FlUpload'] = dirname(__FILE__) . '/includes/uploads.php';
$wgAutoloadClasses['FlAction'] = dirname(__FILE__) . '/includes/actions.php';
$wgAutoloadClasses['FlFile']   = dirname(__FILE__) . '/includes/file.php';
$wgExtensionMessagesFiles['FileList'] = dirname( __FILE__ ) . '/FileList.i18n.php';

/****************** SET HOOKS ******************/

$wgExtensionFunctions[] = 'wfFileList';
function wfFileList() {
    global $wgParser;
    $wgParser->setHook('filelist',  'FileList::onHookHtml');
    Hooks::register('UploadCreateFromRequest', 'FlUpload::attachHook');
    Hooks::register('UnknownAction', 'FlAction::onUnknownAction');
    Hooks::register('SpecialMovepageAfterMove', 'FlAction::onSpecialMovepageAfterMove');
    if (FLGetConf('defaultdeleteperm'))
        Hooks::register('UserCanDeletFile', 'FlAction::onUserCanDeletFile');
}
$wgResourceModules['ext.FileList'] = array(
    'scripts' => array( 'js/form.js', 'js/tableSort.js', 'js/list.js', 'js/iAjaxForm.js'),
    'messages' => array( 'fl_empty_file', 'fl_remove_confirm' ),
    'localBasePath' => dirname( __FILE__ ),
    'remoteExtPath' => basename(dirname(__FILE__)),
);


function FLGetConf($key) {
    global $wgFileListConfig;
    if(isset($wgFileListConfig[$key]))
        return $wgFileListConfig[$key];
    if(isset($GLOBALS['wg'.$key]))
        return $GLOBALS['wg'.$key];
    switch ($key) {
      case 'ExtPath':
        global $wgExtensionAssetsPath;
        return $wgExtensionAssetsPath . '/' . basename(dirname(__FILE__)) . '/';
      default:
        return -1;
    }
}
/**
 * get prefix from page name
 * 
 * @param string $pagename
 * @return string
 */
function get_prefix_from_page_name($pageName) {
    return str_replace(' ', '_', $pageName) . '_-_';
}
