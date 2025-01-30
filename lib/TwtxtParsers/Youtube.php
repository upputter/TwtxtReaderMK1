<?php
namespace Twtxt\Parsers;
// append rawMessage with iFrames of YouTube Videos
class Youtube {
    // private static $pattern = '/(?:youtube(?:-nocookie)?\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/mi';
    // private static $pattern = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i';
    // private static $pattern = '/((?:https?:)?\/\/)?((?:www|m)\.)?(youtu.*be.*)\/(watch\?v=|embed\/|v|shorts|)(.*?((?=[&#?])|$))/mi';
    private static $pattern = '/((?:https?:)?\/\/)?((?:www|m)\.)?((?:youtube(?:-nocookie)?\.com|youtu.be))(\/(?:[\w\-]+\?v=|embed\/|live\/|v\/|shorts\/)?)([\w\-]+)(\S+)?/im'; // https://regex101.com/r/vHEc61/1
    static public function parse(string $rawMessage = '') {        
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