<?php
class FileList {
    /**
     * Setup Medialist extension.
     * Sets a parser hook for <filelist/>.
     */
    
    protected $prefix,
              $dummy,
              $list,
              $ns;
    protected static $instances = array();
    
    /**
     * The hook function. Handles <filelist/>.
     * 
     * @param string $headline: The tag's text content (between <filelist> and </filelist>)
     * @param string $argv: List of tag parameters
     * @param Parser $parser
     */
    public static function onHookHtml($headline, $argv, $parser) {
        global $wgOut;
        $parser->disableCache();
        
        $ns = $parser->getTitle()->getText();
        if (isset($argv['global'])&&$argv['global'])
            $ns = 'FlGLOBAL';
        if (isset($argv['group']))
            $ns .='---'.$argv['group'];
        if ( isset(self::$instances[$ns])) {
            $obj = self::$instances[$ns];
        } else {
            $obj = new self($ns);
        }
        
        $wgOut->addModules( 'ext.FileList' );
        return $obj->dummy;
    } // end of hookML
    
    /**
     * Initialize list
     * 
     */
    public function __construct($namespace){
        self::$instances[$namespace] = $this;
        Hooks::register('ParserAfterTidy', $this);
        $this->prefix = get_prefix_from_page_name($namespace);
        $this->dummy = "xx-FileList-{$namespace}-xx";
        $this->list = self::list_files_of_page($namespace);
        $this->ns = $namespace;
    }
    public function onParserAfterTidy($parser, &$text) {
        $parser->disableCache();
        $output = $this->outputMedia($parser->mTitle).self::linkCSS();
        $text = str_replace($this->dummy, $output, $text);
        return true;
    }
    static function linkCSS() {
        static $done=false;
        if ($done)
            return '';
        $done = true;
        $_getConf='FLGetConf';
        $anon = (FLGetConf('upload_anonymously')?'true':'false');
        $hide = (FLGetConf('hideForm')?'true':'false');
        return<<<HTML
    <link rel="stylesheet" type="text/css" href="{$_getConf('ExtPath')}css/FileList.css" />
    <link rel="stylesheet" type="text/css" href="{$_getConf('ScriptPath')}/skins/common/shared.css" />
    <style>
        a.small_remove_button, a.small_edit_button, a.small_ok_button, a.small_cancel_button {
            width: 11px;
            background-image: url({$_getConf('ExtPath')}icons/buttons_small_edit.gif);
        }
    </style>
    <script>
    var FileList={"anonymous":{$anon},
                  "hideForm":{$hide},};
    </script>

HTML;
    }
    /**
     * Generate output for the list.
     * 
     * @param string $pagename
     * @param array $filelist
     * @return string
     */
    function outputMedia($pageName) {
        $_getMsg='wfMsgForContent';
        $output =<<<HTML
    <table class="wikitable fl_table" id="fl_table-{$this->prefix}">
      <thead>
        <tr>
          <th style="text-align: left">{$_getMsg('listfiles_name')}</th>
          <th style="text-align: left">{$_getMsg('listfiles_date')}</th>
          <th style="text-align: left">{$_getMsg('listfiles_size')}</th>
          <th style="text-align: left" class="fl_desc">{$_getMsg('listfiles_description')}</th>
          <th style="text-align: left" class="fl_user">{$_getMsg('listfiles_user')}</th>
          <th class="nosort"></th>
        </tr>
      </thead>

HTML;
        if(UploadBase::isAllowed(FLGetConf('User'))===true) {
            $form_action = htmlspecialchars(Title::newFromText('Special:Upload')->getFullURL());
            $upload_label = FLGetConf('upload_anonymously') ?
                wfMsgForContent('fl_upload_file_anonymously') : wfMsgForContent('upload');
            $output .=<<<HTML
      <tfoot>
        <tr style="display:none;" class="fl_add unsortable">
          <th colspan="6" class="fl_full_width">
              {$_getMsg('fl_add')}
          </th>
        </tr>
        <tr class="fl_input unsortable">
          <th colspan="5" class="fl_wide" style="text-align:left">
            <div class="error" id="filelist_error"></div>
            <form action="$form_action" method="post"
                  name="filelistform" class="visualClear"
                  enctype="multipart/form-data" id="mw-upload-form">
              <input name="wpUploadFile" type="file" />
              <input name="wpOrigin" type="hidden" value="FileListUpload" />
              <input name="flPage" type="hidden" value="$pageName" />
              <input name="flPrefix" type="hidden" value="{$this->prefix}" />
              <input name="wpWatchthis" type="hidden"/>
              <input name="wpIgnoreWarning" type="hidden" value="1" />
              <input type="hidden" value="Special:Upload" name="title" />
              <input type="hidden" name="wpDestFileWarningAck" />
              <input type="submit" value="$upload_label"
                     name="wpUpload" title="Upload [s]" accesskey="s"
                     class="mw-htmlform-submit fl-upload-form" />
            </form>
          </th>
          <th>
            <table class="noborder" cellspacing="2"><tr><td>
              <a title="{$_getMsg('cancel')}"
                 id="fl_form_cancel" class="small_cancel_button">
                {$_getMsg('cancel')}
              </a>
            </td></tr></table>
          </th>
        </tr>
      </tfoot>

HTML;
        }
        
        $output .=<<<HTML
      <tbody id="fl_contents">

HTML;
        if( sizeof($this->list)  == 0 )
            $output .= '<tr><td colspan="6" class="fl_full_width">'.wfMsgForContent('fl_empty_list').'</td></tr>';
        foreach ($this->list as $file)
            $output.=self::outputRowHtml($file->getArray());
        
        return $output .<<<HTML
      </tbody>
      <tbody style="display:none"></tbody>
    </table>

HTML;
    } // end of outputMedia
    static function outputRowHtml($arr){
        $_getMsg='wfMsgForContent';
        $_getMsgHtml='wfMsgHtml';
        $output =<<<HTML
        <tr id="{$arr['id']}">
          <td class="fl_name" sortval="{$arr['name']}">
            <img src="{$arr['icon']}" alt="" />
            <a href="{$arr['url']}">{$arr['name']}</a></td>
          <td class="fl_time" sortval="{$arr['time']['sort']}">{$arr['time']['disp']}</td>
          <td class="fl_size" sortval="{$arr['size']['sort']}">{$arr['size']['disp']}</td>
          <td class="fl_desc">{$arr['desc']['text']}</td>
          <td class="fl_user">{$arr['user']}</td>
          <td>
            <table class="noborder" cellspacing="2">
              <tr>
                <td>
                  <a title="{$_getMsgHtml('edit')}"
                     href="{$arr['desc']['url']}"
                     class="small_edit_button">
                    {$_getMsg('edit')}
                  </a>
                </td>

HTML;
        if( $arr['deleteable'])
            $output .=<<<HTML
                <td>
                  <a title="{$_getMsgHtml('filedelete',$arr['name'])}"
                     href="{$arr['delUrl']}"
                     class="small_remove_button"
                     fname="{$arr['name']}">
                    {$_getMsg('filedelete',$arr['name'])}
                  </a>
                </td>

HTML;
        return $output .<<<HTML
              </tr>
            </table>
          </td>
        </tr>

HTML;
    }
    /**
     * list files of page
     * 
     * @param string $pagename
     * @return array
     */
    static function list_files_of_page($pagename) {
        // Query the database.
        $dbr =& wfGetDB(DB_SLAVE);
        $res = $dbr->select(array('image'), array('*'), '', '', array('ORDER BY' => 'img_timestamp') );
        if ($res === false)
            return array();

        // Convert the results list into an array.
        $list = array();
        $prefix = get_prefix_from_page_name($pagename);
        while ($row = $dbr->fetchObject($res)) {
            $file = FlFile::newFromRow($row);
            if( strpos($file->getName(), $prefix)!==0)
                continue;
            $file->prefix=$prefix;
            if(!Hooks::run( 'ShowFileOnPage', array( $pagename, $file) ))
                continue;
            $list[]=$file;
        }
        // Free the results.
        $dbr->freeResult($res);
        return $list;
    }
}
