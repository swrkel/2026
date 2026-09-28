document.addEventListener('click', function (event) {
    if (event.target && event.target.classList.contains('stn-support-repair-btn')) {
        if (!confirm('Run this safe repair action now?')) {
            event.preventDefault();
        }
    }
});
