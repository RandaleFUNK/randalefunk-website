# Security-Header: Vorbereitung D

Stand: 8. September 2026. Nur lokaler Entwurf; kein Deploy und keine Live-Prüfung.

## Vorbereitete Regeln

- Root-.htaccess: X-Content-Type-Options: nosniff.
- Referrer-Policy: strict-origin-when-cross-origin.
- Permissions-Policy: camera=(), microphone=(), geolocation=(). Autoplay, Fullscreen, Audioausgabe und Picture-in-Picture werden nicht zusätzlich eingeschränkt.
- X-Frame-Options: SAMEORIGIN. Eigene Seiten dürfen auf derselben Origin eingebettet werden; fremde Seiten dürfen RandaleFUNK-Seiten nicht einrahmen. Das verbietet nicht das Laden eines YouTube-Players auf RandaleFUNK.
- Alle Antworten unter stats/ sowie monthly-polls-admin.php und kolumnen/wahlaufruf-vorschau.php: Cache-Control: private, no-store, max-age=0, must-revalidate; Pragma: no-cache; Expires: 0. Auch Fehlerantworten werden berücksichtigt.
- Admin und Wahlaufruf-Vorschau: Referrer-Policy: no-referrer. So wird eine Vorschau-Adresse mit Zugangsschlüssel nicht als Referrer weitergegeben. Bereits entstandene Logs oder Browser-Verläufe werden dadurch nicht entfernt.

Die vorhandenen Sperren für stats/config.php, stats/lib.php und stats/auth.php sowie die Weitergabe des Authorization-Headers bleiben unverändert. Keine PHP- oder Authentifizierungslogik geändert. Keine globale Cache-Sperre für öffentliche Artikel, Medien oder die Würfel-App.

Header werden in der onsuccess-Tabelle entfernt und in der always-Tabelle gesetzt, um doppelte Werte aus Apache/PHP-FCGI zu vermeiden. IfModule verhindert unbekannte Header-Direktiven ohne mod_headers, bedeutet dann aber auch: diese Header werden nicht gesetzt.

## Bewusst nicht aktiv

### CSP

Weder harte CSP noch Report-Only wird jetzt ausgeliefert. Keine externen Reporting-Dienste.

Für einen späteren, separat freizugebenden ersten Report-Only-Test kommt eine begrenzte Policy in Betracht:

    Content-Security-Policy-Report-Only: base-uri 'self'; object-src 'none'; frame-ancestors 'self'

Das ist ausdrücklich keine vollständige Ressourcen-CSP. Ohne Reporting-Endpunkt wären Hinweise nur im Browser zu prüfen. Eine spätere Ressourcen-Policy muss Inline-Skripte und -Stile, youtube-nocookie.com, gegebenenfalls Spotify-Player, lokale Fonts/Medien, die Würfel-App einschließlich Service Worker sowie gleichursprüngliche PHP-Anfragen berücksichtigen. Keine pauschalen Wildcards oder vorschnelle default-src-Sperre. Nonces/Hashes wären ein eigener Arbeitsschritt.

### HSTS

Kein Strict-Transport-Security-Header, kein preload, kein includeSubDomains.

Vor einer späteren Aktivierung müssen bestätigt sein:

1. HTTPS und gültige Zertifikate für den Hauptauftritt mit und ohne www.
2. Funktionierende HTTP-zu-HTTPS-Weiterleitung, einschließlich relevanter Unterseiten.
3. Zuverlässige Zertifikatserneuerung und keine erforderlichen reinen HTTP-Ressourcen.
4. Zuständigkeit für alle betroffenen Hosts und ein dokumentierter Rücknahmeplan; Browser behalten HSTS bis zum Ablauf.
5. Zunächst kurze max-age-Laufzeit und erneute Prüfung. includeSubDomains und preload bleiben außerhalb dieses Vorschlags und brauchen eigene Freigabe.

### MultiViews

Options -MultiViews ist nur auskommentiert vorbereitet. Aktivierung erst, wenn United Domains diese Options-Direktive in .htaccess ausdrücklich erlaubt. Andernfalls könnte ein HTTP-500-Fehler den Auftritt treffen. Keine Rewrite-Änderung.

## Vor einer späteren Veröffentlichung offen

- Tatsächlicher Webserver, mod_headers und AllowOverride FileInfo beziehungsweise entsprechende AllowOverrideList müssen bestätigt werden. IfModule schützt nicht vor einer verbotenen Header-Direktive.
- Bestehende serverseitige Root-Konfiguration ist unbekannt und darf nicht blind überschrieben werden. Header-Vererbung und vorhandene Hoster-Header abgleichen.
- Die neue Root-.htaccess muss gezielt in das spätere Deploy-Paket gelangen. Die Deploy-Allowlist wurde in diesem Block weder erneut geprüft noch verändert; ihre Aufnahme ist deshalb nicht bestätigt. Vor Deploy separat klären.
- Korrekte MIME-Typen für JavaScript, CSS, Fonts, Medien und Service Worker sind Voraussetzung für nosniff.
- SAMEORIGIN unterscheidet www und ohne www. Interne Einbettungen müssen dieselbe Origin verwenden; eventuelle gewünschte externe Einrahmung wäre vorher zu klären.
- Root-Regeln können in Unterordner wie die bewusst öffentliche LeonBau-Vorschau vererbt werden. Es wird kein Passwortschutz hinzugefügt; Inhalte und separater Workflow bleiben unverändert. Mögliche gewünschte externe Einbettung separat bestätigen.
- No-store verhindert keine bereits vorhandenen Kopien, Downloads oder Browser-Screenshots. Der Offline-Cache der Würfel-App wird nicht geleert oder umgebaut.

## Testumfang

Statische Prüfung der Apache-Direktiven und Blockstruktur, der erwarteten Header und der unveränderten Stats-Sperren. Keine neue Laufzeit installieren, kein Live-Abruf, keine Datenbankprüfung. Ein echter Apache-Konfigurations- und Auslieferungstest unter den Hoster-Einstellungen steht noch aus.

Nach eigener Freigabe gezielt prüfen: 200/401/403-Antworten, Header-Duplikate, Stats/Admin-no-store, lokale Ressourcen, YouTube-Fullscreen und Würfel-App/Offline-Modus. Das ist jetzt noch nicht ausgeführt.

Quellen: [Apache mod_headers](https://httpd.apache.org/docs/2.4/mod/mod_headers.html), [Apache .htaccess](https://httpd.apache.org/docs/2.4/howto/htaccess.html), [Permissions Policy](https://developer.mozilla.org/en-US/docs/Web/HTTP/Guides/Permissions_Policy).
