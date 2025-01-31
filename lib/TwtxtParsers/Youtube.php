<?php

namespace Twtxt\Parsers;

// append rawMessage with iFrames of YouTube Videos
class Youtube
{
    private static $pattern = '/((?:https?:)?\/\/)?((?:www|m)\.)?((?:youtube(?:-nocookie)?\.com|youtu.be))(\/(?:[\w\-]+\?v=|embed\/|live\/|v\/|shorts\/)?)([\w\-]+)(\S+)?/im'; // https://regex101.com/r/vHEc61/1
    public static function parse(string $rawMessage = '')
    {
        if (preg_match_all(self::$pattern, $rawMessage, $youtubeIds)) {
            // var_dump($youtubeIds);
            $youtubeIds = array_unique($youtubeIds[5]);
            foreach ($youtubeIds as $videoId) {
                $rawMessage .= "\n".'<iframe loading="lazy" src="//www.youtube-nocookie.com/embed/' . trim($videoId, '()') . '" class="embeded-video" allow="encrypted-media" allowfullscreen="allowfullscreen" frameborder="0" scrolling="auto"><a href="//youtube.com/watch?v=' . $videoId. '" target="_blank">Video: youtube.com/watch?v=' . $videoId. '</a></iframe>';
            }
        }
        return $rawMessage;
    }
}
