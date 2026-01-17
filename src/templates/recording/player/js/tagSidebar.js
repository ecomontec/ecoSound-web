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
            
            // Update tag border style based on whether there are reviews
            const tagId = $sidebar.find('#reviewForm input[name="tag_id"]').val() || 
                          $sidebar.find('#tag-panel-sidebar input[name="tag_id"]').val() ||
                          $sidebar.find('input[name="tag_id"]').val();
            if (tagId) {
                const hasReviews = $sidebar.find('.review-table tbody tr').length > 0;
                if (hasReviews) {
                    $('#' + tagId).removeClass('tag-dashed');
                } else {
                    $('#' + tagId).addClass('tag-dashed');
                }
            }
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
        
        // Helper function to save review immediately
        function saveReviewImmediately(statusId, statusName) {
            const reviewForm = $sidebar.find('#reviewForm');
            const tagId = reviewForm.find('input[name="tag_id"]').val();
            const speciesId = reviewForm.find('.js-species-id[data-type=review]').val();
            const note = reviewForm.find('#comments').val();
            const userId = reviewForm.data('user-id');
            const userName = reviewForm.data('user-name') || 'You';
            
            // Build form data
            const formData = new FormData();
            formData.append('tag_id', tagId);
            formData.append('tag_review_status_id', statusId);
            formData.append('note', note || '');
            if (speciesId) {
                formData.append('species_id', speciesId);
            }
            
            // Save the review
            postRequest(baseUrl + '/api/tagReview/save', formData, false, false, function(response) {
                // Add new row to the reviews table
                const $reviewTable = $sidebar.find('.review-table tbody');
                const today = new Date();
                const dateStr = today.getDate().toString().padStart(2, '0') + '/' + 
                               (today.getMonth() + 1).toString().padStart(2, '0') + '/' + 
                               today.getFullYear();
                
                // Get species name if available
                const speciesName = $sidebar.find('#reviewSpeciesName').val() || '';
                
                // Check if user already has a review (shouldn't add duplicate)
                const existingRow = $reviewTable.find('tr[data-reviewer-id="' + userId + '"]');
                if (existingRow.length) {
                    // Update existing row
                    existingRow.find('td:eq(1)').text(statusName);
                    existingRow.find('td:eq(2)').text(speciesName);
                    existingRow.find('td:eq(3)').text(dateStr);
                } else {
                    // Add new row - use tag_id and user_id for delete identification
                    const newRow = $('<tr>')
                        .attr('data-tag-id', tagId)
                        .attr('data-reviewer-id', userId)
                        .append('<td class="py-1">' + userName + '</td>')
                        .append('<td class="py-1">' + statusName + '</td>')
                        .append('<td class="py-1">' + speciesName + '</td>')
                        .append('<td class="py-1">' + dateStr + '</td>')
                        .append('<td class="py-1"><button type="button" class="btn btn-link btn-sm p-0 text-danger delete-review-btn" data-tag-id="' + tagId + '" data-user-id="' + userId + '" title="Delete review"><i class="fas fa-times"></i></button></td>');
                    $reviewTable.append(newRow);
                }
                
                // Remove dashed style from the tag (indicates reviewed)
                $('#' + tagId).removeClass('tag-dashed');
                
                // Hide the review buttons (user has already reviewed)
                $sidebar.find('#reviewForm .row').first().hide();
                $sidebar.find('#review_animal_group').hide();
                $sidebar.find('#reviewForm .form-group:has(#comments)').hide();
                
                showAlert("Review saved successfully.");
            });
        }
        
        // Handle review buttons - Accept (immediate save)
        $sidebar.find('#review-accept-btn').off('click').on('click', function(e) {
            e.preventDefault();
            $sidebar.find('#reviewSpeciesName').prop('disabled', true);
            $sidebar.find('.js-species-id[data-type=review]').val('');
            $sidebar.find('#review_status').val(1);
            $sidebar.find('#state').html('Accepted');
            
            // Save immediately
            saveReviewImmediately(1, 'Accepted');
        });
        
        // Handle review buttons - Revise/Correct (waits for species selection)
        $sidebar.find('#review-correct-btn').off('click').on('click', function(e) {
            e.preventDefault();
            $sidebar.find('#reviewSpeciesName')
                .prop('disabled', false)
                .prop('required', true)
                .focus();
            $sidebar.find('#review_status').val(2);
            $sidebar.find('#state').html('Corrected');
            
            // Show message to select species
            showAlert("Please select a species, then the review will be saved automatically.", "info");
        });
        
        // Handle species selection for Revise - auto-save when species is selected
        // We need to intercept after the autocomplete sets the species ID
        $sidebar.find('.js-species-id[data-type=review]').off('change.reviewSave').on('change.reviewSave', function() {
            // Check if we're in Revise mode and have a species selected
            if ($sidebar.find('#review_status').val() == '2' && $(this).val()) {
                saveReviewImmediately(2, 'Corrected');
            }
        });
        
        // Also watch for species ID being set programmatically (autocomplete doesn't trigger change)
        // Use a MutationObserver or poll after autocomplete select
        const originalAutocompleteSelect = $.ui.autocomplete.prototype._trigger;
        $sidebar.find('#reviewSpeciesName').on('autocompleteselect', function(event, ui) {
            // Check if we're in Revise mode
            if ($sidebar.find('#review_status').val() == '2') {
                // Wait for the species ID to be set by the autocomplete handler
                setTimeout(function() {
                    const speciesId = $sidebar.find('.js-species-id[data-type=review]').val();
                    if (speciesId) {
                        saveReviewImmediately(2, 'Corrected');
                    }
                }, 200);
            }
        });
        
        // Handle review buttons - Reject (immediate save)
        $sidebar.find('#review-delete-btn').off('click').on('click', function(e) {
            e.preventDefault();
            $sidebar.find('#reviewSpeciesName').prop('disabled', true);
            $sidebar.find('.js-species-id[data-type=review]').val('');
            $sidebar.find('#review_status').val(3);
            $sidebar.find('#state').html('Rejected');
            
            // Save immediately
            saveReviewImmediately(3, 'Rejected');
        });
        
        // Handle review buttons - Uncertain (immediate save)
        $sidebar.find('#review-uncertain-btn').off('click').on('click', function(e) {
            e.preventDefault();
            $sidebar.find('#reviewSpeciesName').prop('disabled', true);
            $sidebar.find('.js-species-id[data-type=review]').val('');
            $sidebar.find('#review_status').val(4);
            $sidebar.find('#state').html('Uncertain');
            
            // Save immediately
            saveReviewImmediately(4, 'Uncertain');
        });
        
        // Handle delete review button
        $sidebar.find('.review-table').off('click.deleteReview').on('click.deleteReview', '.delete-review-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $btn = $(this);
            const $row = $btn.closest('tr');
            // Use attr() for consistent reading of HTML attributes (data() caches and can be inconsistent)
            const reviewTagId = $btn.attr('data-tag-id') || $row.attr('data-tag-id');
            const reviewUserId = $btn.attr('data-user-id') || $row.attr('data-reviewer-id');
            
            if (!reviewTagId || !reviewUserId) {
                // New review without proper IDs - just remove the row
                $row.remove();
                return;
            }
            
            if (confirm('Are you sure you want to delete this review?')) {
                // Format: tag_id-user_id (as expected by the delete endpoint)
                postRequest(baseUrl + '/api/tagReview/delete', { id: reviewTagId + '-' + reviewUserId }, false, false, function() {
                    showAlert("Review deleted successfully.");
                    
                    // Get the tag ID from the form (for border style update)
                    const tagId = $sidebar.find('#reviewForm input[name="tag_id"]').val() || 
                                  $sidebar.find('#tag-panel-sidebar input[name="tag_id"]').val();
                    
                    // Count remaining reviews (excluding the row we're about to remove)
                    const remainingReviews = $sidebar.find('.review-table tbody tr').length - 1;
                    
                    // If no reviews left, add dashed style back to the tag
                    if (remainingReviews === 0 && tagId) {
                        $('#' + tagId).addClass('tag-dashed');
                    }
                    
                    // Reload the sidebar to get fresh state from server
                    // This ensures review buttons appear correctly (they may not have been rendered initially)
                    if (tagId) {
                        loadTagInSidebar(baseUrl + '/api/tag/edit/' + tagId);
                    } else {
                        $row.remove();
                    }
                });
            }
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
            const $soundscapeComponent = $sidebar.find('#soundscape_component');
            const selectedComponent = $soundscapeComponent.find('option:selected').text();
            const $soundId = $sidebar.find('#sound_id');
            // Get current value - check for selected option first
            const currentSoundId = $soundId.find('option:selected').val() || $soundId.val();
            
            // Use global soundTypes (from recording.html.twig) or extracted sidebarSoundTypes
            const typesData = (typeof soundTypes !== 'undefined') ? soundTypes : 
                              (typeof window.sidebarSoundTypes !== 'undefined') ? window.sidebarSoundTypes : null;
            
            if (typesData) {
                $soundId.empty();
                for (var key in typesData) {
                    // Check both soundscape_component and soundscapeComponent properties
                    const componentValue = typesData[key]['soundscape_component'] || typesData[key]['soundscapeComponent'];
                    if (componentValue == selectedComponent) {
                        const soundIdValue = typesData[key]['sound_id'] || typesData[key]['soundId'];
                        const soundTypeName = typesData[key]['sound_type'] || typesData[key]['soundType'];
                        
                        // Skip entries with empty sound type names
                        if (!soundTypeName || soundTypeName.trim() === '') {
                            continue;
                        }
                        
                        const isSelected = soundIdValue == currentSoundId ? 'selected' : '';
                        $soundId.append("<option value='" + soundIdValue + "' " + isSelected + ">" + soundTypeName + "</option>");
                    }
                }
            }
        }
        
        // Populate on initial load
        populateSoundTypes();
        
        // Handle soundscape component changes directly in sidebar
        $sidebar.find('#soundscape_component').off('change.sidebar').on('change.sidebar', function() {
            const selectedComponent = $(this).find('option:selected').text();
            
            // Show/hide biophony fields
            if (selectedComponent == 'biophony') {
                $sidebar.find(".biophony").show();
                $sidebar.find("#reference").addClass('pt-4');
            } else {
                $sidebar.find(".biophony").hide();
                $sidebar.find("#reference").removeClass('pt-4');
            }
            
            // Use sidebarSoundTypes which we extracted from the response
            const typesData = window.sidebarSoundTypes;
            if (!typesData) {
                console.error('No sidebar sound types data available');
                return;
            }
            
            // Populate sound types dropdown
            const $soundId = $sidebar.find('#sound_id');
            $soundId.empty();
            
            for (var key in typesData) {
                const componentValue = typesData[key]['soundscape_component'] || typesData[key]['soundscapeComponent'];
                const soundIdValue = typesData[key]['sound_id'] || typesData[key]['soundId'];
                const soundTypeName = typesData[key]['sound_type'] || typesData[key]['soundType'];
                
                // Skip entries with empty sound type names
                if (!soundTypeName || soundTypeName.trim() === '') {
                    continue;
                }
                
                if (componentValue == selectedComponent) {
                    $soundId.append("<option value='" + soundIdValue + "'>" + soundTypeName + "</option>");
                }
            }
            
            // Force the select to be visible and refresh any bootstrap-select
            $soundId.show();
            if ($soundId.hasClass('selectpicker') || $soundId.data('selectpicker')) {
                try {
                    $soundId.selectpicker('refresh');
                } catch(e) {
                    try {
                        $soundId.selectpicker('destroy');
                    } catch(e2) {}
                }
            }
            
            // Remove any bootstrap-select wrapper (it doesn't sync with option changes)
            const $bsWrapper = $soundId.closest('.bootstrap-select');
            if ($bsWrapper.length) {
                $soundId.insertBefore($bsWrapper);
                $bsWrapper.remove();
                $soundId.removeClass('selectpicker').addClass('form-control form-control-sm').show();
            }
            
            // Enable save button
            $sidebar.find('#saveButton').removeAttr('disabled');
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
            
            $("input[name=filter]").val('0');
            $("input[name=continuous_play]").val('0');
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
                // Just show success message, don't close sidebar
                showAlert("Saved successfully.");
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
                
                // Note: Review form is now handled separately with immediate saving
                // so we don't submit it here
                
                // Just show success message, don't close sidebar
                showAlert("Saved successfully.");
            });
        });
        
        // Handle save button click
        $sidebar.find('#saveButton').off('click').on('click', function(e) {
            e.preventDefault();
            tagForm.submit();
        });
        
        // Handle review form submission (legacy - reviews are now saved immediately)
        reviewForm.off('submit').on('submit', function(e) {
            e.preventDefault();
            // Reviews are now saved immediately when buttons are clicked
            // This handler is kept for compatibility but doesn't do anything
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
        
        // Determine current tag visibility state from the toggle button or tag visibility
        var isShowTags = $('.tag-controls').first().is(':visible') ? 1 : '';
        
        // Build the appropriate icon/text and button class for toggle button
        var toggleIcon, toggleClass;
        if (isShowTags) {
            // Tags are visible, button should show "Hide Tags" - use grey (inactive)
            toggleIcon = '<i class="fas fa-eye-slash"></i> Hide Tags';
            toggleClass = 'btn-outline-secondary';
        } else {
            // Tags are hidden, button should show "Show Tags" - use green (active)
            toggleIcon = '<i class="fas fa-eye"></i> Show Tags';
            toggleClass = 'btn-outline-success';
        }
        
        const placeholderHTML = `
            <div id="sidebar-default-state" class="text-center">
                <h5 class="text-muted mb-3">Tag Editor</h5>
                <div class="d-flex justify-content-center mb-4" style="gap: 0.5rem;">
                    <a class="btn ${toggleClass} btn-sm js-toggle-tags" href="#" title="Toggle tags" data-show="${isShowTags}">
                        ${toggleIcon}
                    </a>
                    <a class="btn btn-outline-success btn-sm js-new-tag" href="${baseUrl}/api/tag/create" title="Enter for new tag">
                        <i class="fas fa-plus"></i> New Tag
                    </a>
                </div>
                <p class="text-muted mb-2">
                    <i class="fas fa-tag fa-2x mb-3 d-block"></i>
                    No tag selected
                </p>
                <small class="text-muted">
                    Click on a tag to edit it
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
        
        // Get tag name from form
        const speciesName = $sidebar.find('#speciesName').val();
        const soundType = $sidebar.find('#sound_id option:selected').text();
        const soundscapeComponent = $sidebar.find('#soundscape_component').val();
        const tagName = speciesName || soundType || soundscapeComponent || 'Unknown';
        
        // Create the tag box with proper attributes
        const tagBox = $('<div>')
            .addClass('tag-controls tag-dashed')
            .attr('id', tagId)
            .attr('data-tag-id', tagId)
            .attr('data-edit-url', baseUrl + '/api/tag/edit/' + tagId)
            .css({
                'z-index': 800,
                'border-color': 'white',
                'left': coords.left + 'px',
                'top': coords.top + 'px',
                'width': coords.width + 'px',
                'height': coords.height + 'px'
            });
        
        $('#myCanvas').append(tagBox);
        
        // Create the tag panel (popup)
        createTagPanel(tagId, tagName);
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
    
    // Helper function to create tag panel (popup that appears on hover)
    function createTagPanel(tagId, tagName) {
        const panelHTML = `
            <div class="card js-panel-tag" style="display:none;">
                <div class="card-header py-1 px-2">
                    <small>${tagId} | ${tagName}</small>
                </div>
                <div class="card-body p-2 mx-auto">
                    <a href='#' onclick='return false;' class='btn btn-outline-primary btn-sm zoom-tag' title='Zoom tag (+Alt: open in new tab)'>
                        <i class='fas fa-search' aria-hidden='true'></i> Zoom
                    </a>
                    <div class="text-muted small mt-1 text-center">Click tag to edit</div>
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
