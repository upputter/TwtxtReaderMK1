<?php

namespace Twtxt\ViewHelpers;

use DateTime;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class CurrentTimeStringViewHelper extends AbstractViewHelper
{
    public function render()
    {
        $now = new DateTime();
        return $now->format(DateTime::RFC3339) . "\t";
    }
}
