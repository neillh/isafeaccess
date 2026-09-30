# Blocks

This folder contains custom blocks used by the theme.

Custom blocks live in individual folders within the `blocks` directory. Every block located in the `blocks` folder will
be registered by the theme automatically.

## Development-only Blocks

Some block folder names start with an underscore (`_`). Those blocks are **development-only blocks**, and they will be
available only if the `WP_DEBUG` constant is set to `true`. The main purpose for this is to be able to preview templates
before scaffolding a new block.

As the name implies, development-only blocks are not intended to be used in the production environment. They can be
safely removed before handing over a theme to the client, but it's not a hard requirement.

## Block Folder Structure

A valid block must consist of just 2 files:
- `block.json` which contains a block definition while
- `editor.js` which registers a block in the editor.

```
.
+-- blocks
|   +-- my-minimal-block
|   |   +-- block.json
|   |   +-- editor.js
```

However, a real-life block example would consist of more files, e.g. styles, frontend scripts,
additional backend logic, a PHP template (in case of a dynamic block):

```
.
+-- blocks
|   +-- my-complex-block
|   |   +-- css
|   |   |   +-- _header.scss
|   |   |   +-- _content.scss
|   |   |   +-- _footer.scss
|   |   +-- js
|   |   |   +-- edit.js
|   |   |   +-- init.js
|   |   +-- block.json
|   |   +-- editor.js
|   |   +-- editor.scss
|   |   +-- functions.php
|   |   +-- script.js
|   |   +-- style.scss
|   |   +-- template.php
|   |   +-- view.js
```

### Scripts

There are 3 types of scripts supported by blocks:
- `editorScript` is the only required script since it contains a block type registration logic, `registerBlockType()`.
This script will be enqueued only in the block editor.
- `viewScript` contains JS logic that applies to the site frontend only. The script won't be enqueued in the editor.
- `script` is enqueued both in the editor and on the frontend.

Every script should be referenced from the `block.json` file. Thanks to that, the scripts will be automatically built
by the Webpack build process and then used by WordPress. It means that you do not have to manually register and enqueue
those scripts in the theme logic.

You may specify more than one file for every script type. Moreover, you may provide an already registered script handle
instead of a file path.

```json
{
	"editorScript": "file:./editor.js",
	"script": "file:./script.js",
	"viewScript": [ "file:./view.js", "my-registered-script-handle" ]
}
```

Check out the [Block Metadata reference](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#script) for more information.

If part of your block logic is shared across frontend and editor scripts (e.g. a utility function), it is recommended to
store such a module in the `./js` sub-folder and import it in the `editor.js` and `view.js` entry points.

**Note:** Script filenames are arbitrary. You may rename e.g. `editor.js` to `gutenberg.js` but **doing so is strongly
discouraged**. We strive for using consistent naming conventions across various blocks _and_ projects, and we should
stick to the existing filenames and block directory structure.

### Styles

There are 2 types of styles:
- `editorStyle` is enqueued in the editor and should contain editor-specific CSS only.
- `style` is used both in the editor and on the frontend.

Like scripts, stylesheets should be referenced in the `block.json` file. This way, they will be registered and enqueued
automatically by Core.

#### Using SASS

When developing a block, you may want to use SASS instead of plain CSS. In order to do so, the SASS files must be
compiled to CSS, just like modern JS files have to be transpiled by Babel to a well-supported JS version.

In order to do so, you have to import your SASS files in the respective JS entry points. The resulting plain CSS files
will be stored in the `build/[source-block-dirname]` folder.

An important remark is that the resulting CSS file will be renamed to the **JS entrypoint name** and not the original
stylesheet name. You have to keep that in mind when referencing built stylesheets in the `block.json` file.

#### Example

For instance, consider the following directory structure:

```
.
+-- blocks
|   +-- my-block
|   |   +-- block.json
|   |   +-- editor.js
|   |   +-- editor.scss
|   |   +-- script.js
|   |   +-- style.scss
```

The stylesheets are imported in the respective JS entry points:

```js
// editor.js:
import './editor.scss';

// script.js:
import './style.scss';
```

The resulting CSS files will be named after JS entry names, i.e.:

```
.
+-- build
|   +-- my-block
|   |   +-- block.json
|   |   +-- editor.css
|   |   +-- editor.js
|   |   +-- script.js
|   |   +-- script.css
```

In this scenario, the following references must be used in `block.json`:

```json
{
	"editorScript": "file:./editor.js",
	"script": "file:./script.js",
	"editorStyle": "file:./editor.css",
	"style": "file:./script.css"
}
```

#### Using Common SASS Tools

It is very likely that a custom block will need some common SASS tools defined in the theme. A good example might be the
breakpoints' setup along with the `break-` utility mixins which are located in
[`/css/tools/_mixins.scss`](../css/tools/_mixins.scss). In such a scenario, you can safely import all the tools in your
block stylesheet, like:

```scss
// /blocks/my-block/style.scss
@import "../../css/tools/index";
```

Partials located in the `./css/tools` directory are not allowed to produce any CSS output. Thanks to that, no duplicated
code will be rendered when importing tools to your custom block.

### Dynamic Block Template

When dealing with a dynamic block, you must provide a PHP template for your block. By convention, the template should be
located at the root level of the block folder and named `template.php`.

You must reference your template file in the `render` property in the `block.json` file:

```php
{
	"render": "file:./template.php",
}
```

Thanks to that, WordPress will automatically use this file for rendering the block.

Note that inside the template file, you have access to the following globals:

```php
@var array    $attributes Block attributes.
@var string   $content    Block default content.
@var WP_Block $block      Block instance.
```

### Additional Backend Logic

At the time of registering a block type, you can provide a custom backend logic for your block.

In order to do so, create a file named `functions.php` at the root level of the block folder. It will be automatically
picked up by the theme logic and `required()`.

**Note:** This is not a WordPress Core functionality. You can find the implementation in
`Block_Registry::action_register_blocks()` method.

## Adding and Removing Blocks

There are two ways to add a new block:

1. Manually: create a new folder inside the `blocks` directory and provide all the required files (as described above).
2. Use the scaffolder tool: run `npm run scaffold` script in the theme's root directory and follow the instructions.

In order to remove a block from the theme, simply delete the block's folder from the `blocks` directory.

## Blocks Bundled with the Theme

The theme comes with a few block templates and pre-built blocks that may come in handy.  If they are not needed in the
project you are working on, feel free to remove them.

### Columns and Column blocks

The `columns` and `column` folders are meant to be **overriden**, they are a basic fork of Core's blocks with
[basic CSS](./columns/style.scss) to get started.

Every project is different and you will need to customize the columns to fit your needs.

## Allowed Core Blocks List

The Gutenberg core block set has been conceived to support a **hands-on site builder**: for them, building the site is
part of the fun. Corporate engagements are very different: they are **business investments**, not hobbies. Corporate
clients want *accuracy, certainty, and efficiency* over the long term.

By default, every custom, and plugin block is allowed and will be automatically added to the block inserter. But Core
blocks are in its majority **not allowed**, only a few core blocks have been deemed stable enough to be available.

### Enabling core blocks

If a case is made and it is decided that a project requires the use of additional Core blocks, they can be re-enabled by
adding their handle to the `php/components/class-block-types-allowed.php` file under the `ALLOWED_CORE_BLOCK_TYPES`
array. Or by filtering the array in a specific function, for example:

```php
// ...
/**
 * Enable "Code" core block.
 *
 * @param array $allowed_block Existing allowed list.
 *
 * @return array Modified list.
 */
function enable_code_block( array: $allowed_blocks ): array {
    $allowed_blocks['core/code'] = 'core/code';

    return $allowed_blocks;
}
add_filter( 'mavero', 'enable_code_block' );
// ...
```

But one block could have dependencies.
To add `core/reusable-blocks`, `core/block` has to be enabled too for example.
