let playButton = $('#play');
let playbackControl = document.querySelector('.js-playback-rate-control');
let playbackValue = document.querySelector('.playback-rate-value');

// Quick fix for Firefox and Safari
var userAgent = navigator.userAgent;
if (userAgent.indexOf("Firefox") > -1) {
    if (frequency < 8000) {
        frequency = 8000;
    } else if (frequency > 192000) {
        frequency = 192000;
        showAlert("Firefox cannot playback files with sampling frequencies > 192 kHz. Please switch to another browser such as Chrome.");
    }
}

let context = new AudioContext({sampleRate: frequency});
let offctx = new OfflineAudioContext(channelNum, 512, frequency);  // length (512) doesn't matter, just non-zero
let source = null;
let bufferPlay = null;
let request = new XMLHttpRequest();

let startTime = 0;
let pauseTime = 0;
let elapsedRateTime = 0;
let pause = false;
let seek = 0;
let clock;
let download = 0;
let estimateDistID = $("input[name=estimateDistID]").val();
let isDirectStart = false;

if (estimateDistID && estimateDistID > 0) {
    isDirectStart = true;
}

if ($("#continuous-play").is(':checked') || isDirectStart) {
    playButton.prop('disabled', true);
    request.open('GET', soundFilePath, true);
    request.responseType = 'arraybuffer';
    request.onload = function () {
        offctx.decodeAudioData(request.response).then(function (buffer) {
            console.log("Sample rate of buffer: " + buffer.sampleRate);
            playButton.prop('disabled', false);
            bufferPlay = buffer;
            let isCurrentlyContinuous = $("#continuous-play").is(':checked') || (typeof window.isContinuous !== 'undefined' && window.isContinuous);
            if (isCurrentlyContinuous || isDirectStart) {
                playButton.trigger('click');
                isDirectStart = false;
            }
        });
    }
    request.send();
    download = 1
}

playButton.click(function () {
    if (download === 0) {
        playButton.prop('disabled', true);
        request.open('GET', soundFilePath, true);
        request.responseType = 'arraybuffer';
        request.onload = function () {
            offctx.decodeAudioData(request.response).then(function (buffer) {
                console.log("Sample rate of buffer: " + buffer.sampleRate);
                bufferPlay = buffer;
                playButton.prop('disabled', false);
                playButton.trigger('click');
                isDirectStart = false;
            });
        }
        request.send();
        download = 1
    } else {
        if (this.dataset.playing === 'false') {
            createSource();
            if (currentTime >= bufferPlay.duration) {
                currentTime = 0;
                if (typeof window.resetCursor === 'function') {
                    window.resetCursor();
                }
            }
            let currentSelectionDuration = window.selectionDuration || selectionDuration;
            let remainingDuration = Math.min(bufferPlay.duration - currentTime, currentSelectionDuration - currentTime);
            source.start(0, currentTime, remainingDuration);
            startTime = context.currentTime;
            clock = setInterval(function () {
                getCurrentTime();
            }, 30);

            if (!playStartTime) {
                playStartTime = new Date().valueOf() / 1000;
            }
            this.dataset.playing = 'true';
            playButton.html('<span class="fas fa-pause"></span>');
            $('#playerCursor').draggable('disable');
        } else if (this.dataset.playing === 'true') {
            pause = true;
            seek = 0;
            clearSource();
        }
    }
});

$("#stop").click(function () {
    if (!source) {
        stop();
        return;
    }
    clearSource();
});

playbackControl.oninput = function () {
    if (source !== null) {
        elapsedRateTime = currentTime - ((context.currentTime - startTime) * this.value);
        source.playbackRate.value = this.value;
        seek = 0;
    }
    playbackValue.innerHTML = this.value;
};

$('#playerCursor').draggable({
    axis: 'x',
    containment: 'parent',
    cursor: 'ew-resize',
    drag: function () {
        let currentSelectionDuration = window.selectionDuration || selectionDuration;
        let currentSpecWidth = window.specWidth || specWidth;
        seek = parseFloat(this.style.left) / currentSpecWidth * currentSelectionDuration;

        if (bufferPlay && bufferPlay.duration && seek >= bufferPlay.duration) {
            seek = bufferPlay.duration - 0.1;
        }
        currentTime = seek;

        $("#time_sec_div").html(Math.round(minTime + seek));
        pauseTime = 0;
        elapsedRateTime = 0;
    }
});

function clearSource() {
    if (source) {
        source.stop();
        source = null;
    }
}

function stop() {
    clearInterval(clock);

    $('#playerCursor').draggable('enable');
    pauseTime = currentTime;
    elapsedRateTime = 0;

    if (!pause) {
        pauseTime = 0;
        startTime = 0;
        currentTime = 0;
        resetCursor();
        $("#time_sec_div").html(Math.round(minTime));
    }

    playButton.html('<i class="fas fa-play"></i>');
    playButton.attr('data-playing', false);
    pause = false;

    //Distance estimation popup after playing
    let isCurrentlyContinuous = (typeof window.isContinuous !== 'undefined') ? window.isContinuous : (typeof isContinuous !== 'undefined' ? isContinuous : false);
    if (estimateDistID && estimateDistID > 0 && !isCurrentlyContinuous) {
        requestModal(baseUrl + '/tag/showCallDistance/' + estimateDistID);
    }
}

function createSource() {
    source = context.createBufferSource();
    source.buffer = bufferPlay;
    source.loop = false;
    source.onended = function () {
        postRequest(
            baseUrl + '/PlayLog/save',
            {
                recordingId: recordingId,
                userId: userId,
                startTime: playStartTime,
                stopTime: new Date().valueOf() / 1000,
            }
        )
        let isCurrentlyContinuous = (typeof window.isContinuous !== 'undefined') ? window.isContinuous : isContinuous;
        if (isCurrentlyContinuous && !pause) {
            clearInterval(clock);
            $('#playerCursor').draggable('enable');

            let currentSpecWidth = window.specWidth || specWidth;
            playerCursor.style.left = currentSpecWidth + 'px';

            // 重置播放状态
            pauseTime = 0;
            startTime = 0;
            currentTime = 0;
            elapsedRateTime = 0;
            seek = 0;
            pause = false;
            $('#play').attr('data-playing', 'false');
            $('#play').html('<i class="fas fa-play"></i>');

            // 调用连续播放
            if (typeof window.continuousPlay === 'function') {
                window.continuousPlay();
            } else if (typeof continuousPlay === 'function') {
                continuousPlay();
            } else {
                stop();
            }
        } else {
            stop();
        }
    };
    source.connect(context.destination);
    source.playbackRate.value = playbackControl.value;

    let isCurrentlyContinuous = (typeof window.isContinuous !== 'undefined') ? window.isContinuous : isContinuous;
    if (isCurrentlyContinuous) {
        $(document).trigger('audioPlaybackStarted');
    }
}

function getCurrentTime() {
    if (source) {
        currentTime = (context.currentTime - startTime) * source.playbackRate.value + elapsedRateTime + seek;
        currentTime += elapsedRateTime === 0 ? pauseTime : 0;

        moveCursor(currentTime);
        $("#time_sec_div").html(Math.round(minTime + currentTime)); //Add minTime to offset when zooming
    }
}

function resetCursor() {
    playerCursor.style.left = 0;
    seek = 0;
}

function moveCursor(time) {
    let currentSelectionDuration = window.selectionDuration || selectionDuration;
    let currentSpecWidth = window.specWidth || specWidth;
    playerCursor.style.left = (time < 0 ? 0 : time / currentSelectionDuration) * currentSpecWidth + 'px';
}

if (typeof window !== 'undefined') {
    window.resetCursor = resetCursor;
    window.moveCursor = moveCursor;
}

$(document).on('updateAudioBuffer', function (event, buffer) {
    bufferPlay = buffer;
    download = 1;
});

$('.player_img').on('click', function (e) {
    if (e.ctrlKey && $('#play').attr('data-playing') === 'false') {
        let offset = $(this).offset();
        let clickX = e.pageX - offset.left;

        let clickedTime = clickX / specWidth * selectionDuration;
        if (clickedTime < 0) clickedTime = 0;
        if (clickedTime > selectionDuration) clickedTime = selectionDuration;

        currentTime = clickedTime;
        pauseTime = currentTime;
        elapsedRateTime = 0;
        seek = 0;

        moveCursor(currentTime);

        $("#time_sec_div").html(Math.round(minTime + currentTime));
    }
});

$(document).on('keydown', function (e) {
    if (e.code === 'Space') {
        if ($(e.target).is('input, textarea')) return;
        e.preventDefault();
        $('#play').trigger('click');
    }
});