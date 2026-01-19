// Tag Sidebar Handler
// This file handles loading tag editing forms into the sidebar instead of a modal

(function() {
    'use strict';

    // Check if sidebar mode is enabled (sidebar exists and is visible)
    function isSidebarMode() {
        return $('#tag-sidebar').length > 0 && $('#tag-sidebar').is(':visible');
    }

    // Store navigation data for the current tag
    window.currentTagNavigation = null;
    // Restore zoom state from session storage, default: no zoom when navigating (zoom is enabled in review/task mode)
    window.zoomOnNavigate = sessionStorage.getItem('zoomOnNavigate') === 'true' || false;
    // Track if this is the initial tag load (from dashboard/URL) vs. user navigation
    window.isInitialTagLoad = true;

    // Function to load tag into sidebar
    window.loadTagInSidebar = function(href, data = [], isUserNavigation = false) {
        console.log('loadTagInSidebar called with:', href, data); // Debug
        postRequest(href, data, false, false, function (response) {
            console.log('Received response:', response); // Debug
            const $sidebar = $('#tag-sidebar-content');
            const $modal = $('<div>').html(response.data);
            
            // Store navigation data if provided
            if (response.navigation) {
                window.currentTagNavigation = response.navigation;
                console.log('Navigation data received:', response.navigation); // Debug
                // Only auto-enable zoom on initial load from dashboard, not during user navigation
                if (!isUserNavigation && window.isInitialTagLoad && (response.navigation.isTask || response.navigation.hasTaskAssignment)) {
                    window.zoomOnNavigate = true;
                    sessionStorage.setItem('zoomOnNavigate', 'true');
                }
                // Mark that we've completed the initial load
                window.isInitialTagLoad = false;
            } else {
                console.log('No navigation data in response'); // Debug
                window.currentTagNavigation = null;
            }
            
            // Extract the modal content (without the modal wrapper)
            const $modalBody = $modal.find('.modal-body');
            const $modalFooter = $modal.find('.modal-footer');
            const $modalTitle = $modal.find('.modal-title');
            
            // Extract tag ID from title for navigation dropdown
            let currentTagId = null;
            if ($modalTitle.length) {
                const titleMatch = $modalTitle.text().match(/Tag\s+(\d+)/);
                if (titleMatch) {
                    currentTagId = titleMatch[1];
                }
            }
            
            // Restructure for sidebar display
            let sidebarHTML = '<div class="tag-sidebar-wrapper">';
            
            // Add close button
            sidebarHTML += '<button type="button" class="close mb-2" onclick="closeTagSidebar()" aria-label="Close">';
            sidebarHTML += '<span aria-hidden="true">&times;</span></button>';
            
            // Add title with navigation controls
            if ($modalTitle.length && currentTagId) {
                const nav = window.currentTagNavigation;
                const isReviewMode = nav && (nav.isTask || nav.hasTaskAssignment);
                
                sidebarHTML += '<div class="d-flex align-items-center mb-2 pb-2 border-bottom">';
                
                // Tag label
                sidebarHTML += '<span class="mr-2"><strong>Tag</strong></span>';
                
                // Previous button
                const hasPrevious = nav && nav.previous;
                sidebarHTML += '<button class="btn btn-sm ' + (hasPrevious ? 'btn-outline-primary' : 'btn-secondary') + ' mr-1" ';
                sidebarHTML += 'id="sidebar-nav-prev" title="Previous tag"' + (!hasPrevious ? ' disabled' : '') + '>';
                sidebarHTML += '<i class="fas fa-arrow-left"></i></button>';
                
                // Tag dropdown (narrower, without "Tag" prefix)
                sidebarHTML += '<select class="form-control form-control-sm mx-1" id="sidebar-tag-dropdown" style="width: 80px;">';
                
                // Collect all visible tags from the DOM with their positions
                const visibleTags = [];
                $('.tag-controls:visible').each(function() {
                    const tagId = $(this).attr('id');
                    if (tagId && !isNaN(tagId)) {
                        // Get left position (start time) for sorting
                        const leftPos = parseFloat($(this).css('left')) || 0;
                        visibleTags.push({
                            id: parseInt(tagId),
                            left: leftPos
                        });
                    }
                });
                
                // Always include previous and next tags from navigation ONLY if zoom is ON
                // When zoom is OFF, only show visible tags to maintain consistency
                const tagsToShow = new Map(); // Use Map to avoid duplicates
                visibleTags.forEach(tag => tagsToShow.set(tag.id, tag));
                
                // Only add adjacent tags to dropdown if zoom navigation is enabled
                if (window.zoomOnNavigate) {
                    // Add previous tag if available
                    if (nav && nav.previous) {
                        if (!tagsToShow.has(nav.previous.id)) {
                            // Estimate position for sorting (before current)
                            const currentLeft = tagsToShow.get(parseInt(currentTagId))?.left || 0;
                            tagsToShow.set(nav.previous.id, { id: nav.previous.id, left: currentLeft - 100 });
                        }
                    }
                    
                    // Add next tag if available
                    if (nav && nav.next) {
                        if (!tagsToShow.has(nav.next.id)) {
                            // Estimate position for sorting (after current)
                            const currentLeft = tagsToShow.get(parseInt(currentTagId))?.left || 0;
                            tagsToShow.set(nav.next.id, { id: nav.next.id, left: currentLeft + 100 });
                        }
                    }
                }
                
                // Convert Map to array and sort by position
                const allTags = Array.from(tagsToShow.values()).sort((a, b) => a.left - b.left);
                
                // Populate dropdown (without "Tag" prefix or checkmark)
                if (allTags.length > 0) {
                    allTags.forEach(function(tag) {
                        const isSelected = tag.id == currentTagId ? ' selected' : '';
                        sidebarHTML += '<option value="' + tag.id + '"' + isSelected + '>' + tag.id + '</option>';
                    });
                } else {
                    // Fallback if no tags found at all
                    sidebarHTML += '<option value="' + currentTagId + '" selected>' + currentTagId + '</option>';
                }
                
                sidebarHTML += '</select>';
                
                // Next button
                const hasNext = nav && nav.next;
                sidebarHTML += '<button class="btn btn-sm ' + (hasNext ? 'btn-outline-primary' : 'btn-secondary') + ' ml-1" ';
                sidebarHTML += 'id="sidebar-nav-next" title="Next tag"' + (!hasNext ? ' disabled' : '') + '>';
                sidebarHTML += '<i class="fas fa-arrow-right"></i></button>';
                
                // Zoom toggle button (after right arrow)
                sidebarHTML += '<button class="btn btn-sm ' + (window.zoomOnNavigate ? 'btn-outline-primary' : 'btn-outline-secondary') + ' ml-2" ';
                sidebarHTML += 'id="sidebar-zoom-toggle" title="' + (window.zoomOnNavigate ? 'Tag zoom ON: arrow buttons will zoom to adjacent tag' : 'Tag zoom OFF: shuttle to adjacent tag if present in current view') + '">';
                sidebarHTML += '<i class="fas ' + (window.zoomOnNavigate ? 'fa-search' : 'fa-arrows-alt') + '"></i></button>';
                
                sidebarHTML += '</div>';
            } else if ($modalTitle.length) {
                // Fallback: show title without navigation if no tag ID found
                sidebarHTML += '<h6 class="mb-2 pb-2 border-bottom">' + $modalTitle.html() + '</h6>';
            }
            
            // Add footer buttons at the TOP (so they're always visible)
            if ($modalFooter.length) {
                const $footerClone = $modalFooter.clone();
                // Remove legacy navigation arrows (not needed with sidebar navigation)
                $footerClone.find('.fa-arrow-left, .fa-arrow-right').closest('a, button').remove();
                
                // Extract individual buttons (not their parents to avoid duplicates)
                const $saveBtn = $footerClone.find('#saveButton');
                const $exportBtn = $footerClone.find('#exportTagUrl');
                const $deleteBtn = $footerClone.find('#deleteButton');
                
                sidebarHTML += '<div class="tag-sidebar-footer mb-3 pb-3 border-bottom d-flex justify-content-between align-items-center">';
                sidebarHTML += '<div class="d-flex align-items-center" style="gap: 0.5rem;">';
                if ($saveBtn.length) sidebarHTML += $saveBtn[0].outerHTML;
                if ($exportBtn.length) sidebarHTML += $exportBtn[0].outerHTML;
                sidebarHTML += '</div>';
                if ($deleteBtn.length) sidebarHTML += $deleteBtn[0].outerHTML;
                sidebarHTML += '</div>';
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
                    const $reviewClone = $reviewPanel.clone();
                    
                    // Add task indicator if this is an assigned task and not yet reviewed
                    const nav = window.currentTagNavigation;
                    const hasTaskAssignment = nav && nav.hasTaskAssignment;
                    const hasReviewButtons = $reviewClone.find('#review-accept-btn, #review-correct-btn').length > 0;
                    
                    sidebarHTML += '<div id="review-panel-sidebar" class="mt-3 pt-3 border-top">';
                    
                    if (hasTaskAssignment && hasReviewButtons) {
                        sidebarHTML += '<div class="alert alert-info py-2 mb-3"><i class="fas fa-tasks"></i> <strong>Assigned Task:</strong> This tag requires your review</div>';
                    }
                    
                    sidebarHTML += $reviewClone.html();
                    sidebarHTML += '</div>';
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
            
            // Initialize navigation controls
            initializeSidebarNavigation();
            
            // Rebuild navigation dropdown to ensure correct button states based on zoom and visibility
            rebuildNavigationDropdown();
            
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
                
                // Update yellow border (tag selection highlight)
                $('.tag-controls').removeClass('tag-selected');
                $('#' + tagId).addClass('tag-selected');
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
            // Disable form inputs but NOT delete review buttons (users can delete their own reviews)
            reviewForm.find(':input').not('#reviewSpeciesName').not('.delete-review-btn').prop('disabled', reviewForm.data('disabled'));
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
                
                // Check user permissions for delete button
                const reviewForm = $sidebar.find('#reviewForm');
                const isAdmin = reviewForm.data('is-admin') === '1' || reviewForm.data('is-admin') === 1;
                const isUserLogged = reviewForm.data('is-user-logged') === '1' || reviewForm.data('is-user-logged') === 1;
                const canDelete = isUserLogged && (isAdmin || true); // User created this review, so can always delete their own
                
                // Check if user already has a review (shouldn't add duplicate)
                // Check both data-reviewer-id (server-rendered) and data-user-id (client-created)
                const existingRow = $reviewTable.find('tr[data-reviewer-id="' + userId + '"], tr[data-user-id="' + userId + '"]');
                if (existingRow.length) {
                    // Update existing row
                    existingRow.find('td:eq(1)').text(statusName);
                    existingRow.find('td:eq(2)').text(speciesName);
                    existingRow.find('td:eq(3)').text(dateStr);
                } else {
                    // Add new row - use both data-user-id and data-reviewer-id for compatibility
                    const newRow = $('<tr>')
                        .attr('data-tag-id', tagId)
                        .attr('data-user-id', userId)
                        .attr('data-reviewer-id', userId)
                        .append('<td class="py-1">' + userName + '</td>')
                        .append('<td class="py-1">' + statusName + '</td>')
                        .append('<td class="py-1">' + speciesName + '</td>')
                        .append('<td class="py-1">' + dateStr + '</td>');
                    
                    // Only add delete button if user has permission (logged in and owns review or is admin)
                    if (canDelete) {
                        newRow.append('<td class="py-1"><button type="button" class="btn btn-link btn-sm p-0 text-danger delete-review-btn" style="cursor: pointer;" data-tag-id="' + tagId + '" data-user-id="' + userId + '" title="Delete review"><i class="fas fa-times"></i></button></td>');
                    } else {
                        newRow.append('<td class="py-1"></td>');
                    }
                    
                    $reviewTable.append(newRow);
                }
                
                // Remove dashed style from the tag (indicates reviewed)
                $('#' + tagId).removeClass('tag-dashed');
                
                // Hide the review buttons (user has already reviewed)
                $sidebar.find('#reviewForm .row').first().hide();
                $sidebar.find('#review_animal_group').hide();
                $sidebar.find('#reviewForm .form-group:has(#comments)').hide();
                
                // Hide the task indicator alert
                $sidebar.find('.alert-info:has(.fa-tasks)').hide();
                
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
    
    // Function to rebuild navigation dropdown based on zoom state
    function rebuildNavigationDropdown() {
        const $sidebar = $('#tag-sidebar-content');
        const $dropdown = $sidebar.find('#sidebar-tag-dropdown');
        
        if (!$dropdown.length) return;
        
        const currentTagId = $dropdown.val();
        const nav = window.currentTagNavigation;
        
        // Collect tags that are actually visible on-screen (not just CSS visible)
        const visibleTags = [];
        const spectrumWidth = typeof specWidth !== 'undefined' ? specWidth : $('#myCanvas').width() || 1000;
        
        $('.tag-controls:visible').each(function() {
            const tagId = $(this).attr('id');
            if (tagId && !isNaN(tagId)) {
                const leftPos = parseFloat($(this).css('left')) || 0;
                const tagWidth = parseFloat($(this).css('width')) || 0;
                
                // Only include tags that are actually within the spectrogram view
                // Tag is on-screen if any part of it is visible (with small margin for edges)
                if (leftPos + tagWidth > -10 && leftPos < spectrumWidth + 10) {
                    visibleTags.push({ id: parseInt(tagId), left: leftPos });
                }
            }
        });
        
        // Build tags to show based on zoom state
        const tagsToShow = new Map();
        visibleTags.forEach(tag => tagsToShow.set(tag.id, tag));
        
        // Only add adjacent tags if zoom is ON
        if (window.zoomOnNavigate) {
            if (nav && nav.previous && !tagsToShow.has(nav.previous.id)) {
                const currentLeft = tagsToShow.get(parseInt(currentTagId))?.left || 0;
                tagsToShow.set(nav.previous.id, { id: nav.previous.id, left: currentLeft - 100 });
            }
            if (nav && nav.next && !tagsToShow.has(nav.next.id)) {
                const currentLeft = tagsToShow.get(parseInt(currentTagId))?.left || 0;
                tagsToShow.set(nav.next.id, { id: nav.next.id, left: currentLeft + 100 });
            }
        }
        
        // Sort and rebuild dropdown
        const allTags = Array.from(tagsToShow.values()).sort((a, b) => a.left - b.left);
        $dropdown.empty();
        
        if (allTags.length > 0) {
            allTags.forEach(function(tag) {
                const isSelected = tag.id == currentTagId ? ' selected' : '';
                $dropdown.append('<option value="' + tag.id + '"' + isSelected + '>' + tag.id + '</option>');
            });
        } else {
            $dropdown.append('<option value="' + currentTagId + '" selected>' + currentTagId + '</option>');
        }
        
        // Update navigation buttons
        const hasPrevious = nav && nav.previous && (window.zoomOnNavigate || tagsToShow.has(nav.previous.id));
        const hasNext = nav && nav.next && (window.zoomOnNavigate || tagsToShow.has(nav.next.id));
        
        $sidebar.find('#sidebar-nav-prev')
            .prop('disabled', !hasPrevious)
            .toggleClass('btn-outline-primary', hasPrevious)
            .toggleClass('btn-secondary', !hasPrevious);
        
        $sidebar.find('#sidebar-nav-next')
            .prop('disabled', !hasNext)
            .toggleClass('btn-outline-primary', hasNext)
            .toggleClass('btn-secondary', !hasNext);
    }
    
    // Initialize sidebar navigation controls
    function initializeSidebarNavigation() {
        const $sidebar = $('#tag-sidebar-content');
        
        // Zoom toggle handler
        $sidebar.find('#sidebar-zoom-toggle').off('click').on('click', function() {
            window.zoomOnNavigate = !window.zoomOnNavigate;
            
            // Save state to session storage
            sessionStorage.setItem('zoomOnNavigate', window.zoomOnNavigate.toString());
            
            // Update button appearance (use btn-outline-primary when active, btn-outline-secondary when inactive)
            if (window.zoomOnNavigate) {
                $(this).removeClass('btn-outline-secondary').addClass('btn-outline-primary');
            } else {
                $(this).removeClass('btn-outline-primary').addClass('btn-outline-secondary');
            }
            const icon = $(this).find('i');
            // Switch between magnifying glass (zoom) and pan arrows (no zoom)
            icon.toggleClass('fa-search fa-arrows-alt');
            
            // Update title with clear explanation
            $(this).attr('title', window.zoomOnNavigate ? 'Tag zoom ON: arrow buttons will zoom to adjacent tag' : 'Tag zoom OFF: shuttle to adjacent tag if present in current view');
            
            // Rebuild dropdown to reflect new zoom state
            rebuildNavigationDropdown();
        });
        
        // Previous button handler
        $sidebar.find('#sidebar-nav-prev').off('click').on('click', function() {
            if ($(this).prop('disabled') || !window.currentTagNavigation || !window.currentTagNavigation.previous) {
                return;
            }
            navigateToTag(window.currentTagNavigation.previous, true); // true = user navigation
        });
        
        // Next button handler
        $sidebar.find('#sidebar-nav-next').off('click').on('click', function() {
            if ($(this).prop('disabled') || !window.currentTagNavigation || !window.currentTagNavigation.next) {
                return;
            }
            navigateToTag(window.currentTagNavigation.next, true); // true = user navigation
        });
        
        // Dropdown change handler
        $sidebar.find('#sidebar-tag-dropdown').off('change').on('change', function() {
            const selectedTagId = $(this).val();
            const currentTagId = $(this).find('option:selected').siblings('[selected]').val();
            
            // Don't navigate if same tag is selected
            if (selectedTagId == currentTagId) {
                return;
            }
            
            const nav = window.currentTagNavigation;
            
            // If zoom is ON, always fetch tag data to ensure we have accurate coordinates
            if (window.zoomOnNavigate) {
                // Fetch tag data first to get coordinates
                const baseUrl = window.baseUrl || '';
                const href = baseUrl + '/api/tag/edit/' + selectedTagId;
                const recordingName = document.getElementsByName('recording_name')[0]?.value;
                const postData = recordingName ? {'recording_name': recordingName} : {};
                
                // Add type parameter if in review/task mode
                if (nav && nav.isTask) {
                    postData.type = 'task';
                }
                
                // Fetch tag data to get coordinates
                postRequest(href, postData, false, false, function(response) {
                    // Extract coordinates from the response HTML
                    const $tempModal = $('<div>').html(response.data);
                    const minTime = parseFloat($tempModal.find('#min_time').val());
                    const maxTime = parseFloat($tempModal.find('#max_time').val());
                    const minFreq = parseFloat($tempModal.find('#min_freq').val());
                    const maxFreq = parseFloat($tempModal.find('#max_freq').val());
                    
                    // Create full tag data object
                    const fetchedTagData = {
                        id: selectedTagId,
                        minTime: minTime,
                        maxTime: maxTime,
                        minFrequency: minFreq,
                        maxFrequency: maxFreq
                    };
                    
                    // Now navigate with full data
                    navigateToTag(fetchedTagData);
                });
            } else {
                // Without zoom: just load tag in sidebar
                const baseUrl = window.baseUrl || '';
                const href = baseUrl + '/api/tag/edit/' + selectedTagId;
                const recordingName = document.getElementsByName('recording_name')[0]?.value;
                const postData = recordingName ? {'recording_name': recordingName} : {};
                
                // Add type parameter if in review/task mode
                if (nav && nav.isTask) {
                    postData.type = 'task';
                }
                
                loadTagInSidebar(href, postData, true); // true = user navigation from dropdown
            }
        });
    }
    
    // Navigate to a tag (with or without zoom)
    function navigateToTag(tagData, isUserNavigation = false) {
        if (!tagData) return;
        
        console.log('navigateToTag called with:', tagData, 'zoom:', window.zoomOnNavigate); // Debug
        
        // Update yellow border (tag selection highlight)
        $('.tag-controls').removeClass('tag-selected');
        $('#' + tagData.id).addClass('tag-selected');
        
        if (window.zoomOnNavigate) {
            // Navigate WITH zoom: update form values and submit (like modal behavior)
            console.log('Setting zoom coordinates:', tagData.minTime, tagData.maxTime, tagData.minFrequency, tagData.maxFrequency); // Debug
            $("#x").val(tagData.minTime);
            $("#w").val(tagData.maxTime);
            $("#y").val(tagData.minFrequency);
            $("#h").val(tagData.maxFrequency);
            $("#open").val(tagData.id);
            
            console.log('Form values set, open=' + $("#open").val() + ', submitting...'); // Debug
            
            // Add openTagId to the form action URL to ensure it persists after page reload
            const baseUrl = window.baseUrl || '';
            const currentRecording = window.recording_id || $('#recordingForm input[name="recording_id"]').val();
            let actionUrl = baseUrl + '/recording/show/' + currentRecording;
            
            // If different recording, use that recording ID
            if (tagData.recording && tagData.recording != currentRecording) {
                actionUrl = baseUrl + '/recording/show/' + tagData.recording;
            }
            
            // Append openTagId parameter to URL, and preserve type=task if in review mode
            const urlParams = new URLSearchParams();
            urlParams.set('openTagId', tagData.id);
            
            // Preserve type=task parameter if in task/review mode
            if (window.currentTagNavigation && window.currentTagNavigation.isTask) {
                urlParams.set('type', 'task');
            }
            
            actionUrl += '?' + urlParams.toString();
            
            $('#recordingForm').attr('action', actionUrl);
            console.log('Form action set to:', actionUrl); // Debug
            
            // Submit the form to reload with new tag zoomed
            $("#recordingForm").submit();
        } else {
            // Navigate WITHOUT zoom: just load tag in sidebar
            const baseUrl = window.baseUrl || '';
            const href = baseUrl + '/api/tag/edit/' + tagData.id;
            
            // Check if different recording
            const currentRecording = window.recording_id || $('#recordingForm input[name="recording_id"]').val();
            if (tagData.recording && tagData.recording != currentRecording) {
                // Different recording: need to reload page with new recording but keep current view
                // Save current view coordinates
                const currentX = $("#x").val();
                const currentW = $("#w").val();
                const currentY = $("#y").val();
                const currentH = $("#h").val();
                
                // Navigate to new recording with the new tag and preserve view
                $("#x").val(currentX);
                $("#w").val(currentW);
                $("#y").val(currentY);
                $("#h").val(currentH);
                $("#open").val(tagData.id);
                $('#recordingForm').attr('action', baseUrl + '/recording/show/' + tagData.recording);
                $("#recordingForm").submit();
            } else {
                // Same recording: just load tag in sidebar without changing view
                const recordingName = document.getElementsByName('recording_name')[0]?.value;
                const postData = recordingName ? {'recording_name': recordingName} : {};
                
                // Add type parameter if in review/task mode
                if (window.currentTagNavigation && window.currentTagNavigation.isTask) {
                    postData.type = 'task';
                }
                
                loadTagInSidebar(href, postData, isUserNavigation); // Pass through navigation flag
            }
        }
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
        
        // Global handler for delete review button - catches clicks on button OR its icon child
        $(document).off('click.deleteReview').on('click.deleteReview', '#tag-sidebar-content .delete-review-btn, #tag-sidebar-content .delete-review-btn *', function(e) {
            console.log('DELETE BUTTON CLICKED! Event caught.');
            e.preventDefault();
            e.stopPropagation();
            
            // Find the actual button element (might be clicking on icon child)
            const $btn = $(this).hasClass('delete-review-btn') ? $(this) : $(this).closest('.delete-review-btn');
            console.log('Button found:', $btn.length, $btn.get(0));
            
            const $row = $btn.closest('tr');
            const $sidebar = $('#tag-sidebar-content');
            
            // Try multiple ways to get the IDs
            let reviewTagId = $btn.attr('data-tag-id') || $btn.data('tag-id') || $row.attr('data-tag-id') || $row.data('tag-id');
            let reviewUserId = $btn.attr('data-user-id') || $btn.data('user-id') || $row.attr('data-user-id') || $row.data('user-id') || $row.attr('data-reviewer-id') || $row.data('reviewer-id');
            
            // If still not found, try to get from form
            if (!reviewTagId) {
                reviewTagId = $sidebar.find('#reviewForm input[name="tag_id"]').val() || $sidebar.find('input[name="tag_id"]').val();
            }
            
            console.log('Delete review clicked:', 'tagId=', reviewTagId, 'userId=', reviewUserId);
            console.log('Button element:', $btn.get(0));
            console.log('Button classes:', $btn.attr('class'));
            console.log('Button data-tag-id:', $btn.attr('data-tag-id'));
            console.log('Button data-user-id:', $btn.attr('data-user-id'));
            
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
                    if (tagId) {
                        loadTagInSidebar(baseUrl + '/api/tag/edit/' + tagId);
                    } else {
                        $row.remove();
                    }
                });
            }
        });
        
        // Check if page loaded with an 'open' parameter (from zoom navigation)
        // If so, and sidebar mode is active, open the tag in the sidebar
        function checkAndOpenTag() {
            if (!isSidebarMode()) {
                return false;
            }
            
            // Wait for recording name to be available first
            const recordingNameElement = document.getElementsByName('recording_name')[0];
            if (!recordingNameElement || !recordingNameElement.value) {
                console.log('Recording name not yet available, will retry...'); // Debug
                return false;
            }
            
            // Check multiple sources for the tag ID to open
            const urlParams = new URLSearchParams(window.location.search);
            const urlTagId = urlParams.get('tagId');
            const openTagIdParam = urlParams.get('openTagId'); // Parameter we add during zoom navigation
            const openParam = urlParams.get('open'); // Parameter from dashboard/task navigation
            
            // Check #open field value directly (may be stale)
            const openFieldValue = $('#open').val();
            
            // Also check URL hash
            let hashTagId = null;
            if (window.location.hash) {
                const hashMatch = window.location.hash.match(/openTag=(\d+)/);
                if (hashMatch) {
                    hashTagId = hashMatch[1];
                }
            }
            
            // Check for task/review mode
            const typeParam = urlParams.get('type') || $('input[name="type"]').val();
            const isTaskMode = typeParam === 'task';
            
            // Priority: openTagId parameter > URL tagId parameter > open parameter (from dashboard) > hash > field value
            let openTagId = openTagIdParam || urlTagId || openParam || hashTagId || openFieldValue;
            
            // Validate: must be a positive number, not "0" or empty
            if (!openTagId || openTagId === '' || openTagId === '0') {
                console.log('No valid tag ID found to open (field value:', openFieldValue, ')'); // Debug
                return false;
            }
            
            console.log('Checking for tag to open - openTagId:', openTagIdParam, 'tagId:', urlTagId, 'open:', openParam, 'Hash:', hashTagId, 'Field:', openFieldValue, 'TaskMode:', isTaskMode, 'Using:', openTagId); // Debug
            
            if (openTagId && openTagId !== '' && openTagId !== '0') {
                console.log('Opening tag in sidebar:', openTagId); // Debug
                // Page was loaded with a tag to open - load it in sidebar
                const baseUrl = window.baseUrl || '';
                const href = baseUrl + '/api/tag/edit/' + openTagId;
                
                const recordingName = recordingNameElement.value;
                const postData = recordingName ? {'recording_name': recordingName} : {};
                
                console.log('Type parameter detected:', typeParam); // Debug
                if (isTaskMode) {
                    postData.type = 'task';
                }
                
                console.log('Loading tag with data:', postData); // Debug
                loadTagInSidebar(href, postData);
                
                // Clear the parameters to prevent re-opening on subsequent actions
                $('#open').val('');
                
                // Remove openTagId from URL without page reload
                if (openTagIdParam) {
                    const newUrl = new URL(window.location.href);
                    newUrl.searchParams.delete('openTagId');
                    history.replaceState(null, '', newUrl.toString());
                }
                
                if (window.location.hash) {
                    history.replaceState(null, null, ' ');
                }
                return true;
            }
            return false;
        }
        
        // Try multiple times with increasing delays to catch different page load states
        // Start early for fast loads
        setTimeout(checkAndOpenTag, 300);
        
        // Retry if sidebar is still empty
        setTimeout(function() {
            if (!$('#tag-sidebar-content .tag-sidebar-wrapper').length) {
                console.log('Retry at 800ms - sidebar still empty'); // Debug
                checkAndOpenTag();
            }
        }, 800);
        
        setTimeout(function() {
            if (!$('#tag-sidebar-content .tag-sidebar-wrapper').length) {
                console.log('Retry at 1500ms - sidebar still empty'); // Debug
                checkAndOpenTag();
            }
        }, 1500);
        
        // Final retry with longer delay for dashboard navigation (task/review mode)
        setTimeout(function() {
            if (!$('#tag-sidebar-content .tag-sidebar-wrapper').length) {
                console.log('Final retry at 2500ms - sidebar still empty'); // Debug
                checkAndOpenTag();
            }
        }, 2500);
    });
})();
