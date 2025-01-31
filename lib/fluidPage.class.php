<?php
namespace FluidPage;

use Language;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\View\TemplatePaths;
use TYPO3Fluid\Fluid\View\TemplateView;

// require_once ('./ViewHelpers/init.php');

class Page
{
    public $rootPath =  __DIR__ . '/../Resources/Private/';
    public $content = '';
    protected $siteConfig;
    protected $view;
    protected $controllerName = 'Twtxt';

    public function __construct(
        public string $action = 'Page',  
        public Language $language 
    )
    {
        // get global SiteConfigs from config.ini
        global $config;
        $this->siteConfig = $config->site;

        // init fluid template engine an set paths
        $paths = new TemplatePaths();       
        $paths->setTemplateRootPaths([$this->rootPath . 'Templates/']);
        $paths->setLayoutRootPaths([$this->rootPath . 'Layouts/']);
        $paths->setPartialRootPaths([$this->rootPath . 'Partials/']);

        $context = new RenderingContext();
        $context->setControllerName($this->controllerName);
        $context->setTemplatePaths($paths);           
        
        $this->view = new TemplateView($context);
        // add custom fluid view helpers
        $this->view->getRenderingContext()->getViewHelperResolver()->addNamespace('t', 'Twtxt\\ViewHelpers');

    }
    
    public function setContent(string $content) {
        $this->content = $content;
    }

    public function assign($name, $var) {
        $this->view->assign($name, $var);
    }

    public function render(string $content = null){        
        $this->view->assign('L', $this->language->getLanguageData());
        $this->view->assign('site', $this->siteConfig);

        if ($content) {
            $this->view->assign('content', $content);
        } else {
            $this->view->assign('content', $this->content);
        }
        echo $this->view->render($this->action);
    }
}





