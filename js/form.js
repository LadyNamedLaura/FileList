function fileListError(message){
    $("#filelist_error").show().html(message);
}
if(FileList.hideForm){
    $('.fl_input').css('display','none');
    $('.fl_add').show().click(function (event){
        var table=$(this).closest('.fl_table');
        table.find('.fl_input').show();
        table.find('.fl_add').hide();
        event.preventDefault()})
    $('.small_cancel_button').click(function (event){
        var table=$(this).closest('.fl_table');
        table.find('.fl_input').hide();
        table.find('.fl_add').show();
        table.find('.fl-upload-form')[0].reset();
        event.preventDefault()})
} else {
    $('.small_cancel_button').click(function (event){
        var table=$(this).closest('.fl_table');
        table.find('.fl-upload-form')[0].reset();
        event.preventDefault()})
}
$('.fl-upload-form').submit(function (event){
    if( $(this).find('input[name="wpUploadFile"]').val() == "" ) {
        fileListError(mw.msg('fl_empty_file'));
        return false;
    }
    return true;
});

