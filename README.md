File Upload form control
========================

[![Build Status](https://github.com/nepada/file-upload-control/workflows/CI/badge.svg)](https://github.com/nepada/file-upload-control/actions?query=workflow%3ACI+branch%3Amaster)
[![Coverage Status](https://coveralls.io/repos/github/nepada/file-upload-control/badge.svg?branch=master)](https://coveralls.io/github/nepada/file-upload-control?branch=master)
[![Downloads this Month](https://img.shields.io/packagist/dm/nepada/file-upload-control.svg)](https://packagist.org/packages/nepada/file-upload-control)
[![Latest stable](https://img.shields.io/packagist/v/nepada/file-upload-control.svg)](https://packagist.org/packages/nepada/file-upload-control)


Installation
------------

Via Composer:

```sh
$ composer require nepada/file-upload-control
```

### Register and configure container extension

The only required configuration option is `uploadDirectory`, which determines where are the uploaded files temporarily stored.
```yaml
extensions:
    fileUploadControl: Nepada\Bridges\FileUploadControlDI\FileUploadControlExtension
fileUploadControl:
    uploadDirectory: %appDir%/../files/uploads
```

### Add factory method to your forms

#### Option A: install form container extension method via DI extension

```yaml
fileUploadControl:
    registerExtensionMethod: true # defaults to false
```

This will register extension method `addFileUpload($name, $label = null): FileUploadControl` to `Nette\Forms\Container`.

#### Option B: use trait in your base form/container class

You can also use `FileUploadControlMixin` trait in your base form/container class to add method `addFileUpload($name, $label = null): FileUploadControl`. You need to inject `FileUploadControlFactory` into every form/container you create, e.g. by combination of a common form factory service and decorator configuration for enabling injects.

Example:

```yaml
services:
    - FormFactory
decorator:
    Form:
        inject: true
```

```php
trait FormControls
{

    use \Nepada\Bridges\FileUploadControlForms\FileUploadControlMixin;

    public function addContainer($name)
    {
        $control = new Container;
        $control->injectFileUploadFactory($this->fileUploadControlFactory);
        $control->setCurrentGroup($this->getCurrentGroup());
        if ($this->currentGroup !== null) {
            $this->currentGroup->add($control);
        }
        return $this[$name] = $control;
    }

}

class Container extends \Nette\Forms\Container
{

    use FormControls;

}

class Form extends \Nette\Application\UI\Form
{

    use FormControls;

}

interface FormFactory
{

    public function create(): Form;

}
```


Usage
-----

`FileUploadControl` provides a way to upload files similarly to the standard `Nette\Forms\Controls\UploadControl`. The main difference is that the files are uploaded in chunks via AJAX requests into the temporary storage. This means you can upload multiple large files without the risk of running into server-side file/POST size limits. After submitting the form, the form control's value is set to a list of `FileUpload` instances of previously uploaded files in temporary storage.

### Configuration

The DI extension supports to following configuration options:
```yaml
fileUpload:
    uploadDirectory: path/to/temp/storage # required, no default value
    registerExtensionMethod: true # defaults to false
    templateFile: path/to/alternative/control-template.latte # defaults to the bundled Bootstrap 4 template
    thumbnails: # image thumbnail settings
        enable: true
        width: 200
        height: 150
```

The package includes templates and styles build for Bootstrap [version 4](https://getbootstrap.com/docs/4.6/) and [version 5](https://getbootstrap.com/docs/5.3/). By default, version 4 is used, but to improve forward compatibility you should always explicitly specify which version you'd like to be used:
```yaml
fileUpload:
    templateFile: Nepada\FileUploadControl\FileUploadControl::TEMPLATE_FILE_BOOTSTRAP4
    # or for Bootstrap 5
    # templateFile: Nepada\FileUploadControl\FileUploadControl::TEMPLATE_FILE_BOOTSTRAP5
```

### Validation

All standard Nette file upload related validations work as expected. Furthermore, you can limit the number of allowed uploads by "length" rules.

### Client side

This package comes with client side built on top of [blueimp-file-upload](https://yarnpkg.com/package/blueimp-file-upload). It is published as npm package [@nepada/file-upload-control](https://yarnpkg.com/package/@nepada/file-upload-control).

![Bootstrap 4 theme](docs/example.png)

#### Using precompiled bundle

Using precompiled bundles is the quick'n'dirty way of getting client side to work.

```html
<link rel="stylesheet" href="https://unpkg.com/@nepada/file-upload-control@%5E1.7/dist/css/file-upload-control-bootstrap4.min.css">
<!-- or for Bootstrap 5
<link rel="stylesheet" href="https://unpkg.com/@nepada/file-upload-control@%5E1.7/dist/css/file-upload-control-bootstrap5.min.css">
-->
<script src="https://unpkg.com/jquery@%5E3.5.0/dist/jquery.min.js"></script>
<script src="https://unpkg.com/blueimp-file-upload@%5E10.10.0/js/vendor/jquery.ui.widget.js"></script>
<script src="https://unpkg.com/blueimp-file-upload@%5E10.10.0/js/jquery.fileupload.js"></script>
<script src="https://unpkg.com/nette-forms@%5E3.0.3/src/assets/netteForms.min.js"></script>
<script src="https://unpkg.com/@nepada/file-upload-control@%5E1.7/dist/js/file-upload-control.min.js"></script>
```

#### Building your own bundle

It is highly recommended to install the client side package via nmp and compile your own bundle.

Here is an example script for initialization of file upload control and Nette forms.

```js
import Nette from 'nette-forms';
import initializeFileUploadControl from '@nepada/file-upload-control';

initializeFileUploadControl(Nette);

Nette.initOnLoad();
```

If you use Bootstrap 4 or 5, you can easily customize the default look by importing the source SCSS files into your Sass styles:
- [_file-upload-control-module-bootstrap4.scss](src/assets/scss/_file-upload-control-module-bootstrap4.scss), see also available [variables](src/assets/scss/partials-bootstrap4/_variables.scss) for overrides
- [_file-upload-control-module-bootstrap5.scss](src/assets/scss/_file-upload-control-module-bootstrap5.scss), see also available [variables](src/assets/scss/partials-bootstrap5/_variables.scss) for overrides
