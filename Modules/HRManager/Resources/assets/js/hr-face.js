document.addEventListener('DOMContentLoaded', function () {
    const cameraBox = document.querySelector('.hr-camera-box');
    if (cameraBox) {
        cameraBox.addEventListener('click', function () {
            cameraBox.classList.toggle('hr-camera-active');
        });
    }
});
