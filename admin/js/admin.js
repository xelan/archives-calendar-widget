/* PREVIEW MODAL */
jQuery(document).ready(function ($) {
    $('.calendar-archives.preview a').on('click', function (e) {
        e.preventDefault();
    });
    $('.preview_theme_select li.preview-theme').on('click', function () {
        var d = (new Date()).getTime();
        var theme = $(this).attr('id');
        $(".arcw.preview-zone .calendar-archives ").addClass(theme);
        select_preview(theme);
        $("#ac_preview_css").remove();
        $("head").append('<link id="ac_preview_css" href="' + ARCWPATH + '/themes/' + theme + '.css?v=' + d + '" type="text/css" rel="stylesheet" />');
    });

    $('.button.preview_theme').on('click', function () {
        //console.log('click preview')
        var d = (new Date()).getTime();
        var theme = $('select.theme_select').val();
        $(".arcw.preview-zone .calendar-archives ").addClass(theme);
        //$('#themepreview option[value='+css+']').attr('selected', true);
        select_preview(theme);
        $("#ac_preview_css").remove();
        $("head").append('<link id="ac_preview_css" href="' + ARCWPATH + '/themes/' + theme + '.css?v=' + d + '" type="text/css" rel="stylesheet" />');
    });

    $('.ok_theme').on('click', function () {
        tb_remove();
        $('select.theme_select option[value=' + $('.preview-theme.selected').attr('id') + ']').attr('selected', true);
    });
    $('.cancel_theme').on('click', function () {
        tb_remove();
    });

    function select_preview(theme) {
        $('.preview_theme_select li').removeClass('selected');
        $('.preview_theme_select li#' + theme).addClass('selected');
    }

    /* PREVIEW CALENDAR MENU */
    var $calendarArchives = $('.calendar-archives');

    $calendarArchives.find('.arrow-down').on('click', function () {
        $(this).parent().children('.menu').show();
    });

    $calendarArchives.find('.menu')
        .mouseleave(function () {
            var menu = $(this);
            window.arctimer = setTimeout(
                function () {
                    menu.parent().children('.menu').hide();
                },
                300
            );
        })
        .mouseenter(function () {
            if (window.arctimer) {
                clearTimeout(window.arctimer);
                window.arctimer = undefined;
            }
        });
});
