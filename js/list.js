window.update_table = function() {
    var table=this;
    if(!table.sTable){
        new SortableTable(table);
    } else {
        table.sTable.resort();
    }
    
    $('.small_remove_button').click(function (event) {
        if (!confirm(mw.msg('fl_remove_confirm',$(this).attr('fname'))))
        {
            event.preventDefault();
        }
    });
    if (window.uprows)
    {
        window.uprows.add();
    }
    rows=origrows;
    if(window.FileList.anonymous){
        $(".fl_user").hide();
        rows--;}
    
    var desc_col=$(table).find('.fl_desc');
    var desc=false;
    for (i=1;i<desc_col.length;i++)
    {
        if (desc_col[i].innerHTML.length>1)
        {
            desc=true;
        }
    }
    if (!desc){
        $(".fl_desc").hide();
        rows--;
    } else {
        $(".fl_desc").show();
    }
    $(".fl_full_width").attr("colspan",rows);
    $(".fl_wide").attr("colspan",rows-1);
};
var origrows=6;

$('.fl_table').each(window.update_table);

window.getRowFromObj = function(obj)
{
    var element = document.createElement('tr');
    element.id  = obj.id;
    var cell = document.createElement('td');
    cell.className = "fl_name";
    cell.sortval   = obj.name;
    cell.innerHTML = "<img alt=\"\" src=\""+obj.icon+"\"> <a href=\""+obj.url+"\">"+obj.name+"</a>";
    element.appendChild(cell);
    cell = document.createElement('td');
    cell.className = "fl_time";
    cell.sortval   = obj.time.sort;
    cell.innerHTML = obj.time.disp;
    element.appendChild(cell);
    cell = document.createElement('td');
    cell.className = "fl_size";
    cell.sortval   = obj.size.sort;
    cell.innerHTML = obj.size.disp;
    element.appendChild(cell);
    cell = document.createElement('td');
    cell.className = "fl_desc";
    cell.innerHTML = obj.desc.text;
    element.appendChild(cell);
    cell = document.createElement('td');
    cell.className = "fl_user";
    cell.innerHTML = obj.user;
    element.appendChild(cell);
    cell = document.createElement('td');
    if (obj.deleteable)
        cell.innerHTML = '<table class="noborder" cellspacing="2"><tr><td><a title="'+mw.msg('edit')+'" '+
                           'href="'+obj.desc.url+'" class="small_edit_button">'+mw.msg('edit')+'</a></td>' +
                         '<td><a title="'+mw.msg('filedelete',obj.name)+'" href="'+obj.delUrl+'"̈́ '+
                           'class="small_remove_button" fname="'+obj.name+'">'+mw.msg('filedelete',obj.name)+'</a></td></tr></table>';
    else
        cell.innerHTML = '<table class="noborder" cellspacing="2"><tr><td><a title="'+mw.msg('edit')+'" '+
                           'href="'+obj.desc.url+'" class="small_edit_button">'+mw.msg('edit')+'</a></td></tr></table>';
    element.appendChild(cell);
    return element;
};
window.fetchUpdate = function(){
    $.ajax({url:'?action=getFileListJSON', dataType:'json',cache:false,success:function(data,stat,req){window.doUpdateRows(data)}})
    
};
window.doUpdateRows = function(obj){
    var table=$('.fl_table')[0];
    for(i in obj)
    {
        window.insert_row(obj[i],table,false);
    }
    var found=[];
    var remove=[];
    var j=0;
    for(i in table.tBodies[0].rows)
    {
        var row=table.tBodies[0].rows[i];
        if(row.id && !obj[row.id]) {
            remove[j++]=row;
        } else if(row.id&&found[row.id]) {
            remove[j++]=row;
        } else {
            found[row.id]=true;
        }
    }
    for(i in remove){
        remove[i].parentNode.removeChild(remove[i]);
    }
    alert("done");
    $('#fl_table').each(window.update_table);
};
window.insert_row = function(obj,table,force){
    if(document.getElementById(obj.id) && !force)
        document.getElementById(obj.id).innerHTML = window.getRowFromObj(obj).innerHTML;
    else
        table.tBodies[0].appendChild(window.getRowFromObj(obj));
};
