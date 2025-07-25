let continuousPlaySelector = $("input[name='continuous_play']");

isContinuous = continuousPlaySelector.prop('checked');
isDirectStart = false;
estimateDistID = $("input[name=estimateDistID]").val();
selectionDuration = maxTime - minTime;

if (estimateDistID && estimateDistID > 0) {
    isDirectStart = true;
}

continuousPlaySelector.change(function () {
    isContinuous = this.checked;
});

$('#continue-playback').click(function () {
    $(this).toggleClass('active');
    $('#btn-playback').click()
})

$('#stop').click(function () {
    setContinuousPlay(false);
});

continuousPlay = function () {
    if (fileDuration.toFixed(1) > maxTime) {
        $('#x').val(maxTime);
        $('#w').val((maxTime + selectionDuration) > fileDuration.toFixed(1) ? fileDuration.toFixed(1) : (maxTime + selectionDuration));
        $('#y').val($('input[name="minFreqView"]').val())
        $('#h').val($('input[name="maxFreqView"]').val())
        $('#recordingForm').submit();
    }
};

let setContinuousPlay = function (value) {
    isContinuous = value;
    continuousPlaySelector.prop('checked', value);
    if (value) {
        $("label[for='continuous-play']").addClass('active');
        $('#continue-playback').addClass('active');
    } else {
        $("label[for='continuous-play']").removeClass('active');
        $('#continue-playback').removeClass('active');
    }
};

$('#play-dropdown').click(function (event) {
    event.stopPropagation();
    $('#dropdown-menu-play').slideToggle();
    $(this).find('i').toggleClass('fa-caret-down fa-caret-up');
});

$('#dropdown-menu-play').click(function (event) {
    event.stopPropagation();
});