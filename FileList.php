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
);

// extension on the left corresponds with a .gif icon on the right
$fileListCorrespondingImages = array(
    'pdf' =>  'pdf', // .gif
    'rar' =>  'rar',
    '7z' =>   'rar',
    'gz' =>   'rar',
    'zip' =>  'zip',
    'txt' =>  'txt',
    'doc' =>  'doc',
    'docx' => 'doc',
    'ppt' =>  'ppt',
    'pptx' => 'ppt',
    'xls' =>  'xls',
    'xlsx' => 'xls',
    'odt' =>  'odt',
    'odp' =>  'odt',
    'ods' =>  'odt',
    'jpg' =>  'gif',
    'jpeg' => 'gif',
    'gif' =>  'gif',
    'png' =>  'gif',
);

/****************** SET HOOKS ******************/
// filelist tag
$wgExtensionFunctions[] = 'wfFileList';
// before upload: remove user info (ensure anonymity)
$wgHooks['UploadForm:BeforeProcessing'][] = 'fileListUploadBeforeProcessing';
// upload complete: redirect appropriately
$wgHooks['SpecialUploadComplete'][] = 'fileListUploadComplete';
// delete action
$wgHooks['UnknownAction'][] = 'actionDeleteFile';
// move page hook
$wgHooks['SpecialMovepageAfterMove'][] = 'fileListMovePage';
// insert output in file
$wgHooks['ParserAfterTidy'][] = 'FileListParserAfterTidy';
// credits
$wgExtensionCredits['parserhook'][] = array(
    'name'           => 'FileList',
    'author'         => 'Jens Nyman, Simon Peeters (VTK Ghent)',
	'descriptionmsg' => 'fl_credits_desc',
	'url'            => 'https://github.com/SimonPe/FileList',
);
$wgResourceModules['ext.FileList'] = array(
    // JavaScript and CSS styles. To combine multiple file, just list them as an array.
    'scripts' => array( 'js/form.js', 'js/tableSort.js', 'js/list.js'),

    // When your module is loaded, these messages will be available through mw.msg()
    'messages' => array( 'fl_empty_file', 'fl_remove_confirm' ),

    // If your scripts need code from other modules, list their identifiers as dependencies
    // and ResourceLoader will make sure they're loaded before you.
    // You don't need to manually list 'mediawiki' or 'jquery', which are always loaded.
    
    // ResourceLoader needs to know where your files are; specify your
    // subdir relative to "/extensions" (or $wgExtensionAssetsPath)
    'localBasePath' => dirname( __FILE__ ),
    'remoteExtPath' => 'FileList'
);


// internationalization file
$wgExtensionMessagesFiles['myextension'] = dirname( __FILE__ ) . '/FileList.i18n.php';
//require_once( dirname(__FILE__) . '/FileList.i18n.php' );

// functions
require_once( dirname(__FILE__) . '/library.php' );

/**
 * Setup Medialist extension.
 * Sets a parser hook for <filelist/>.
 */
function wfFileList() {
    new FileList();
}

function FileListParserAfterTidy($parser, &$text) {
    global $FileListOutput;
    
    $parser->disableCache();
    
    $text = str_replace("xx-FileListOutput-xx", $FileListOutput, $text);
    return true;
}

/**
 * Redirect to originating page after upload
 * 
 * @param UploadForm $form
 * @return boolean
 */
function fileListUploadComplete($form){
    $filename = $form->mDesiredDestName;
    $pos = strpos($filename, '_-_');
    if($pos === false)
        return true;
    // check if exam topic
    if(pagename_is_exam_page($filename))
        $pos += strlen(EXAM_STR);
    // get name
    $name = substr($filename, 0, $pos);
    $title = Title::newFromText($name);
    if(! $title->exists())
        return true;
    $nextpage = $title->getFullURL();
    header( 'location: ' . $nextpage );
    exit;
}

/**
 * Remove user data to ensure anonymity
 * 
 * @param UploadForm $form
 * @return boolean
 */
function fileListUploadBeforeProcessing($form) {
    global $wgUser, $wgFileListConfig;
    if($wgFileListConfig['upload_anonymously'])
        $wgUser = User::newFromName( 'anonymous' );
    return true;
}

/**
 * Event handler for delete action
 * 
 * @param string $action
 * @param Article $article
 * @return boolean
 */
function actionDeleteFile( $action, $article ) {
    global $wgRequest, $wgOut;
    
    // check if this is the right action
    if( $action != 'deletefile' )
        return true;
    
    // set redirect params
    $wgOut->setSquidMaxage( 1200 );
    $wgOut->redirect( $article->getTitle()->getFullURL(), '301' );
    
    // get file to delete
    $filename = $wgRequest->getVal('file');
    $image = Image::newFromTitle($filename);
    
    // is user allowed to delete?
    if(!this_user_is_allowed_to_delete($image))
        return false;
    
    // delete file
    $image->delete('FileList deletefile action');
    
    return false;
}

/**
 * Reprefix files when moving (renaming) page
 * 
 * @param UploadForm $form
 * @param Title $old_title
 * @param Title $new_title
 * @return boolean
 */
function fileListMovePage($form, $old_title, $new_title) {
    // get vars
    $files = list_files_of_page($old_title);
    $old_prefix = get_prefix_from_page_name($old_title);
    $new_prefix = get_prefix_from_page_name($new_title);
    // foreach file that matches prefix --> rename
    foreach($files as $file) {
        $new_fname = $new_prefix . substr($file->getName(), strlen($old_prefix));
        $new_file = Title::newFromText('File:' . $new_fname);
        $file->move($new_file);
    }
    
    return true;
}

$FileListOutput="";
class FileList {
    /**
     * Setup Medialist extension.
     * Sets a parser hook for <filelist/>.
     */
    public function __construct() {
        global $wgParser;
        $wgParser->setHook('filelist', array(&$this, 'hookML'));
    } // end of constructor

    /**
     * The hook function. Handles <filelist/>.
     * 
     * @param string $headline: The tag's text content (between <filelist> and </filelist>)
     * @param string $argv: List of tag parameters
     * @param Parser $parser
     */
    public function hookML($headline, $argv, $parser) {
        global $FileListOutput, $wgOut;
        $parser->disableCache();
        $wgOut->addModules( 'ext.FileList' );
        
        // Get all files for this article
        $articleFiles = list_files_of_page($parser->mTitle);
        
        // Generate the media listing.
        if ($FileListOutput != "") //second tag of the page, do not regenerate
        {
            return "xx-FileListOutput-xx";
        }
        $FileListOutput = $this->outputMedia($parser->mTitle, $articleFiles);
        return "xx-FileListOutput-xx";
    } // end of hookML
    
    /**
     * Generate output for the list.
     * 
     * @param string $pagename
     * @param array $filelist
     * @return string
     */
    function outputMedia($pageName, $filelist) {
        global $wgUser, $fileListCorrespondingImages, $wgFileListConfig;
        
        
        $prefix = htmlspecialchars(get_prefix_from_page_name($pageName));
        $extension_folder_url = htmlspecialchars(get_index_url()) . 'extensions/' . basename(dirname(__FILE__)) . '/';
        $icon_folder_url = $extension_folder_url . 'icons/';
        
        $descr_column = false;
        foreach ($filelist as $dataobject) {
            $descr = Revision::newFromTitle($dataobject->title)->getText();
            if(trim($descr) != "") {
                $descr_column = true;
                break;
            }
        }
        
        $output = '';
        // style
        $output .= '<link rel="stylesheet" type="text/css" href="'. $extension_folder_url .'css/FileList.css" />
            <style>
            a.small_remove_button, a.small_edit_button,  a.small_cancel_button {
                background-image: url('.$icon_folder_url.'/buttons_small_edit.gif);
            }
            </style>';
        
        
        // table
        $output .= '<table class="wikitable" id="fl_table">
                      <thead>
                        <tr>
                          <th style="text-align: left">' . wfMsgForContent('listfiles_name') . '</th>
                          <th style="text-align: left">' . wfMsgForContent('listfiles_date') . '</th>
                          <th style="text-align: left">' . wfMsgForContent('listfiles_size') . '</th>
                          <th style="text-align: left" class="fl_descr">' . wfMsgForContent('listfiles_description') . '</th>
                          <th style="text-align: left" class="fl_user">' . wfMsgForContent('listfiles_user') . '</th>
                          <th class="nosort"></th>
                        </tr>
                      </thead>';
        if(UploadBase::isAllowed( $wgUser )===true) {
            $form_action = htmlspecialchars(page_link_by_title('Special:Upload'));
            $upload_label = $wgFileListConfig['upload_anonymously'] ?
                wfMsgForContent('fl_upload_file_anonymously') : wfMsgForContent('upload');
            $output .= '<tfoot>
                          <tr id="fl_add" style="display:none;" class=" unsortable">
                            <th colspan="6" class="fl_full_width">
                              '.wfMsgHtml('fl_add').'
                            </th>
                          </tr>
                          <tr id="fl_input" class=" unsortable">
                            <th colspan="5" class="fl_wide" style="text-align:left">
                              <div class="error" id="filelist_error"></div>
                              <form action="'.$form_action.'" method="post"
                                    name="filelistform" class="visualClear"
                                    enctype="multipart/form-data" id="mw-upload-form">
                                <input name="wpUploadFile" type="file" />
                                <input name="wpDestFile" type="hidden" value="" />
                                <input name="wpWatchthis" type="hidden"/>
                                <input name="wpIgnoreWarning" type="hidden" value="1" />
                                <input type="hidden" value="Special:Upload" name="title" />
                                <input type="hidden" name="wpDestFileWarningAck" />
                                <input type="submit" value="'.$upload_label.'"
                                       name="wpUpload" title="Upload [s]" accesskey="s"
                                       class="mw-htmlform-submit" />
                              </form>
                            </th>
                            <th>
                              <table class="noborder" cellspacing="2"><tr><td>
                                <a title="'.wfMsgHtml('cancel').'" href="#"
                                   id="fl_form_cancel" class="small_cancel_button">
                                  '.wfMsgHtml('cancel').'
                                </a>
                              </td></tr></table>
                            </th>
                          </tr>
                        </tfoot>';
        }
        
        $output .= '
                        <tbody id="fl_contents">';
        if( sizeof($filelist)  == 0 )
            $output .= '<tr><td colspan="6" class="fl_full_width">'.wfMsgForContent('fl_empty_list').'</td></tr>';

        foreach ($filelist as $dataobject) {
                $img_name_w_underscores = substr($dataobject->getName(), strlen($prefix));
                if($dataobject->getDescription())
                    $img_name = $dataobject->getDescription();
                else
                    $img_name = str_replace('_', ' ', $img_name_w_underscores);
                
                $ext = $dataobject->getExtension();
                if(isset($fileListCorrespondingImages[$ext]))
                    $ext_img = $icon_folder_url . $fileListCorrespondingImages[$ext];
                else
                    $ext_img = $icon_folder_url .'default';
                
                $username = $wgFileListConfig['upload_anonymously']?"":$dataobject->getUser();
                $timestamp = wfTimestamp(TS_UNIX, $dataobject->getTimestamp());
                $size = $dataobject->getSize();
                
                $output .= '<tr>
                              <td>
                                <img src="'. $ext_img.'.gif" alt="" />
                                <a href="'.htmlspecialchars($dataobject->getURL()).'">'.htmlspecialchars($img_name).'</a></td>
                              <td class="fl_time" sortval="'.$timestamp.'">' . time_to_string($timestamp) . '</td>
                              <td class="fl_size" sortval="'.$size.'">'.human_readable_filesize($size).'</td>
                              <td class="fl_descr">'.Revision::newFromTitle($dataobject->title)->getText().'</td>
                              <td class="fl_user">'.htmlspecialchars($username).'</td>
                              <td><table class="noborder" cellspacing="2"><tr>
                                <td>
                                  <a title="'.wfMsgForContent('edit').'"
                                     href="'.htmlspecialchars($dataobject->getDescriptionUrl()).'"
                                     class="small_edit_button">
                                       '.wfMsgForContent('edit').'
                                  </a>
                                </td>';
                // delete
                if(this_user_is_allowed_to_delete($dataobject))
                    $output .= '<td>
                                  <a title="'.wfMsgHtml('filedelete',htmlspecialchars($img_name)).'"
                                     href="?file='.htmlspecialchars(urlencode($dataobject->getName())).'&action=deletefile"
                                     class="small_remove_button"
                                     fname="'.$img_name.'"
                                     id="delete_'.$dataobject->img_name.'">
                                       '.wfMsgForContent('filedelete',$img_name).'
                                  </a>
                                </td>';
                $output .= '</tr></table></td></tr>';
        }
        $output .= '</tbody></table>
          <script>
            var FileList={"prefix":"'.htmlspecialchars($prefix).'",
                          "extension_folder_url":"'.$extension_folder_url.'",
                          "anonymous":'.($wgFileListConfig['upload_anonymously']?'true':'false').'};
          </script>';
        
        return $output;
    } // end of outputMedia
} // end of class FileList

