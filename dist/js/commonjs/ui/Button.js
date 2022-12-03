'use strict';

var $ = require('jquery');

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

module.exports = Button;
//# sourceMappingURL=Button.js.map
