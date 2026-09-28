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
    const ocrTextInput = document.getElementById('ocr_text');

    let stream = null;
    let processing = false;

    function setCapturedState(active) {
        video.classList.toggle('d-none', active);
        capturedPreview.classList.toggle('d-none', !active);
        cameraHint.classList.toggle('d-none', active);
        btnCapture.classList.toggle('d-none', active);
        btnRetake.classList.toggle('d-none', !active);
        btnProcess.classList.toggle('d-none', !active);
        btnProcess.disabled = !active || processing;
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
        if (!stream || processing) {
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
            statusText.textContent = 'Image captured. Click Process Document and wait for OCR.';
            stopCamera();
        }, 'image/jpeg', 0.92);
    }

    function handleUpload(file) {
        if (!file || processing) {
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
        statusText.textContent = 'Image ready. Click Process Document and wait for OCR (may take 30-90 seconds).';
        stopCamera();
    }

    /**
     * Improve contrast before OCR (helps plastic glare / soft photos).
     */
    async function preprocessForOcr(file) {
        const bitmap = await createImageBitmap(file);
        const maxWidth = 1600;
        let width = bitmap.width;
        let height = bitmap.height;

        if (width > maxWidth) {
            height = Math.round(height * (maxWidth / width));
            width = maxWidth;
        }

        const prep = document.createElement('canvas');
        prep.width = width;
        prep.height = height;
        const ctx = prep.getContext('2d');
        ctx.drawImage(bitmap, 0, 0, width, height);
        bitmap.close();

        const imageData = ctx.getImageData(0, 0, width, height);
        const data = imageData.data;
        for (let i = 0; i < data.length; i += 4) {
            const gray = (data[i] * 0.299) + (data[i + 1] * 0.587) + (data[i + 2] * 0.114);
            // Increase contrast
            let v = ((gray - 128) * 1.35) + 128;
            v = Math.max(0, Math.min(255, v));
            data[i] = data[i + 1] = data[i + 2] = v;
        }
        ctx.putImageData(imageData, 0, 0);

        return new Promise(function (resolve) {
            prep.toBlob(function (blob) {
                resolve(blob || file);
            }, 'image/png');
        });
    }

    async function runBrowserOcr(file) {
        if (typeof Tesseract === 'undefined' || !Tesseract.createWorker) {
            throw new Error('OCR library failed to load. Please refresh the page (Ctrl+F5) and ensure internet is available.');
        }

        statusText.textContent = 'Preparing image for OCR...';
        const prepared = await preprocessForOcr(file);

        statusText.textContent = 'Loading OCR engine (first time may take a minute)...';

        const worker = await Tesseract.createWorker('eng', 1, {
            workerPath: '/js/tesseract/worker.min.js',
            corePath: '/js/tesseract/tesseract-core.wasm.js',
            langPath: 'https://tessdata.projectnaptha.com/4.0.0',
            logger: function (message) {
                if (!message) {
                    return;
                }
                if (message.status === 'recognizing text' && message.progress != null) {
                    statusText.textContent = 'Reading document... ' + Math.round(message.progress * 100) + '%';
                } else if (message.status) {
                    statusText.textContent = 'OCR: ' + message.status + '...';
                }
            }
        });

        try {
            await worker.setParameters({
                tessedit_pageseg_mode: '6'
            });

            const result = await worker.recognize(prepared);
            let text = (result && result.data && result.data.text) ? result.data.text.trim() : '';

            // Second pass with Urdu if CNIC digits not found (old Urdu cards)
            if (!/\d{5}[-\s]?\d{7}[-\s]?\d/.test(text)) {
                statusText.textContent = 'Retrying with Urdu language pack...';
                try {
                    await worker.loadLanguage('urd');
                    await worker.reinitialize('eng+urd');
                    const urduResult = await worker.recognize(prepared);
                    const urduText = (urduResult && urduResult.data && urduResult.data.text)
                        ? urduResult.data.text.trim()
                        : '';
                    if (urduText.length > text.length) {
                        text = urduText;
                    }
                } catch (e) {
                    // Keep English result
                }
            }

            return text;
        } finally {
            await worker.terminate();
        }
    }

    async function processDocument() {
        if (processing) {
            return;
        }

        if (!fileInput.files || fileInput.files.length === 0) {
            statusText.textContent = 'Please capture or upload a document image first.';
            return;
        }

        processing = true;
        btnProcess.disabled = true;
        btnProcess.textContent = 'Processing...';
        btnRetake.disabled = true;

        try {
            const file = fileInput.files[0];
            const text = await runBrowserOcr(file);

            if (!text || text.length < 5) {
                throw new Error('OCR could not read text from this image. Try a clearer photo (less glare, fuller card in frame).');
            }

            ocrTextInput.value = text;
            statusText.textContent = 'Text extracted (' + text.length + ' chars). Opening form...';

            // Native submit bypasses the button handler; ocr_text is already filled
            HTMLFormElement.prototype.submit.call(form);
        } catch (error) {
            processing = false;
            btnProcess.disabled = false;
            btnProcess.textContent = 'Process Document';
            btnRetake.disabled = false;
            statusText.textContent = (error && error.message)
                ? error.message
                : 'Unable to process the document. Please try again.';
            alert(statusText.textContent);
        }
    }

    btnCapture.addEventListener('click', captureFromCamera);
    btnProcess.addEventListener('click', processDocument);

    btnRetake.addEventListener('click', function () {
        if (processing) {
            return;
        }
        fileInput.value = '';
        uploadInput.value = '';
        uploadName.textContent = '';
        ocrTextInput.value = '';
        capturedPreview.removeAttribute('src');
        setCapturedState(false);
        statusText.textContent = 'Restarting camera...';
        startCamera();
    });

    uploadInput.addEventListener('change', function (event) {
        const file = event.target.files && event.target.files[0];
        handleUpload(file);
    });

    // Prevent accidental native submit without OCR
    form.addEventListener('submit', function (event) {
        if (!ocrTextInput.value) {
            event.preventDefault();
            processDocument();
        }
    });

    window.addEventListener('beforeunload', stopCamera);

    if (typeof Tesseract === 'undefined') {
        statusText.textContent = 'OCR library did not load. Refresh with Ctrl+F5. You can still upload, then try Process again.';
    }

    setCapturedState(false);
    startCamera();
})();
