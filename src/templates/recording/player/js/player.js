let continuousPlaySelector = $("input[name='continuous_play']");

window.isContinuous = continuousPlaySelector.prop('checked');
isContinuous = window.isContinuous;
isContinuous = continuousPlaySelector.prop('checked');
isDirectStart = false;
estimateDistID = $("input[name=estimateDistID]").val();
selectionDuration = maxTime - minTime;
window.selectionDuration = selectionDuration;

if (typeof window.audioBufferQueue === 'undefined') {
    window.audioBufferQueue = [];
}
let maxBufferSize = 1;
let isBuffering = false;

if (estimateDistID && estimateDistID > 0) {
    isDirectStart = true;
}

continuousPlaySelector.change(function () {
    isContinuous = this.checked;
    window.isContinuous = this.checked;
    if (!isContinuous) {
        window.audioBufferQueue.length = 0;
    }
});

$('#continue-playback').click(function () {
    $(this).toggleClass('active');
    $('#btn-playback').click()
})

$('#stop').click(function () {
    setContinuousPlay(false);
    window.audioBufferQueue.length = 0;
});

function preloadNextSegment(segmentStartTime, segmentEndTime) {
    if (isBuffering || window.audioBufferQueue.length >= maxBufferSize) {
        return;
    }

    if (segmentStartTime >= fileDuration.toFixed(1)) {
        return;
    }

    isBuffering = true;

    let formData = new FormData($('#recordingForm')[0]);
    formData.set('t_min', segmentStartTime);
    formData.set('t_max', segmentEndTime);
    formData.set('f_min', $('input[name="minFreqView"]').val());
    formData.set('f_max', $('input[name="maxFreqView"]').val());
    formData.set('showTags', 'true');

    $.ajax({
        url: $('#recordingForm').attr('action'),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            let $response = $(response);
            let scriptContent = $response.filter('script').text() + $response.find('script').text();

            let soundPathMatch = scriptContent.match(/soundFilePath\s*=\s*['"](.*?)['"]/);
            if (!soundPathMatch) {
                console.error('Failed to extract sound path from response');
                isBuffering = false;
                return;
            }

            let audioPath = soundPathMatch[1];
            let imagePath = $response.find('.player_img').attr('src');
            let viewportPath = $response.find('#thumbnail').attr('src');
            let tags = $response.find('.tag-controls, .js-panel-tag');

            let minTimeMatch = scriptContent.match(/var minTime\s*=\s*([\d.]+)/);
            let maxTimeMatch = scriptContent.match(/var maxTime\s*=\s*([\d.]+)/);
            let minFreqMatch = scriptContent.match(/var minFrequency\s*=\s*([\d.]+)/);
            let maxFreqMatch = scriptContent.match(/var maxFrequency\s*=\s*([\d.]+)/);
            let specWidthMatch = scriptContent.match(/var specWidth\s*=\s*([\d.]+)/);
            let specHeightMatch = scriptContent.match(/var specHeight\s*=\s*([\d.]+)/);

            let audioRequest = new XMLHttpRequest();
            audioRequest.open('GET', audioPath, true);
            audioRequest.responseType = 'arraybuffer';
            audioRequest.onload = function () {
                let audioContext = new AudioContext({sampleRate: frequency});
                let offlineContext = new OfflineAudioContext(channelNum, 512, frequency);
                offlineContext.decodeAudioData(audioRequest.response).then(function (buffer) {
                    let actualMinTime = minTimeMatch ? parseFloat(minTimeMatch[1]) : segmentStartTime;
                    let actualMaxTime = maxTimeMatch ? parseFloat(maxTimeMatch[1]) : segmentEndTime;

                    window.audioBufferQueue.push({
                        buffer: buffer,
                        minTime: actualMinTime,
                        maxTime: actualMaxTime,
                        minFrequency: minFreqMatch ? parseFloat(minFreqMatch[1]) : minFrequency,
                        maxFrequency: maxFreqMatch ? parseFloat(maxFreqMatch[1]) : maxFrequency,
                        specWidth: specWidthMatch ? parseFloat(specWidthMatch[1]) : specWidth,
                        specHeight: specHeightMatch ? parseFloat(specHeightMatch[1]) : specHeight,
                        imagePath: imagePath,
                        viewportPath: viewportPath,
                        tags: tags,
                        audioPath: audioPath
                    });
                    isBuffering = false;
                }).catch(function (err) {
                    console.error('Error decoding buffered audio:', err);
                    isBuffering = false;
                });
            };
            audioRequest.onerror = function () {
                console.error('Error loading buffered audio file');
                isBuffering = false;
            };
            audioRequest.send();
        },
        error: function (xhr, status, error) {
            console.error('Failed to load segment metadata:', error);
            isBuffering = false;
        }
    });
}

continuousPlay = function () {
    if (window.audioBufferQueue.length > 0) {
        let nextSegment = window.audioBufferQueue.shift();

        minTime = nextSegment.minTime;
        maxTime = nextSegment.maxTime;
        minFrequency = nextSegment.minFrequency;
        maxFrequency = nextSegment.maxFrequency;
        specWidth = nextSegment.specWidth;
        specHeight = nextSegment.specHeight;
        window.specWidth = specWidth;
        selectionDuration = maxTime - minTime;
        window.selectionDuration = selectionDuration;
        soundFilePath = nextSegment.audioPath;

        $('#x').val(minTime);
        $('#w').val(maxTime);
        $('#y').val(minFrequency);
        $('#h').val(maxFrequency);
        $('input[name="minTimeView"]').val(minTime);
        $('input[name="maxTimeView"]').val(maxTime);
        $('input[name="minFreqView"]').val(minFrequency);
        $('input[name="maxFreqView"]').val(maxFrequency);
        window.table.ajax.url(window.table.ajax.url().replace(/minTime=[^&]*/, 'minTime=' + minTime).replace(/maxTime=[^&]*/, 'maxTime=' + maxTime)).load();
        if (nextSegment.imagePath) {
            let $img = $('.player_img');
            let imageLoadPromise = new Promise(function (resolve) {
                $img.one('load', function () {
                    let actualWidth = $(this).width();
                    if (actualWidth > 0) {
                        specWidth = actualWidth;
                        window.specWidth = actualWidth;
                    }
                    resolve();
                });
                $img.attr('src', nextSegment.imagePath);

                if ($img[0].complete) {
                    $img.trigger('load');
                }
            });

            imageLoadPromise.then(function () {
                continuePlayback();
            });
        } else {
            continuePlayback();
        }

        function continuePlayback() {
            if (nextSegment.viewportPath) {
                $('#thumbnail').attr('src', nextSegment.viewportPath);
            }

            $('.tag-controls').remove();
            $('.js-panel-tag').remove();
            let showTags = $('input[name="showTags"]').val() === 'true' || $('input[name="showTags"]').val() === '1';
            $('#myCanvas').prepend(nextSegment.tags);
            $("#myCanvas .tag-controls").each(function () {
                $(this).css('left', (parseFloat($(this).css('left')) / nextSegment.specWidth * $('#player_box').width()) + "px")
                $(this).css('width', (parseFloat($(this).css('width')) / nextSegment.specWidth * $('#player_box').width()) + "px")
            })
            if (!showTags) {
                $('.tag-controls').hide();
            }

            $('#time_sec_div').html(Math.round(minTime));

            currentTime = 0;
            if (typeof resetCursor === 'function') {
                resetCursor();
            } else if (typeof window.resetCursor === 'function') {
                window.resetCursor();
            }

            $(document).trigger('updateAudioBuffer', [nextSegment.buffer]);

            setTimeout(function () {
                if (isContinuous && $('#play').attr('data-playing') === 'false') {
                    $('#play').trigger('click');
                }
                myCanvas
            }, 50);
        }

        if (maxTime < fileDuration.toFixed(1)) {
            let nextStart = maxTime;
            let nextEnd = Math.min(nextStart + selectionDuration, fileDuration.toFixed(1));
            preloadNextSegment(nextStart, nextEnd);
        }
    } else if (fileDuration.toFixed(1) > maxTime) {
        $('#x').val(maxTime);
        $('#w').val(Math.min(maxTime + selectionDuration, fileDuration.toFixed(1)));
        $('#recordingForm').submit();
    } else {
        setContinuousPlay(false);
    }
};

let setContinuousPlay = function (value) {
    isContinuous = value;
    window.isContinuous = value;
    continuousPlaySelector.prop('checked', value);
    if (value) {
        $("label[for='continuous-play']").addClass('active');
        $('#continue-playback').addClass('active');
    } else {
        $("label[for='continuous-play']").removeClass('active');
        $('#continue-playback').removeClass('active');
    }
};

window.continuousPlay = continuousPlay;
window.setContinuousPlay = setContinuousPlay;

$('#play-dropdown').click(function (event) {
    event.stopPropagation();
    $('#dropdown-menu-play').slideToggle();
    $(this).find('i').toggleClass('fa-caret-down fa-caret-up');
});

$('#dropdown-menu-play').click(function (event) {
    event.stopPropagation();
});

$(document).on('audioPlaybackStarted', function () {
    if (isContinuous && window.audioBufferQueue.length === 0 && maxTime < fileDuration.toFixed(1)) {
        let nextStart = maxTime;
        let nextEnd = Math.min(nextStart + selectionDuration, fileDuration.toFixed(1));
        preloadNextSegment(nextStart, nextEnd);
    }
});

$(document).ready(function () {
    if (isContinuous && maxTime < fileDuration.toFixed(1)) {
        setTimeout(function () {
            let nextStart = maxTime;
            let nextEnd = Math.min(nextStart + selectionDuration, fileDuration.toFixed(1));
            preloadNextSegment(nextStart, nextEnd);
        }, 500);
    }
});