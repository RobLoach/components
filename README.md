# Component Installer for [Composer](http://getcomposer.org)

## Example `composer.json` File

This is an example for a JavaScript plugin. The only important parts to set in
your composer.json file are `"type": "component"` which describes what your
package is and `"require": { "robloach/components": "*" }` which tells Composer
to load the custom installers.

```json
{
    "name": "my/jquery",
    "type": "component",
    "require": {
        "robloach/components": "*"
    },
    "extra": {
        "components": {
            "name": "jquery",
            "main": "jquery.js"
        }
    }
}
```
