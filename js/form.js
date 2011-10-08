function getMsg(msg){
    if (arguments.length == 1)
        return i18n[msg];
    arguments.shift();
    return i18n[msg].format(arguments);
}
function fileListSubmit(){
    form = document.filelistform;
    filename = form.wpUploadFile.value;
    if( filename == "" ) {
        fileListError(getMsg('fl_empty_file'));
        return false;
    }
    form.wpDestFile.value = FileList.prefix + filename;
    return true;
}
function fileListError(message){
    document.getElementById("filelist_error").innerHTML = message;
}

