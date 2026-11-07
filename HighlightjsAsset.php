<?php
/**
 * @link https://github.com/borodulin/yii2-highlightjs
 * @copyright Copyright (c) 2015 Andrey Borodulin
 * @license https://github.com/borodulin/yii2-highlightjs/blob/master/LICENSE
 */

namespace conquer\highlightjs;

use Yii;
use yii\base\InvalidArgumentException;
use yii\web\AssetBundle;

/**
 * highlight.js 11 with its styles and language files
 *
 * ```php
 * HighlightjsAsset::register($view)->setStyle('github')->addLanguages(['erlang']);
 * ```
 *
 * The default build includes the common languages (php, javascript, xml/html, css, sql, json, bash...),
 * other languages are loaded from the `languages` directory.
 *
 * @link https://highlightjs.org/
 * @author Andrey Borodulin
 */
class HighlightjsAsset extends AssetBundle
{
    const DEFAULT_STYLE = 'default';

    /**
     * @inheritdoc
     */
    public $sourcePath = '@npm/highlightjs--cdn-assets';

    /**
     * @inheritdoc
     */
    public $js = ['highlight.min.js'];

    /**
     * @inheritdoc
     */
    public $publishOptions = [
        'only' => ['highlight.min.js', 'languages/*.min.js', 'styles/*.min.css', 'styles/base16/*.min.css', 'styles/*.png', 'styles/*.jpg'],
        'except' => ['/es/'],
    ];

    /**
     * @var array[] bundled languages by build file
     */
    private static $_bundled = [];

    private $_core;
    private $_languages = [];
    private $_style;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->_core = $this->js;
        $this->setStyle(static::DEFAULT_STYLE);
    }

    /**
     * Sets the style of the page. The styles are global CSS, so one style is used for all the blocks of the page.
     * @param string $style e.g. `github`, `atom-one-dark`, `base16/dracula`
     * @return $this
     * @throws InvalidArgumentException for an unknown style
     */
    public function setStyle($style)
    {
        $style = (string)$style;
        if (!preg_match('#^(base16/)?[a-z0-9][a-z0-9_.-]*$#i', $style) || strpos($style, '..') !== false
            || !is_file(Yii::getAlias($this->sourcePath) . "/styles/$style.min.css")) {
            throw new InvalidArgumentException("Unknown highlight.js style \"$style\". See the styles at https://highlightjs.org/demo.");
        }
        $this->_style = $style;
        $this->css = ["styles/$style.min.css"];
        return $this;
    }

    /**
     * @return string the style of the page
     */
    public function getStyle()
    {
        return $this->_style;
    }

    /**
     * Loads language files
     * @param string[] $languages language names, e.g. `erlang`
     * @param bool $strict whether to throw for a language without a file; aliases like `html` or `js`
     * have no files, they are part of other languages
     * @return $this
     * @throws InvalidArgumentException for an unknown language in strict mode
     */
    public function addLanguages(array $languages, $strict = true)
    {
        foreach ($languages as $language) {
            $language = (string)$language;
            $file = "languages/$language.min.js";
            if (!preg_match('/^[a-z0-9][a-z0-9_-]*$/i', $language)
                || !is_file(Yii::getAlias($this->sourcePath) . '/' . $file)) {
                if ($strict) {
                    throw new InvalidArgumentException("Unknown highlight.js language \"$language\": $file is not found in "
                        . $this->sourcePath . '.');
                }
                continue;
            }
            if (!in_array($language, $this->bundledLanguages(), true)) {
                $this->_languages[$file] = $file;
            }
        }
        $this->js = array_values(array_unique(array_merge((array)$this->_core, array_values($this->_languages))));
        return $this;
    }

    /**
     * @return string[] languages included in `highlight.min.js`
     */
    public function bundledLanguages()
    {
        $build = Yii::getAlias($this->sourcePath) . '/highlight.min.js';
        if (!isset(self::$_bundled[$build])) {
            $languages = [];
            if (is_file($build) && preg_match_all('/\bgrmr_([a-z0-9_]+)/', file_get_contents($build), $m)) {
                foreach (array_unique($m[1]) as $name) {
                    // the same as in highlight.js: the first underscore becomes a dash
                    $languages[] = preg_replace('/_/', '-', $name, 1);
                }
            }
            self::$_bundled[$build] = $languages;
        }
        return self::$_bundled[$build];
    }
}
