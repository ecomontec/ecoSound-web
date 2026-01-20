document.addEventListener('DOMContentLoaded', function () {

    $(document).on('show.bs.modal', '#modal-div', function () {
        const viewFreqRange = maxFrequency - minFrequency;
        const viewTotalTime = maxTime - minTime;

        let readyToClose = true;
        let tagForm = $('#tagForm');
        let reviewForm = $('#reviewForm');
        let left, top, height, width;

        $('#tagForm :input').prop('disabled', tagForm.data('disabled'));
        $('#reviewForm :input').not('#reviewSpeciesName').prop('disabled', reviewForm.data('disabled'));

        if (reviewForm.length) {
            $(this).find('.modal-content').addClass('modal-tag');
            $('#tag-panel')
                .removeClass('col-md-12')
                .addClass('col-md-7')
                .addClass('line-right');
            $('#review-panel').addClass('col-md-5');
        }

        $('#googleImages').click(function () {
            $(this).attr('href', 'http://www.google.com/images?q=' + $('#speciesName[data-type=edit]').val());
        });

        $('#xenoImages').click(function () {
            $(this).attr('href', 'http://www.xeno-canto.org/explore?query=' + $('#speciesName[data-type=edit]').val());
        });

        tagForm.submit(function (e) {
            e.preventDefault();

            if ($("#tagForm :input").prop('disabled') === true) {
                if (reviewForm.length) {
                    reviewForm.submit();
                }
                // Just show success message, don't close or navigate
                showAlert("Saved successfully.")
                return;
            }

            readyToClose = true;

            if (this.checkValidity() === false) {
                e.stopPropagation();
                readyToClose = false;
            } else {
                let tagId = $("input[name='tag_id']").val();

                postRequest(baseUrl + '/api/tag/save', new FormData($(this)[0]), false, false, function (response) {
                    calculateCoordinates();

                    if (response.tagId && response.tagId > 1) {
                        tagId = response.tagId;
                        createTag(tagId);
                    }
                    updateTag(tagId);

                    if (reviewForm.length) {
                        reviewForm.submit();
                    }
                    
                    // Just show success message, don't close or navigate
                    showAlert("Saved successfully.")
                });
            }
            this.classList.add('was-validated');
        });

        $('#deleteButton').click(function () {
            if (confirm('Are you sure you want to delete this tag?')) {
                let modal = $('#modal-div');
                modal.find('button').prop('disabled', true);
                modal.modal('hide');

                let tagId = $(this).data('tag-id');

                deleteRequest(baseUrl + '/api/tag/delete/' + tagId, [], true, false, function () {
                    $('#' + tagId).remove();
                });
            } else {
                return false;
            }
        });

        $('#saveButton').click(function () {
            tagForm.submit();

            // Reload the tags table to reflect changes
            $('#tagsTable').DataTable().ajax.reload(null, false);
            $('.tagsForm').removeAttr("hidden");
        });

        $('#distance').click(function () {
            let callDistance = $('#call_distance');
            if ($(this).is(':checked')) {
                callDistance.val(null);
                callDistance.prop('readonly', true);
                return;
            }
            callDistance.prop('readonly', false);
        });

        $("#review-accept-btn").click(function (e) {
            $('#reviewSpeciesName').prop('disabled', true);
            $('.js-species-id[data-type=review]').val('');
            $('#review_status').val(1);
            $('#state').html('Accepted');
            e.preventDefault();
        });

        $("#review-correct-btn").click(function (e) {
            $('#reviewSpeciesName')
                .prop('disabled', function (i, v) {
                    return !v;
                })
                .prop('required', function (i, v) {
                    return !v;
                });
            $('#review_status').val(2);
            $('#state').html('Corrected');
            e.preventDefault();
        });

        $("#review-delete-btn").click(function (e) {
            $('#reviewSpeciesName').prop('disabled', true);
            $('.js-species-id[data-type=review]').val('');
            $('#review_status').val(3);
            $('#state').html('Rejected');
            e.preventDefault();
        });

        $("#review-uncertain-btn").click(function (e) {
            $('#reviewSpeciesName').prop('disabled', true);
            $('.js-species-id[data-type=review]').val('');
            $('#review_status').val(4);
            $('#state').html('Uncertain');
            e.preventDefault();
        });

        reviewForm.submit(function (e) {
            let reviewStatus = $('#review_status');

            if (this.checkValidity() === false
                || (parseInt(reviewStatus.val()) === 2 && !$('.js-species-id[data-type=review]').val())
            ) {
                e.stopPropagation();
            } else {
                if (reviewStatus.val()) {
                    postRequest(baseUrl + '/api/tagReview/save', new FormData($(this)[0]), false, false, function () {
                        $('#' + $('input[name=tag_id]').val()).removeClass('tag-dashed');
                        $('#reviewForm .row').hide()
                        $('#reviewForm .form-group').hide()
                        var review_text = ''
                        if (reviewStatus.val() == 1) {
                            review_text = 'Accepted'
                        } else if (reviewStatus.val() == 2) {
                            review_text = 'Corrected'
                        } else if (reviewStatus.val() == 3) {
                            review_text = 'Rejected'
                        } else if (reviewStatus.val() == 4) {
                            review_text = 'Uncertain'
                        }
                        var row = '<tr><td class="form-control-sm">' + username + '</td><td class="form-control-sm">' + review_text + '</td><td class="form-control-sm">' + $('#reviewSpeciesName').val() + '</td><td class="form-control-sm">' + new Date().toLocaleDateString('en-GB') + '</td></tr>'
                        $('.review-table tbody').append(row)
                        reviewStatus.val('')
                    })
                }
            }

            this.classList.add('was-validated');
            e.preventDefault();
        });

        $('#distanceButton').click(function () {
            $("#callDistanceForm").submit()
        });

        $("#callDistanceForm").submit(function (e) {
            postRequest(baseUrl + '/api/tag/save', new FormData($(this)[0]), true, false);
            $('#modal-div').modal('hide');
            e.preventDefault();
        });

        let createTag = function (tagId) {
            let speciesName = $('#speciesName').val();
            let soundType = $('#sound_id').find("option:selected").text();
            let soundscape_component = $('#soundscape_component').val();
            let tagName = speciesName ? speciesName : soundType ? soundType : soundscape_component

            // Create tag box matching the structure in tagBoxes.html.twig
            let newTag = "<div class='tag-controls tag-dashed' id='" + tagId + "' data-tag-id='" + tagId + "' data-edit-url='" + baseUrl + "/api/tag/edit/" + tagId + "' style='z-index:800; border-color: white; left: ";
            newTag += left + "px; top: " + top + "px; height: " + height + "px; width: " + width + "px;'></div>";
            
            // Create popup card matching the regular tag structure
            newTag += "<div class='card js-panel-tag' style='display:none;'>";
            newTag += "<div class='card-header py-1 px-2'><small>" + tagId + " | " + tagName + "</small></div>";
            newTag += "<div class='card-body p-2 mx-auto'>";
            newTag += "<div class='text-muted small text-center'>Click tag to edit</div>";
            newTag += "</div></div>";

            $('#myCanvas').append(newTag);
        };

        let updateTag = function (tagId) {
            const callDistance = $('#callDistance').val();
            const distanceNotEstimable = $('#distanceNotEstimable').is(':checked');

            let speciesName = $('#speciesName').val();
            let soundType = $('#sound_id').find("option:selected").text();
            let soundscape_component = $('#soundscape_component').val();
            let tagName = speciesName ? speciesName : soundType ? soundType : soundscape_component

            let tagElement = $('#' + tagId);

            tagElement.removeClass('tag-orange');

            if (!callDistance && !distanceNotEstimable && soundscape_component == 'biophony') {
                tagElement.addClass('tag-orange');
            }

            tagElement.next('.js-panel-tag').find('.card-header').find('small').text(tagId + " | " + tagName);
            tagElement
                .css('width', width + 'px')
                .css('height', height + 'px')
                .css('left', left + 'px')
                .css('top', top + 'px');
        };

        let calculateCoordinates = function () {
            let freq_min = $('#min_freq').val();
            let freq_max = $('#max_freq').val();
            let time_min = $('#min_time').val();
            let time_max = $('#max_time').val();

            time_max = time_max > maxTime ? maxTime : time_max;
            time_min = time_min < minTime ? minTime : time_min;
            freq_max = freq_max > maxFrequency ? maxFrequency : freq_max;
            freq_min = freq_min < minFrequency ? minFrequency : freq_min;

            left = ((time_min - minTime) / viewTotalTime) * specWidth;
            top = (((viewFreqRange + minFrequency) - freq_max) / viewFreqRange) * specHeight;
            height = ((freq_max - freq_min) / viewFreqRange) * specHeight;
            width = ((time_max - time_min) / viewTotalTime) * specWidth;
        }
        $('#exportTagUrl').click(function () {
            let minTime = $('#min_time').val();
            let maxTime = $('#max_time').val();
            let minFrequency = $('#min_freq').val();
            let maxFrequency = $('#max_freq').val();
            let col_id = $("input[name='recording_id']").val();
            let message = 'Url copied to clipboard successfully.'

            const input = document.createElement('input');
            document.body.appendChild(input);
            input.value = baseUrl + "/recording/show/" + col_id + "?t_min=" + minTime + "&t_max=" + maxTime + "&f_min=" + minFrequency + "&f_max=" + maxFrequency;
            input.focus();
            input.select();

            const isSuccessful = document.execCommand('copy');

            if (!isSuccessful) {
                message = 'Url copy to clipboard failed.';
            }

            input.remove();
            showAlert(message);
        });
    });
});
