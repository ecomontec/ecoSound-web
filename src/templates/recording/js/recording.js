$(function () {

    let shiftRate = 0.95;

    $("#shift-left").click(function (e) {
        let shiftLeftMin = Math.round(minTime - (selectionDuration * shiftRate));

        if (shiftLeftMin < 0) {
            shiftLeftMin = 0;
        }

        $("#x").val(shiftLeftMin);
        $("#w").val(Math.round(maxTime - (selectionDuration * shiftRate)));
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
        $("#recordingForm").submit();

        e.preventDefault();
    });

    $(".viewport").click(function (e) {
        $("#x").val(0);
        $("#w").val(fileDuration);
        $("#y").val(1);
        $("#h").val(fileFreqMax);
        $("input[name=filter]").prop("checked", false);
        $("input[name=continuous_play]").prop("checked", false);
        $("input[name=estimateDistID]").val("");
        $("#recordingForm").submit();
        e.preventDefault();
    });

    $("#zoom-submit").click(function (e) {
        $(this).prop("disabled", true);
        $("#recordingForm").submit();
    });

    $("#readingModeSelect").change(function (e) {
        if ($("#readingModeSelect").val() == 'birds') {
            $("#readingModeInput").val(60)
        } else if ($("#readingModeSelect").val() == 'bats') {
            $("#readingModeInput").val(10)
        }
    });
    $("#readingModeInput").change(function (e) {
        $("#readingModeSelect option[value='others']").attr("selected", true)
    });

    $(".readingMode").click(function (e) {
        $("#x").val(currentTime + minTime);
        $("#w").val(currentTime + minTime +  $("#readingModeInput").val());
        $("#y").val(1);
        $("#h").val(fileFreqMax);
        $("input[name=filter]").prop("checked", false);
        $("input[name=continuous_play]").prop("checked", true);
        $("#recordingForm").submit();
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
        toggleLoading();
    });
});
