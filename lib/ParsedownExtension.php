<?php

namespace Twtxt;

use Exception;

// https://stackoverflow.com/questions/47145213/add-target-blank-to-external-link-parsedown-php
class TwtxtParsedown extends \Parsedown
{
    private $MarkdownImageRegex = "~^!\[.*?\]\(.*?\)$~";

    public function __construct()
    {
        // Add blockFigure to non-exclusive handlers for text starting with !
        $this->BlockTypes['!'][] = 'Spotlight';
        $this->InlineTypes['!'] = ['Spotlight'];
    }

    protected function inlineSpotlight($Excerpt)
    {
        if (! isset($Excerpt['text'][1]) or $Excerpt['text'][1] !== '[') {
            return;
        }

        $Excerpt['text']= substr($Excerpt['text'], 1);
        $Link = $this->inlineLink($Excerpt);

        if ($Link === null) {
            return;
        }

        $Inline = [
            'extent' => $Link['extent'] + 1,
            'element' => [
                'name' => 'img',
                'attributes' => array(
                    'src' => $Link['element']['attributes']['href'],
                    'alt' => $Link['element']['text'],                    
                ),
            ],
        ];

        $Link['element']['attributes']['class'] = 'spotlight';
        $Inline['element']['attributes'] += $Link['element']['attributes'];
        unset($Inline['element']['attributes']['href']);
        return $Inline;
    }

    protected function blockSpotlight($Line)
    {
        // If line does not match image def, don't handle it
        if (1 !== preg_match($this->MarkdownImageRegex, $Line['text'])) {
            return;
        }

        $InlineImage = $this->inlineImage($Line);
        if (!isset($InlineImage)) {
            return;
        }

        $SpotlightBlock = [
            'element' => [
                'name'=> 'a',
                'handler' => 'elements',
                'attributes' => [
                    'href' => $InlineImage['element']['attributes']['src'],
                    'class' => 'spotlight'
                ],
                'text' => [
                    $InlineImage['element']
                ]
            ]
        ];

        return $SpotlightBlock;
    }

    //Add target to links
    protected function element(array $Element)
    {
        if (strcasecmp($Element['name'], 'a')===0) {
            if ($Element['name'] == 'a' && $this->isExternalUrl($Element['attributes']['href'])) {
                $Element['attributes']['target'] = '_blank';
            }
        }
        return parent::element($Element);
    }

    protected function isExternalUrl($url, $internalHostName = null)
    {
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

    protected function inlineImage($Excerpt)
    {
        $InlineImage = parent::inlineImage($Excerpt);
        if ($InlineImage) {
            if (array_key_exists('src', $InlineImage['element']['attributes'])) {
                if (
                    !str_starts_with($InlineImage['element']['attributes']['src'], 'http')
                ) {
                    $InlineImage['element']['attributes']['src'] = '//' . rtrim($InlineImage['element']['attributes']['src'], '\\');
                }
            }
        }
        return $InlineImage;
    }
}
