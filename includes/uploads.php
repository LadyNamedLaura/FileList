<?php
class FlUpload extends UploadFromFile {
    protected $flPrefix;
    function initializeFromRequest( &$request ) {
        global $wgUser;
        if(FLGetConf('upload_anonymously'))
            $wgUser = User::newFromName( 'anonymous' );
        
        $upload = $request->getUpload( 'wpUploadFile' );
        $desiredDestName = $request->getText( 'wpDestFile' );
        if( $request->getText( 'wpOrigin')=='FileListUpload');
        {
            $this->flPrefix=$request->getText( 'flPrefix');
            $desiredDestName = $this->flPrefix.$upload->getName();
        }
        if( !$desiredDestName )
            $desiredDestName = $upload->getName();
        
        return $this->initialize( $desiredDestName, $upload );
    }
    
    public function performUpload( $comment, $pageText, $watch, $user ) {
        $status = parent::performUpload( $comment, $pageText, $watch, $user );
        header( 'location: ' . $_SERVER['HTTP_REFERER']."#fl_table-{$this->flPrefix}" );
        exit;
    }
    static function attachHook( $type, &$className ) {
        global $wgRequest;
        if( $type == "File" && $wgRequest->getText( 'wpOrigin') == 'FileListUpload' )
        {
            $className = 'FlUpload';
            return false;
        }
        return true;
    }
}
