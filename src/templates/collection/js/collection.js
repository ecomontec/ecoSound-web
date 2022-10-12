$(function () {

    $('.view').click(function () {
        $(this).addClass('active');
    });

    $("#btn_map").click(function () {
        $("#map").toggle()
        if ($("#btn_map").text() == 'Hide Map') {
            $("#btn_map").text('Show Map')
        } else {
            $("#btn_map").text('Hide Map')
        }
    });
});