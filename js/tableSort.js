window.SortableTable=function (tableEl) {
    this.tbodies = tableEl.tBodies;
    this.thead = tableEl.tHead;
    this.tfoot = tableEl.tFoot;
    this.sortColumnIndex = -1;
    
    this.getInnerText = function (el) {
        if (typeof(el.textContent) != 'undefined') return el.textContent;
        if (typeof(el.innerText) != 'undefined') return el.innerText;
        if (typeof(el.innerHTML) == 'string') return el.innerHTML.replace(/<[^<>]+>/g,'');
    }

    this.getParent = function (el, pTagName) {
        if (el == null)
        {
            return null;
        }
        else if (el.nodeType == 1 && el.tagName.toLowerCase() == pTagName.toLowerCase())
        {
            return el;
        }
        else
        {
            return this.getParent(el.parentNode, pTagName);
        }
    }
    this.sort = function (cell) {
        var column = cell.cellIndex;
        var itm = this.getInnerText(this.tbodies[0].rows[1].cells[column]);
        var sortfn = this.sortCaseInsensitive;
        if ($(this.tbodies[0].rows[1].cells[column]).attr('sortval'))
        {
            itm = $(this.tbodies[0].rows[1].cells[column]).attr('sortval');
        }
        if (itm.match(/\d\d[-]+\d\d[-]+\d\d\d\d/))
        {
            sortfn = this.sortDate; // date format mm-dd-yyyy
        }
        if (itm.replace(/^\s+|\s+$/g,"").match(/^[\d\.]+$/))
        {
            sortfn = this.sortNumeric;
        }
        
        this.sortColumnIndex = column;
        
        var newRows = new Array();
        for (j = 0; j < this.tbodies[0].rows.length; j++)
        {
            newRows[j] = this.tbodies[0].rows[j];
        }
        newRows.sort(sortfn);
        if (cell.getAttribute("sortdir") == 'down') {
            newRows.reverse();
            cell.setAttribute('sortdir','up');
        } else {
            cell.setAttribute('sortdir','down');
        }
        
        for (i=0;i<newRows.length;i++)
        {
            this.tbodies[0].appendChild(newRows[i]);
        }
    }
    this.resort = function () {
        if(this.sortColumnIndex == -1)
        {
            return;
        }
        var column=this.sortColumnIndex;
        var itm = this.getInnerText(this.tbodies[0].rows[1].cells[column]);
        var sortfn = this.sortCaseInsensitive;
        if ($(this.tbodies[0].rows[1].cells[column]).attr('sortval'))
            itm = $(this.tbodies[0].rows[1].cells[column]).attr('sortval');
        if (itm.match(/\d\d[-]+\d\d[-]+\d\d\d\d/))
            sortfn = this.sortDate; // date format mm-dd-yyyy
        if (itm.replace(/^\s+|\s+$/g,"").match(/^[\d\.]+$/))
            sortfn = this.sortNumeric;
        
        var newRows = new Array();
        for (j = 0; j < this.tbodies[0].rows.length; j++)
            newRows[j] = this.tbodies[0].rows[j];
        
        newRows.sort(sortfn);
        if (cell.getAttribute("sortdir") == 'up')
            newRows.reverse();
        
        for (i=0;i<newRows.length;i++)
            this.tbodies[0].appendChild(newRows[i]);
    }
    this.sortCaseInsensitive = function(a,b) {
        if ($(a.cells[thisObject.sortColumnIndex]).attr('sortval'))
            aa = $(a.cells[thisObject.sortColumnIndex]).attr('sortval').toLowerCase();
        else
            aa = thisObject.getInnerText(a.cells[thisObject.sortColumnIndex]).toLowerCase();
        if ($(b.cells[thisObject.sortColumnIndex]).attr('sortval'))
            bb = $(b.cells[thisObject.sortColumnIndex]).attr('sortval').toLowerCase();
        else
            bb = thisObject.getInnerText(b.cells[thisObject.sortColumnIndex]).toLowerCase();
        if (aa==bb) return 0;
        if (aa<bb) return -1;
        return 1;
    }
    this.sortDate = function(a,b) {
        if ($(a.cells[thisObject.sortColumnIndex]).attr('sortval'))
            aa = $(a.cells[thisObject.sortColumnIndex]).attr('sortval');
        else
            aa = thisObject.getInnerText(a.cells[thisObject.sortColumnIndex]);
        if ($(b.cells[thisObject.sortColumnIndex]).attr('sortval'))
            bb = $(b.cells[thisObject.sortColumnIndex]).attr('sortval');
        else
            bb = thisObject.getInnerText(b.cells[thisObject.sortColumnIndex]);
        date1 = aa.substr(6,4)+aa.substr(3,2)+aa.substr(0,2);
        date2 = bb.substr(6,4)+bb.substr(3,2)+bb.substr(0,2);
        if (date1==date2) return 0;
        if (date1<date2) return -1;
        return 1;
    }
    this.sortNumeric = function(a,b) {
        if ($(a.cells[thisObject.sortColumnIndex]).attr('sortval'))
            aa = $(a.cells[thisObject.sortColumnIndex]).attr('sortval');
        else
            aa = thisObject.getInnerText(a.cells[thisObject.sortColumnIndex]);
        if ($(b.cells[thisObject.sortColumnIndex]).attr('sortval'))
            bb = $(b.cells[thisObject.sortColumnIndex]).attr('sortval');
        else
            bb = thisObject.getInnerText(b.cells[thisObject.sortColumnIndex]);
        if (isNaN(aa)) aa = 0;
        if (isNaN(bb)) bb = 0;
        return aa-bb;
    }
    // define variables
    var thisObject = this;
    var sortSection = this.thead;
    var nosort = new RegExp("(?:^|\\s)nosort(?:$|\\s)");

    // constructor actions
    if (!(this.tbodies && this.tbodies[0].rows && this.tbodies[0].rows.length > 0))
        return;
    if (sortSection && sortSection.rows && sortSection.rows.length > 0)
        var sortRow = sortSection.rows[0];
    else
        return;
    tableEl.sTable = this;
    for (var i=0; i<sortRow.cells.length; i++) {
        sortRow.cells[i].sTable = this;
        if(nosort.test(sortRow.cells[i].className))
            continue;
        sortRow.cells[i].onclick = function () {
            this.sTable.sort(this);
            return false;
        }
    }
}
