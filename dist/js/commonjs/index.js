'use strict';

var $ = require('jquery');
require('blueimp-file-upload');
var Buttons = require('./ui/Buttons.js');
var FilesList = require('./ui/FilesList.js');
var FileUpload = require('./ui/FileUpload.js');

function initializeControl(container) {
    const $fileUpload = $(container);
    if ($fileUpload.data('blueimpFileupload')) {
        return;
    }

    const uniqueFilenames = {};
    ($fileUpload.data('uniqueFilenames') || []).forEach((filename) => {
        uniqueFilenames[filename] = true;
    });

    $fileUpload.fileupload({
        url: $fileUpload.data('fileUploadUrl'),
        dropZone: $fileUpload,
        pasteZone: null,
        dataType: 'json',
        formData: [], // do not send other form data with file uploads
        uniqueFilenames,
        singleFileUploads: true,
        maxChunkSize: 2 * 1024 * 1024,
        limitConcurrentUploads: 3,
        submit: () => {
            return !$fileUpload.hasClass('disabled') && !$fileUpload.is(':disabled') && !$fileUpload.find('input[type=file]').prop('disabled');
        },
    });

    const filesList = new FilesList($fileUpload);
    const buttons = new Buttons($fileUpload);

    // eslint-disable-next-line consistent-return
    $fileUpload.on('fileuploadadd', (e, data) => {
        if (e.isDefaultPrevented()) {
            return false;
        }

        data.fileUpload = new FileUpload($fileUpload, data.files);
        data.fileUpload.$file.data('upload', data);
        buttons.refreshState();

    // eslint-disable-next-line consistent-return
    }).on('fileuploadprogress', (e, data) => {
        if (e.isDefaultPrevented()) {
            return false;
        }

        data.fileUpload.updateProgress(data.loaded / data.total);

    }).on('fileuploadfail', (e, data) => {
        if (data.fileUpload && data.errorThrown !== 'abort') {
            data.fileUpload.failed();
            buttons.refreshState();
        }

    }).on('fileuploaddone', (e, data) => {
        const file = data.result.files[0];
        if (file.error) {
            data.fileUpload.failed(file);
        } else {
            data.fileUpload.done(file);
            filesList.add(file);
        }
        buttons.refreshState();

    }).on('fileuploadchunkdone', (e, data) => {
        const file = data.result.files[0];
        if (file.error) {
            data.fileUpload.failed(file);
            buttons.refreshState();
        } else {
            data.fileUpload.processing(file);
        }

    }).on('fileuploadchunksend', (e, data) => {
        return !data.fileUpload || data.fileUpload.status !== 'failed';

    }).on('click', '[data-file-upload-role=file-delete]', function () {
        const $this = $(this);
        const $file = $this.closest('[data-file-upload-role=file]');
        const upload = $file.data('upload');
        if (upload) {
            upload.abort();
        }
        if ($this.is('[data-url]')) {
            $.get($this.data('url'));
        }
        const fileUrl = $file.find('[data-file-upload-role=file-download]').attr('href');
        filesList.remove(fileUrl);
        $file.fadeOut(() => {
            $file.remove();
            buttons.refreshState();
        });

    });
}


function initializeForm(form) {
    $(form)
        .find('[data-file-upload-url]')
        .each((idx, container) => {
            initializeControl(container);
        });
}


function initializeFileUploadControl(Nette) {
    // Disable default browser drop event
    $(document).on('drop dragover', (e) => {
        e.preventDefault();
    });

    // Effective value
    const originalGetEffectiveValue = Nette.getEffectiveValue;
    Nette.getEffectiveValue = (elem, filter) => {
        if (!elem || !elem.nodeName || elem.nodeName.toLowerCase() !== 'input' || !$(elem).data('files')) {
            return originalGetEffectiveValue(elem, filter);
        }
        return $(elem).data('files');
    };

    // Initialize all forms on document ready
    $(() => {
        $('form').each((idx, form) => {
            initializeForm(form);
        });
    });

    // Tap into Nette.initForm() to provide AJAX snippet support via e.g. Naja
    const originalInitForm = Nette.initForm;
    Nette.initForm = (form) => {
        originalInitForm(form);
        initializeForm(form);
    };
}

module.exports = initializeFileUploadControl;
//# sourceMappingURL=index.js.map
