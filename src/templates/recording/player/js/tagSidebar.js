// Tag Sidebar Handler
// This file handles loading tag editing forms into the sidebar instead of a modal

(function() {
    'use strict';

    // Check if sidebar mode is enabled (sidebar exists and is visible)
    function isSidebarMode() {
        return $('#tag-sidebar').length > 0 && $('#tag-sidebar').is(':visible');
    }

    // Function to load tag into sidebar
    window.loadTagInSidebar = function(href, data = []) {
        postRequest(href, data, false, false, function (response) {
            const $sidebar = $('#tag-sidebar-content');
            const $modal = $('<div>').html(response.data);
            
            // Extract the modal content (without the modal wrapper)
            const $modalBody = $modal.find('.modal-body');
            const $modalFooter = $modal.find('.modal-footer');
            const $modalTitle = $modal.find('.modal-title');
            
            // Restructure for sidebar display
            let sidebarHTML = '<div class="tag-sidebar-wrapper">';
            
            // Add close button
            sidebarHTML += '<button type="button" class="close mb-2" onclick="closeTagSidebar()" aria-label="Close">';
            sidebarHTML += '<span aria-hidden="true">&times;</span></button>';
            
            // Add title
            if ($modalTitle.length) {
                sidebarHTML += '<h6 class="mb-2 pb-2 border-bottom">' + $modalTitle.html() + '</h6>';
            }
            
            // Add footer buttons at the TOP (so they're always visible)
            if ($modalFooter.length) {
                sidebarHTML += '<div class="tag-sidebar-footer mb-3 pb-3 border-bottom d-flex flex-wrap gap-2">' + $modalFooter.html() + '</div>';
            }
            
            // Add body content
            if ($modalBody.length) {
                const $tagPanel = $modalBody.find('#tag-panel');
                const $reviewPanel = $modalBody.find('#review-panel');
                
                // Tag form (full width in sidebar)
                if ($tagPanel.length) {
                    sidebarHTML += '<div id="tag-panel-sidebar" class="mb-3">' + $tagPanel.html() + '</div>';
                }
                
                // Review panel (below tag form in sidebar)
                if ($reviewPanel.length && $reviewPanel.html().trim()) {
                    sidebarHTML += '<div id="review-panel-sidebar" class="mt-3 pt-3 border-top">' + $reviewPanel.html() + '</div>';
                }
            }
            
            sidebarHTML += '</div>';
            
            // Load into sidebar
            $sidebar.html(sidebarHTML);
            $sidebar.show();
            
            // Copy the scripts from the modal - but filter out problematic global selectors
            const $scripts = $modal.find('script');
            $scripts.each(function() {
                if (this.src) {
                    // External script - skip, already loaded
                } else {
                    // Inline script - adapt for sidebar and execute
                    let scriptContent = $(this).html();
                    
                    // Extract soundTypes variable if present (we need this for the dropdown)
                    const soundTypesMatch = scriptContent.match(/var\s+soundTypes\s*=\s*(\[.*?\]);/s);
                    if (soundTypesMatch) {
                        try {
                            // Make soundTypes available globally for the sidebar
                            window.sidebarSoundTypes = JSON.parse(soundTypesMatch[1]);
                        } catch (e) {
                            console.warn('Could not parse soundTypes:', e);
                        }
                    }
                    
                    // Replace modal-specific selectors with sidebar equivalents
                    scriptContent = scriptContent.replace(/#modal-div/g, '#tag-sidebar-content');
                    scriptContent = scriptContent.replace(/\.modal\(/g, '.sidebarModal(');
                    
                    // Skip execution of scripts that contain global button CSS - we handle this ourselves
                    if (scriptContent.includes('$(".btn").css')) {
                        return; // Skip this script entirely
                    }
                    
                    try {
                        eval(scriptContent);
                    } catch (e) {
                        console.error('Error executing tag sidebar script:', e);
                    }
                }
            });
            
            // Initialize form handlers for sidebar
            initializeSidebarTagForm();
        });
    };
    
    // Initialize tag form in sidebar mode
    function initializeSidebarTagForm() {
        const $sidebar = $('#tag-sidebar-content');
        
        // Update selectors to work with sidebar
        const tagForm = $sidebar.find('#tagForm');
        const reviewForm = $sidebar.find('#reviewForm');
        
        if (tagForm.length) {
            tagForm.find(':input').prop('disabled', tagForm.data('disabled'));
        }
        
        if (reviewForm.length) {
            reviewForm.find(':input').not('#reviewSpeciesName').prop('disabled', reviewForm.data('disabled'));
        }
        
        // Enable save button when form inputs change
        $sidebar.find('#tag-panel-sidebar').off('input change').on('input change', 'input, select, textarea', function () {
            $sidebar.find('#saveButton').removeAttr('disabled');
            $sidebar.find('.type-btn').removeAttr('disabled');
        });
        
        // Also handle selectpicker changes
        $sidebar.find('#sound_id').off('changed.bs.select').on('changed.bs.select', function () {
            $sidebar.find('#saveButton').removeAttr('disabled');
            $sidebar.find('.type-btn').removeAttr('disabled');
        });
        
        // Handle review form button clicks - enable save button
        $sidebar.find('#reviewForm').off('click.enableSave').on('click.enableSave', 'button', function () {
            $sidebar.find('#saveButton').removeAttr('disabled');
            $sidebar.find('.type-btn').removeAttr('disabled');
        });
        
        // Handle review buttons - Accept
        $sidebar.find('#review-accept-btn').off('click').on('click', function(e) {
            e.preventDefault();
            $sidebar.find('#reviewSpeciesName').prop('disabled', true);
            $sidebar.find('.js-species-id[data-type=review]').val('');
            $sidebar.find('#review_status').val(1);
            $sidebar.find('#state').html('Accepted');
        });
        
        // Handle review buttons - Revise/Correct
        $sidebar.find('#review-correct-btn').off('click').on('click', function(e) {
            e.preventDefault();
            $sidebar.find('#reviewSpeciesName')
                .prop('disabled', function(i, v) { return !v; })
                .prop('required', function(i, v) { return !v; });
            $sidebar.find('#review_status').val(2);
            $sidebar.find('#state').html('Corrected');
        });
        
        // Handle review buttons - Reject
        $sidebar.find('#review-delete-btn').off('click').on('click', function(e) {
            e.preventDefault();
            $sidebar.find('#reviewSpeciesName').prop('disabled', true);
            $sidebar.find('.js-species-id[data-type=review]').val('');
            $sidebar.find('#review_status').val(3);
            $sidebar.find('#state').html('Rejected');
        });
        
        // Handle review buttons - Uncertain
        $sidebar.find('#review-uncertain-btn').off('click').on('click', function(e) {
            e.preventDefault();
            $sidebar.find('#reviewSpeciesName').prop('disabled', true);
            $sidebar.find('.js-species-id[data-type=review]').val('');
            $sidebar.find('#review_status').val(4);
            $sidebar.find('#state').html('Uncertain');
        });
        
        // Handle Google Images link
        $sidebar.find('#googleImages').off('click').on('click', function(e) {
            e.preventDefault();
            const species = $sidebar.find('#speciesName[data-type=edit]').val();
            window.open('http://www.google.com/images?q=' + species, '_blank');
        });
        
        // Handle Xeno-canto link
        $sidebar.find('#xenoImages').off('click').on('click', function(e) {
            e.preventDefault();
            const species = $sidebar.find('#speciesName[data-type=edit]').val();
            window.open('http://www.xeno-canto.org/explore?query=' + species, '_blank');
        });
        
        // Handle share/export tag URL button
        $sidebar.find('#exportTagUrl').off('click').on('click', function(e) {
            e.preventDefault();
            const tagId = $sidebar.find("input[name='tag_id']").val() || $sidebar.find('#exportTagUrlData').val();
            if (tagId) {
                const url = window.location.origin + baseUrl + '/recording/show/' + $('input[name="recording_id"]').val() + '?tagId=' + tagId;
                navigator.clipboard.writeText(url).then(function() {
                    showAlert('Tag URL copied to clipboard!');
                }).catch(function() {
                    // Fallback for older browsers
                    prompt('Copy this URL:', url);
                });
            }
        });
        
        // Initialize sound type dropdown - DON'T use selectpicker in sidebar (it causes issues)
        // Instead, use a regular select with proper styling
        const $soundIdSelect = $sidebar.find('#sound_id');
        if ($soundIdSelect.length) {
            // Remove selectpicker classes if present and convert to regular select
            $soundIdSelect.removeClass('selectpicker');
            if ($soundIdSelect.data('selectpicker')) {
                $soundIdSelect.selectpicker('destroy');
            }
            // Style as regular form control
            $soundIdSelect.addClass('form-control form-control-sm');
            $soundIdSelect.css('width', '100%');
        }
        
        // Focus on species name input
        setTimeout(function() {
            $sidebar.find('#speciesName').focus();
        }, 100);
        
        // Populate sound types based on current soundscape component on load
        function populateSoundTypes() {
            const selectedComponent = $sidebar.find('#soundscape_component option:selected').text();
            const $soundId = $sidebar.find('#sound_id');
            // Get current value - check for selected option first
            const currentSoundId = $soundId.find('option:selected').val() || $soundId.val();
            
            // Use global soundTypes (from recording.html.twig) or extracted sidebarSoundTypes
            const typesData = (typeof soundTypes !== 'undefined') ? soundTypes : 
                              (typeof window.sidebarSoundTypes !== 'undefined') ? window.sidebarSoundTypes : null;
            
            if (typesData) {
                $soundId.empty();
                for (var key in typesData) {
                    if (typesData[key]['soundscape_component'] == selectedComponent) {
                        const isSelected = typesData[key]['sound_id'] == currentSoundId ? 'selected' : '';
                        $soundId.append("<option value='" + typesData[key]['sound_id'] + "' " + isSelected + ">" + typesData[key]['sound_type'] + "</option>");
                    }
                }
            }
        }
        
        // Populate on initial load
        populateSoundTypes();
        
        // Handle soundscape component changes - populate sound types
        $sidebar.find('#soundscape_component').off('change').on('change', function() {
            if ($(this).find("option:selected").text() == 'biophony') {
                $sidebar.find(".biophony").show();
                $sidebar.find("#reference").addClass('pt-5');
            } else {
                $sidebar.find(".biophony").hide();
                $sidebar.find("#reference").removeClass('pt-5');
            }
            
            // Update sound type dropdown
            populateSoundTypes();
        });
        
        // Handle distance not estimable checkbox
        $sidebar.find('#distanceNotEstimable').off('click').on('click', function () {
            if ($(this).is(':checked')) {
                $sidebar.find('#callDistance').val(null);
            } else {
                $sidebar.find('#callDistance').val($sidebar.find('#old_call_distance').val());
            }
        });
        
        // Handle estimate distance button in sidebar
        $sidebar.find('.estimate-distance-btn').off('click').on('click', function(e) {
            e.preventDefault();
            
            // Get tag ID from the button's id attribute (format: est_123)
            const tagId = this.id.substring(this.id.indexOf('_') + 1);
            
            // Get time bounds from the tag form
            const minTime = parseFloat($sidebar.find('#min_time').val()) || 0;
            const maxTime = parseFloat($sidebar.find('#max_time').val()) || 0;
            
            // Calculate times - limit to 30 seconds max
            let startTime = minTime;
            let endTime = maxTime;
            if ((endTime - startTime) > 30) {
                endTime = startTime + 30;
            }
            
            // Set form values for distance estimation
            $('#x').val(startTime);
            $('#w').val(endTime);
            $('#y').val(1);
            $('#h').val(fileFreqMax);
            
            $("input[name=filter]").prop('checked', false);
            $("input[name=continuous_play]").prop('checked', false);
            $("input[name=estimateDistID]").val(tagId);
            
            // Submit the form to trigger distance estimation
            $("#recordingForm").submit();
        });
        
        // Handle form submission
        tagForm.off('submit').on('submit', function(e) {
            e.preventDefault();
            
            if (tagForm.find(':input').prop('disabled') === true) {
                if (reviewForm.length) {
                    reviewForm.submit();
                }
                const buttonText = $sidebar.find('#saveButton').text();
                if (buttonText.includes('Close')) {
                    closeTagSidebar();
                    showAlert("Saved successfully.");
                } else if (buttonText.includes('Next')) {
                    $sidebar.find('#btn-next').click();
                } else if (buttonText.includes('Previous')) {
                    $sidebar.find('#btn-previous').click();
                } else {
                    showAlert("Saved successfully.");
                }
                return;
            }
            
            if (this.checkValidity() === false) {
                e.stopPropagation();
                tagForm.addClass('was-validated');
                return;
            }
            
            const tagId = tagForm.find("input[name='tag_id']").val();
            
            postRequest(baseUrl + '/api/tag/save', new FormData(tagForm[0]), false, false, function (response) {
                let newTagId = tagId;
                
                // Update the tag visual on the spectrogram
                if (response.tagId && response.tagId > 0) {
                    newTagId = response.tagId;
                    
                    // If this is a new tag (tagId was empty or <= 0), create the visual
                    if (!tagId || parseInt(tagId) <= 0) {
                        createTagVisual(newTagId);
                    } else {
                        // Update existing tag visual
                        updateTagVisual(newTagId);
                    }
                } else if (tagId && parseInt(tagId) > 0) {
                    // Update existing tag
                    updateTagVisual(tagId);
                }
                
                // Refresh the tag table
                if (window.table && typeof window.table.ajax !== 'undefined') {
                    window.table.ajax.reload(null, false);
                }
                
                // Handle review form if present
                if (reviewForm.length) {
                    reviewForm.submit();
                }
                
                const buttonText = $sidebar.find('#saveButton').text();
                if (buttonText.includes('Close')) {
                    closeTagSidebar();
                    showAlert("Saved successfully.");
                } else if (buttonText.includes('Next')) {
                    showAlert("Saved successfully.");
                    $sidebar.find('#btn-next').click();
                } else if (buttonText.includes('Previous')) {
                    showAlert("Saved successfully.");
                    $sidebar.find('#btn-previous').click();
                } else {
                    showAlert("Saved successfully.");
                    // Just close the sidebar, don't reload
                    closeTagSidebar();
                }
            });
        });
        
        // Handle save button click
        $sidebar.find('#saveButton').off('click').on('click', function(e) {
            e.preventDefault();
            tagForm.submit();
        });
        
        // Handle review form submission
        reviewForm.off('submit').on('submit', function(e) {
            e.preventDefault();
            
            const reviewStatus = $sidebar.find('#review_status');
            const reviewSpeciesId = $sidebar.find('.js-species-id[data-type=review]');
            
            // Validation
            if (this.checkValidity() === false || 
                (parseInt(reviewStatus.val()) === 2 && !reviewSpeciesId.val())) {
                e.stopPropagation();
                return;
            }
            
            // Only submit if there's a review status set
            if (reviewStatus.val()) {
                postRequest(baseUrl + '/api/tagReview/save', new FormData($(this)[0]), false, false, function() {
                    // Remove dashed style from the tag (indicates reviewed)
                    const tagId = $sidebar.find("input[name='tag_id']").val();
                    $('#' + tagId).removeClass('tag-dashed');
                    
                    // Hide the review buttons and show confirmation
                    $sidebar.find('#reviewForm .row').hide();
                    $sidebar.find('#reviewForm .form-group').hide();
                    
                    showAlert("Review saved successfully.");
                });
            }
        });
        
        // Handle delete button
        $sidebar.find('#deleteButton').off('click').on('click', function(e) {
            e.preventDefault();
            const tagId = $(this).data('tag-id');
            
            if (confirm('Are you sure you want to delete this tag?')) {
                deleteRequest(baseUrl + '/api/tag/delete/' + tagId, {}, false, false, function() {
                    // Remove tag visual from spectrogram
                    $('#' + tagId).remove();
                    $('.js-panel-tag[data-tag-id="' + tagId + '"]').remove();
                    
                    // Refresh the tag table
                    if (window.table && typeof window.table.ajax !== 'undefined') {
                        window.table.ajax.reload(null, false);
                    }
                    
                    showAlert('Tag deleted successfully.');
                    closeTagSidebar();
                });
            }
        });
        
        // Handle save type dropdown
        $sidebar.find('.save-type').off('click').on('click', function(e) {
            e.preventDefault();
            if ($.cookie('cookieConsent') == 'accepted') {
                $.cookie('save_button', $(this).text(), {path: '/', expires: 180, samesite: 'None'});
            }
            $sidebar.find('#saveButton').html('<i class="fa fa-save"></i> ' + $(this).text());
        });
    }
    
    // Function to close sidebar and deselect tag
    window.closeTagSidebar = function() {
        // Remove highlight from any selected tags
        $('.tag-controls').removeClass('tag-selected');
        
        const placeholderHTML = `
            <div class="text-center py-5">
                <h5 class="text-muted mb-3">Tag Editor</h5>
                <p class="text-muted">
                    <i class="fas fa-tag fa-2x mb-3 d-block"></i>
                    No tag selected
                </p>
                <small class="text-muted">
                    Click on a tag or create a new one to edit
                </small>
            </div>
        `;
        $('#tag-sidebar-content').html(placeholderHTML);
    };
    
    // Helper function to calculate tag coordinates
    function calculateTagCoordinates() {
        const $sidebar = $('#tag-sidebar-content');
        const minTime = parseFloat($sidebar.find('#min_time').val());
        const maxTime = parseFloat($sidebar.find('#max_time').val());
        const minFreq = parseFloat($sidebar.find('#min_freq').val());
        const maxFreq = parseFloat($sidebar.find('#max_freq').val());
        
        // Calculate position relative to current view
        const viewMinTime = parseFloat($('input[name="minTimeView"]').val());
        const viewMaxTime = parseFloat($('input[name="maxTimeView"]').val());
        const viewMinFreq = parseFloat($('input[name="minFreqView"]').val());
        const viewMaxFreq = parseFloat($('input[name="maxFreqView"]').val());
        
        const viewTimeRange = viewMaxTime - viewMinTime;
        const viewFreqRange = viewMaxFreq - viewMinFreq;
        
        const left = ((minTime - viewMinTime) / viewTimeRange) * specWidth;
        const width = ((maxTime - minTime) / viewTimeRange) * specWidth;
        const top = ((viewMaxFreq - maxFreq) / viewFreqRange) * specHeight;
        const height = ((maxFreq - minFreq) / viewFreqRange) * specHeight;
        
        return { left, top, width, height };
    }
    
    // Helper function to create new tag visual
    function createTagVisual(tagId) {
        const coords = calculateTagCoordinates();
        const $sidebar = $('#tag-sidebar-content');
        
        // Create the tag box
        const tagBox = $('<div>')
            .addClass('tag-controls tag-dashed')
            .attr('id', tagId)
            .css({
                'z-index': 800,
                'border-color': 'white',
                'left': coords.left + 'px',
                'top': coords.top + 'px',
                'width': coords.width + 'px',
                'height': coords.height + 'px',
                'position': 'absolute'
            });
        
        $('#myCanvas').append(tagBox);
        
        // Create the tag panel (edit buttons)
        const species = $sidebar.find('#speciesName').val() || 'Unknown';
        createTagPanel(tagId, species);
    }
    
    // Helper function to update existing tag visual
    function updateTagVisual(tagId) {
        const coords = calculateTagCoordinates();
        const $tagBox = $('#' + tagId);
        
        if ($tagBox.length) {
            $tagBox.css({
                'left': coords.left + 'px',
                'top': coords.top + 'px',
                'width': coords.width + 'px',
                'height': coords.height + 'px'
            });
        }
    }
    
    // Helper function to create tag panel (edit buttons)
    function createTagPanel(tagId, species) {
        const recordingId = $('input[name="recording_id"]').val();
        const $sidebar = $('#tag-sidebar-content');
        const minTime = $sidebar.find('#min_time').val();
        const maxTime = $sidebar.find('#max_time').val();
        const minFreq = $sidebar.find('#min_freq').val();
        const maxFreq = $sidebar.find('#max_freq').val();
        
        const panelHTML = `
            <div class="js-panel-tag" data-tag-id="${tagId}" style="display: none; position: absolute;">
                <div class="btn-group-vertical btn-group-sm" role="group">
                    <button type="button" class="btn btn-secondary btn-sm" disabled>
                        <small>${species}</small>
                    </button>
                    <a href="${baseUrl}/tag/show/${tagId}" class="btn btn-primary btn-sm js-tag">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="#" class="btn btn-info btn-sm zoom-tag">
                        <i class="fas fa-search-plus"></i>
                    </a>
                </div>
            </div>
        `;
        
        $('#' + tagId).after(panelHTML);
    }
    
    // Dummy modal function for sidebar (prevents errors)
    $.fn.sidebarModal = function() {
        return this;
    };
    
    // Override the tag-related handlers to use sidebar when appropriate
    $(document).ready(function() {
        // Store original handlers
        const originalRequestModal = window.requestModal;
        
        // Override requestModal to check for sidebar mode
        window.requestModal = function(href, data = [], showLoading = false, backdrop = true) {
            // Use sidebar for tag edit/create requests, but NOT for call distance estimation
            const isTagEditOrCreate = (href.includes('/api/tag/edit/') || href.includes('/api/tag/create'));
            if (isSidebarMode() && isTagEditOrCreate) {
                // Use sidebar for tag edit/create requests
                loadTagInSidebar(href, data);
            } else {
                // Use original modal for everything else (including call distance estimation)
                originalRequestModal(href, data, showLoading, backdrop);
            }
        };
    });
})();
