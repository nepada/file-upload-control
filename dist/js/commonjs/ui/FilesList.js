'use strict';

var $ = require('jquery');

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
        this.getInput().data(this.dataAttribute, $.grep(
            this.list(),
            (file) => {
                return file.url !== fileUrl;
            },
        ));
    }

}

module.exports = FilesList;
//# sourceMappingURL=FilesList.js.map
