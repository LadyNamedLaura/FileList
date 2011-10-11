$('.small_remove_button').click(function (event) {
    if (!confirm(mw.msg('fl_remove_confirm',$(this).attr('fname'))))
        event.preventDefault();
    });

