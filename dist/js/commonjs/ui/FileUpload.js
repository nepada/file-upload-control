'use strict';

var $ = require('jquery');
var FilesList = require('./FilesList.js');

class FileUpload {

    constructor($fileUpload, files) {
        let name = '';
        let size = 0;
        let type = '';
        $.each(files, (index, file) => {
            name = name + (index ? ', ' : '') + file.name;
            size = size + file.size;
            type = type || file.type;
            if (type !== file.type) {
                type = type + ', ' + file.type;
            }
        });

        this.name = name;
        this.size = size;
        this.type = type;
        this.status = 'processing';
        this.progress = 0;
        this.deleteUrl = null;
        this.downloadUrl = null;
        this.thumbnailUrl = null;
        this.error = null;
        this.$filesContainer = $fileUpload.find('[data-file-upload-role=files]');
        this.$file = this.createUI();

        const uid = [name, size, Math.floor(Math.random() * 2**32)].join('|');
        this.fileListItem = {name, size, type, uid};
        this.fileList = new FilesList($fileUpload);
        this.fileList.add(this.fileListItem);

        this.updateFileInfoUI();
        this.$filesContainer.append(this.$file);
        this.$file.fadeIn();
    }

    createUI() {
        const $file = $(this.$filesContainer.data('templateFile'));
        $file.find('[data-file-upload-role=file-status]').append($(this.$filesContainer.data('templateProcessing')));
        $file.hide();
        return $file;
    }

    updateProgress(progress) {
        this.progress = Math.max(0, Math.min(progress, 1)) * 100;
        this.updateFileInfoUI();
    }

    updateFileInfo(file) {
        this.downloadUrl = file.url || this.downloadUrl;
        this.thumbnailUrl = file.thumbnailUrl || this.thumbnailUrl;
        this.deleteUrl = file.deleteUrl || this.deleteUrl;
        this.type = file.type || this.type;
        this.replaceFileListItem(file);
    }

    replaceFileListItem(file) {
        if (this.fileListItem) {
            this.fileList.removeByUid(this.fileListItem.uid);
            this.fileList.add(file);
            this.fileListItem = null;
        }
    }

    processing(file) {
        this.updateFileInfo(file);
        this.updateFileInfoUI();
    }

    failed(file) {
        this.status = 'failed';
        if (file && file.error) {
            this.error = file.error;
        }
        const $error = $(this.$filesContainer.data('templateFailed'));
        this.updateFileInfoUI($error);
        this.$file.find('[data-file-upload-role=file-status]').html($error);
        this.updateFileInfoUI();
        this.removeFileListItem();
    }

    aborted() {
        this.removeFileListItem();
    }

    removeFileListItem() {
        if (this.fileListItem) {
            this.fileList.removeByUid(this.fileListItem.uid);
            this.fileListItem = null;
        }
    }

    done(file) {
        this.status = 'done';
        this.updateFileInfo(file);
        const $done = $(this.$filesContainer.data('templateDone'));
        this.updateFileInfoUI($done);
        this.$file.find('[data-file-upload-role=file-status]').html($done);
        this.updateFileInfoUI();
    }

    updateFileInfoUI(element) {
        this.$file.attr('data-file-upload-status', this.status);
        this.$file.attr('data-content-type', this.type);
        this.$file.attr('title', this.name);

        const $element = $(element || this.$file);
        $element.find('[data-file-upload-role=file-name]').text(this.name);
        $element.find('[data-file-upload-role=file-size]').text(this.formatBytes(this.size));
        $element.find('[data-file-upload-role=file-progress-bar]')
            .attr('aria-valuenow', Math.floor(this.progress))
            .css('width', this.progress.toFixed(2) + '%');
        if (this.error) {
            $element.find('[data-file-upload-role=file-error]').text(this.error);
        }
        if (this.deleteUrl) {
            $element.find('[data-file-upload-role=file-delete]').attr('data-url', this.deleteUrl);
        }
        if (this.downloadUrl) {
            $element.find('[data-file-upload-role=file-download]').attr('href', this.downloadUrl);
        }
        if (this.thumbnailUrl) {
            const $thumbnail = $element.find('[data-file-upload-role=file-thumbnail]');
            if ($thumbnail.is('img')) {
                $thumbnail.attr('src', this.thumbnailUrl);
            } else if ($thumbnail.length > 0) {
                const thumbnailAttributes = {};
                $.each($thumbnail.get(0).attributes, (idx, attribute) => {
                    thumbnailAttributes[attribute.name] = attribute.value;
                });
                $thumbnail.replaceWith($('<img>').attr(thumbnailAttributes).attr('src', this.thumbnailUrl));
            }
        }
    }

    formatBytes(bytes) {
        const units = ['B', 'kB', 'MB', 'GB', 'TB', 'PB'];
        let unit;
        for (let i = 0; i < units.length; i++) {
            unit = units[i];
            if (Math.abs(bytes) < 1024 || i === units.length - 1) {
                break;
            }
            bytes = bytes / 1024;
        }
        return bytes.toFixed(2) + ' ' + unit;
    }

}

module.exports = FileUpload;
//# sourceMappingURL=FileUpload.js.map
