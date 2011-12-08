<?php
class FlAction {
    static function onUnknownAction( $action, $article ) {
        switch ($action) {
            case 'deletefile':
                return self::deleteFile($article);
            case 'getFileListJSON':
                return self::getJSON($article);
            default:
                return true;
        }
    }
    /**
     * Reprefix files when moving (renaming) page
     * 
     * @param UploadForm $form
     * @param Title $old_title
     * @param Title $new_title
     * @return boolean
     */
    static function onSpecialMovepageAfterMove($form, $old_title, $new_title) {
        // get vars
        $files = FileList::list_files_of_page($old_title);
        $old_prefix = get_prefix_from_page_name($old_title);
        $new_prefix = get_prefix_from_page_name($new_title);
        // foreach file that matches prefix --> rename
        foreach($files as $file) {
            $new_fname = str_replace($old_prefix, $new_prefix, $file->getName());
            $file->move(Title::newFromText('File:' . $new_fname));
        }
        return true;
    }

    /**
     * Event handler for delete action
     * 
     * @param string $action
     * @param Article $article
     * @return boolean
     */
    static function deleteFile( $article ) {
        global $wgRequest, $wgOut, $wgUser;
        
        // set redirect params
        $wgOut->setSquidMaxage( 1200 );
        $wgOut->redirect( $article->getTitle()->getFullURL(), '301' );
        
        // get file to delete
        $filename = $wgRequest->getVal('file');
        $file = FlFile::newFromTitle(Title::makeTitle( NS_FILE, $filename));
        
        // is user allowed to delete?
        if($file->userCanDelete())
            $file->delete('FileList deletefile action');
        
        return false;
    }
    static function getJSON($article) {
        $files=FileList::list_files_of_page($article->mTitle);
        $obj=array();
        foreach($files as $file)
            $obj[$file->getName()] = $file->getArray();
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        echo json_encode($obj);
        exit;
    }
    static function onUserCanDeletFile($user, $file, &$result) {
        if ($user->getName() == $file->getUser()
             || in_array('sysop', $user->getGroups()))
            return $result=true;
        return true;
    }
}
