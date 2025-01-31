<?php

namespace Twtxt\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class DebugViewHelper extends AbstractViewHelper
{
    public function render()
    {
        echo '<pre>';
        var_dump($this->renderChildren());
        echo '</pre>';
    }
}
