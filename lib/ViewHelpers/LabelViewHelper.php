<?php

namespace Twtxt\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class LabelViewHelper extends AbstractViewHelper
{
    public function initializeArguments()
    {
        $this->registerArgument('label', 'string', 'translation value', true);
        $this->registerArgument('prefix', 'string', 'translation value path', false, 'L.');
    }
    public function render()
    {
        global $language;
        $labelPath = rtrim($this->arguments['prefix'], '.') . '.' . ltrim($this->arguments['label'], '.');
        return $language->get($labelPath);
    }
}
