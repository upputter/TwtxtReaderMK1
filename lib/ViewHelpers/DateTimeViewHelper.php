<?php
namespace Twtxt\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class DateTimeViewHelper extends AbstractViewHelper {
    public function initializeArguments() {
        $this->registerArgument('date', 'mixed', 'dateTimeObject', true);
        $this->registerArgument('format', 'string', 'output format', false, 'd.m.Y - H:i:s');
    }

    public function render() {
        return $this->arguments['date']->format($this->arguments['format']);
    }
}