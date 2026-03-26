$('.canvas')
    .on('mouseleave', '.tag-controls', function (e) {
        // Remove hover highlight (but keep selected highlight if selected)
        $(this).removeClass('tag-hover');
        
        // Don't hide popup if mouse is moving to the popup
        let $popup = $(this).next('.js-panel-tag');
        let popupRect = $popup.length ? $popup[0].getBoundingClientRect() : null;
        if (popupRect && e.clientX >= popupRect.left && e.clientX <= popupRect.right &&
            e.clientY >= popupRect.top && e.clientY <= popupRect.bottom) {
            return;
        }
        // Small delay to allow moving to popup
        let $tag = $(this);
        setTimeout(function() {
            if (!$popup.is(':hover') && !$tag.is(':hover')) {
                $popup.fadeOut('fast');
            }
        }, 100);
    })
    .on('mouseenter', '.tag-controls', function (e) {
        // Add hover highlight (yellow border)
        $(this).addClass('tag-hover');
        
        // Show popup on hover
        let $popup = $(this).next('.js-panel-tag');
        let tagRect = this.getBoundingClientRect();
        let canvasRect = $(this).parent()[0].getBoundingClientRect();
        
        // Position popup near the tag
        $popup.css({
            "top": (tagRect.top - canvasRect.top + tagRect.height + 5) + "px",
            "left": (tagRect.left - canvasRect.left) + "px",
            "position": "absolute",
            "z-index": 1000
        });
        
        $(".js-panel-tag").not($popup).hide();
        $popup.fadeIn(200);
    })
    .on('click', '.tag-controls', function (e) {
        // Click on tag opens the editor in sidebar
        e.preventDefault();
        e.stopPropagation();
        
        let $tag = $(this);
        let editUrl = $tag.data('edit-url');
        
        // Remove highlight from previously selected tags
        $('.tag-controls').removeClass('tag-selected');
        
        // Highlight this tag
        $tag.addClass('tag-selected');
        
        // Note: We intentionally do NOT call selectData() here anymore
        // because it conflicts with Jcrop selections. The time/frequency
        // coordinates are available in the tag editor form instead.
        
        // Hide the popup
        $(this).next('.js-panel-tag').hide();
        
        // Load tag in sidebar (or modal if sidebar not available)
        if (editUrl) {
            // Get data from the recording form (includes all hidden inputs like type)
            const formData = new FormData($('#recordingForm')[0]);
            
            // The form already has the type input, so it will be included automatically
            // We just need the form data for the request
            
            if (typeof loadTagInSidebar === 'function' && $('#tag-sidebar').length) {
                loadTagInSidebar(editUrl, formData);
            } else {
                requestModal(editUrl, formData);
            }
        }
    })
    .on('mouseenter', '.js-panel-tag', function () {
        $(this).show();
    })
    .on('mouseleave', '.js-panel-tag', function () {
        let $popup = $(this);
        let $tag = $(this).prev('.tag-controls');
        setTimeout(function() {
            if (!$popup.is(':hover') && !$tag.is(':hover')) {
                $popup.fadeOut('fast');
            }
        }, 100);
    })
    .on('click', '.zoom-tag', function (e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Find the tag element - traverse up to card, then get previous sibling
        let $card = $(this).closest('.js-panel-tag');
        let tagElement = $card.prev('.tag-controls')[0];
        
        if (!tagElement) return;
        
        // Get tag ID
        const tagId = tagElement.getAttribute('id');
        
        let canvasPosition = $('#myCanvas')[0].getBoundingClientRect();
        let tagRect = tagElement.getBoundingClientRect();
        let left = tagRect.left - canvasPosition.left;
        let top = tagRect.top - canvasPosition.top;
        let x = $('#x').val();
        let w = $('#w').val();
        let y = $('#y').val();
        let h = $('#h').val();
        
        selectData({
            x: left,
            x2: left + tagRect.width,
            y: top,
            y2: top + tagRect.height,
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

        $("input[name=filter]").val('0');
        $("input[name=continuous_play]").val('0');
        $("input[name=estimateDistID]").val(this.id.substring(this.id.indexOf('_') + 1, this.id.length)); //Set Tag ID
        $("#recordingForm").submit();
        e.preventDefault();
    });

// Click on canvas (not on a tag) deselects any selected tag
$('#myCanvas').on('click', function(e) {
    // Only if click is directly on canvas or jcrop elements, not on tag-controls
    if (!$(e.target).closest('.tag-controls').length && !$(e.target).closest('.js-panel-tag').length) {
        $('.tag-controls').removeClass('tag-selected');
        // Close sidebar if open
        if (typeof closeTagSidebar === 'function') {
            closeTagSidebar();
        }
    }
});
