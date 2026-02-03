$(function () {

    let shiftRate = 0.95;

    $("#shift-left").click(function (e) {
        e.preventDefault();
        
        // Don't execute if button is disabled
        if ($(this).hasClass('a-disabled')) {
            return false;
        }
        
        let shiftLeftMin = Math.round(minTime - (selectionDuration * shiftRate));

        if (shiftLeftMin < 0) {
            shiftLeftMin = 0;
        }

        $("#x").val(shiftLeftMin);
        $("#w").val(Math.round(maxTime - (selectionDuration * shiftRate)));
        $('#y').val($('input[name="minFreqView"]').val())
        $('#h').val($('input[name="maxFreqView"]').val())

        if (typeof window.audioBufferQueue !== 'undefined') {
            window.audioBufferQueue.length = 0;
        }

        $("#recordingForm").submit();

        e.preventDefault();
    });

    $("#shift-right").click(function (e) {
        e.preventDefault();
        
        // Don't execute if button is disabled
        if ($(this).hasClass('a-disabled')) {
            return false;
        }
        
        let shiftRightMax = Math.round(maxTime + (selectionDuration * shiftRate));

        if (shiftRightMax > fileDuration) {
            shiftRightMax = fileDuration;
        }

        $("#x").val(Math.round(minTime + (selectionDuration * shiftRate)));
        $("#w").val(shiftRightMax);
        $('#y').val($('input[name="minFreqView"]').val())
        $('#h').val($('input[name="maxFreqView"]').val())

        if (typeof window.audioBufferQueue !== 'undefined') {
            window.audioBufferQueue.length = 0;
        }

        $("#recordingForm").submit();

        e.preventDefault();
    });

    $(".viewport").click(function (e) {
        $("#x").val(0);
        $("#w").val(fileDuration);
        $("#y").val(1);
        $("#h").val(fileFreqMax);
        $("#type").val('');
        // Note: filter state is NOT reset here - it persists as per user setting
        $("input[name=continuous_play]").val('0');
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
        $("input[name=continuous_play]").val('0');

        if (typeof window.audioBufferQueue !== 'undefined') {
            window.audioBufferQueue.length = 0;
        }

        $("#recordingForm").submit();
    });

    $(".zoom-option, #btn-zoom-in, #btn-zoom-out, #zoom-pix").click(function (e) {
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
            const x = parseFloat($('input[name=minTimeView]').val());
            const w = parseFloat($('input[name=maxTimeView]').val());
            const y = parseInt($('input[name=minFreqView]').val());
            const h = parseInt($('input[name=maxFreqView]').val());
            const p = 1 - $("#zoom_in_input").val() / 100
            const centerX = (x + w) / 2;
            const centerY = (y + h) / 2;
            const newW = (w - x) * p;
            const newH = (h - y) * p;
            $("#x").val(Math.max(parseFloat(centerX - newW / 2), 0));
            $("#w").val(Math.min(parseFloat(centerX + newW / 2), fileDuration));
            $("#y").val(Math.max(parseInt(centerY - newH / 2), 1));
            $("#h").val(Math.min(parseInt(centerY + newH / 2), fileFreqMax));
            $("input[name=continuous_play]").val('0');
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
            const x = parseFloat($('input[name=minTimeView]').val());
            const w = parseFloat($('input[name=maxTimeView]').val());
            const y = parseInt($('input[name=minFreqView]').val());
            const h = parseInt($('input[name=maxFreqView]').val());
            const p = 1 + $("#zoom_out_input").val() / 100
            const centerX = (x + w) / 2;
            const centerY = (y + h) / 2;
            const newW = (w - x) * p;
            const newH = (h - y) * p;
            $("#x").val(Math.max(parseFloat(centerX - newW / 2), 0));
            $("#w").val(Math.min(parseFloat(centerX + newW / 2), fileDuration));
            $("#y").val(Math.max(parseInt(centerY - newH / 2), 1));
            $("#h").val(Math.min(parseInt(centerY + newH / 2), fileFreqMax));
            $("input[name=continuous_play]").val('0');
            $("#recordingForm").submit();
        } else if ($(this).attr('id') == 'zoom-pix') {
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
            $("input[name=filter]").val('0');
            $("input[name=continuous_play]").val('0');
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

    // Use event delegation for toggle-tags so it works with dynamically added buttons in sidebar
    $(document).on('click', '.js-toggle-tags', function (e) {
        let show = this.dataset.show;
        this.dataset.show = show ? '' : 1;
        document.getElementsByName('showTags')[0].value = !show;

        // Toggle both tag boxes on spectrogram and tags table
        $('.tag-controls').toggle();
        $('.tagsForm').toggle();

        if (show) {
            // Tags are now hidden, button shows "Show Tags" - use green (active state)
            $(this).html('<i class="fas fa-eye"></i> Show Tags');
            $(this).removeClass('btn-outline-secondary').addClass('btn-outline-success');
        } else {
            // Tags are now visible, button shows "Hide Tags" - use grey (inactive state)
            $(this).html('<i class="fas fa-eye-slash"></i> Hide Tags');
            $(this).removeClass('btn-outline-success').addClass('btn-outline-secondary');
        }
        
        // Also update any other toggle buttons on the page to keep them in sync
        $('.js-toggle-tags').not(this).each(function() {
            this.dataset.show = show ? '' : 1;
            if (show) {
                $(this).html('<i class="fas fa-eye"></i> Show Tags');
                $(this).removeClass('btn-outline-secondary').addClass('btn-outline-success');
            } else {
                $(this).html('<i class="fas fa-eye-slash"></i> Hide Tags');
                $(this).removeClass('btn-outline-success').addClass('btn-outline-secondary');
            }
        });
        
        e.preventDefault();
    });

    // Handle filter toggle button
    $(document).on('click', '#filter-toggle', function(e) {
        e.preventDefault();
        const $hiddenInput = $('#filter-hidden');
        const $statusText = $('#filter-status');
        const $button = $(this);
        const currentValue = $hiddenInput.val();
        
        // Toggle between '1' (ON) and '0' (OFF)
        if (currentValue === '1') {
            $hiddenInput.val('0');
            $statusText.text('OFF');
            $button.removeClass('btn-success').addClass('btn-outline-success');
        } else {
            $hiddenInput.val('1');
            $statusText.text('ON');
            $button.removeClass('btn-outline-success').addClass('btn-success');
        }
    });

    // Initialize filter button state on page load
    $(document).ready(function() {
        const $hiddenInput = $('#filter-hidden');
        const $statusText = $('#filter-status');
        const $button = $('#filter-toggle');
        if ($hiddenInput.val() === '1') {
            $statusText.text('ON');
            $button.removeClass('btn-outline-success').addClass('btn-success');
        } else {
            $statusText.text('OFF');
            $button.removeClass('btn-success').addClass('btn-outline-success');
        }
        
        // Disable shift buttons if at boundaries
        updateShiftButtonStates();
    });
    
    // Function to update shift button states based on current position
    function updateShiftButtonStates() {
        const $shiftLeft = $('#shift-left');
        const $shiftRight = $('#shift-right');
        
        // Check if we're at the start (can't shift left)
        if (minTime <= 0) {
            $shiftLeft.addClass('a-disabled');
        } else {
            $shiftLeft.removeClass('a-disabled');
        }
        
        // Check if we're at the end (can't shift right)
        if (maxTime >= fileDuration) {
            $shiftRight.addClass('a-disabled');
        } else {
            $shiftRight.removeClass('a-disabled');
        }
    }

    // Use event delegation for new-tag so it works with dynamically added buttons in sidebar
    $(document).on('click', '.js-new-tag', function (e) {
        e.preventDefault();

        if ($('#zoom-submit').hasClass('a-disabled')) {
            showAlert('Please, select an area of the spectrogram.');
            return;
        }
        requestModal(this.href, new FormData($("#recordingForm")[0]), false);
    });

    $('#recordingForm').on('submit', function () {
        $('.loading-grey').toggle();
    });

    // Restore zoom input values from cookie
    if ($.cookie('zoom_info')) {
        const zoom_info = JSON.parse(decodeURIComponent($.cookie('zoom_info')));
        $('#zoom_in_input').val(zoom_info[1])
        $('#zoom_out_input').val(zoom_info[2])
        $('#zoom_input').val(zoom_info[3])
    }
});
