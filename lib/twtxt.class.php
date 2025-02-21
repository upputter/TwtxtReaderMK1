<?php

class Twtxt
{
    public $config = [];
    public $entries = [];
    public $following = [];
    public $type = 'user';
    public $debug = false;

    public $status = 'valid';

    public $nick = '';
    public $avatar = '';
    public $description = '';
    public $links = [];
    public $publicKey = '';

    public $timelineLimit = '-2 months'; // limit timeline by date
    public $timelineLimitDateTime;

    public $conversation = [];
    public $conversationHashes = [];
    public $showAsConversation = false;
    public $conversationCounter = 0;

    protected $cache;

    public function __construct(
        public string $url = '',
        public int $followerLevel = 0,
        public $updateCachedFiles = false,
        public $limitMaxEntries = true,
        public $unlimitedTimeline = false,
        public $entryTypeFilter = ['post', 'reply'],
    ) {
        global $config;
        $this->config = $config->settings;
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $this->timelineLimitDateTime = new DateTime();
        $this->timelineLimitDateTime->setTimestamp(strtotime($this->timelineLimit));
        $this->cache = new TwtxtCache(forceUpdate: $this->updateCachedFiles);
        $this->url = $url;
    }

    public function parseTwtxtData(string $url, string $data)
    {
        if (mb_strlen($data) > 0) {
            $urlInfo = parse_url($url);
            $this->nick = $urlInfo['host'];
            $lineCounter = 0;
            $lines = explode("\n", $data);
            foreach ($lines as $currentLine) {
                $lineCounter++;
                if (empty(trim($currentLine))) {
                    continue;
                }

                // TWTXT Header
                if (str_starts_with($currentLine, '#')) {
                    if ($currentLine != '#~~~#') {
                        $currentHeaderParameter = $this->getHeaderParameter($currentLine);
                        switch (strtolower(trim((string) $currentHeaderParameter[0]))) {
                            case 'nick':
                                $this->nick = trim($currentHeaderParameter[1]);
                                break;
                            case 'avatar':
                                $this->avatar = $currentHeaderParameter[1];
                                break;
                            case 'follow':
                                list($nick, $twtxtUrl) = mb_split(' ', $currentHeaderParameter[1]);
                                $this->following[] = ['nick' => $nick, 'url' => $twtxtUrl];
                                break;
                            case 'description':
                                $this->description = $currentHeaderParameter[1];
                                break;
                            case 'link':
                                $linkData = explode(' ', $currentHeaderParameter[1]);
                                $linkTarget = array_pop($linkData);
                                $linkLabel = (count($linkData) > 0) ? implode(' ', $linkData) : $linkTarget;
                                $link = [
                                    'label' => $linkLabel,
                                    'target' => $linkTarget
                                ];
                                $this->links[] = $link;
                                break;
                            case 'public_key':
                                $this->publicKey = $currentHeaderParameter[1];
                                break;
                            case 'type':
                                $this->type = 'list';
                                break;
                        }
                    }
                } else {
                    try {
                        // TWTXT Messages
                        if ($items = mb_split("\t", $currentLine)) {
                            if (count($items) < 2) {
                                $items = mb_split(" ", $currentLine, 2);
                            }
                        }
                        try {
                            @list($timestring, $message) = $items;
                            if ($message and $timestring) {
                                $dateTime = new DateTime($timestring, new DateTimeZone('UTC'));
                                // $dateTimeString = $dateTime->format('Y-m-d\TH:i:sP');
                                if (($dateTime >= $this->timelineLimitDateTime) or $this->unlimitedTimeline) {
                                    $newEntry = new TwtxtEntry(
                                        dateTime: $dateTime,
                                        rawMessageLine: $currentLine,
                                        rawMessage: $message,
                                        nick: $this->nick,
                                        url: rtrim($url, '/'),
                                        avatarUrl: $this->avatar,
                                        publicKey: $this->publicKey,
                                    );
                                    if (in_array($newEntry->type, $this->entryTypeFilter)) {
                                        $this->entries[$newEntry->hash] = $newEntry;
                                    }
                                }
                            }
                        } catch (Exception $e) {
                            if ($this->debug) {
                                // echo 'Error for URL: ' . $url . ' (Cache: ' . $this->getCacheFilename($url) . ') in line ' . $lineCounter . ': ' . $currentLine . '<br />';
                            }
                        }
                    } catch (Exception $e) {
                    }
                }
            }
        } else {
            // echo 'Empty data for URL: ' . $url;
        }
    }

    protected function getHeaderParameter($currentLine)
    {
        $currentLine = trim($this->str_replace_first('#', '', $currentLine));
        if (mb_strlen($currentLine) > 0) {
            $paramValues = mb_split('=', $currentLine, 2);
            if (count($paramValues) == 2) {
                list($key, $value) = $paramValues;
                return [trim($key), trim($value)];
            }
        }
        return [null, null];
    }

    protected function str_replace_first($search, $replace, $subject)
    {
        return implode($replace, explode($search, $subject, 2));
    }

    public function getTwtxt($url = '')
    {
        $url = (!empty($url)) ? trim($url) : $this->url;
        if (str_contains($url, 'gemini://')) {
            return false;
        }
        if ($twtxtData = $this->cache->getTwtxt($url)) {
            $this->parseTwtxtData($url, $twtxtData);
        }
        $this->followerLevel++;
        if (count($this->following) > 0 and $this->followerLevel <= 1) {
            $followingUrls = [];
            foreach ($this->following as $followed) {
                $followingUrls[] = $followed['url'];
            }
            $followerData = $this->cache->getMultiTwtxt($followingUrls);

            foreach ($followerData as $url => $follower) {
                $currentFollower = new Twtxt(
                    url: $url,
                    followerLevel: $this->followerLevel,
                    updateCachedFiles: $this->updateCachedFiles,
                    limitMaxEntries: $this->limitMaxEntries,
                    unlimitedTimeline: $this->unlimitedTimeline,
                    entryTypeFilter: $this->entryTypeFilter,
                );

                $currentFollower->getTwtxt();
                if (!is_null($currentFollower->entries) and !is_null($this->entries)) {
                    try {
                        $this->entries = array_merge($this->entries, $currentFollower->entries);
                    } catch (Exception $e) {
                    }
                }
            }
        }

        if (!is_null($this->entries)) {
            if (strtolower($this->config['sorting']) == 'desc') {
                @usort($this->entries, function ($a, $b) {
                    return ($a->dateTime <= $b->dateTime) ? true : false;
                });
            } else {
                @usort($this->entries, function ($a, $b) {
                    return ($a->dateTime >= $b->dateTime) ? true : false;
                });
            }
        }
    }

    public function filterRepliesOnly()
    {
        $entryStorage = [];
        foreach ($this->entries as $entry) {
            if ($entry->replyTo) {
                $entryStorage[$entry->hash] = $entry;
            }
        }
        $this->entries = $entryStorage;
    }

    public function filerEntriesByHash($hash)
    {
        // iterate through all entries and find hash dependencies
        $hashStorage = $this->getCorrespondingHashesFromEntries([$hash]); // initialize hashstorage array with current hash
        // sort conversation (nicks and urls)
        ksort($this->conversation);

        $entryStorage = [];

        // match entries to hashstorage
        foreach ($this->entries as $entry) {
            if (in_array($entry->hash, $hashStorage)) {
                $entryStorage[$entry->hash] = $entry;
            }
        }
        $this->conversationHashes = $hashStorage;

        // reduce entries to matched hashes
        $this->entries = $entryStorage;

        // sort conversation
        @uasort($this->entries, function ($a, $b) {
            return ($a->dateTime >= $b->dateTime) ? true : false;
        });

        // stack sorted entries together
        if ($this->showAsConversation) {
            $this->buildConversation();
        }
    }

    public function buildConversation()
    {
        $conversationArray = [];
        // echo count($this->entries);
        $conversationCounter = 1;
        foreach ($this->entries as $hash => $entry) {
            if (isset($entry->replyTo)) {
                $conversationCounter++;
                if (isset($this->entries[$entry->replyTo])) {
                    $this->entries[$entry->replyTo]->setConversationEntries($hash, $entry);
                    $conversationArray[] = $hash;
                } else {
                    // TODO: Error logging
                    // echo 'Can not find hash #' . $entry->replyTo . ' in conversation. <br />';
                }
            }
        }
        // remove stacked entries from global timeline
        foreach ($this->entries as $hash => $entry) {
            if (in_array($hash, $conversationArray)) {
                unset($this->entries[$hash]);
            }
        }
        $this->conversationCounter = (int) $conversationCounter;
    }

    public function getCorrespondingHashesFromEntries(
        $hashStorage = []
    ): array {
        $maxLevel = (int) (($this->config['conversationLevel']) ?? 5); // we are going this "deep" in a conversation
        for ($i = 0; $i <= $maxLevel; $i++) { // iterate $maxLevels through all entries
            foreach ($this->entries as $entry) {
                if (isset($entry->replyTo)) { // it all depends on $entry->replyTo
                    if (in_array($entry->hash, $hashStorage)) {
                        $hashStorage[] = $entry->replyTo; // save replyTo hash of entry in hashStorage
                    }
                    if (in_array($entry->replyTo, $hashStorage)) {
                        $hashStorage[] = $entry->hash; // save hash of entry replied to a hash in hashstorage
                    }
                }
                // build conversation data for hash (nick and url)
                if (in_array($entry->hash, $hashStorage)) {
                    $this->conversation[$entry->nick] = $entry->url;
                }
            }
            $hashStorage = array_unique($hashStorage, SORT_STRING); // reduce hashstorage to distinct values
        }
        return array_unique($hashStorage, SORT_STRING);
    }

    public function filterMentions()
    {
        $mentionString = $this->config['filterMention'];
        foreach ($this->entries as $hash => $entry) {
            if (!str_contains($entry->rawMessage, $mentionString)) {
                unset($this->entries[$hash]);
            }
        }
    }

    // get only unique entries (by hash) and limit the number of entries by config value "maxEntries"
    public function unique_entries(bool $limitEntries = true)
    {
        $hashStorage = [];
        $entryStorage = [];

        if (is_array($this->entries)) {
            foreach ($this->entries as $entry) {
                if (!in_array($entry->hash, $hashStorage)) {
                    $hashStorage[] = $entry->hash;
                    $entryStorage[$entry->hash] = $entry;
                }
            }
            $this->entries = $entryStorage;
        }
        if ($limitEntries and count($this->entries) > 0) {
            if ($this->limitMaxEntries) {
                $this->entries = array_slice($this->entries, 0, $this->config['maxEntries']);
            }
        }
    }
}
