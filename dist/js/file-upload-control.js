(function (global, factory) {
    typeof exports === 'object' && typeof module !== 'undefined' ? factory(require('nette-forms'), require('jquery'), require('blueimp-file-upload')) :
    typeof define === 'function' && define.amd ? define(['nette-forms', 'jquery', 'blueimp-file-upload'], factory) :
    (global = typeof globalThis !== 'undefined' ? globalThis : global || self, factory(global.Nette, global.jQuery));
})(this, (function (Nette, $) { 'use strict';

    class Button {
      constructor($fileUpload, buttonSelector, targetSelector) {
        this.$fileUpload = $fileUpload;
        this.button = $fileUpload.find(buttonSelector);
        this.targetSelector = targetSelector;
        this.button.click(() => {
          // Trigger click from the newest to oldest to mitigate race condition bug with limitConcurrentUploads
          $(this.$fileUpload.find(this.targetSelector).get().reverse()).click();
        });
      }
      refreshState() {
        this.button.toggleClass('disabled', this.$fileUpload.find(this.targetSelector).length === 0);
      }
    }

    class Buttons {
      constructor($fileUpload) {
        this.abortButton = new Button($fileUpload, '[data-file-upload-role=abort]', '[data-file-upload-status=processing] [data-file-upload-role=file-delete]');
        this.deleteButton = new Button($fileUpload, '[data-file-upload-role=delete]', '[data-file-upload-role=file-delete]');
      }
      refreshState() {
        this.abortButton.refreshState();
        this.deleteButton.refreshState();
      }
    }

    class FilesList {
      constructor($fileUpload) {
        this.$fileUpload = $fileUpload;
        this.dataAttribute = 'files';
      }
      getInput() {
        // We need to do a lookup every time, because file input is replaced after each upload
        return this.$fileUpload.find('input[type=file]');
      }
      list() {
        return this.getInput().data(this.dataAttribute) || [];
      }
      add(file) {
        var files = this.list();
        files.push(file);
        this.getInput().data(this.dataAttribute, files);
      }

      /**
       * @deprecated
       */
      remove(fileUrl) {
        this.getInput().data(this.dataAttribute, $.grep(this.list(), file => {
          return file.url !== fileUrl;
        }));
      }
      removeByDeleteUrl(deleteUrl) {
        this.getInput().data(this.dataAttribute, $.grep(this.list(), file => {
          return file.deleteUrl !== deleteUrl;
        }));
      }
      removeByUid(uid) {
        this.getInput().data(this.dataAttribute, $.grep(this.list(), file => {
          return file.uid !== uid;
        }));
      }
    }

    class FileUpload {
      constructor($fileUpload, files) {
        var name = '';
        var size = 0;
        var type = '';
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
        var uid = [name, size, Math.floor(Math.random() * 2 ** 32)].join('|');
        this.fileListItem = {
          name,
          size,
          type,
          uid
        };
        this.fileList = new FilesList($fileUpload);
        this.fileList.add(this.fileListItem);
        this.updateFileInfoUI();
        this.$filesContainer.append(this.$file);
        this.$file.fadeIn();
      }
      createUI() {
        var $file = $(this.$filesContainer.data('templateFile'));
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
        var $error = $(this.$filesContainer.data('templateFailed'));
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
        var $done = $(this.$filesContainer.data('templateDone'));
        this.updateFileInfoUI($done);
        this.$file.find('[data-file-upload-role=file-status]').html($done);
        this.updateFileInfoUI();
      }
      updateFileInfoUI(element) {
        this.$file.attr('data-file-upload-status', this.status);
        this.$file.attr('data-content-type', this.type);
        this.$file.attr('title', this.name);
        var $element = $(element || this.$file);
        $element.find('[data-file-upload-role=file-name]').text(this.name);
        $element.find('[data-file-upload-role=file-size]').text(this.formatBytes(this.size));
        $element.find('[data-file-upload-role=file-progress-bar]').attr('aria-valuenow', Math.floor(this.progress)).css('width', this.progress.toFixed(2) + '%');
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
          var $thumbnail = $element.find('[data-file-upload-role=file-thumbnail]');
          if ($thumbnail.is('img')) {
            $thumbnail.attr('src', this.thumbnailUrl);
          } else if ($thumbnail.length > 0) {
            var thumbnailAttributes = {};
            $.each($thumbnail.get(0).attributes, (idx, attribute) => {
              thumbnailAttributes[attribute.name] = attribute.value;
            });
            $thumbnail.replaceWith($('<img>').attr(thumbnailAttributes).attr('src', this.thumbnailUrl));
          }
        }
      }
      formatBytes(bytes) {
        var units = ['B', 'kB', 'MB', 'GB', 'TB', 'PB'];
        var unit;
        for (var i = 0; i < units.length; i++) {
          unit = units[i];
          if (Math.abs(bytes) < 1024 || i === units.length - 1) {
            break;
          }
          bytes = bytes / 1024;
        }
        return bytes.toFixed(2) + ' ' + unit;
      }
    }

    function initializeControl(container) {
      var $fileUpload = $(container);
      if ($fileUpload.data('blueimpFileupload')) {
        return;
      }
      var uniqueFilenames = {};
      ($fileUpload.data('uniqueFilenames') || []).forEach(filename => {
        uniqueFilenames[filename] = true;
      });
      $fileUpload.fileupload({
        url: $fileUpload.data('fileUploadUrl'),
        dropZone: $fileUpload,
        pasteZone: null,
        dataType: 'json',
        formData: [],
        // do not send other form data with file uploads
        uniqueFilenames,
        singleFileUploads: true,
        maxChunkSize: 2 * 1024 * 1024,
        limitConcurrentUploads: 3,
        submit: () => {
          return !$fileUpload.hasClass('disabled') && !$fileUpload.is(':disabled') && !$fileUpload.find('input[type=file]').prop('disabled');
        }
      });
      var filesList = new FilesList($fileUpload);
      var buttons = new Buttons($fileUpload);

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
        if (!data.fileUpload) {
          return;
        }
        if (data.errorThrown === 'abort') {
          data.fileUpload.aborted();
        } else {
          data.fileUpload.failed();
          buttons.refreshState();
        }
      }).on('fileuploaddone', (e, data) => {
        var file = data.result.files[0];
        if (file.error) {
          data.fileUpload.failed(file);
        } else {
          data.fileUpload.done(file);
        }
        buttons.refreshState();
      }).on('fileuploadchunkdone', (e, data) => {
        var file = data.result.files[0];
        if (file.error) {
          data.fileUpload.failed(file);
          buttons.refreshState();
        } else {
          data.fileUpload.processing(file);
        }
      }).on('fileuploadchunksend', (e, data) => {
        return !data.fileUpload || data.fileUpload.status !== 'failed';
      }).on('click', '[data-file-upload-role=file-delete]', function () {
        var $this = $(this);
        var $file = $this.closest('[data-file-upload-role=file]');
        var upload = $file.data('upload');
        if (upload) {
          upload.abort();
        }
        var deleteUrl = $this.data('url');
        if (deleteUrl) {
          $.get(deleteUrl);
          filesList.removeByDeleteUrl(deleteUrl);
        }
        $file.fadeOut(() => {
          $file.remove();
          buttons.refreshState();
        });
      });
    }
    function initializeForm(form) {
      $(form).find('[data-file-upload-url]').each((idx, container) => {
        initializeControl(container);
      });
    }
    function initializeFileUploadControl(Nette) {
      // Disable default browser drop event
      $(document).on('drop dragover', e => {
        e.preventDefault();
      });

      // Validation
      Nette.validators.NepadaFileUploadControlValidationClientSide_noUploadInProgress = element => {
        return $(element).closest('[data-file-upload-url]').find('[data-file-upload-status=processing] [data-file-upload-role=file-delete]').length === 0;
      };

      // Effective value
      var originalGetEffectiveValue = Nette.getEffectiveValue;
      Nette.getEffectiveValue = (elem, filter) => {
        if (!elem || !elem.nodeName || elem.nodeName.toLowerCase() !== 'input' || !$(elem).data('files')) {
          return originalGetEffectiveValue.apply(Nette, [elem, filter]);
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
      var originalInitForm = Nette.initForm;
      Nette.initForm = form => {
        originalInitForm.apply(Nette, [form]);
        initializeForm(form);
      };
    }

    initializeFileUploadControl(Nette);

}));
//# sourceMappingURL=file-upload-control.js.map
