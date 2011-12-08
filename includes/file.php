<?php

class FlFile extends LocalFile {
    var $prefix;
    protected static $icons = array(
        'pdf'  => 'pdf', // .gif
        'rar'  => 'rar',
        '7z'   => 'rar',
        'gz'   => 'rar',
        'zip'  => 'zip',
        'txt'  => 'txt',
        'doc'  => 'doc',
        'docx' => 'doc',
        'ppt'  => 'ppt',
        'pptx' => 'ppt',
        'xls'  => 'xls',
        'xlsx' => 'xls',
        'odt'  => 'odt',
        'odp'  => 'odt',
        'ods'  => 'odt',
        'jpg'  => 'gif',
        'jpeg' => 'gif',
        'gif'  => 'gif',
        'png'  => 'gif',
    );
    
    static function newFromRow( $row, $repo=null ) {
        if ($repo==null)
            $repo = RepoGroup::singleton()->getLocalRepo();
        $title = Title::makeTitle( NS_FILE, $row->img_name );
        $file = new self( $title, $repo );
        $file->loadFromRow( $row );
        
        return $file;
    }
    static function newFromTitle( $title, $repo=null, $unused = null ) {
        if ($repo==null)
            $repo = RepoGroup::singleton()->getLocalRepo();
        return new self( $title, $repo );
    }
    
    function getSISize() {
        $size = $this->getSize();
        $units = array(' Bytes', ' kiB', ' MiB', ' GiB', ' TiB', ' PiB', ' EiB', ' ZiB', ' YiB');
        for ($i = 0; $size > 1024; $i++)
            $size /= 1024;
        return round($size, 2) . $units[$i];
    }
    function getUnixStamp() {
        return wfTimestamp(TS_UNIX, $this->getTimestamp());
    }
    function getHumanStamp() {
        $time = $this->getUnixStamp();
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
    function getIconUrl() {
        $ext=$this->getExtension();
        if(isset(self::$icons[$ext]))
            return FLGetConf('ExtPath') . 'icons/'. self::$icons[$ext]. '.gif';
        else
            return FLGetConf('ExtPath') . 'icons/default.gif';
    }
    function userCanDelete() {
        $result=null;
        Hooks::run( 'UserCanDeletFile', array( FLGetConf('User'), $this, &$result));
        return $result;
    }
    function getUser( $type = 'text' ) {
        if (!FLGetConf('upload_anonymously'))
            return parent::getUser();
        if ($type=='text')
            return '';
        return null;
    }
    function getRealName() {
        if($this->getDescription())
            return $this->getDescription();
        return str_replace('_', ' ', substr($this->getName(), strlen($this->prefix)));
    }
    function getText() {
        return Revision::newFromTitle($this->title)->getText();
    }
    function getArray() {
        return array(
            'id'   => $this->getName(),
            'icon' => $this->getIconUrl(),
            'name' => htmlspecialchars($this->getRealName()),
            'url'  => $this->getURL(),
            'user' => htmlspecialchars($this->getUser()),
            'time' => array(
                'disp' => htmlspecialchars($this->getHumanStamp()),
                'sort' => $this->getUnixStamp()),
            'size' => array(
                'disp' => htmlspecialchars($this->getSISize()),
                'sort' => $this->getSize()),
            'desc' => array(
                'text' => htmlspecialchars($this->getText()),
                'url'  => $this->getDescriptionUrl()),
            'deleteable' => $this->userCanDelete(),
            'delUrl'  => '?file='.htmlspecialchars(urlencode($this->getName())).'&action=deletefile');
    }
    function getJSON() {
        return json_encode($this->getArray());
    }
}
