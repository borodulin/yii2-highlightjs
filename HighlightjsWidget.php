<?php
/**
 * @link https://github.com/borodulin/yii2-highlightjs
 * @copyright Copyright (c) 2015 Andrey Borodulin
 * @license https://github.com/borodulin/yii2-highlightjs/blob/master/LICENSE
 */

namespace conquer\highlightjs;

use yii\base\InvalidConfigException;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\View;

/**
 * Highlighted code block
 *
 * ```php
 * <?= HighlightjsWidget::widget([
 *     'language' => 'php',
 *     'content' => file_get_contents(__FILE__),
 * ]) ?>
 *
 * <?php HighlightjsWidget::begin(['language' => 'html']) ?>
 * <div class="alert">The code is HTML-encoded, so it is shown, not rendered.</div>
 * <?php HighlightjsWidget::end() ?>
 * ```
 *
 * @link https://highlightjs.org/
 * @author Andrey Borodulin
 */
class HighlightjsWidget extends Widget
{
    /**
     * Options of `hljs.configure()`; they are global and apply to the blocks highlighted after them.
     * Use yii\web\JsExpression for functions.
     * @link https://highlightjs.readthedocs.io/en/latest/api.html#configure
     * @var array
     */
    public $options = [];

    /**
     * Language of the code, e.g. `php`, `html`, `sql`. Empty to detect it automatically.
     * Its language file is loaded if it is not in the default build.
     * @link https://highlightjs.readthedocs.io/en/latest/supported-languages.html
     * @var string|null
     */
    public $language;

    /**
     * Extra languages to load, e.g. for automatic detection: `['erlang', 'haskell']`
     * @var string[]
     */
    public $languages = [];

    /**
     * Container tag
     * @var string
     */
    public $tag = 'pre';

    /**
     * HTML attributes of the container
     * @var array
     */
    public $htmlOptions = [];

    /**
     * HTML attributes of the `<code>` element
     * @var array
     */
    public $codeOptions = [];

    /**
     * Style of the page, e.g. `github`, `atom-one-dark`, `base16/dracula`; null keeps the style
     * set by another widget or `default`. Styles are global CSS: all blocks of a page use one style.
     * @link https://highlightjs.org/demo
     * @var string|null
     */
    public $style;

    /**
     * Code to highlight. With begin()/end() the content between them is used.
     * @var string|null
     */
    public $content;

    /**
     * Whether to HTML-encode the code. Disable only for code that is already encoded.
     * @var bool
     */
    public $encode = true;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if (empty($this->codeOptions['id'])) {
            $this->codeOptions['id'] = $this->getId() . '_hljs';
        }
        ob_start();
        ob_implicit_flush(false);
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $captured = ob_get_clean();
        $code = $this->content !== null ? (string)$this->content : $this->trimCaptured($captured);

        $this->registerAssets();

        $codeOptions = $this->codeOptions;
        if ($this->language) {
            Html::addCssClass($codeOptions, 'language-' . $this->language);
        }
        return Html::tag($this->tag, Html::tag('code', $this->encode ? Html::encode($code) : $code, $codeOptions), $this->htmlOptions);
    }

    /**
     * Registers the scripts of the block
     */
    protected function registerAssets()
    {
        $view = $this->getView();
        $asset = HighlightjsAsset::register($view);
        if ($this->style !== null && $this->style !== '') {
            $key = __CLASS__ . '::style';
            $current = isset($view->params[$key]) ? $view->params[$key] : null;
            if ($current !== null && $current !== $this->style) {
                throw new InvalidConfigException("The page already uses the highlight.js style \"$current\", "
                    . "\"{$this->style}\" cannot be used with it: styles are global CSS.");
            }
            $asset->setStyle($this->style);
            $view->params[$key] = $this->style;
        }
        $asset->addLanguages((array)$this->languages);
        if ($this->language) {
            $asset->addLanguages([$this->language], false);
        }

        if (!empty($this->options)) {
            $view->registerJs('hljs.configure(' . Json::htmlEncode($this->options) . ');', View::POS_END);
        }
        $view->registerJs('hljs.highlightElement(document.getElementById('
            . Json::htmlEncode((string)$this->codeOptions['id']) . '));', View::POS_END);
    }

    /**
     * Removes the line breaks around the code written between begin() and end()
     * @param string $captured
     * @return string
     */
    private function trimCaptured($captured)
    {
        $captured = preg_replace('/^[ \t]*\r?\n/', '', (string)$captured);
        return preg_replace('/\r?\n[ \t]*$/', '', $captured);
    }
}
