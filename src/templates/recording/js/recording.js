$(function () {

    let shiftRate = 0.95;

    $("#shift-left").click(function (e) {
        let shiftLeftMin = Math.round(minTime - (selectionDuration * shiftRate));

        if (shiftLeftMin < 0) {
            shiftLeftMin = 0;
        }

        $("#x").val(shiftLeftMin);
        $("#w").val(Math.round(maxTime - (selectionDuration * shiftRate)));
        $('#y').val($('input[name="minFreqView"]').val())
        $('#h').val($('input[name="maxFreqView"]').val())
        $("#recordingForm").submit();

        e.preventDefault();
    });

    $("#shift-right").click(function (e) {
        let shiftRightMax = Math.round(maxTime + (selectionDuration * shiftRate));

        if (shiftRightMax > fileDuration) {
            shiftRightMax = fileDuration;
        }

        $("#x").val(Math.round(minTime + (selectionDuration * shiftRate)));
        $("#w").val(shiftRightMax);
        $('#y').val($('input[name="minFreqView"]').val())
        $('#h').val($('input[name="maxFreqView"]').val())
        $("#recordingForm").submit();

        e.preventDefault();
    });

    $(".viewport").click(function (e) {
        $("#x").val(0);
        $("#w").val(fileDuration);
        $("#y").val(1);
        $("#h").val(fileFreqMax);
        $("#type").val('');
        $("input[name=filter]").prop("checked", false);
        $("input[name=continuous_play]").prop("checked", false);
        $("input[name=estimateDistID]").val("");
        $("#recordingForm").submit();
        e.preventDefault();
    });

    $("#zoom-submit").click(function (e) {
        if ($.cookie('cookieConsent') == 'accepted') {
            const arr = [3, $('#zoom_in_input').val(), $('#zoom_out_input').val(), $('#zoom_input').val()]
            $.cookie('zoom_info', encodeURIComponent(JSON.stringify(arr)), {path: '/', expires: 180, samesite: 'None'});
        }
        $(this).prop("disabled", true);
        $("input[name=continuous_play]").prop("checked", false);
        $("#recordingForm").submit();
    });

    $(".zoom-btn").click(function (e) {
        if ($(this).attr('id') == 'btn-zoom-in') {
            if ($("#zoom_in_input").val() == '' || $("#zoom_in_input").val() <= 0 || $("#zoom_in_input").val() >= 100) {
                showAlert("Please enter a positive number smaller than 100 for zooming in")
                e.preventDefault();
                return false;
            }
            if ($.cookie('cookieConsent') == 'accepted') {
                const arr = [1, $('#zoom_in_input').val(), $('#zoom_out_input').val(), $('#zoom_input').val()]
                $.cookie('zoom_info', encodeURIComponent(JSON.stringify(arr)), {path: '/', expires: 180, samesite: 'None'});
            }
            const x = parseInt($('input[name=minTimeView]').val());
            const w = parseInt($('input[name=maxTimeView]').val());
            const y = parseInt($('input[name=minFreqView]').val());
            const h = parseInt($('input[name=maxFreqView]').val());
            const p = 1 - $("#zoom_in_input").val() / 100
            const centerX = (x + w) / 2;
            const centerY = (y + h) / 2;
            const newW = (w - x) * p;
            const newH = (h - y) * p;
            $("#x").val(Math.max(parseInt(centerX - newW / 2), 0));
            $("#w").val(Math.min(parseInt(centerX + newW / 2), fileDuration));
            $("#y").val(Math.max(parseInt(centerY - newH / 2), 1));
            $("#h").val(Math.min(parseInt(centerY + newH / 2), fileFreqMax));
            $("input[name=continuous_play]").prop("checked", false);
            $("#recordingForm").submit();
        } else if ($(this).attr('id') == 'btn-zoom-out') {
            if ($("#zoom_out_input").val() == '' || $("#zoom_out_input").val() <= 0 || $("#zoom_out_input").val() > 200) {
                showAlert("Please enter a positive number no more than 200 for zoom out")
                e.preventDefault();
                return false;
            }
            if ($.cookie('cookieConsent') == 'accepted') {
                const arr = [2, $('#zoom_in_input').val(), $('#zoom_out_input').val(), $('#zoom_input').val()]
                $.cookie('zoom_info', encodeURIComponent(JSON.stringify(arr)), {path: '/', expires: 180, samesite: 'None'});
            }
            const x = parseInt($('input[name=minTimeView]').val());
            const w = parseInt($('input[name=maxTimeView]').val());
            const y = parseInt($('input[name=minFreqView]').val());
            const h = parseInt($('input[name=maxFreqView]').val());
            const p = 1 + $("#zoom_out_input").val() / 100
            const centerX = (x + w) / 2;
            const centerY = (y + h) / 2;
            const newW = (w - x) * p;
            const newH = (h - y) * p;
            $("#x").val(Math.max(parseInt(centerX - newW / 2), 0));
            $("#w").val(Math.min(parseInt(centerX + newW / 2), fileDuration));
            $("#y").val(Math.max(parseInt(centerY - newH / 2), 1));
            $("#h").val(Math.min(parseInt(centerY + newH / 2), fileFreqMax));
            $("input[name=continuous_play]").prop("checked", false);
            $("#recordingForm").submit();
        } else {
            if ($("#zoom_input").val() == '' || $("#zoom_input").val() < 0) {
                showAlert('Please enter a positive integer')
                e.preventDefault();
                return false;
            }
            if ($.cookie('cookieConsent') == 'accepted') {
                const arr = [4, $('#zoom_in_input').val(), $('#zoom_out_input').val(), $('#zoom_input').val()]
                $.cookie('zoom_info', encodeURIComponent(JSON.stringify(arr)), {path: '/', expires: 180, samesite: 'None'});
            }
            $("#x").val(currentTime + minTime);
            $("#w").val(currentTime + minTime + ($('#player_box').width() / $("#zoom_input").val()));
            $("#y").val(1);
            $("#h").val(fileFreqMax);
            $("input[name=filter]").prop("checked", false);
            $("input[name=continuous_play]").prop("checked", true);
            $("#recordingForm").submit();
        }
        e.preventDefault();
    });

    $(".channel-left").click(function (e) {
        $("input[name=channel]").val(1);
        $("#recordingForm").submit();
        e.preventDefault();
    });

    $(".channel-right").click(function (e) {
        $("input[name=channel]").val(2);
        $("#recordingForm").submit();
        e.preventDefault();
    });

    $('.js-toggle-tags').click(function (e) {
        let show = this.dataset.show;
        this.dataset.show = show ? '' : 1;
        document.getElementsByName('showTags')[0].value = !show;

        $('.tag-controls').toggle();

        if (show) {
            $(this).find('i').removeClass('fa-eye-slash').addClass('fa-eye');
        } else {
            $(this).find('i').removeClass('fa-eye').addClass('fa-eye-slash');
        }
        e.preventDefault();
    });

    $('.js-new-tag').click(function (e) {
        e.preventDefault();

        if ($('#zoom-submit').is(':disabled')) {
            showAlert('Please, select an area of the spectrogram.');
            return;
        }
        requestModal(this.href, new FormData($("#recordingForm")[0]), false);
    });

    $('#recordingForm').on('submit', function () {
        $('.loading-grey').toggle();
    });

    if ($.cookie('zoom_info')) {
        const zoom_info = JSON.parse(decodeURIComponent($.cookie('zoom_info')));
        if (zoom_info[0] == 1) {
            $('#zoom-btn').html('<i class="fa-solid fa-magnifying-glass-plus"></i>');
            $("#zoom-btn").removeClass("a-disabled");
        } else if (zoom_info[0] == 2) {
            $('#zoom-btn').html('<i class="fa-solid fa-magnifying-glass-minus"></i>');
            $("#zoom-btn").removeClass("a-disabled");
        } else if (zoom_info[0] == 3) {
            $('#zoom-btn').html('<i class="fa-solid fa-magnifying-glass"></i>');
        } else if (zoom_info[0] == 4) {
            $('#zoom-btn').html('<i class="fa-solid fa-magnifying-glass-arrow-right"></i>');
            $("#zoom-btn").removeClass("a-disabled");
        }
        $('#zoom_in_input').val(zoom_info[1])
        $('#zoom_out_input').val(zoom_info[2])
        $('#zoom_input').val(zoom_info[3])
    }

    $('#zoom-btn').click(function (e) {
        if ($.cookie('zoom_info')) {
            const zoom_info = JSON.parse(decodeURIComponent($.cookie('zoom_info')));
            if (zoom_info[0] == 1) {
                $("#btn-zoom-in").trigger('click')
            } else if (zoom_info[0] == 2) {
                $("#btn-zoom-out").trigger('click')
            } else if (zoom_info[0] == 3) {
                $("#zoom-submit").trigger('click')
            } else if (zoom_info[0] == 4) {
                $("#zoom-pix").trigger('click')
            }
        } else {
            $("#zoom-submit").trigger('click')
        }
        e.preventDefault();
    })
});
