# TwtxtReader

Diese Software/Anwendung trägt den Arbeitstitel "TwtxtReader".

Es handelst sich hierbei um eine webbasierte Client-Software für den dezentralisierten, minimalistischen Mircoblogging Dienst "[Twtxt](https://twtxt.readthedocs.io/)".

### Unterstützte Twtxt-Erweiterungen
Die Standardspezifikation von Twtxt ist um einige Funktionen erweitert worden. Diese Anwendung unterstützt folgende Twtxt-Erweiterungen:

- [TwtHash](https://twtxt.dev/exts/twt-hash.html)
- [Multiline](https://twtxt.dev/exts/multiline.html)
- [User-Agent](https://twtxt.dev/exts/multiuser-user-agent.html)
- [Metadata](https://twtxt.dev/exts/metadata.html)

Eine Liste aller verfügbaren Erweiterungen für Twtxt existiert hier: https://twtxt.dev/extensions.html

## 👓 Voraussetzungen
Der TwtxtReader ist eine PHP-Webanwendung und sie sollte mit `PHP >= 8.2` und einem Apache Webserver (`.htaccess` Dateien) lauffähig sein. Es wird keine Datenbank benötigt.

Für das Deployment sind `composer` und `git` notwendig.

### PHP Extensions
- cURL: Um externe Twtxt-Dateien zu laden.
- Sodium: Für das Berechnen der TwHashes wird die Kryptografische Erweiterung "[Sodium](https://www.php.net/manual/de/book.sodium.php)" verwendet.

## 🛠 Installation & Konfiguration

### Externe Bibliotheken / Deployment

Dieses Repository verwendet Submodule für die externen Pakete: `fontawesome`, `htmx` und `picocss`.
Sie werden in den jeweiligen Ordnern unter `external/` eingebunden.

Die folgende Befehlskette klont dieses Repository in das aktuelle Verzeichnis `.`, legt die Submodule an und blendet unnötige Inhalte der Submodule aus. Anschließend werden per `composer` die verwendeten PHP-Bibliotheken geladen.

*Tipp:* Die folgenden Befehle an geeigneter Stelle kopieren und einfügen!

```shell
git clone https://github.com/upputter/TwtxtReader.git .
```
```shell
git submodule update --init
```
```shell
git -C external/pico sparse-checkout set /css | git -C external/htmx sparse-checkout set /dist
```
```shell
composer install --ignore-platform-reqs
```

### Konfiguration
Die Beispiel-Konfiurationsdatei sollte `private/config-example.ini` in  `private/config.ini` umbenannt und die notwendigen inhaltlichen Anpassungen vorgenommen werden.

### Ende
Die Dateien können nach dem Deployment-Prozess über einen Webserver bereitgestellt werden.


## 📚 Verwendete Bibliotheken und Software
Die folgende Bibliotheken und Softwarepakete werden in dieser Anwendung genutzt:

- [Base32 Class](https://github.com/bbars/utils/blob/master/php-base32-encode-decode/Base32.php)
- [bigskysoftware/htmx  - AJAX Schnittstelle](https://github.com/bigskysoftware/htmx)
- [erusev/parsedown - Markdown](https://github.com/erusev/parsedown)
- [Font Awesome - Icons](https://fontawesome.com)
- [josantonius/session - Sessionverwaltung](https://github.com/josantonius/php-session)
- [LC-emoji-picker - Emojis](https://github.com/LCweb-ita/LC-emoji-picker/)
- [Pico - CSS Framework](https://picocss.com/)
- [phpfastcache/phpfastcache - Caching](https://github.com/PHPSocialNetwork/phpfastcache)
- [Simple & Lightweight PHP YAML Class](https://github.com/eriknyk/Yaml)
- [spotlight - lightbox gallery](https://github.com/nextapps-de/spotlight)
- [tiny-markdown-editor - JavaScript Markdown Editor](https://github.com/jefago/tiny-markdown-editor)
- [typo3fluid/fluid - Templating Engine](https://github.com/TYPO3/Fluid)
- [xantios/mimey - MIME Types](https://github.com/Xantios/mimey)

## 💡 Inspiration

Dieses Projekt ist inspiriert durch ...

 * https://github.com/sorenpeter/timeline
 * https://yarn.social/

... und die tollen Menschen im Twtxt-Universum ♥.
