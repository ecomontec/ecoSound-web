document.getElementById('exportCoordinates').addEventListener('click', function () {
    let minTime = $('#x').val();
    let maxTime = $('#w').val();
    let minFrequency = $('#y').val();
    let maxFrequency = $('#h').val();
    let message = 'Data copied to clipboard successfully.'

    const input = document.createElement('input');
    document.body.appendChild(input);
    input.value = minTime + '\t' + maxTime + '\t' + minFrequency + '\t' + maxFrequency;
    input.focus();
    input.select();

    const isSuccessful = document.execCommand('copy');

    if (!isSuccessful) {
        message = 'Data copy to clipboard failed.';
    }

    input.remove();

    showAlert(message);
});

$('#exportMaxF').on('click', function (e) {
    $('.loading').toggle();
    let data = {
        'minTime': $('input[name=minTimeView]').val(),
        'maxTime': $('input[name=maxTimeView]').val(),
        'minFrequency': $('input[name=minFreqView]').val(),
        'maxFrequency': $('input[name=maxFreqView]').val(),
        'collection_id': $('input[name=collection_id]').val(),
        'recording_id': $('input[name=recording_id]').val(),
        'filename': soundFilePath,
        'recording_directory': $('input[name=recording_directory]').val(),
        'index': '',
        'channel_num': $('input[name=channel_num]').val(),
        'channel': $('input[name=channel]').val(),
    };
    data = jsToFormData(data)
    $.ajax({
        type: 'POST',
        url: this.href,
        data: data,
        dataType: 'json',
        processData: false,
        contentType: false,
    })
        .done(function (result) {
            let message = 'Frequency of maximum energy: ' + result + ' Hz (copied to clipboard)'

            const input = document.createElement('input');
            document.body.appendChild(input);
            input.value = result
            input.focus();
            input.select();

            const isSuccessful = document.execCommand('copy');

            if (!isSuccessful) {
                message = 'Frequency of maximum energy: ' + result + ' Hz (Data copy to clipboard failed)';
            }

            input.remove();
            $('.loading').toggle();
            showAlert(message);
        })
    e.preventDefault();

});