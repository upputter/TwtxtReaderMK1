<?php
// general functions

function login()
{
    global $page, $session;

    $errorMessage = [];
    if (isset($_POST['password'])) {
        if (checkPassword($_POST['password'])) {
            $session->set('isValidLogin', 'true');
        } else {
            $session->pull('isValidLogin');
            $errorMessage = [
                'title' => uniqid('err_') . ' ⚡🤖',
                // 'description' => 'Your login information is NOT correct.',
            ];
            $page->assign('error', $errorMessage);
            $page->render('Login');
            exit();
        }
        header('Location: index.php');
    }

    $page->render('Login');
}

function checkPassword($plainTextPassword)
{
    global $config;
    return password_verify($plainTextPassword, $config->login['password']);
}

function generatePasswordHash($plainTextPassword)
{
    return password_hash($plainTextPassword, PASSWORD_BCRYPT);
}

function uploadMedia(): string|bool
{
    global $config, $validUser;

    $allowedUploadMimeTypes = [
        'image/gif',
        'image/jpeg',
        'image/png',
        'image/svg+xml',
        'image/webp',
    ];

    if ($validUser and isset($_FILES['file'])) {
        $mimes = new \Mimey\MimeTypes;
        $uploadFileName = basename($_FILES['file']['name']);
        $uploadFileMimeType = $_FILES['file']['type'];
        $mediaStoreFolder = rtrim($config->settings['mediaUploadFolder'], '/') . '/';
        if (in_array($uploadFileMimeType, $allowedUploadMimeTypes)) {
            $storeFileName = strtoupper(substr(Base32::encode('media' . date('dmYHis') . $uploadFileName), -16)) . '.' . $mimes->getExtension($uploadFileMimeType);
            $storeFileFullPathAndFileName = $mediaStoreFolder . $storeFileName;
            move_uploaded_file($_FILES['file']['tmp_name'], $storeFileFullPathAndFileName);
            $publicUrl = rtrim($config->settings['publicMediaFolderUrl'], '/') . '/' . $storeFileName;
            return $publicUrl;
        }
    }
    return false;
}

function postEntry($message)
{
    global $config, $validUser;

    if ($validUser) {
        if (file_exists($config->settings['twtfile'])) {
            if (mb_strlen($message) > 0) {
                $now = new DateTime();
                if (isset($config->settings['timezone'])) {
                    $now->setTimezone(new DateTimeZone($config->settings['timezone']));
                }
                $messageLine = "\n" . $now->format(DateTime::RFC3339) . "\t" . mb_ereg_replace("\n", "\u{2028}", $message);
                return file_put_contents(
                    $config->settings['twtfile'],
                    $messageLine,
                    FILE_APPEND | LOCK_EX
                );
            } // else {die ('empty message');}
        } // else {die('file not found:' . $config->settings['twtfile']);}
    } // else {die('invalid user');}
    return false;
}

function showStaticDocument($document)
{
    global $config, $language, $validUser, $selectedLanguage;
    if ($document) {
        $documentFullFilenameAndPath = './Resources/Private/Static/' . $document . '.md';
        if (file_exists($documentFullFilenameAndPath)) {
            $documentContent = file_get_contents($documentFullFilenameAndPath);
            $pd = new Twtxt\TwtxtParsedown;
            $pd->setSafeMode(false);
            $pd->setBreaksEnabled(true);
            $pd->setMarkupEscaped(false);
            $renderedDocumentContent = $pd->text($documentContent);
            $page = new FluidPage\Page(action: 'Document', language: $language);
            $page->assign('languageSelector', ['current' => $selectedLanguage, 'available' => $language->getAvailableLanguages()]);
            $page->assign('validUser', $validUser);
            $page->render($renderedDocumentContent);
            exit;
        }
    }
}