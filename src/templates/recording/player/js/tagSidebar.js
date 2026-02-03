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
                const isReviewMode = nav && nav.isTask;
                
                sidebarHTML += '<div class="d-flex align-items-center mb-2 pb-2 border-bottom">';
                
                // Tag label
                sidebarHTML += '<span class="mr-2"><strong>Tag ' + currentTagId + '</strong></span>';
                
                // Find previous and next visible tags
                const visibleTags = [];
                $('.tag-controls:visible').each(function() {
                    const tagId = $(this).attr('id');
                    if (tagId && !isNaN(tagId)) {
                        const leftPos = parseFloat($(this).css('left')) || 0;
                        visibleTags.push({ id: parseInt(tagId), left: leftPos });
                    }
                });
                visibleTags.sort((a, b) => a.left - b.left);
                
                const currentIndex = visibleTags.findIndex(t => t.id == currentTagId);
                const hasPrevVisible = currentIndex > 0;
                const hasNextVisible = currentIndex >= 0 && currentIndex < visibleTags.length - 1;
                
                // Navigation from sequence (for review mode)
                const hasPrevSequence = nav && nav.previous;
                const hasNextSequence = nav && nav.next;
                
                // Shift to adjacent visible tag buttons (no zoom)
                sidebarHTML += '<button class="btn btn-sm ' + (hasPrevVisible ? 'btn-outline-success' : 'btn-secondary') + ' mr-1" ';
                sidebarHTML += 'id="sidebar-shift-prev" title="Previous visible tag (no zoom)"' + (!hasPrevVisible ? ' disabled' : '') + '>';
                sidebarHTML += '<i class="fas fa-arrow-left"></i></button>';
                
                sidebarHTML += '<button class="btn btn-sm ' + (hasNextVisible ? 'btn-outline-success' : 'btn-secondary') + ' mr-2" ';
                sidebarHTML += 'id="sidebar-shift-next" title="Next visible tag (no zoom)"' + (!hasNextVisible ? ' disabled' : '') + '>';
                sidebarHTML += '<i class="fas fa-arrow-right"></i></button>';
                
                // Zoom to adjacent tag buttons (left button on left, right button on right)
                sidebarHTML += '<button class="btn btn-sm ' + (hasPrevSequence ? 'btn-outline-success' : 'btn-secondary') + ' mr-1" ';
                sidebarHTML += 'id="sidebar-zoom-prev" title="Zoom to previous tag"' + (!hasPrevSequence ? ' disabled' : '') + '>';
                sidebarHTML += '<i class="fas fa-arrow-left"></i> <i class="fas fa-search"></i></button>';
                
                sidebarHTML += '<button class="btn btn-sm ' + (hasNextSequence ? 'btn-outline-success' : 'btn-secondary') + ' mr-2" ';
                sidebarHTML += 'id="sidebar-zoom-next" title="Zoom to next tag"' + (!hasNextSequence ? ' disabled' : '') + '>';
                sidebarHTML += '<i class="fas fa-search"></i> <i class="fas fa-arrow-right"></i></button>';
                
                // Padding multiplier for zoom
                const savedPadding = sessionStorage.getItem('tagZoomPadding') || '0';
                sidebarHTML += '<div class="d-flex align-items-center mr-2" style="font-size: 0.85rem;">';
                sidebarHTML += '<label class="mb-0 mr-1" for="zoom-padding" title="Add context around tag when zooming (multiplier of tag duration)">Pad:</label>';
                sidebarHTML += '<input type="number" id="zoom-padding" class="form-control form-control-sm" ';
                sidebarHTML += 'style="width: 45px; height: 31px; padding: 0.25rem 0.3rem;" min="0" max="10" step="0.5" value="' + savedPadding + '" title="Padding multiplier (0-10x tag duration)">';
                sidebarHTML += '<span class="ml-1">×</span>';
                sidebarHTML += '</div>';
                
                // Review mode badge (only shown when in task/review mode)
                if (isReviewMode) {
                    sidebarHTML += '<span class="badge badge-success ml-2">Review Mode</span>';
                }
                
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
        
        // Initially disable save button (will enable on form changes)
        const $saveBtn = $sidebar.find('#saveButton');
        $saveBtn.prop('disabled', true);
        $saveBtn.removeClass('btn-outline-primary').addClass('btn-secondary');
        
        if (tagForm.length) {
            tagForm.find(':input').prop('disabled', tagForm.data('disabled'));
        }
        
        if (reviewForm.length) {
            // Disable form inputs but NOT delete review buttons (users can delete their own reviews)
            reviewForm.find(':input').not('#reviewSpeciesName').not('.delete-review-btn').prop('disabled', reviewForm.data('disabled'));
        }
        
        // Store initial form state to detect actual changes
        // This prevents programmatic value setting during initialization from enabling the save button
        let initialFormState = null;
        
        // Capture initial form state after a delay to allow form population
        setTimeout(function() {
            initialFormState = tagForm.serialize();
            console.log('Initial form state captured'); // Debug
        }, 200);
        
        // Enable save button when form inputs change (only if values actually changed from initial state)
        $sidebar.find('#tag-panel-sidebar').off('input change').on('input change', 'input, select, textarea', function () {
            if (!initialFormState) return; // Not initialized yet
            
            const currentState = tagForm.serialize();
            if (currentState !== initialFormState) {
                const $saveBtn = $sidebar.find('#saveButton');
                $saveBtn.prop('disabled', false);
                $saveBtn.removeClass('btn-secondary').addClass('btn-outline-success');
                $sidebar.find('.type-btn').removeAttr('disabled');
                console.log('Form changed - save button enabled'); // Debug
            }
        });
        
        // Also handle selectpicker changes (only if values actually changed from initial state)
        $sidebar.find('#sound_id').off('changed.bs.select').on('changed.bs.select', function () {
            if (!initialFormState) return; // Not initialized yet
            
            const currentState = tagForm.serialize();
            if (currentState !== initialFormState) {
                const $saveBtn = $sidebar.find('#saveButton');
                $saveBtn.prop('disabled', false);
                $saveBtn.removeClass('btn-secondary').addClass('btn-outline-success');
                $sidebar.find('.type-btn').removeAttr('disabled');
                console.log('Sound type changed - save button enabled'); // Debug
            }
        });
        
        // Handle review form button clicks - enable save button
        $sidebar.find('#reviewForm').off('click.enableSave').on('click.enableSave', 'button', function () {
            const $saveBtn = $sidebar.find('#saveButton');
            $saveBtn.prop('disabled', false);
            $saveBtn.removeClass('btn-secondary').addClass('btn-outline-success');
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
            console.log('Export tag URL clicked'); // Debug
            const tagId = $sidebar.find("input[name='tag_id']").val() || $sidebar.find('#exportTagUrlData').val();
            console.log('Tag ID:', tagId); // Debug
            if (tagId) {
                const url = baseUrl + '/recording/show/' + $('input[name="recording_id"]').val() + '?tagId=' + tagId;
                console.log('Generated URL:', url); // Debug
                
                // Try to use clipboard API if available (HTTPS only)
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(url).then(function() {
                        showAlert('Tag URL copied to clipboard!');
                    }).catch(function(err) {
                        console.error('Clipboard error:', err); // Debug
                        // Fallback for clipboard errors
                        prompt('Copy this URL:', url);
                    });
                } else {
                    // Fallback for HTTP or older browsers
                    console.log('Clipboard API not available, using prompt fallback'); // Debug
                    prompt('Copy this URL:', url);
                }
            } else {
                console.log('No tag ID found'); // Debug
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
            const $saveBtn = $sidebar.find('#saveButton');
            $saveBtn.prop('disabled', false);
            $saveBtn.removeClass('btn-secondary').addClass('btn-outline-success');
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
                
                // Reset save button to disabled/gray state after successful save
                const $saveBtn = $sidebar.find('#saveButton');
                $saveBtn.prop('disabled', true);
                $saveBtn.removeClass('btn-outline-success').addClass('btn-secondary');
                
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
                    <div class="text-muted small text-center">Click tag to edit</div>
                </div>
            </div>
        `;
        
        $('#' + tagId).after(panelHTML);
    }
    
    // Initialize sidebar navigation controls
    function initializeSidebarNavigation() {
        const $sidebar = $('#tag-sidebar-content');
        
        // Handle padding multiplier changes - persist to sessionStorage
        $sidebar.find('#zoom-padding').off('change').on('change', function() {
            let value = parseFloat($(this).val()) || 0;
            // Validate range
            if (value < 0) value = 0;
            if (value > 10) value = 10;
            $(this).val(value);
            sessionStorage.setItem('tagZoomPadding', value.toString());
        });
        
        // Shift to previous visible tag (no zoom)
        $sidebar.find('#sidebar-shift-prev').off('click').on('click', function() {
            if ($(this).prop('disabled')) return;
            
            // Find previous visible tag
            const visibleTags = [];
            $('.tag-controls:visible').each(function() {
                const tagId = $(this).attr('id');
                if (tagId && !isNaN(tagId)) {
                    const leftPos = parseFloat($(this).css('left')) || 0;
                    visibleTags.push({ id: parseInt(tagId), left: leftPos });
                }
            });
            visibleTags.sort((a, b) => a.left - b.left);
            
            const currentTagId = parseInt($sidebar.find('input[name="tag_id"]').val());
            const currentIndex = visibleTags.findIndex(t => t.id === currentTagId);
            
            if (currentIndex > 0) {
                const prevTag = visibleTags[currentIndex - 1];
                navigateToTag(prevTag, false); // false = no zoom
            }
        });
        
        // Shift to next visible tag (no zoom)
        $sidebar.find('#sidebar-shift-next').off('click').on('click', function() {
            if ($(this).prop('disabled')) return;
            
            // Find next visible tag
            const visibleTags = [];
            $('.tag-controls:visible').each(function() {
                const tagId = $(this).attr('id');
                if (tagId && !isNaN(tagId)) {
                    const leftPos = parseFloat($(this).css('left')) || 0;
                    visibleTags.push({ id: parseInt(tagId), left: leftPos });
                }
            });
            visibleTags.sort((a, b) => a.left - b.left);
            
            const currentTagId = parseInt($sidebar.find('input[name="tag_id"]').val());
            const currentIndex = visibleTags.findIndex(t => t.id === currentTagId);
            
            if (currentIndex >= 0 && currentIndex < visibleTags.length - 1) {
                const nextTag = visibleTags[currentIndex + 1];
                navigateToTag(nextTag, false); // false = no zoom
            }
        });
        
        // Zoom to previous tag
        $sidebar.find('#sidebar-zoom-prev').off('click').on('click', function() {
            if ($(this).prop('disabled') || !window.currentTagNavigation || !window.currentTagNavigation.previous) {
                return;
            }
            navigateToTag(window.currentTagNavigation.previous, true); // true = zoom
        });
        
        // Zoom to next tag
        $sidebar.find('#sidebar-zoom-next').off('click').on('click', function() {
            if ($(this).prop('disabled') || !window.currentTagNavigation || !window.currentTagNavigation.next) {
                return;
            }
            navigateToTag(window.currentTagNavigation.next, true); // true = zoom
        });
    }
    
    // Navigate to a tag (with or without zoom)
    function navigateToTag(tagData, shouldZoom) {
        if (!tagData) return;
        
        console.log('navigateToTag called with:', tagData, 'zoom:', shouldZoom); // Debug
        
        // Update yellow border (tag selection highlight)
        $('.tag-controls').removeClass('tag-selected');
        $('#' + tagData.id).addClass('tag-selected');
        
        const baseUrl = window.baseUrl || '';
        const currentRecording = window.recording_id || $('#recordingForm input[name="recording_id"]').val();
        
        if (shouldZoom) {
            // Navigate WITH zoom: fetch tag data if needed, then zoom
            const fetchAndZoom = function(fetchedData) {
                console.log('Zooming to tag:', fetchedData); // Debug
                
                // Apply padding multiplier if set
                let minTime = fetchedData.minTime;
                let maxTime = fetchedData.maxTime;
                const paddingMultiplier = parseFloat($('#zoom-padding').val()) || 0;
                
                if (paddingMultiplier > 0) {
                    const tagDuration = maxTime - minTime;
                    const padding = tagDuration * paddingMultiplier;
                    minTime = Math.max(0, minTime - padding);
                    // Don't exceed recording duration if available
                    const recordingDuration = parseFloat($('input[name="fileDuration"]').val());
                    if (recordingDuration) {
                        maxTime = Math.min(recordingDuration, maxTime + padding);
                    } else {
                        maxTime = maxTime + padding;
                    }
                }
                
                $("#x").val(minTime);
                $("#w").val(maxTime);
                $("#y").val(fetchedData.minFrequency);
                $("#h").val(fetchedData.maxFrequency);
                $("#open").val(fetchedData.id);
                
                // Set action URL
                let actionUrl = baseUrl + '/recording/show/';
                if (fetchedData.recording && fetchedData.recording != currentRecording) {
                    actionUrl += fetchedData.recording;
                } else {
                    actionUrl += currentRecording;
                }
                
                // Add URL parameters
                const urlParams = new URLSearchParams();
                urlParams.set('openTagId', fetchedData.id);
                if (window.currentTagNavigation && window.currentTagNavigation.isTask) {
                    urlParams.set('type', 'task');
                }
                actionUrl += '?' + urlParams.toString();
                
                $('#recordingForm').attr('action', actionUrl);
                $("#recordingForm").submit();
            };
            
            // If we have coordinates, use them; otherwise fetch
            if (tagData.minTime !== undefined && tagData.maxTime !== undefined) {
                fetchAndZoom(tagData);
            } else {
                // Fetch tag data to get coordinates
                const href = baseUrl + '/api/tag/edit/' + tagData.id;
                const recordingName = document.getElementsByName('recording_name')[0]?.value;
                const postData = recordingName ? {'recording_name': recordingName} : {};
                if (window.currentTagNavigation && window.currentTagNavigation.isTask) {
                    postData.type = 'task';
                }
                
                postRequest(href, postData, false, false, function(response) {
                    const $tempModal = $('<div>').html(response.data);
                    fetchAndZoom({
                        id: tagData.id,
                        minTime: parseFloat($tempModal.find('#min_time').val()),
                        maxTime: parseFloat($tempModal.find('#max_time').val()),
                        minFrequency: parseFloat($tempModal.find('#min_freq').val()),
                        maxFrequency: parseFloat($tempModal.find('#max_freq').val()),
                        recording: tagData.recording
                    });
                });
            }
        } else {
            // Navigate WITHOUT zoom: just load tag in sidebar
            const href = baseUrl + '/api/tag/edit/' + tagData.id;
            
            // Check if different recording
            if (tagData.recording && tagData.recording != currentRecording) {
                // Different recording: reload page but keep current view
                $("#open").val(tagData.id);
                $('#recordingForm').attr('action', baseUrl + '/recording/show/' + tagData.recording);
                $("#recordingForm").submit();
            } else {
                // Same recording: just load tag in sidebar without changing view
                const recordingName = document.getElementsByName('recording_name')[0]?.value;
                const postData = recordingName ? {'recording_name': recordingName} : {};
                if (window.currentTagNavigation && window.currentTagNavigation.isTask) {
                    postData.type = 'task';
                }
                loadTagInSidebar(href, postData, true);
            }
        }
    }
    
    // Dummy modal function for sidebar (prevents errors)
    $.fn.sidebarModal = function() {
        return this;
    };
    
    // Override the tag-related handlers to use sidebar when appropriate
    $(document).ready(function() {
        // Store original handler for non-tag modals (like call distance estimation)
        const originalRequestModal = window.requestModal;
        
        // Override requestModal to always use sidebar for tag edit/create
        window.requestModal = function(href, data = [], showLoading = false, backdrop = true) {
            const isTagRequest = (href.includes('/api/tag/edit/') || href.includes('/api/tag/create'));
            
            if (isTagRequest) {
                // Always use sidebar for tag requests (no modal fallback)
                loadTagInSidebar(href, data);
            } else {
                // Use original modal for everything else (like call distance estimation)
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
