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
    let message = 'Frequency of maximum energy: ' + $("#FMaxE").val() + ' kHz (copied to clipboard)'

    const input = document.createElement('input');
    document.body.appendChild(input);
    input.value = message.split('(')[0]
    input.focus();
    input.select();

    const isSuccessful = document.execCommand('copy');

    if (!isSuccessful) {
        message = 'Data copy to clipboard failed.';
    }

    input.remove();

    showAlert(message);
});