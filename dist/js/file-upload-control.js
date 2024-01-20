(function (global, factory) {
  typeof exports === 'object' && typeof module !== 'undefined' ? factory(require('nette-forms'), require('jquery'), require('blueimp-file-upload')) :
  typeof define === 'function' && define.amd ? define(['nette-forms', 'jquery', 'blueimp-file-upload'], factory) :
  (global = typeof globalThis !== 'undefined' ? globalThis : global || self, factory(global.Nette, global.jQuery));
})(this, (function (Nette, $) { 'use strict';

  function _toPrimitive(t, r) {
    if ("object" != typeof t || !t) return t;
    var e = t[Symbol.toPrimitive];
    if (void 0 !== e) {
      var i = e.call(t, r || "default");
      if ("object" != typeof i) return i;
      throw new TypeError("@@toPrimitive must return a primitive value.");
    }
    return ("string" === r ? String : Number)(t);
  }
  function _toPropertyKey(t) {
    var i = _toPrimitive(t, "string");
    return "symbol" == typeof i ? i : String(i);
  }
  function _classCallCheck(instance, Constructor) {
    if (!(instance instanceof Constructor)) {
      throw new TypeError("Cannot call a class as a function");
    }
  }
  function _defineProperties(target, props) {
    for (var i = 0; i < props.length; i++) {
      var descriptor = props[i];
      descriptor.enumerable = descriptor.enumerable || false;
      descriptor.configurable = true;
      if ("value" in descriptor) descriptor.writable = true;
      Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor);
    }
  }
  function _createClass(Constructor, protoProps, staticProps) {
    if (protoProps) _defineProperties(Constructor.prototype, protoProps);
    if (staticProps) _defineProperties(Constructor, staticProps);
    Object.defineProperty(Constructor, "prototype", {
      writable: false
    });
    return Constructor;
  }

  var Button = /*#__PURE__*/function () {
    function Button($fileUpload, buttonSelector, targetSelector) {
      var _this = this;
      _classCallCheck(this, Button);
      this.$fileUpload = $fileUpload;
      this.button = $fileUpload.find(buttonSelector);
      this.targetSelector = targetSelector;
      this.button.click(function () {
        // Trigger click from the newest to oldest to mitigate race condition bug with limitConcurrentUploads
        $(_this.$fileUpload.find(_this.targetSelector).get().reverse()).click();
      });
    }
    _createClass(Button, [{
      key: "refreshState",
      value: function refreshState() {
        this.button.toggleClass('disabled', this.$fileUpload.find(this.targetSelector).length === 0);
      }
    }]);
    return Button;
  }();

  var Buttons = /*#__PURE__*/function () {
    function Buttons($fileUpload) {
      _classCallCheck(this, Buttons);
      this.abortButton = new Button($fileUpload, '[data-file-upload-role=abort]', '[data-file-upload-status=processing] [data-file-upload-role=file-delete]');
      this.deleteButton = new Button($fileUpload, '[data-file-upload-role=delete]', '[data-file-upload-role=file-delete]');
    }
    _createClass(Buttons, [{
      key: "refreshState",
      value: function refreshState() {
        this.abortButton.refreshState();
        this.deleteButton.refreshState();
      }
    }]);
    return Buttons;
  }();

  var FilesList = /*#__PURE__*/function () {
    function FilesList($fileUpload) {
      _classCallCheck(this, FilesList);
      this.$fileUpload = $fileUpload;
      this.dataAttribute = 'files';
    }
    _createClass(FilesList, [{
      key: "getInput",
      value: function getInput() {
        // We need to do a lookup every time, because file input is replaced after each upload
        return this.$fileUpload.find('input[type=file]');
      }
    }, {
      key: "list",
      value: function list() {
        return this.getInput().data(this.dataAttribute) || [];
      }
    }, {
      key: "add",
      value: function add(file) {
        var files = this.list();
        files.push(file);
        this.getInput().data(this.dataAttribute, files);
      }

      /**
       * @deprecated
       */
    }, {
      key: "remove",
      value: function remove(fileUrl) {
        this.getInput().data(this.dataAttribute, $.grep(this.list(), function (file) {
          return file.url !== fileUrl;
        }));
      }
    }, {
      key: "removeByDeleteUrl",
      value: function removeByDeleteUrl(deleteUrl) {
        this.getInput().data(this.dataAttribute, $.grep(this.list(), function (file) {
          return file.deleteUrl !== deleteUrl;
        }));
      }
    }, {
      key: "removeByUid",
      value: function removeByUid(uid) {
        this.getInput().data(this.dataAttribute, $.grep(this.list(), function (file) {
          return file.uid !== uid;
        }));
      }
    }]);
    return FilesList;
  }();

  var FileUpload = /*#__PURE__*/function () {
    function FileUpload($fileUpload, files) {
      _classCallCheck(this, FileUpload);
      var name = '';
      var size = 0;
      var type = '';
      $.each(files, function (index, file) {
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
      var uid = [name, size, Math.floor(Math.random() * Math.pow(2, 32))].join('|');
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
    _createClass(FileUpload, [{
      key: "createUI",
      value: function createUI() {
        var $file = $(this.$filesContainer.data('templateFile'));
        $file.find('[data-file-upload-role=file-status]').append($(this.$filesContainer.data('templateProcessing')));
        $file.hide();
        return $file;
      }
    }, {
      key: "updateProgress",
      value: function updateProgress(progress) {
        this.progress = Math.max(0, Math.min(progress, 1)) * 100;
        this.updateFileInfoUI();
      }
    }, {
      key: "updateFileInfo",
      value: function updateFileInfo(file) {
        this.downloadUrl = file.url || this.downloadUrl;
        this.thumbnailUrl = file.thumbnailUrl || this.thumbnailUrl;
        this.deleteUrl = file.deleteUrl || this.deleteUrl;
        this.type = file.type || this.type;
        this.replaceFileListItem(file);
      }
    }, {
      key: "replaceFileListItem",
      value: function replaceFileListItem(file) {
        if (this.fileListItem) {
          this.fileList.removeByUid(this.fileListItem.uid);
          this.fileList.add(file);
          this.fileListItem = null;
        }
      }
    }, {
      key: "processing",
      value: function processing(file) {
        this.updateFileInfo(file);
        this.updateFileInfoUI();
      }
    }, {
      key: "failed",
      value: function failed(file) {
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
    }, {
      key: "aborted",
      value: function aborted() {
        this.removeFileListItem();
      }
    }, {
      key: "removeFileListItem",
      value: function removeFileListItem() {
        if (this.fileListItem) {
          this.fileList.removeByUid(this.fileListItem.uid);
          this.fileListItem = null;
        }
      }
    }, {
      key: "done",
      value: function done(file) {
        this.status = 'done';
        this.updateFileInfo(file);
        var $done = $(this.$filesContainer.data('templateDone'));
        this.updateFileInfoUI($done);
        this.$file.find('[data-file-upload-role=file-status]').html($done);
        this.updateFileInfoUI();
      }
    }, {
      key: "updateFileInfoUI",
      value: function updateFileInfoUI(element) {
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
            $.each($thumbnail.get(0).attributes, function (idx, attribute) {
              thumbnailAttributes[attribute.name] = attribute.value;
            });
            $thumbnail.replaceWith($('<img>').attr(thumbnailAttributes).attr('src', this.thumbnailUrl));
          }
        }
      }
    }, {
      key: "formatBytes",
      value: function formatBytes(bytes) {
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
    }]);
    return FileUpload;
  }();

  function initializeControl(container) {
    var $fileUpload = $(container);
    if ($fileUpload.data('blueimpFileupload')) {
      return;
    }
    var uniqueFilenames = {};
    ($fileUpload.data('uniqueFilenames') || []).forEach(function (filename) {
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
      submit: function submit() {
        return !$fileUpload.hasClass('disabled') && !$fileUpload.is(':disabled') && !$fileUpload.find('input[type=file]').prop('disabled');
      }
    });
    var filesList = new FilesList($fileUpload);
    var buttons = new Buttons($fileUpload);

    // eslint-disable-next-line consistent-return
    $fileUpload.on('fileuploadadd', function (e, data) {
      if (e.isDefaultPrevented()) {
        return false;
      }
      data.fileUpload = new FileUpload($fileUpload, data.files);
      data.fileUpload.$file.data('upload', data);
      buttons.refreshState();

      // eslint-disable-next-line consistent-return
    }).on('fileuploadprogress', function (e, data) {
      if (e.isDefaultPrevented()) {
        return false;
      }
      data.fileUpload.updateProgress(data.loaded / data.total);
    }).on('fileuploadfail', function (e, data) {
      if (!data.fileUpload) {
        return;
      }
      if (data.errorThrown === 'abort') {
        data.fileUpload.aborted();
      } else {
        data.fileUpload.failed();
        buttons.refreshState();
      }
    }).on('fileuploaddone', function (e, data) {
      var file = data.result.files[0];
      if (file.error) {
        data.fileUpload.failed(file);
      } else {
        data.fileUpload.done(file);
      }
      buttons.refreshState();
    }).on('fileuploadchunkdone', function (e, data) {
      var file = data.result.files[0];
      if (file.error) {
        data.fileUpload.failed(file);
        buttons.refreshState();
      } else {
        data.fileUpload.processing(file);
      }
    }).on('fileuploadchunksend', function (e, data) {
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
      $file.fadeOut(function () {
        $file.remove();
        buttons.refreshState();
      });
    });
  }
  function initializeForm(form) {
    $(form).find('[data-file-upload-url]').each(function (idx, container) {
      initializeControl(container);
    });
  }
  function initializeFileUploadControl(Nette) {
    // Disable default browser drop event
    $(document).on('drop dragover', function (e) {
      e.preventDefault();
    });

    // Validation
    Nette.validators.NepadaFileUploadControlValidationClientSide_noUploadInProgress = function (element) {
      return $(element).closest('[data-file-upload-url]').find('[data-file-upload-status=processing] [data-file-upload-role=file-delete]').length === 0;
    };

    // Effective value
    var originalGetEffectiveValue = Nette.getEffectiveValue;
    Nette.getEffectiveValue = function (elem, filter) {
      if (!elem || !elem.nodeName || elem.nodeName.toLowerCase() !== 'input' || !$(elem).data('files')) {
        return originalGetEffectiveValue(elem, filter);
      }
      return $(elem).data('files');
    };

    // Initialize all forms on document ready
    $(function () {
      $('form').each(function (idx, form) {
        initializeForm(form);
      });
    });

    // Tap into Nette.initForm() to provide AJAX snippet support via e.g. Naja
    var originalInitForm = Nette.initForm;
    Nette.initForm = function (form) {
      originalInitForm(form);
      initializeForm(form);
    };
  }

  initializeFileUploadControl(Nette);

}));
//# sourceMappingURL=file-upload-control.js.map
