# Informationen zur Software
*( Only in german! `:(` )*

Diese Software/Anwendung trägt den Arbeitstitel "TwtxtReader".

Es handelst sich hierbei um eine Client-Software für den dezentralisierten, minimalistischen Mircoblogging Dienst "[Twtxt](https://twtxt.readthedocs.io/)".

## Was macht die Anwendung?
Die Anwendung liest eine angegebene "eigene" Twtxt-Datei aus und bereitet deren Inhalte auf.
Aus den "Meta"-Informationen der Twtxt-Datei werden Informationen zum Anzeigen eines möglichen Profils ausgelesen: `nick`, `url`, `avatar`, `description`, `link` und `follow`. Die eigentlichen Inhalte (Beiträge/Posts/Tweets - wie man es auch nennt) ebenso.

Um ein umfangreiches Bild der Microblogging-Blase aus der Twtxt-Datei zu erhalten, werden die abonierten externen Twtxt-Dateien (`follow`) ebenfalls eingelesen. Alle Informationen werden chronologisch sortiert und in einer Zeitachse/Timeline präsentiert. Es kann nach Beiträgen, Antworten auf Beiträge, Erwähnungen und Unterhaltungen gefiltert werden.

Es können neue Beiträge an die eigene Twtxt-Datei hinzugefügt werden (es kann auf die Beiträge in externen Twtxt-Dateien geantwortet werden).

## Unterstützte Twtxt-Erweiterungen
Die Standardspezifikation von Twtxt ist um einige Funktionen erweitert worden. Diese Anwendung unterstützt folgende Erweiterungen:

- [TwtHash](https://twtxt.dev/exts/twt-hash.html)
- [Multiline](https://twtxt.dev/exts/multiline.html)
- [User-Agent](https://twtxt.dev/exts/multiuser-user-agent.html)
- [Metadata](https://twtxt.dev/exts/metadata.html)

Eine Liste aller verfügbaren Erweiterungen für Twtxt existiert hier: https://twtxt.dev/extensions.html

## Unter der Haube
Diese Anwndung ist für die Darstellung auf mobilen Endgeräten optimiert. Sie verwendet `HTML`, `CSS`, `JavsScript` und `PHP`. Sie kommt ohne Datenbank aus: Alle Informationen werden aus den zwischengespeicherten (externen) Twtxt-Dateien geladen.

Es wurde versucht dem "Model View Controller"-Konzept zu folgen. Die Daten (`Model`) werden aus den Twtxt-Daten aufbereitet und in der Templating Engine dargestellt (`View`). Verwaltet wird das Zusammenspiel von Daten und Template über eine zentrale Datei (`Controller`).

### Sprache
Es werden standardmäßig **Deutsch** und **Englisch** als Sprachen unterstützt. Dabei geht es um die Bezeichnung von Feldern, Funktionen, Knöpfen und Navigationselementen. Die Übersetzung jeder Sprache wird je in einer `YAML`-Datei gepflegt. Über passende ViewHelper kann im Template unkompliziert auf die Sprach-Platzhalter zugegriffen werden.

### Verwendete Bibliotheken und Software
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
- [tiny-markdown-editor - JavaScript Markdown Editor](https://github.com/jefago/tiny-markdown-editor)
- [typo3fluid/fluid - Templating Engine](https://github.com/TYPO3/Fluid)
- [xantios/mimey - MIME Types](https://github.com/Xantios/mimey)

### Erweiterungen
Die o.g. verwendeten Bibliotheken wurden teilweise um einige Funktionen und Verhaltensweisen erweitert.

- `Phpfastcache\Drivers\TwtxtFiles`: Um den Cache bedingt statisch zu halten.
- `Twtxt\Parsers`: Zur Individualisierung der Darstellung von Twtxt-Inhalten.
  - `Twtxt\Parsers\HtmlEntities`: Fehlerhaftes HTML ersetzen
  - `Twtxt\Parsers\iFrameVideo`: Videos aus Nachrichtenquellen als iFrame einbinden (`![iframe](URL)`)
  - `Twtxt\Parsers\ImageLinkToMarkdown`: Text-Links auf Bild-Dateien werden als Bilder dargestellt
  - `Twtxt\Parsers\MaskHashtags`: Den Markdwon-Parser davor schützen Überschriften zu rendern
  - `Twtxt\Parsers\TwtxtMention`: Erwähnungen in Beiträgen richtig darstellen
  - `Twtxt\Parsers\VideoLinkToPlayer`: Text-Links von Video-Dateien werden als HTML-Video-Element dargestellt
  - `Twtxt\Parsers\Youtube`: Text-Links auf YouTube-Videos werden als DSGVO-konforme iFrames dargestellt
- `TwtxtParsedown`: Um externe Link-Tags mit einem Ziel (`target`) auszustatten.

---
Stand: 29.01.2025
