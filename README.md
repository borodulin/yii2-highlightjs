Highlightjs widget for Yii2 framework
=================

## Description

Syntax highlighting for the Web

For more information please visit [Highlightjs Home Page](https://highlightjs.org/) 

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/). 

To install, either run

```
$ php composer.phar require conquer/highlightjs "*"
```
or add

```
"conquer/highlightjs": "*"
```

to the ```require``` section of your `composer.json` file.

## Usage

```php
use conquer\highlightjs\HighlightjsWidget;

<?= HighlightjsWidget::widget([
    'language' => 'xml',
    'content' => file_get_contents('some_xml_file.xml'),
]); ?>

```

## License

**conquer/highlightjs** is released under the MIT License. See the bundled `LICENSE.md` for details.