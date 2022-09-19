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