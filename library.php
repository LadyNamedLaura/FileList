<?php
/**
 * Functions file
 * 
 * Author: Jens Nyman <nymanjens.nj@gmail.com> (VTK Ghent)
 * 
 */

/**
 * Constants
 */
define('EXAM_STR', 'Examen_....-...._-_');

/**
 * returns wether user is allowed to delete files
 * 
 * @param string $filename
 * @return bool
 */
function this_user_is_allowed_to_delete($file){
    global $wgUser;
    $groups = $wgUser->getGroups();
    $username = $wgUser->getName();
    
    // get file user
    $file_user = $file->getUser();
    
    // allowed to delete own files
    if($file_user == $username)
        return true;
    
    // admins can delete everything
    return in_array('sysop', $groups);
}

/**
 * Returns a human readable filesize
 *
 * @author      wesman20 (php.net)
 * @author      Jonas John
 * @version     0.3
 * @link        http://www.jonasjohn.de/snippets/php/readable-filesize.htm
 */
function human_readable_filesize($size) {
 
    // Adapted from: http://www.php.net/manual/en/function.filesize.php
 
    $mod = 1024;
 
    $units = explode(' ','B kB MB GB TB PB');
    for ($i = 0; $size > $mod; $i++) {
        $size /= $mod;
    }
 
    return round($size, 2) . ' ' . $units[$i];
}

/**
 * get page URL
 *
 * @param string $title
 * @return string
 */
function page_link_by_title($title, $query = ''){
    $title = Title::newFromText($title);
    return $title->getFullURL($query);
}

/**
 * get wiki root url (for example for linking to images)
 *
 * @param string $title
 * @return string
 */
function get_index_url(){
    $title = Title::newMainPage();
    return dirname($title->getFullURL("query=")) . '/';
}

/**
 * Userfriendly time string
 * 
 * @param int $time
 * @return string
 */
function time_to_string($time){
	if(date('Y-m-d', $time) == date('Y-m-d') )
		return date("H:i", $time);
	if(date('z')-1 == date('z',$time) && date('Y') == date('Y',$time) )
		return wfMsgForContent('yesterday').", ".date("H:i", $time);
	if(date('z')-6 <= date('z',$time) && date('Y') == date('Y',$time) )
		return wfMsgForContent(date("D", $time)) . date(", H:i", $time);
	if(time() - $time < 60*60*24*50)
		return wfMsgForContent(date("D", $time)) . date(", j ", $time) . wfMsgForContent(date("M", $time));
	if(date('y', $time) == date('y'))
		return date("j ", $time) . wfMsgForContent(date("M", $time)) . date(" 'y", $time);
	return wfMsgForContent(date("M", $time)) . date(" 'y", $time);
}

/**
 * pagename is exam page
 * 
 * @param string $pagename
 * @return bool
 */
function pagename_is_exam_page($pagename) {
    $pos = strpos($pagename, '_-_');
    if($pos === false)
        return false;
    $exam_str = substr($pagename, $pos + strlen('_-_'), strlen(EXAM_STR));
    return preg_match(sprintf("/^%s$/", EXAM_STR), $exam_str);
}

/**
 * list files of page
 * 
 * @param string $pagename
 * @return array
 */
function list_files_of_page($pagename) {
    // Query the database.
    $dbr =& wfGetDB(DB_SLAVE);
    $res = $dbr->select(
        array('image'),
        array('img_name','img_media_type','img_user_text','img_description', 'img_size',
              'img_timestamp','img_major_mime','img_minor_mime'),
        '',
        '',
        array('ORDER BY' => 'img_timestamp')
        );
    if ($res === false)
        return array();

    // Convert the results list into an array.
    $list = array();
    $prefix = get_prefix_from_page_name($pagename);
    $exam_page = pagename_is_exam_page($prefix);
    while ($x = $dbr->fetchObject($res)) {
        if( strtolower(substr($x->img_name, 0, strlen($prefix))) == strtolower($prefix))
            if( $exam_page || !pagename_is_exam_page($x->img_name) ) // remove exam-files from non-exam pages
                $list[] = RepoGroup::singleton()->getLocalRepo()->newFileFromRow($x);
    }

    // Free the results.
    $dbr->freeResult($res);

    return $list;
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
