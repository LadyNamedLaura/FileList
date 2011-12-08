(function ($) {
    $.fn.iAjaxForm = function (options) {
        options = $.extend({
                iframeID : 'iAjaxFrame',          // Iframe ID.
                type : 'text',                    // Can be 'text', 'json' or 'html'
                post : function () {},            // Form onsubmit.
                complete : function (response) {} // After response from the server has been received.
            }, options);
        // Submit listener.
        return this.filter('form').bind( 'submit.iAjaxForm' ,function () {
            var iframe,
                id;
            // If status is false then abort.
            if (options.post.call(this) === false) {
                return false;}
            // Add the iframe.
            id=obtions.iframeID;
            for (var i=1;
                 document.getElementById(id);
                 ++i){
                    id=options.iframeID+'_'+i;}
            this.target = id;
            
            iframe  = $('<iframe name="'+id+'" id="'+id+'"></iframe>').hide();
            iframe.bind('load.iAjaxForm', function () {
                switch(options.type){
                  case 'json':
                    options.complete.call(this, $.parseJSON(iframe.contents().html()));
                    break;
                  case 'text':
                    options.complete.call(this, iframe.contents().html());
                    break;
                  default:
                    options.complete.call(this, iframe.contents());
                }
                
                iframe.unbind('load.iAjaxForm');
                iframe.remove();
//                setTimeout(iframe.remove}, 1);
            });
            $('body').append(iframe);
        });
    }
})(jQuery);
