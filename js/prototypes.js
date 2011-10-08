String.prototype.format = function(array) {
    var formatted = this;
    for (var i = 0; i < array.length; i++) {
        var regexp = new RegExp("\\\\{"+i+"\\\\}", "gi");
        formatted = formatted.replace(regexp, array[i]);
    }
    return formatted;
};
Object.prototype.Inherits = function( p ) {
    if(!p)
        return;
    if( arguments.length == 1 )
        p.prototype.constructor.call( this );
    else
        p.prototype.constructor.apply( this, Array.prototype.slice.call( arguments, 1 ) );
};
Function.prototype.Inherits = function( parent ) {
    this.prototype = new parent();
    this.prototype.constructor = this;
};
Node.prototype.getChildNodesByTagName=function (tag) {
    var out=new Array();
    var i
    for (i in this.childNodes)
    {
        if (this.childNodes[i].tagName==tag.toUpperCase())
            out[out.length]=this.childNodes[i];
    }
    return out;
};
if (Node.prototype.getElementsByClassName == undefined) {
	Node.prototype.getElementsByClassName = function(className)
	{
		var hasClassName = new RegExp("(?:^|\\s)" + className + "(?:$|\\s)");
		var allElements = this.getElementsByTagName("*");
		var results = [];

		var element;
		for (var i = 0; (element = allElements[i]) != null; i++) {
			var elementClass = element.className;
			if (elementClass && elementClass.indexOf(className) != -1 && hasClassName.test(elementClass))
				results.push(element);
		}
		return results;
	}
}
