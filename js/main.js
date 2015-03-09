$(document).ready(function() {
    //Make current page active on menu
    $("nav > ul > li > a").each(function(){
        if ($(this).attr("href") == window.location.pathname)
            $(this).addClass("active");
    });

    $('.minmax_icon,.form_container > h1')
        .click(function(){
            $(this).siblings("form").stop().slideToggle();
            toggleIcon(true, null);
        })
        .mouseenter(function(){
            toggleIcon(false, '-34px');
        })
        .mouseleave(function(){
            toggleIcon(false, '0px');
        });

    $('#clearForm').click(function (e) {
        $(this).parents('form').trigger('reset');
        $('input:hidden').val(0);
        return false; //Don't set url to #
    });
});

$(document).on("submit", "#barcode_scan_form", function(e) {
    e.preventDefault();
    //Hide form
    $(this).stop().slideUp();
    toggleIcon(true, null);
    $("#results").html('');
    $('.loading_gif').show();
    var dataArray = $(this).serialize();
    $.post('../inc/fetch_labels.php', dataArray, function(data) {
        $("#results").html(data);
    })
    .fail(function(data, status, jqXHR) {
        $("#results").html("<h3>Could not get results.</h3>");
    })
    .always(function() {
        $('.loading_gif').hide();
    });
});

function toggleIcon(vert, pos) {
    var bgPos = $('.minmax_icon').css('background-position').split(" ");
    if (vert == true) {
        if (bgPos[1] == '0px') {
            $('.minmax_icon').css('background-position', bgPos[0] + ' -34px');
        }
        else {
            $('.minmax_icon').css('background-position', bgPos[0] + ' 0px');
        }
    } else {
        $('.minmax_icon').css('background-position', pos + ' ' + bgPos[1]);
    }
}