<?php
use Phpfastcache\CacheManager;
use Phpfastcache\Config\ConfigurationOption;

class TwtxtCache
{
    protected $CacheInstance;
    protected $CachedString;
    protected $cacheDuration = 600;
    protected $objectCacheDuration = 900;
    protected $config;

    public function __construct(
        public bool $forceUpdate = false
    ) {
        global $config;
        $this->config = $config->settings;
        $this->cacheDuration = ($this->config['maxCacheTime']) ?? $this->cacheDuration;
        $cacheDir = $config->settings['fastCacheDir'];
        CacheManager::setDefaultConfig(new ConfigurationOption([
            'path' => __DIR__ . '/../' . $cacheDir,
            'itemDetailedDate' => true,
        ]));
        // $this->CacheInstance = CacheManager::getInstance('files');        
        $this->CacheInstance = CacheManager::getInstance('twtxtfiles'); // use modified file cache w/o expired items
    }

    public function getStatus()
    {
        return $this->CacheInstance->getStats();
    }

    protected function cleanedUpUrl($url)
    {
        return rtrim(trim($url), " \n\r/");
    }

    public function clearCache($url)
    {
        $url = $this->cleanedUpUrl($url);
        $urlHash = hash('sha256', $url);
        $this->CacheInstance->deleteItems([$urlHash]);
    }

    protected function setCache($cacheHash, $cacheContent, $tag = false)
    {
        $this->CachedString = $this->CacheInstance->getItem($cacheHash);
        $this->CachedString->set($cacheContent)->expiresAfter($this->cacheDuration);
        if ($tag) {
            $this->CachedString->addTag($tag);
        }
        $this->CacheInstance->save($this->CachedString);
        $this->CacheInstance->commit();
    }

    public function getTwtxt(string $url = '')
    {
        $url = $this->cleanedUpUrl($url);
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $urlHash = hash('sha256', $url);

            if ($this->CacheInstance->hasItem($urlHash)) { // check cache for stored twtxt
                if ($this->forceUpdate) { // force cache update for twtxt
                    $lastCacheDateTime = $this->CacheInstance->getItem($urlHash)->getModificationDate() ?? false;
                    if ($remoteContent = $this->getRemoteContentFromTwtxtUrl($url, $lastCacheDateTime)) {
                        $this->setCache($urlHash, $remoteContent);
                    }
                }
                return $this->CacheInstance->getItem($urlHash)->get();
            } else {
                if ($remoteContent = $this->getRemoteContentFromTwtxtUrl($url)) {
                    $this->setCache($urlHash, $remoteContent);
                    return $this->CacheInstance->getItem($urlHash)->get();
                }
            }
        } else {
            return '';
        }
    }

    public function getMultiTwtxt(array $urls)
    {
        $returnObject = [];
        $updateUrls = [];
        foreach ($urls as $url) {
            $url = $this->cleanedUpUrl($url);
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $urlHash = hash('sha256', $url);
                // $this->CachedString = $this->CacheInstance->getItem($urlHash);
                if ($this->CacheInstance->hasItem($urlHash)) {
                    $returnObject[$url] = $this->CacheInstance->getItem($urlHash)->get();
                } else {
                    $updateUrls[] = $url;
                }
            }
        }
        if (count($updateUrls) > 0) {
            $this->getMultiRemoteContentFromTwtxt($updateUrls);
            foreach ($updateUrls as $url) {
                $urlHash = hash('sha256', $url);
                // $this->CachedString = $this->CacheInstance->getItem($urlHash);
                if ($this->CacheInstance->hasItem($urlHash)) {
                    $returnObject[$url] = $this->CacheInstance->getItem($urlHash)->get();
                }
            }
        }
        return $returnObject;
    }

    protected function getRemoteContentFromTwtxtUrl($url, $lastUpdateDateTime = false)
    {

        if (!$lastUpdateDateTime) {
            $lastUpdateDateTime = new DateTime();
            $lastUpdateDateTime->setTimestamp(0);
        }

        $headerLastUpdateDateTime = $lastUpdateDateTime;
        $headerLastUpdateDateTime->setTimezone(new DateTimeZone('GMT'));
        // $headerLastTimeModifiedSince = 'If-Modified-Since: ' . $headerLastUpdateDateTime->format('D, d M Y H:i:s') . ' GMT';

        $curlHeader = [
            'User-Agent: TwtxtReader/0.0.1 (+https://uplegger.eu/twtxt.txt; @arne)',
            'If-Modified-Since: ' . $headerLastUpdateDateTime->format('D, d M Y H:i:s') . ' GMT',
        ];

        $curlOptArray = [
            CURLOPT_HEADER => 0,
            CURLOPT_VERBOSE => 0,
            CURLOPT_RETURNTRANSFER => true,
            // CURLOPT_USERAGENT => 'TwtxtReader/0.0.1 (+https://uplegger.eu/twtxt.txt; @arne)', // ToDo: Use TWTXT-User
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_URL => $url,
            CURLOPT_FILETIME => true,
            CURLOPT_ENCODING => '', // handle gzip response  
            CURLOPT_HTTPHEADER => $curlHeader,
            CURLOPT_NOSIGNAL => 1,
            CURLOPT_TIMEOUT_MS => 7500,
        ];

        $ch = curl_init(CURL_IPRESOLVE_WHATEVER);
        curl_setopt_array($ch, $curlOptArray);
        $response = curl_exec($ch);

        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $modTimestamp = abs((int) curl_getinfo($ch, CURLINFO_FILETIME)); // prevent negative unixtimestamps

        $httpStatusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if (
            ((200 <= $httpStatusCode) and ($httpStatusCode <= 400)) // check http status | within Successfull and Redirection
            and $this->isValidTwtxtContentType($contentType) // check for valid twtxt content type
        ) {
            $modDateTimeDateTime = new DateTime();
            $modDateTimeDateTime->setTimezone(new DateTimeZone('GMT'));
            $modDateTimeDateTime->setTimestamp($modTimestamp);

            if ($modDateTimeDateTime > $lastUpdateDateTime) { // if the remote modified datetime is younger than the cache
                return $response;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    protected function getMultiRemoteContentFromTwtxt(array $urls = [])
    {
        $multiHandle = curl_multi_init();
        $curlHandles = [];
        $curlOptArray = [
            CURLOPT_HEADER => 0,
            CURLOPT_VERBOSE => 0,
            CURLOPT_RETURNTRANSFER => true,
            // CURLOPT_USERAGENT => 'TwtxtReader/0.0.1 (+https://uplegger.eu/twtxt.txt; @arne)', // ToDo: Use TWTXT-User
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_MAXREDIRS => 10,
            // CURLOPT_URL => $url,
            CURLOPT_FILETIME => true,
            CURLOPT_ENCODING => '', // handle gzip response
            CURLOPT_NOSIGNAL => 1,
            CURLOPT_TIMEOUT_MS => 7500,

        ];

        foreach ($urls as $id => $url) {
            $urlHash = hash('sha256', $url);

            $curlHeader = [
                'User-Agent: TwtxtReader/0.0.1 (+https://uplegger.eu/twtxt.txt; @arne)', // set user agent with twtxt user info
                'Connection: close',
            ];

            if ($this->CacheInstance->hasItem($urlHash)) { // insert las update time into http header
                $lastUpdateDateTime = $this->CacheInstance->getItem($urlHash)->getModificationDate();
                $curlHeader[] = 'If-Modified-Since: ' . $lastUpdateDateTime->format('D, d M Y H:i:s') . ' GMT';
            }

            $ch = curl_init(CURL_IPRESOLVE_WHATEVER);
            $curlOptArray[CURLOPT_URL] = $url;
            $curlOptArray[CURLOPT_HTTPHEADER] = $curlHeader;

            curl_setopt_array($ch, $curlOptArray);
            $curlHandles[$url] = $ch;
            curl_multi_add_handle($multiHandle, $ch);
        }

        $active = null;
        //execute the handles
        do {
            $mrc = curl_multi_exec($multiHandle, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($multiHandle) != -1) {
                do {
                    $mrc = curl_multi_exec($multiHandle, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }

        foreach ($curlHandles as $url => $ch) {
            $content = curl_multi_getcontent($ch); // get the content
            $urlHash = hash('sha256', $url);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $modTimestamp = (int) curl_getinfo($ch, CURLINFO_FILETIME);
            $httpStatusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            // echo $url .': ' .  (curl_getinfo($ch, CURLINFO_TOTAL_TIME_T) / 1000000) . 's<br />';

            if (
                ((200 <= $httpStatusCode) and ($httpStatusCode <= 400)) // check http status | within Successfull and Redirection
                and $this->isValidTwtxtContentType($contentType) // check for valid twtxt content type
            ) {
                // create DateTime object from http last-modified response
                $modDateTimeDateTime = new DateTime();
                $modDateTimeDateTime->setTimestamp($modTimestamp);
                $modDateTimeDateTime->setTimezone(new DateTimeZone('GMT'));

                if ($this->CacheInstance->hasItem($urlHash)) { // if item for url in cache
                    $lastUpdateDateTime = $this->CacheInstance->getItem($urlHash)->getModificationDate(); // get modification DateTime object of cached item
                    if ($modDateTimeDateTime > $lastUpdateDateTime) { // if response DateTime Object is younger 
                        $this->setCache($urlHash, $content); // update item in cache
                    }
                } else { // if no item in cache
                    $this->setCache($urlHash, $content); // set item in cache
                }
            } else {
                // echo 'LOG: invalid contentType for ' . $url . ' : ' . $contentType .'<br />';
                $this->setCache($urlHash, '', 'error');
            }
            // echo 'LOG set multi cache for ' . $url . '<br />';
            curl_multi_remove_handle($multiHandle, $ch); // remove the handle (assuming  you are done with it);
        }
        /* End of the relevant bit */
        curl_multi_close($multiHandle);
    }

    // $contentTypeString = 'text/plain;charset=utf-8
    protected function isValidTwtxtContentType($contentTypeString = '')
    {
        $contentTypeParts = array_map('trim', explode(';', strtolower($contentTypeString)));
        if (count($contentTypeParts) == 2) {
            if ($contentTypeParts[0] == 'text/plain' and $contentTypeParts[1] == 'charset=utf-8') {
                return true;
            }
        }
        if ($contentTypeParts[0] == 'text/plain') {
            return true;
        }
        return false;
    }
}