let uploadDir = Math.floor(Math.random() * (10000000 - 100000) + 100000);
var isUploading = false;
var closeButtonClicked = false;
var uploadTable = $('#upload-table').DataTable({
    "bAutoWidth": false,
    "scrollX": true,
    "paging": false,
    "searching": false,
    "info": false,
    "ordering": false,
});

$("#file-uploader").pluploadQueue({
    runtimes: 'html5',
    url: baseUrl + '/scripts/uploaded.php?dir=' + uploadDir,
    max_file_size: '1000mb',
    chunk_size: '1mb',
    unique_names: false,
    multiple_queues: true,
    prevent_duplicates: true,
    filters: {
        max_file_size: '1000mb',
        mime_types: [
            {title: "Recording files", extensions: "flac,wav,ogg,mp3"}
        ]
    },
    init: {
        UploadComplete: function (up) {
            $("#save_button").prop("disabled", false);
            isUploading = false;
            $.ajax({
                url: baseUrl + "/api/admin/recordingManager/getid3/" + uploadDir,
                method: 'POST',
                data: {
                    project_id: $('#projectId').val(),
                    collection_id: $('#colId').val(),
                    freq: $('#freq').val()
                },
                success: function (data) {
                    if (data) {
                        var rows = uploadTable.rows().nodes();
                        for (var i = rows.length - 1; i > 0; i--) {
                            uploadTable.row(rows[i]).remove().draw();
                        }
                        $.each(data, function (index, item) {
                            uploadTable.row.add(item)
                        })
                        uploadTable.draw()
                    }
                },
            })
        },
        Error: function (up, args) {
            showAlert(args.message.replace(/\.([^\.]*)$/, ": $1") + args.file.name);
            $('.loading').hide()
        },
        FilesAdded: function (up, files) {
            plupload.each(files, function (file) {
                if (file.name.replace(/\.[^/.]+$/, "").length > 150) {
                    $(".plupload_start").addClass('plupload_disabled');
                    up.removeFile(file);
                    showAlert('File name: ' + file.name + ' too long. Maximum: 150 characters. File was skipped.', true);
                }
            });
            if (up.files.length > 0) {
                isUploading = true;
                document.querySelector('.plupload_start').click();
                $('#uploadForm').collapse('show');
                $("#save_button").prop("disabled", true);
            }
            $('.loading').hide()
        }
    }
});

$('#closeButton').click(function () {
    closeButtonClicked = true;
});

$(window).on('beforeunload', function (event) {
    if (!closeButtonClicked && isUploading) {
        var confirmationMessage = 'Leaving this page will abort the ongoing upload. Abort?';
        (event || window.event).returnValue = confirmationMessage;
        return confirmationMessage;
    }
});

$('#uploadForm').submit(function (e) {
    let formData = new FormData();
    $('#upload-table tbody tr').each(function (index) {
        if (index > 0) {
            $(this).find('input, select').each(function () {
                let name = $(this).attr('name');
                let value = $(this).val();
                if (name) {
                    formData.append(`${name.replace('upload_', '')}[${index}]`, value);
                }
            });
        }
    });
    formData.append('collection_id', $('#collection_id').val())
    formData.append('count', $('#file-uploader_count').val())
    $("#save_button").prop("disabled", true);
    $('.card-body input').prop('disabled', true);
    $('.card-body select').prop('disabled', true);
    $('#upload-table button').prop('disabled', true);
    $('.plupload_add').hide();
    postRequest(
        baseUrl + '/api/file/upload/' + uploadDir,
        formData,
        true,
        true,
        function (response) {
            $('#upload_btn').toggle();
        })
    e.preventDefault();
})

$("#reference").change(function (e) {
    let referenceFields = $('.js-reference-field');
    let requiredFields = $('.js-field-required');
    referenceFields.prop('disabled', !referenceFields.prop('disabled'));
    requiredFields.prop('required', !requiredFields.prop('required'));
});

$(function () {

    $("#metaDataButton").on("click", function () {
        $("#metaDataFile").click();
    })

    $("#metaDataFile").on("change", function () {
        analysisList(this.files);
    })

    function analysisList(obj) {
        if (obj.length < 1) {
            return false;
        }
        var fileObj = obj[0];
        var name = fileObj.name;
        var size = fileObj.size;
        var type = fileType(name);
        if (("csv").indexOf(type) == -1) {
            showAlert(name + ' file type error.');
            return
        }
        if (size > 5 * 1024 * 1024 || size == 0) {
            showAlert(name + ' file size exceeds 5M.');
            return
        }
        toggleLoading();
        $.ajax({
            url: baseUrl + '/api/file/metadata',
            type: "POST",
            data: new FormData($("#metaDataForm")[0]),
            processData: false,
            contentType: false,
            success: function (result) {
                if (result.message == 'Upload success.') {
                    location.reload();
                } else {
                    showAlert(result.message)
                    $('#metaDataFile').val('')
                    toggleLoading();
                }
            },
            error: function () {
                showAlert("Failed to upload file.")
                toggleLoading();
            }
        });
    }

    function fileType(name) {
        var nameArr = name.split(".");
        return nameArr[nameArr.length - 1].toLowerCase();
    }

    $("#upload-table").on('change', '#name', function () {
        var input_text = $(this).val();
        uploadTable.rows().every(function () {
            var row = $(this.node())
            row.find('[name="upload_name"]').val(input_text + row.find('[name="upload_filename"]').val())
        })
    })

    $("#upload-table").on('change', '#site', function () {
        var select_value = $(this).val()
        uploadTable.rows().every(function () {
            var row = $(this.node())
            row.find('select[name="upload_site"]').val(select_value)
        })
    })

    $("#upload-table").on('change', '#recorder', function () {
        var microphone = $('#microphone')
        var upload_microphone = $('[name="upload_microphone"]')
        var microphone_option = $(this).find('option:selected').attr('data-microphone').split(',')
        microphone.empty()
        upload_microphone.empty()
        microphone.removeAttr('disabled')
        upload_microphone.removeAttr('disabled')
        microphone.append("<option selected disabled></option>")
        upload_microphone.append("<option selected disabled></option>")
        for (var key in microphones) {
            if ($.inArray(microphones[key]['microphone_id'] += '', microphone_option) >= 0) {
                microphone.append("<option value='" + microphones[key]['microphone_id'] + "'>" + microphones[key]['name'] + "</option>");
                upload_microphone.append("<option value='" + microphones[key]['microphone_id'] + "'>" + microphones[key]['name'] + "</option>");
            }
        }
        var select_value = $(this).val()
        uploadTable.rows().every(function () {
            var row = $(this.node())
            row.find('select[name="upload_recorder"]').val(select_value)
        })
    })

    $("#upload-table").on('change', '[name="upload_recorder"]', function () {
        var upload_microphone = $(this).parent().parent().find('[name="upload_microphone"]')
        var microphone_option = $(this).find('option:selected').attr('data-microphone').split(',')
        upload_microphone.empty()
        upload_microphone.removeAttr('disabled')
        upload_microphone.append("<option selected disabled></option>")
        for (var key in microphones) {
            if ($.inArray(microphones[key]['microphone_id'] += '', microphone_option) >= 0) {
                upload_microphone.append("<option value='" + microphones[key]['microphone_id'] + "'>" + microphones[key]['name'] + "</option>");
            }
        }
    })

    $("#upload-table").on('change', '#microphone', function () {
        var select_value = $(this).val()
        uploadTable.rows().every(function () {
            var row = $(this.node())
            row.find('select[name="upload_microphone"]').val(select_value)
        })
    })

    $("#upload-table").on('change', '#license', function () {
        var select_value = $(this).val()
        uploadTable.rows().every(function () {
            var row = $(this.node())
            row.find('select[name="upload_license"]').val(select_value)
        })
    })

    $("#upload-table").on('change', '#type', function () {
        var select_value = $(this).val()
        uploadTable.rows().every(function () {
            var row = $(this.node())
            row.find('select[name="upload_type"]').val(select_value)
        })
    })

    $("#upload-table").on('change', '#medium', function () {
        var select_value = $(this).val()
        uploadTable.rows().every(function () {
            var row = $(this.node())
            row.find('select[name="upload_medium"]').val(select_value)
        })
    })

    $("#upload-table").on('change', '#note', function () {
        var input_text = $(this).val();
        uploadTable.rows().every(function () {
            $(this.node()).find('[name="upload_note"]').val(input_text)
        })
    })

    $("#upload-table").on('change', '#doi', function () {
        var input_text = $(this).val();
        uploadTable.rows().every(function () {
            $(this.node()).find('[name="upload_DOI"]').val(input_text)
        })
    })

    $("#upload-table").on('change', '#date', function () {
        var input_text = $(this).val();
        uploadTable.rows().every(function () {
            $(this.node()).find('[name="upload_file_date"]').val(input_text)
        })
    })

    $("#upload-table").on('change', '#time', function () {
        var input_text = $(this).val();
        uploadTable.rows().every(function () {
            $(this.node()).find('[name="upload_file_time"]').val(input_text)
        })
    })

    const DATE_TIME_PATTERN = /(\d{4})(\d{2})(\d{2})_(\d{2})(\d{2})(\d{2})/;

    function formatDateTime(fileName) {
        if (fileName) {
            const match = fileName.match(DATE_TIME_PATTERN);
            if (match) {
                const [_, year, month, day, hour, minute, second] = match;

                const fileDate = `${year}-${month}-${day}`;
                const fileTime = `${hour}:${minute}:${second}`;

                return {fileDate, fileTime};
            }
        }
        return null;
    }

    $("#upload-table").on('click', '#btn-date', function (event) {
        uploadTable.rows().every(function () {
            var row = $(this.node())
            var filename = row.find('[name="upload_filename"]').val()
            var date = formatDateTime(filename)
            if (date) {
                $(this.node()).find('[name="upload_file_date"]').val(date.fileDate)
                $(this.node()).find('[name="upload_file_time"]').val(date.fileTime)
                $(this.node()).find('[name="upload_file_date"]').addClass('is-valid')
                $(this.node()).find('[name="upload_file_time"]').addClass('is-valid')
            }
        })
        showAlert('Set date success.')
        event.preventDefault();
    })
})
