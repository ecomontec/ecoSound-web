document.addEventListener('DOMContentLoaded', function () {

    $(document).on('show.bs.modal', '#modal-div', function () {
        const viewFreqRange = maxFrequency - minFrequency;
        const viewTotalTime = maxTime - minTime;

        let closeModalTagForm = false;
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
            $(this).attr('href', 'http://www.xeno-canto.org/browse.php?query=' + $('#speciesName[data-type=edit]').val());
        });

        tagForm.submit(function (e) {
            e.preventDefault();

            if ($("#tagForm :input").prop('disabled') === true) {
                return;
            }

            readyToClose = true;

            if (this.checkValidity() === false) {
                e.stopPropagation();
                readyToClose = false;
            } else {
                let tagId = $("input[name='tag_id']").val();

                postRequest(baseUrl + '/api/tag/save', $(this).serialize(), false, false, function (response) {
                    calculateCoordinates();

                    if (response.tagId && response.tagId > 1) {
                        tagId = response.tagId;
                        createTag(tagId);
                    }
                    updateTag(tagId);

                    if (closeModalTagForm === true) {
                        $('#modal-div').modal('hide');
                    }
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
            closeModalTagForm = !reviewForm.length;

            tagForm.submit();

            if (reviewForm.length) {
                reviewForm.submit();
            }
        });

        $('#distanceNotEstimable').click(function () {
            let callDistance = $('#callDistance');
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
            $('#state').html('Deleted');
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
                    postRequest(baseUrl + '/api/tagReview/save', $(this).serialize(), false, false, function () {
                        $('#' + $('input[name=tag_id]').val()).removeClass('tag-dashed');
                    })
                }

                if (!closeModalTagForm && readyToClose) {
                    $('#modal-div').modal('hide');
                }
            }

            this.classList.add('was-validated');
            e.preventDefault();
        });

        let createTag = function (tagId) {
            let speciesName = $('#speciesName').val();

            let newTag = "<div class='tag-controls tag-dashed' id='" + tagId + "' style='z-index:800; border-color: white; left: ";
            newTag += left + "px; top: " + top + "px; height: " + height + "px; width: " + width + "px;'></div>";
            newTag += "<div class='card js-panel-tag'><div class='card-header'><small>" + tagId + " | " + speciesName + "</small></div>";
            newTag += "<div class='card-body mx-auto'><div class='btn-group' role='group'>";
            newTag += "<a href='" + baseUrl + "/tag/edit/" + tagId + "' class='btn btn-outline-primary btn-sm js-tag' title='Edit tag'>";
            newTag += "<i class='fas fa-edit' aria-hidden='true'></i></a>";
            newTag += "<a href='#' onclick='return false;' class='btn btn-outline-primary btn-sm zoom-tag' title='Zoom tag'><i class='fas fa-search' aria-hidden='true'></i></a>";
            newTag += "<a href='#' onclick='return false;' id='est_" + tagId + "' type='button' class='btn btn-outline-primary btn-sm estimate-distance' title='Estimate call distance'><i class='fas fa-bullhorn' aria-hidden='true'></i></a>";
            newTag += "</div></div></div>";

            $('#myCanvas').append(newTag);
        };

        let updateTag = function (tagId) {
            const callDistance = $('#callDistance').val();
            const distanceNotEstimable = $('#distanceNotEstimable').is(':checked');

            let tagElement = $('#' + tagId);

            tagElement.removeClass('tag-orange');
            console.log(callDistance);
            if (!callDistance && !distanceNotEstimable) {
                tagElement.addClass('tag-orange');
            }

            tagElement.next('.js-panel-tag').find('.card-header').find('small').text(tagId + " | " + $('#speciesName').val());
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
    });
});
