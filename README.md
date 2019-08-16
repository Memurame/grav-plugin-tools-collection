# Grav - Helpful tools
Here is a small collection of helpful tools that make working with the Grav easier.

## Features
* Advanced Markdown features
* Twig extension
* Insert blueprints on pages
* Resize images
* Info box for your customer

### Advanced Markdown features
The Markdown extension is possible when creating or editing a page in the corresponding Markdown field
#### Font color
Write in the markdown field:
```
{color:red}Put your hands up in the air{/color}
```

The HTML output:
```
<span style="color: red">Put your hands up in the air</span>
```

#### Phone link
Write in the markdown field:
```
{tel:0323733773}Rufen Sie uns an{/tel}
```

The HTML output:
```
<a href="tel:0323733773" class="link-telefon">Rufen Sie uns an</a>
```

#### Mail link
Write in the markdown field:
```
{mail:github@hirter.dev}Schreiben Sie uns{/mail}
```

The HTML output:
```
<a href="mailto:github@hirter.dev" class="link-mail">Schreiben Sie uns</a>
```

#### Call-To-Action Button
Write in the markdown field:
```
{cta:sendmail}Button for javascript action{/cta}
```

The HTML output:
```
<a href="#" id="sendmail" class="link-cta">Button for javascript action</a>
```

### Twig extension
Extends twig by a few more filters.

In the plugin settings can be set which extensions are active and which are not.

* The `Intl` module provides three filters:
  * `localizeddate` formats a date based on the locale.
  * `localizednumber` formats a number based on the locale.
  * `localizedcurrency` formats a number based on a given currency code.

* The `Array` module provides a single filter:
  * `shuffle` randomizes an array.
  * **Note:** This code was slightly modified to allow shuffling associative arrays. Simply pass `true` to enable this feature: `{{ myArray | shuffle(true) }}`.

The `Date` module also only provides a single filter:
  * `time_diff` dispays the delta between two dates in a human readable form (e.g., `2 days ago`).

For more information, [read the official documentation](https://twig-extensions.readthedocs.io/en/latest/).

### Insert blueprints on pages

Normally you add the following code to the blueprint of your page to see the tab Options and Advanced.
```
extends@: default
```
However, you can omit this on all blueprints, this plugin automatically adds the tab Options and Advanced if you activate it.

For more information, [read the official documentation](https://learn.getgrav.org/16/forms/blueprints/example-page-blueprint).

### Resize images

Grav provides some nifty built-in features for editing images on the fly through the use of [Gregwar/Image](https://github.com/Gregwar/Image). 
But there's no support yet for automatically generating responsive image alternatives at upload time rather than at request time.

This plugin shrinks the images directly when uploading and saves them with the ones with the extension `@1x`,` @2x` etc in the name.

This plugin won't convert PNG's to JPEG's, so the quality number only applies to JPEG images.

In the settings the desired sizes can be defined in which the pictures should be reduced. It can also be restricted on which pages this reduction should be applied.

### Info box for your customer

You can set up a new navigation point on the left side of the menu. There you can define any text that is displayed when the customer clicks on the link. You can, for example, deposit the contact information of you.

## Installation
The plugin can be installed via GPM or manually.
If you have the admin plugin, you can install it right there.

### GPM Installation
To install the plugin, open the console and navigate to the folder where your Grav is located.
Then you enter the following command:
```
bin/gpm install tools-collection
```
This installs the plugin into the directory `/user/plugins`. Die Dateien findet ihr in `/your/site/grav/user/plugins/tools-collection`.
### Manual Installation
First, download the plugin from this repository. Then unpack it into the folder `/user/plugins`. 
