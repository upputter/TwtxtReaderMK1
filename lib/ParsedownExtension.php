<?php
namespace Twtxt;
// https://stackoverflow.com/questions/47145213/add-target-blank-to-external-link-parsedown-php
class TwtxtParsedown extends \Parsedown
{
    //Add target to links
    protected function element(array $Element) {
        if (strcasecmp($Element['name'], 'a')===0)
        if ($Element['name'] == 'a' && $this->isExternalUrl($Element['attributes']['href'])) {
            $Element['attributes']['target'] = '_blank';
        }
        return parent::element($Element);
    }

    protected function isExternalUrl($url, $internalHostName = null) {
        $components = parse_url($url);
        $internalHostName = ($internalHostName == null) ? $_SERVER['HTTP_HOST'] : $internalHostName;
        // we will treat url like '/relative.php' as relative
        if (empty($components['host'])) {
            return false;
        }
        // url host looks exactly like the local host
        if (strcasecmp($components['host'], $internalHostName) === 0) {
            return false;
        }
        $isNotSubdomain = strrpos(strtolower($components['host']), '.'.$internalHostName) !== strlen($components['host']) - strlen('.'.$internalHostName);
        return $isNotSubdomain;
    }
}