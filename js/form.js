function fileListError(message){
    document.getElementById("filelist_error").innerHTML = message;
}

$('#fl_input').css('display','none');
$('#fl_add').css('display','');
$('#fl_add').click(function (event){
    $('#fl_input').css('display','');
    $('#fl_add').css('display','none');})
$('#fl_form_cancel').click(function (event){
    $('#fl_input').css('display','none');
    $('#fl_add').css('display','');
    $('#mw-upload-form')[0].reset();
    return false;})
$('#mw-upload-form').submit(function (event){
    var filename = $('#mw-upload-form > input[name="wpUploadFile"]').val();
    if( filename == "" ) {
        fileListError(mw.msg('fl_empty_file'));
        return false;
    }
    $('#mw-upload-form > input[name="wpDestFile"]').val(FileList.prefix + filename);
    return true;
});

