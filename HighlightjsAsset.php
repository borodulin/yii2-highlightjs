<?php
/**
 * @link https://github.com/borodulin/yii2-highlightjs
 * @copyright Copyright (c) 2015 Andrey Borodulin
 * @license https://github.com/borodulin/yii2-highlightjs/blob/master/LICENSE
 */

namespace conquer\highlightjs;

use yii\base\InvalidConfigException;

/**
 * @author Andrey Borodulin
 */
class HighlightjsAsset extends \yii\web\AssetBundle
{
	public $sourcePath = '@bower/highlightjs';

	public $js = [
            'highlight.pack.min.js',
	];
	
	public static $style = 'default';
	
	/**
	 * @inheridoc
	 */
	public function registerAssetFiles($view)
	{
	    $css = '/styles/' . self::$style . '.css';
	    if (!file_exists($filename = $this->sourcePath.$css)) {
	        throw new InvalidConfigException('Unknown style ' . self::$style);
	    }
	    $this->css = [$this->baseUrl . $css];
	    parent::registerAssetFiles($view);
	}
}