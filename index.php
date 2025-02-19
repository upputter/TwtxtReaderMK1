<?php

// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);


require_once(__DIR__ . '/lib/init.class.php');

use Josantonius\Session\Session;

$config->site['styles'] = [
    'external/fontawesome/css/all.css' => 'all',
    'external/pico/css/pico.orange.css' => 'all',
    'Resources/Public/Style/twtxt.css' => 'all',
    'Resources/Public/Style/tiny-mde.min.css' => 'all',
];

$config->site['scripts'] = [
    'Resources/Public/JavaScript/pico/modal.js',
    'external/htmx/dist/htmx.min.js',
    'Resources/Public/JavaScript/TinyMDE/tiny-mde.min.js',
    'Resources/Public/JavaScript/spotlight/spotlight.bundle.js',
];

// all kinds of settings
$url = (isset($_GET['url'])) ? $_GET['url'] : $config->settings['twturl'];
if (strlen($url) == 0) {
    $url = $config->settings['twturl'];
}
$filterHash = ($_GET['hash']) ?? null;
$followerLevel = 0;
$currentAction = (isset($_GET['action'])) ? strtolower($_GET['action']) : 'start';
$twtxtUpdate = false;
$fluidAction = 'Timeline';
$limitMaxEntries = true;
$entryTypeFilter = ['post', 'reply'];
$unlimitedTimeline = false;
$displayType = ($_GET['display']) ?? null;
$autoPaginate = false; // use htmx based pagination (or not)

$filterAction = 'none';
if ($filterHash) {
    $filterAction = 'hash';
}
$subtitle = '';

// static information pages
$staticDoument = false;
if (isset($_GET['doc'])) {
    $currentAction = 'static';
    $staticDoument = $_GET['doc'];
}


// check for API controller
$controller = (isset($_REQUEST['controller'])) ? $_REQUEST['controller'] : false;

$validUser = false; // noone is valid w/o auth!

// session management
$session = new Session();

if (!$session->isStarted()) {
    $session->start([
        'cookie_httponly' => true,
        'cookie_lifetime' => 0,
        'cookie_samesite' => 'Strict',
        'cookie_secure' => true,
    ]);
}

if ($session->get('isValidLogin')) {
    $validUser = true;
}

// set language
$selectedLanguage = ($session->get('language')) ?? 'de'; // default language ist german

if (isset($_GET['L'])) {
    $userSelectedLanguage = trim(strtolower($_GET['L']));
    if (in_array($userSelectedLanguage, Language::getAvailableLanguages())) {
        $selectedLanguage = $userSelectedLanguage;
        $session->set('language', $selectedLanguage);
    }
}
$language = new Language($selectedLanguage);

switch ($currentAction) {
    // view actions
    case 'start':
        $autoPaginate = true;
        break;

    case 'posts':
        $subtitle = $language->get('L.feed.title.onlyPosts');
        $entryTypeFilter = ['post'];
        $autoPaginate = true;
        break;

    case 'own':
        $followerLevel = 1;
        $subtitle = $language->get('L.feed.title.onlyOwnPosts');
        $autoPaginate = true;
        break;

    case 'replies':
        $followerLevel = 1;
        $filterAction = 'replies';
        $subtitle = $language->get('L.feed.title.onlyReplies');
        $autoPaginate = true;
        break;

    case 'mentions':
        $filterAction = 'mentions';
        $subtitle = $language->get('L.feed.title.onlyMentions');
        $autoPaginate = true;
        break;

    case 'profile':
        $fluidAction = 'Profile';
        $followerLevel = 1;
        break;
    case 'static':
        showStaticDocument($staticDoument);
        break;

    // functional actions
    case 'logout':
        $session->destroy();
        header('Location: index.php');
        exit();

    case 'login':
        $fluidAction = 'Login';
        break;

    case 'update':
        if (!$validUser) {
            header('Location: index.php?action=login');
            exit();
        }
        $twtxtUpdate = true;
        // change action for autopagination
        $currentAction = 'start';
        $autoPaginate = true;
        break;

}

// here we try not to render to much

if ($controller) {
    $fluidAction = 'Api';

    switch ($currentAction) {
        case 'upload':
            if (isset($_FILES['file'])) {
                if ($publicMediaUrl = uploadMedia()) {
                    echo $publicMediaUrl;
                }
            }
            exit();

        case 'postentry':
            if (postEntry($_REQUEST['message'])) {
                // after a successful post, update the own twtxt timeline
                $followerLevel = 1;
                $twtxtUpdate = true;
                $twtxtUpdate = new Twtxt(
                    url: $config->settings['twturl'],
                    followerLevel: $followerLevel,
                    updateCachedFiles: $twtxtUpdate,
                    limitMaxEntries: 0,
                );
                $twtxtUpdate->getTwtxt();
            } else {
                http_response_code(403);
                die('Forbidden');
            }
            exit();
    }
}

// move on wtith page building

$page = new FluidPage\Page(
    action: $fluidAction,
    language: $language
);


switch (strtolower($fluidAction)) {
    case 'login':
        login();
        exit();
}

// do all the Twtxt stuff

$twtxt = new Twtxt(
    url: $url,
    followerLevel: $followerLevel,
    updateCachedFiles: $twtxtUpdate,
    limitMaxEntries: $limitMaxEntries,
    unlimitedTimeline: $unlimitedTimeline,
    entryTypeFilter: $entryTypeFilter,
);

$twtxt->getTwtxt();

// TODO: may redirect the action
if ($currentAction == 'update') {
}

// set filters for feeds
switch (strtolower($filterAction)) {
    case 'api':
        $twtxt->filerEntriesByHash($filterHash);
        $limitMaxEntries = false; // get all hashes outside of page limit
        break;
    case 'hash':
        if ($displayType != 'timeline') {
            $twtxt->showAsConversation = true;
        }
        $twtxt->filerEntriesByHash($filterHash);
        $limitMaxEntries = false; // get all hashes outside of page limit
        break;
    case 'replies':
        $twtxt->filterRepliesOnly();
        break;
    case 'mentions':
        $twtxt->filterMentions();
        break;
}

$twtxt->unique_entries($limitMaxEntries);

$paginationPage = ($_GET['page']) ?? 1;

// TODO: may clean up some variables
$page->assign('languageSelector', ['current' => $selectedLanguage, 'available' => $language->getAvailableLanguages()]);
$page->assign('validUser', $validUser);
$page->assign('filterHash', $filterHash);
$page->assign('timeline', $twtxt->entries);
$page->assign('currentAction', $currentAction);
$page->assign('currentUrl', $url);
$page->assign('paginationCurrentPage', $paginationPage);
$page->assign('twtxt', $twtxt);
$page->assign('following', $twtxt->following);
$page->assign('subtitle', $subtitle);
$page->assign('autoPaginate', $autoPaginate);

$page->render();
