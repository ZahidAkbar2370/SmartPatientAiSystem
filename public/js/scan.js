(function () {
    const video = document.getElementById('camera-preview');
    const canvas = document.getElementById('capture-canvas');
    const capturedPreview = document.getElementById('captured-preview');
    const cameraHint = document.getElementById('camera-hint');
    const permissionAlert = document.getElementById('camera-permission-alert');
    const statusText = document.getElementById('status-text');
    const fileInput = document.getElementById('document_image');
    const uploadInput = document.getElementById('upload-image');
    const uploadName = document.getElementById('upload-name');
    const btnCapture = document.getElementById('btn-capture');
    const btnRetake = document.getElementById('btn-retake');
    const btnProcess = document.getElementById('btn-process');
    const form = document.getElementById('scan-form');

    let stream = null;
    let hasImage = false;

    function setCapturedState(active) {
        hasImage = active;
        video.classList.toggle('d-none', active);
        capturedPreview.classList.toggle('d-none', !active);
        cameraHint.classList.toggle('d-none', active);
        btnCapture.classList.toggle('d-none', active);
        btnRetake.classList.toggle('d-none', !active);
        btnProcess.classList.toggle('d-none', !active);
        btnProcess.disabled = !active;
        btnCapture.disabled = active || !stream;
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(function (track) {
                track.stop();
            });
            stream = null;
        }
    }

    async function startCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            permissionAlert.classList.remove('d-none');
            statusText.textContent = 'Camera is not supported in this browser. Please upload a document image.';
            return;
        }

        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: 'environment' },
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false
            });

            video.srcObject = stream;
            await video.play();
            permissionAlert.classList.add('d-none');
            statusText.textContent = 'Camera ready. Place the document in view and capture.';
            btnCapture.disabled = false;
        } catch (error) {
            permissionAlert.classList.remove('d-none');
            statusText.textContent = 'Unable to access camera. You can upload a document image instead.';
            btnCapture.disabled = true;
        }
    }

    function assignFileToInput(file) {
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        fileInput.files = dataTransfer.files;
    }

    function captureFromCamera() {
        if (!stream) {
            return;
        }

        const width = video.videoWidth || 1280;
        const height = video.videoHeight || 720;
        canvas.width = width;
        canvas.height = height;

        const context = canvas.getContext('2d');
        context.drawImage(video, 0, 0, width, height);

        canvas.toBlob(function (blob) {
            if (!blob) {
                statusText.textContent = 'Unable to capture image. Please try again or upload a file.';
                return;
            }

            const file = new File([blob], 'captured-document.jpg', { type: 'image/jpeg' });
            assignFileToInput(file);
            capturedPreview.src = URL.createObjectURL(blob);
            setCapturedState(true);
            statusText.textContent = 'Image captured. Process the document or retake.';
            stopCamera();
        }, 'image/jpeg', 0.92);
    }

    function handleUpload(file) {
        if (!file) {
            return;
        }

        const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        if (!allowed.includes(file.type)) {
            statusText.textContent = 'Please upload a valid image (JPG, PNG, or WEBP).';
            return;
        }

        assignFileToInput(file);
        capturedPreview.src = URL.createObjectURL(file);
        uploadName.textContent = 'Selected: ' + file.name;
        setCapturedState(true);
        statusText.textContent = 'Image ready. Click Process Document to continue.';
        stopCamera();
    }

    btnCapture.addEventListener('click', captureFromCamera);

    btnRetake.addEventListener('click', function () {
        fileInput.value = '';
        uploadInput.value = '';
        uploadName.textContent = '';
        capturedPreview.removeAttribute('src');
        setCapturedState(false);
        statusText.textContent = 'Restarting camera...';
        startCamera();
    });

    uploadInput.addEventListener('change', function (event) {
        const file = event.target.files && event.target.files[0];
        handleUpload(file);
    });

    form.addEventListener('submit', function (event) {
        if (!fileInput.files || fileInput.files.length === 0) {
            event.preventDefault();
            statusText.textContent = 'Please capture or upload a document image first.';
            return;
        }

        btnProcess.disabled = true;
        btnProcess.textContent = 'Processing...';
        statusText.textContent = 'Processing document with OCR. Please wait...';
    });

    window.addEventListener('beforeunload', stopCamera);

    setCapturedState(false);
    startCamera();
})();
