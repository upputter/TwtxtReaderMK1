<?php
namespace Twtxt\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class PaginationViewHelper extends AbstractViewHelper {
    public function initializeArguments() {
        $this->registerArgument('data', 'mixed', 'data to paginate', true);
        $this->registerArgument('as', 'sting', 'name of return variable', true);

        $this->registerArgument('itemsPerPage', 'integer', 'data to show', false, 20);
        $this->registerArgument('currentPage', 'integer', 'start entry', false, 1);

        $this->registerArgument('pageLimit', 'integer', 'max number of pages in pagination', false, 10);
    }

    public function render() {

        $total = count($this->arguments['data']);
        $maxPages = (int) ceil($total / $this->arguments['itemsPerPage']);
        $currentPage = $this->arguments['currentPage'];
        $currentPage = ($currentPage > $maxPages) ? $maxPages : $currentPage;        

        $prevPage = ($currentPage > 1) ? $currentPage - 1 : 1; 
        $nextPage = ($currentPage < $maxPages) ? $currentPage + 1 : $maxPages;
        $nextPage = ($maxPages == 1) ? 1 : $nextPage;

        $maxPagesToRender = ($maxPages > $this->arguments['pageLimit']) ? $this->arguments['pageLimit'] : $maxPages;

        $pages = [];
        for($i = 1; $i <= $maxPagesToRender; $i++) {
            $pages[$i] = $i;
        }        

        $data = array_slice(
            $this->arguments['data'],
            (($this->arguments['currentPage'] - 1) * $this->arguments['itemsPerPage']),
            $this->arguments['itemsPerPage']
        );    

        $returnData = [
            'isNeeded' => ($total > $this->arguments['itemsPerPage']),
            'total' => $total,            
            'pages' => $pages,
            'prevPage' => $prevPage,
            'nextPage' => $nextPage,
            'currentPage' => $this->arguments['currentPage'],
            'items' => $data,

        ];
        $this->renderingContext->getVariableProvider()->add($this->arguments['as'], $returnData);
    }
}