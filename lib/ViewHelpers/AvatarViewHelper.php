<?php
namespace Twtxt\ViewHelpers;

use Exception;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class AvatarViewHelper extends AbstractViewHelper {
    public function initializeArguments() {
        $this->registerArgument('image', 'string', 'image url', true);        
    }
    public function render() {
        global $config;

        // var_dump($config);

        $imageUrl = $this->arguments['image'];

        if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            $imageUrlHash = hash('sha256', $imageUrl);
            $cachedImagePath = rtrim($config->settings['imageCacheDir'], '/') . '/avatars/' . $imageUrlHash;
            if (!file_exists($cachedImagePath)) {
                try {
                    if($imageFile = @file_get_contents($imageUrl, false)) {
                        file_put_contents($cachedImagePath, $imageFile);
                    } else {
                        return $imageUrl;
                    }
                } catch (Exception $e) {
                    return $imageUrl;
                }
            }
            return $cachedImagePath;
            

        } else {
            return $imageUrl;
        }
    }
}