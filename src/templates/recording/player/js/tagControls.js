$('.canvas')
    .on('mouseleave', '.tag-controls', function () {
        $(".js-panel-tag").hide();
        $(this).css("background-color", '');
    })
    .on('click', '.tag-controls', function (e) {
        e.preventDefault();
        $(this).css("background-color", "rgba(255,255,255, 0.15)");

        let controls = $(this).next();
        $(this).next()
            .css("top", e.pageY - $(this).parent().offset().top)
            .css("left", e.pageX - $(this).parent().offset().left);

        if (!$(this).next().is(':visible')) {
            $(this).next().fadeIn(400);
        }
    })
    .on('mouseenter', '.js-panel-tag', function () {
        $(this).prev().css("background-color", "rgba(255,255,255, 0.15)");
        $(this).show().not(this).hide();
    })
    .on('mouseleave', '.js-panel-tag', function () {
        $(this).prev().css("background-color", '');
        $(this).fadeOut("fast");
    })
    .on('click', '.zoom-tag', function (e) {
        let canvasPosition = $('#myCanvas')[0].getBoundingClientRect();
        let tagElement = $(this).parent().parent().parent().prev()[0].getBoundingClientRect();
        let left = tagElement.left - canvasPosition.left;
        let top = tagElement.top - canvasPosition.top;
        let x = $('#x').val();
        let w = $('#w').val();
        let y = $('#y').val();
        let h = $('#h').val();
        selectData({
            x: left,
            x2: left + tagElement.width,
            y: top,
            y2: top + tagElement.height,
        });
        if (e.altKey) {
            var tempform = document.createElement('form');
            tempform.action = window.location.href;
            tempform.target = "_blank";
            tempform.method = "post";
            tempform.style.display = "none";
            var t = $('#recordingForm').serializeArray();
            $.each(t, function () {
                var opt = document.createElement("input");
                opt.setAttribute('name', this.name);
                opt.setAttribute('value', this.value);
                tempform.appendChild(opt);
            });
            $('#x').val(x);
            $('#w').val(w);
            $('#y').val(y);
            $('#h').val(h);
            document.body.appendChild(tempform);
            tempform.submit();
            tempform.remove();
        } else {
            $('#recordingForm').submit();
        }
        e.preventDefault();
    })
    .on('click', '.js-tag', function (e) {
        $('.js-panel-tag').hide();
        e.preventDefault();
        if ($("#open").val()) {
            requestModal(this.href, {'recording_name': document.getElementsByName('recording_name')[0].value}, false, false);
        } else {
            requestModal(this.href, {'recording_name': document.getElementsByName('recording_name')[0].value});
        }
    })
    .on('click', '.estimate-distance', function (e) {
        let tagElement = $(this).parent().parent().parent().prev()[0].getBoundingClientRect();
        let left = tagElement.left - $('#myCanvas')[0].getBoundingClientRect().left;
        let width = left + tagElement.width;

        let startTime = (left / specWidth * selectionDuration + minTime);
        let endTime = (width / specWidth * selectionDuration + startTime);
        endTime = (endTime - startTime) > 30 ? startTime + 30 : endTime;

        $('#x').val(startTime);
        $('#w').val(endTime);
        $('#y').val(1);
        $('#h').val(fileFreqMax);

        $("input[name=filter]").prop('checked', false);
        $("input[name=continuous_play]").prop('checked', false);
        $("input[name=estimateDistID]").val(this.id.substring(this.id.indexOf('_') + 1, this.id.length)); //Set Tag ID
        $("#recordingForm").submit();
        e.preventDefault();
    });
