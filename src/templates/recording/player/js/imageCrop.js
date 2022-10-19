let enableZoom = function () {
    let filterElement = $("label[for='filter']");
    let filterCheckbox = $("input[name='filter']");

    if (!filterCheckbox.prop("checked")) {
        filterElement.trigger("click");
    }
    $('#zoom-submit').prop("disabled", false);
}

let calculateDecimals = function (value) {
    if (Math.floor(value) === 0) {
        return 5 - (value * 1000).toString().split('.')[0].length;
    }

    if (0 < Math.floor(value) && Math.floor(value) < 10) {
        return 1;
    }

    return 0;
}

let setSelectionData = function (coordinates) {
    let xmin = (coordinates.x / specWidth * selectionDuration + minTime);
    let xmax = (coordinates.x2 / specWidth * selectionDuration + minTime);
    let ymax = Math.round((coordinates.y / specHeight) * -(maxFrequency - minFrequency) + maxFrequency);
    let ymin = Math.round((coordinates.y2 / specHeight) * -(maxFrequency - minFrequency) + maxFrequency);

    if (xmin === xmax || ymin === ymax) {
        xmin = minTime;
        xmax = maxTime;
        ymin = minFrequency;
        ymax = maxFrequency;
    }

    let decimals = calculateDecimals(xmax - xmin);

    //Values for Boxes Filter
    $('#x').val(xmin.toFixed(decimals));
    $('#w').val(xmax.toFixed(decimals));
    $('#y').val(ymin);
    $('#h').val(ymax);
}

selectData = function (coordinates) {
    enableZoom();
    setSelectionData(coordinates);
}


$(function () {
    $("#thumbnail").width($(".recording-navigation").width()).height('69px')
    var myJcrop = img_jcrop()
    myJcrop.destroy()
    myJcrop = img_jcrop()
    $(window).resize(function () {
        myJcrop.destroy()
        $("#thumbnail").width($(".recording-navigation").width()).height('69px')
        myJcrop = img_jcrop()
    })
})

function img_jcrop() {
    $(".tag-controls").each(function () {
        $(this).css('left', (parseFloat($(this).css('left')) / specWidth * $('#player_box').width()) + "px")
        $(this).css('width', (parseFloat($(this).css('width')) / specWidth * $('#player_box').width()) + "px")
    })
    specWidth = $('#player_box').width()
    var myJcrop = $.Jcrop('#cropbox', {
        boxWidth: $('#player_box').width(),
        boxHeight: 400,
        onChange: setSelectionData,
        onSelect: selectData,
        addClass: 'custom',
        bgColor: 'black',
    });
    $("#myCanvas > div.jcrop-holder.custom > div.jcrop-tracker").height('404px')
    $("#myCanvas > div.jcrop-holder.custom > div:nth-child(1) > div:nth-child(1) > img").height('400px')
    $("#myCanvas > div.jcrop-holder.custom > div:nth-child(1) > div:nth-child(1) > img").width($('#player_box').width())
    $(".jcrop-holder").height('400px')
    $(".jcrop-holder").width($('#player_box').width())
    $(".player_img").height('400px')
    $(".player_img").width($('#player_box').width())
    return myJcrop
}