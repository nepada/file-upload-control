'use strict';

var $ = require('jquery');

function _interopDefaultLegacy (e) { return e && typeof e === 'object' && 'default' in e ? e : { 'default': e }; }

var $__default = /*#__PURE__*/_interopDefaultLegacy($);

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
        const files = this.list();
        files.push(file);
        this.getInput().data(this.dataAttribute, files);
    }

    remove(fileUrl) {
        this.getInput().data(this.dataAttribute, $__default['default'].grep(
            this.list(),
            (file) => {
                return file.url !== fileUrl;
            },
        ));
    }

}

module.exports = FilesList;
//# sourceMappingURL=FilesList.js.map
