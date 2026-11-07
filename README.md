# highlight.js widget for Yii2 framework

[![Latest Version](https://img.shields.io/packagist/v/conquer/highlightjs.svg)](https://packagist.org/packages/conquer/highlightjs)
[![Total Downloads](https://img.shields.io/packagist/dt/conquer/highlightjs.svg)](https://packagist.org/packages/conquer/highlightjs)
[![CI](https://github.com/borodulin/yii2-highlightjs/actions/workflows/ci.yml/badge.svg)](https://github.com/borodulin/yii2-highlightjs/actions/workflows/ci.yml)
[![License](https://img.shields.io/packagist/l/conquer/highlightjs.svg)](LICENSE)

## Description

Syntax highlighting with [highlight.js](https://highlightjs.org/) 11: 190+ languages, 250+ styles,
automatic language detection.

## Requirements

- PHP 8.0 or later (Composer on PHP 7.4 refuses to unpack npm packages)
- Yii 2.0.14 or later
- [asset-packagist](https://asset-packagist.org) repository configured in your application's `composer.json` (standard for Yii2 app templates)

## Installation

```bash
composer require conquer/highlightjs:^2.0
```

## Usage

```php
use conquer\highlightjs\HighlightjsWidget;

<?= HighlightjsWidget::widget([
    'language' => 'php',
    'style' => 'github',
    'content' => file_get_contents(__FILE__),
]) ?>

<?php HighlightjsWidget::begin(['language' => 'html']) ?>
<div class="alert">This markup is shown as code, not rendered.</div>
<?php HighlightjsWidget::end() ?>
```

| Property | Default | Description |
|---|---|---|
| `content` | `null` | Code to highlight; with `begin()`/`end()` the content between them |
| `language` | `null` | [Language](https://highlightjs.readthedocs.io/en/latest/supported-languages.html) of the code, empty to detect it |
| `languages` | `[]` | Extra languages to load, e.g. for detection: `['erlang', 'haskell']` |
| `style` | `null` | [Style](https://highlightjs.org/demo) of the page: `github`, `atom-one-dark`, `base16/dracula`...; `default` if no widget sets it |
| `options` | `[]` | Options of [`hljs.configure()`](https://highlightjs.readthedocs.io/en/latest/api.html#configure) |
| `encode` | `true` | HTML-encode the code; disable only for code that is already encoded |
| `htmlOptions` | `[]` | HTML attributes of the container |
| `codeOptions` | `[]` | HTML attributes of the `<code>` element |
| `tag` | `'pre'` | Container tag |

The default build of highlight.js includes the common languages (bash, c, cpp, csharp, css, diff, go, graphql,
ini, java, javascript, json, kotlin, less, lua, makefile, markdown, objectivec, perl, php, python, r, ruby,
rust, scss, shell, sql, swift, typescript, vbnet, wasm, xml/html, yaml). The file of any other `language`
is loaded automatically; an unknown name in `languages` throws `yii\base\InvalidArgumentException`.
Automatic detection only knows the loaded languages and can be wrong for short snippets: set `language`
when you know it.

A style is global CSS, so all the blocks of a page use one style. Two widgets with different styles on one page
throw `yii\base\InvalidConfigException`.

Blocks are highlighted at the end of the page with `hljs.highlightElement()`, jQuery is not required.

## Upgrading from 1.0

- highlight.js 11 (`npm-asset/highlightjs--cdn-assets ^11.11`) instead of highlight.js 9.16
  (`bower-asset/highlightjs`, no longer updated); PHP 8.0 is required.
- The code between `begin()` and `end()` is HTML-encoded, as `content` always was. Set `'encode' => false`
  for code that is already encoded.
- The language class is `language-xxx`; the block id is on the `<code>` element (`codeOptions['id']`).
- `options` with functions need `yii\web\JsExpression`: strings with the `js:` prefix are not JavaScript
  (in 1.0 `options` failed with `Class "conquer\helpers\Json" not found`).
- `HighlightjsAsset::$style` is removed: use `HighlightjsAsset::register($view)->setStyle('github')`
  or the widget's `style`.
- `run()` returns the block instead of echoing it.

## License

**conquer/highlightjs** is released under the MIT License. See the bundled [`LICENSE`](LICENSE) for details.
highlight.js is released under the BSD-3-Clause License.
