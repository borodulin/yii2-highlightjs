<?php

namespace conquer\highlightjs\tests;

use conquer\highlightjs\HighlightjsWidget;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\Widget;
use yii\helpers\FileHelper;
use yii\web\Application;
use yii\web\JsExpression;
use yii\web\View;

class HighlightjsWidgetTest extends TestCase
{
    private $assetPath;

    protected function setUp(): void
    {
        $this->assetPath = sys_get_temp_dir() . '/highlightjs-test-' . uniqid();
        mkdir($this->assetPath);
        new Application([
            'id' => 'test',
            'basePath' => __DIR__,
            'vendorPath' => dirname(__DIR__) . '/vendor',
            'aliases' => ['@bower' => '@vendor/bower-asset', '@npm' => '@vendor/npm-asset'],
            'components' => [
                'request' => ['cookieValidationKey' => 'test', 'scriptFile' => __DIR__ . '/index.php', 'scriptUrl' => '/index.php'],
                'assetManager' => ['basePath' => $this->assetPath, 'baseUrl' => '/assets'],
                'view' => ['class' => View::class],
            ],
        ]);
        Widget::$counter = 0;
    }

    protected function tearDown(): void
    {
        FileHelper::removeDirectory($this->assetPath);
        Yii::$app = null;
    }

    /**
     * @param array[] $configs widget configurations
     * @return array [widgets html, page html]
     */
    private function render(array ...$configs)
    {
        $widgets = '';
        foreach ($configs as $config) {
            $widgets .= HighlightjsWidget::widget($config);
        }
        return [$widgets, $this->page()];
    }

    private function page()
    {
        return Yii::$app->view->renderFile(__DIR__ . '/views/page.php');
    }

    private function files($html)
    {
        preg_match_all('#(?:src|href)="/assets/\w+/([^"]+)"#', $html, $m);
        return $m[1];
    }

    public function testCode()
    {
        list($widget, $html) = $this->render(['language' => 'php', 'content' => '<?php echo "<b>"; ?>']);
        $this->assertSame('<pre><code id="w0_hljs" class="language-php">&lt;?php echo &quot;&lt;b&gt;&quot;; ?&gt;</code></pre>', $widget);
        // php is in the default build
        $this->assertSame(['styles/default.min.css', 'highlight.min.js'], $this->files($html));
        $this->assertStringContainsString('hljs.highlightElement(document.getElementById("w0_hljs"));', $html);
        // no jQuery
        $this->assertStringNotContainsString('jquery', $html);
        $this->assertStringNotContainsString('jQuery(', $html);
    }

    public function testBeginEndContentIsEncoded()
    {
        ob_start();
        HighlightjsWidget::begin(['language' => 'html', 'htmlOptions' => ['class' => 'code']]);
        echo "\n    <div class=\"alert\">Hi</div>\n";
        HighlightjsWidget::end();
        $this->assertSame('<pre class="code"><code id="w0_hljs" class="language-html">    &lt;div class=&quot;alert&quot;&gt;Hi&lt;/div&gt;</code></pre>', ob_get_clean());
        // html is an alias of xml, it has no file
        $this->assertSame(['styles/default.min.css', 'highlight.min.js'], $this->files($this->page()));
    }

    public function testEncodeDisabled()
    {
        list($widget) = $this->render(['content' => '<span class="hljs-keyword">echo</span>', 'encode' => false]);
        $this->assertSame('<pre><code id="w0_hljs"><span class="hljs-keyword">echo</span></code></pre>', $widget);
    }

    public function testStyle()
    {
        list(, $html) = $this->render(['content' => 'a'], ['content' => 'b', 'style' => 'base16/dracula'], ['content' => 'c', 'style' => 'base16/dracula'], ['content' => 'd']);
        $this->assertSame(['styles/base16/dracula.min.css', 'highlight.min.js'], $this->files($html));
        preg_match('#href="/assets/(\w+)/styles/base16/dracula\.min\.css"#', $html, $m);
        $dir = $this->assetPath . '/' . $m[1];
        $this->assertFileExists("$dir/styles/base16/dracula.min.css");
        $this->assertFileExists("$dir/languages/erlang.min.js");
        $this->assertFileDoesNotExist("$dir/highlight.js");
        $this->assertFileDoesNotExist("$dir/styles/github.css");
        $this->assertDirectoryDoesNotExist("$dir/es");
    }

    public function testConflictingStyles()
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('The page already uses the highlight.js style "github", "monokai" cannot be used with it');
        $this->render(['content' => 'a', 'style' => 'github'], ['content' => 'b', 'style' => 'monokai']);
    }

    public function testUnknownStyle()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown highlight.js style "../../highlight"');
        $this->render(['content' => 'a', 'style' => '../../highlight']);
    }

    public function testStyleIsNotSharedBetweenApplications()
    {
        $this->render(['content' => 'a', 'style' => 'github']);
        $this->tearDown();
        $this->setUp();
        list(, $html) = $this->render(['content' => 'b', 'style' => 'monokai']);
        $this->assertSame(['styles/monokai.min.css', 'highlight.min.js'], $this->files($html));
    }

    public function testLanguages()
    {
        list(, $html) = $this->render(['content' => 'a', 'languages' => ['erlang', 'haskell']], ['content' => 'b', 'language' => 'erlang']);
        $this->assertSame(['styles/default.min.css', 'highlight.min.js', 'languages/erlang.min.js', 'languages/haskell.min.js'], $this->files($html));
    }

    public function testBundledLanguages()
    {
        $asset = \conquer\highlightjs\HighlightjsAsset::register(Yii::$app->view);
        $bundled = $asset->bundledLanguages();
        foreach (['php', 'javascript', 'xml', 'css', 'sql', 'json', 'bash', 'php-template'] as $language) {
            $this->assertContains($language, $bundled);
        }
        $this->assertNotContains('erlang', $bundled);
        list(, $html) = $this->render(['content' => 'a', 'languages' => ['sql', 'php-template']]);
        $this->assertSame(['styles/default.min.css', 'highlight.min.js'], $this->files($html));
    }

    public function testUnknownLanguage()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown highlight.js language "cobol2": languages/cobol2.min.js is not found');
        $this->render(['content' => 'a', 'languages' => ['cobol2']]);
    }

    public function testOptionsAndIdAreEscaped()
    {
        list($widget, $html) = $this->render([
            'content' => 'x',
            'codeOptions' => ['id' => "a'));alert(1);//"],
            'options' => ['ignoreUnescapedHTML' => true, 'cssSelector' => '</script><script>alert(2)', 'f' => new JsExpression('function () {}')],
        ]);
        $this->assertSame('<pre><code id="a&#039;));alert(1);//">x</code></pre>', $widget);
        $u = '\\' . 'u';
        $this->assertStringContainsString('hljs.highlightElement(document.getElementById("a' . $u . '0027));alert(1);\/\/"));', $html);
        $this->assertStringContainsString('hljs.configure({"ignoreUnescapedHTML":true,"cssSelector":"' . $u . '003C\/script' . $u . '003E' . $u . '003Cscript' . $u . '003Ealert(2)","f":function () {}});', $html);
        $this->assertStringNotContainsString('<script>alert(2)', $html);
        $this->assertLessThan(strpos($html, 'highlightElement'), strpos($html, 'hljs.configure'));
    }
}
