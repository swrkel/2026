document.addEventListener('submit', function (event) {
    if (event.target && event.target.querySelector('.stn-tester-save')) {
        var button = event.target.querySelector('.stn-tester-save');
        if (button) {
            button.setAttribute('disabled', 'disabled');
            button.innerText = 'Saving...';
        }
    }
});
