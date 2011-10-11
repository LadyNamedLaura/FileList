function update_table(){
    $('.small_remove_button').click(function (event) {
        if (!confirm(mw.msg('fl_remove_confirm',$(this).attr('fname'))))
            event.preventDefault();});
    
    if (window.uprows)
        window.uprows.add();
    
    rows=origrows;
    if(window.FileList.anonymous){
        $(".fl_user").hide();
        rows--;}
    
    var descr_row=$('.fl_descr');
    var descr=false;
    for (i=1;i<descr_row.length;i++)
        if (descr_row[i].innerHTML.length>1)
            descr=true;
    if (!descr){
        $(".fl_descr").hide();
        rows--;}
    $(".fl_full_width").attr("colspan",rows);
    $(".fl_wide").attr("colspan",rows-1);
    
}
var origrows=6;
update_table();
