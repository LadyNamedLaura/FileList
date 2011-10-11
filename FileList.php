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

/****************** CHANGING GLOBAL SETTINGS ******************/

/** Set allowed extensions **/
$wgVerifyMimeType = false;

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

// these will be opened in the browser when clicked on them
// all other will be forced a download
$fileListOpenInBrowser = array('pdf','txt','htm','html','css',
                               'jpg','jpeg','bmp','gif','png');

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
//
$wgHooks['ParserAfterTidy'][] = 'FileListParserAfterTidy';
// credits
$wgExtensionCredits['parserhook'][] = array(
    'name'           => 'FileList',
    'author'         => 'Jens Nyman (VTK Ghent)',
	'descriptionmsg' => 'fl_credits_desc',
	'url'            => 'http://www.mediawiki.org/wiki/Extension:FileList',
);
$wgResourceModules['ext.FileList'] = array(
    // JavaScript and CSS styles. To combine multiple file, just list them as an array.
    'scripts' => array( 'js/form.js', 'js/list.js'),
    'styles' => 'css/FileList.css',

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
    // find markers in $text
    // replace markers with actual output
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
    
    // is user allowed to delete?
    if(!this_user_is_allowed_to_delete($filename))
        return false;
    
    // delete file
    $image = Image::newFromTitle($filename);
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
        $new_fname = $new_prefix . substr($file->img_name, strlen($old_prefix));
        $old_file = Title::newFromText('File:' . $file->img_name);
        $new_file = Title::newFromText('File:' . $new_fname);
        
        // move file
		$movePageForm = new MovePageForm($old_file, $new_file);
    	$movePageForm->reason = "";
		$movePageForm->doSubmit();
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
        $colls = 4;
        
        $output = '';
        // style
        $output .= '<style>
            a.small_remove_button, a.small_edit_button, a.small_cancel_button {
                background-image: url('.$icon_folder_url.'/buttons_small_edit.gif);
            }
            </style>';
        
        // check if exists
        $descr_column = false;
        foreach ($filelist as $dataobject) {
            $article = new Article ( Title::newFromText( 'File:'.$dataobject->img_name ) );
            $descr = $article->getContent();
            if(trim($descr) != "") {
                $descr_column = true;
                $colls++;
                break;
            }
        }
        if(!$wgFileListConfig['upload_anonymously'])
            $colls++;
        
        // table
        $output .= '<table class="wikitable sortable" id="fl_table">
                      <thead>
                        <tr>
                          <th style="text-align: left">' . wfMsgForContent('listfiles_name') . '</th>
                          <th style="text-align: left">' . wfMsgForContent('listfiles_date') . '</th>
                          <th style="text-align: left">' . wfMsgForContent('listfiles_size') . '</th>';
        if($descr_column)
            $output .= '  <th style="text-align: left">' . wfMsgForContent('listfiles_description') . '</th>';
        if(!$wgFileListConfig['upload_anonymously'])
            $output .= '  <th style="text-align: left">' . wfMsgForContent('listfiles_user') . '</th>';
        $output .= '      <th class="unsortable"></th>
                        </tr>
                      </thead>';
        if(UploadBase::isAllowed( $wgUser )===true) {
            $form_action = htmlspecialchars(page_link_by_title('Special:Upload'));
            $upload_label = $wgFileListConfig['upload_anonymously'] ?
                wfMsgForContent('fl_upload_file_anonymously') : wfMsgForContent('upload');
            $output .= '<tfoot>
                          <tr id="fl_add" style="display:none;" class=" unsortable">
                            <th colspan="'.$colls.'">
                              '.wfMsgHtml('fl_add').'
                            </th>
                          </tr>
                          <tr id="fl_input" class=" unsortable">
                            <th colspan="'.($colls-1).'" style="text-align:left">
                              <div style="color: red;text-align:center" id="filelist_error"></div>
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
            $output .= '<tr><td colspan="'.$colls.'">'.wfMsgForContent('fl_empty_list').'</td></tr>';

        foreach ($filelist as $dataobject) {
                $output .= '<tr>';
                /** ICON PROCESSING **/
                $ext = file_get_extension($dataobject->img_name);
                if(isset($fileListCorrespondingImages[$ext]))
                    $ext_img = $fileListCorrespondingImages[$ext];
                else
                    $ext_img = 'default';
                $output .= '<td><img src="'.$icon_folder_url . $ext_img.'.gif" alt="" /> ';
                
                /** FILENAME PROCESSING**/
                $img_name = str_replace('_', ' ', $dataobject->img_name);
                $img_name = substr($img_name, strlen($prefix));
                $img_name_w_underscores = substr($dataobject->img_name, strlen($prefix));
                $link = $extension_folder_url . 'file.php?name='.urlencode($img_name_w_underscores) . "&file=" . urlencode($dataobject->img_name);
                // if description exists, use this as filename
                $descr = $dataobject->img_description;
                if($descr)
                    $img_name = $descr;
                $output .= '<a href="'.htmlspecialchars($link).'">'.htmlspecialchars($img_name).'</a></td>';
                
                /** TIME PROCESSING**/
                // converts (database-dependent) timestamp to unix format, which can be used in date()
                $timestamp = wfTimestamp(TS_UNIX, $dataobject->img_timestamp);
                $output .= '<td>' . time_to_string($timestamp) . '</td>';
                
                /** SIZE PROCESSING **/
                $size = human_readable_filesize($dataobject->img_size);
                $output .= '<td>'.$size.'</td>';
                
                /** DESCRIPTION **/
                if($descr_column) {
                    $article = new Article ( Title::newFromText( 'File:'.$dataobject->img_name ) );
                    $descr = $article->getContent();
                    $descr = str_replace("\n", " ", $descr);
                    $output .= '<td>'.htmlspecialchars($descr).'</td>';
                }
                
                /** USERNAME **/
                if(!$wgFileListConfig['upload_anonymously']) {
                    $output .= '<td>'.htmlspecialchars($dataobject->img_user_text).'</td>';
                }
                
                /** EDIT AND DELETE **/
                $output .= '<td><table class="noborder" cellspacing="2"><tr>';
                // edit
                $output .= '<td>
                              <a title="'.wfMsgForContent('edit').'"
                                 href="'.htmlspecialchars(page_link_by_title('File:'.$dataobject->img_name)).'"
                                 class="small_edit_button">
                                   '.wfMsgForContent('edit').'
                              </a>
                            </td>';
                // delete
                if(this_user_is_allowed_to_delete($dataobject->img_name))
                    $output .= '<td>
                                  <a title="'.wfMsgHtml('filedelete',htmlspecialchars($img_name)).'"
                                     href="?file='.htmlspecialchars(urlencode($dataobject->img_name)).'&action=deletefile"
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
                          "extension_folder_url":"'.$extension_folder_url.'"};
          </script>';
        
        return $output;
    } // end of outputMedia
} // end of class FileList

