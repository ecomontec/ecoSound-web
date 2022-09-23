let uploadDir = Math.floor(Math.random() * (10000000 - 100000) + 100000);

$("#file-uploader").pluploadQueue({
    runtimes : 'html5',
    url : baseUrl + '/scripts/uploaded.php?dir=' + uploadDir,
    max_file_size : '1000mb',
    chunk_size : '1mb',
    unique_names : false,
    filters : {
        max_file_size : '1000mb',
        mime_types: [
            {title : "Recording files", extensions : "flac,wav,ogg,mp3"}
        ]
    },
    init : {
        UploadComplete: function(up) {
            $("#save_button").toggleDisabled();
        },
        Error: function(up, args) {
            showAlert("Error uploading files.");
        },
        FilesAdded: function(up, files) {
            plupload.each(files, function(file) {
                if (file.name.replace(/\.[^/.]+$/, "").length > 40) {
                    $(".plupload_start").addClass('plupload_disabled');
                    up.removeFile(file);
                    showAlert('File name too long. Max is 40 characters. Please rename the file '
                        + file.name + ' and upload it again.', true);
                }
            });
        }
    }
});

$('#uploadForm')
    .submit(function(e){
        $('#save_button').toggleDisabled();
        let values = $(this).serialize();
        values["colID"] = $("#collection").val();

        postRequest(
            baseUrl + '/api/file/upload/' + uploadDir,
            values,
            true,
            true,
            function() {
                $('#hiddenForm').toggle();
                $('#upload_btn').toggle();
            }
        );
        e.preventDefault();
    })
    .on('show.bs.collapse', function () {
        document.getElementById('uploadButton').style.display = 'none';
    })
    .on('hide.bs.collapse', function () {
        document.getElementById('uploadButton').style.display = 'block';
    });

$("#reference").change(function(e) {
    let referenceFields = $('.js-reference-field');
    let requiredFields = $('.js-field-required');
    referenceFields.prop('disabled', !referenceFields.prop('disabled'));
    requiredFields.prop('required', !requiredFields.prop('required'));
});

$("#from-file").change(function(e) {
    let fileFields = $('.js-file-field');
    fileFields.prop('disabled', !fileFields.prop('disabled'));
    fileFields.prop('required', !fileFields.prop('required'));
});
$("#type").change(function (){
    if($("#type").val()=="passive"){
        $("#type_label").text("recording from automated sound recorder, no influence on animals, typically omnidirectional microphone")
    }else if($("#type").val()=="focal"){
        $("#type_label").text("recording from active search for animals, e.g. with directional microphone during point counts")
    }else if($("#type").val()=="enclosure"){
        $("#type_label").text("recording from captured animals, movement constrained")
    }
})