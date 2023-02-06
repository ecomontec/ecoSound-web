$(function () {

    $('.view').click(function () {
        $(this).addClass('active');
    });

    $("#btn_map").click(function () {
        $("#map").toggle()
        $.cookie('map', $("#btn_map").text(), {expires: 7});
        if ($("#btn_map").text() == 'Hide Map') {
            $("#btn_map").text('Show Map')
        } else {
            $("#btn_map").text('Hide Map')

        }
    });
});
$(document).ready(function () {
    if ($.cookie('map') == 'Hide Map') {
        $("#btn_map").text('Show Map')
        $("#map").hide()
    } else {
        $("#btn_map").text('Hide Map')
        $("#map").show()
    }
});