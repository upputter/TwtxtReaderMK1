<?php
namespace Twtxt\Parsers;
// replace image-link in $string with markdown, skip markdown formated image-urls
class VideoLink {
    static public function parse(string $rawMessage = '') {
        $string = $rawMessage;
        $pattern = '/(?!\[\s)(?<!\])(http(s?)?:\/\/[^ ]+?(?:\.webm|\.mp4))/i';
        if (preg_match_all($pattern, $string, $matches)) {
            $videoUrls = @array_unique($matches);
            foreach ($videoUrls[0] AS $videoUrl) {
                if (filter_var($videoUrl, FILTER_VALIDATE_URL)) {
                    $fileExtension = pathinfo($videoUrl, PATHINFO_EXTENSION);
                    $string .= '<video class="embeded-video" width="320" height="240" controls muted><source src="' . $videoUrl . '" type="video/' . $fileExtension . '"></video>';
                }
            }
        }
        return $string;
    }
}