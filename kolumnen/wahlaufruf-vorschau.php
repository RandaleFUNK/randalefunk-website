<?php
declare(strict_types=1);

const WAHLVORSCHAU_TOKEN_HASH = 'e00c5d7cfaef7578b3cd6253493166445006c3adb7c60d9d1f6453951843cb2c';

$zugang = isset($_GET['zugang']) && is_string($_GET['zugang']) ? $_GET['zugang'] : '';
$zugangErlaubt = $zugang !== '' && hash_equals(WAHLVORSCHAU_TOKEN_HASH, hash('sha256', $zugang));

header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet');
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');

if (!$zugangErlaubt) {
    http_response_code(404);
    ?>
<!doctype html>
<html lang="de">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Seite nicht gefunden - RandaleFUNK.de</title>
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <style>
      body { margin: 0; background: #111; color: #eee; font: 700 18px/1.5 Arial, sans-serif; }
      main { width: min(100% - 40px, 680px); margin: 12vh auto 0; }
      a { color: #e5583e; }
    </style>
  </head>
  <body>
    <main>
      <h1>404. Hier gibt es nichts zu sehen.</h1>
      <p>Entweder ist der Link unvollständig oder diese Seite möchte gerade nicht mit dir reden.</p>
      <p><a href="../index.html">Zurück zu RandaleFUNK.de</a></p>
    </main>
  </body>
</html>
    <?php
    exit;
}
?>
<!doctype html>
<html lang="de">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fick das System. Aber geht wählen. - RandaleFUNK.de</title>
    <meta name="description" content="Warum RandaleFUNK vor der Landtagswahl in Sachsen-Anhalt auf Eis liegt.">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <link rel="icon" href="../assets/favicon/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" href="../assets/favicon/apple-touch-icon.png">
    <link rel="stylesheet" href="../style.css?v=20260820-riot-shop">
    <link rel="stylesheet" href="wahlaufruf-vorschau.css?v=20260829-1">
  </head>
  <body class="election-preview-page">
    <div class="category-strip" aria-label="RandaleFUNK Vorschau">
      RANDALEFUNK · NICHT ÖFFENTLICHE VORSCHAU
    </div>

    <header class="site-header election-preview-header" aria-label="RandaleFUNK Kopfbereich">
      <a class="brand" href="../index.html" aria-label="Zur normalen RandaleFUNK-Startseite">
        <img src="../assets/randalefunk-logo.png" alt="RandaleFUNK.de Logo" width="160" height="160">
      </a>
      <p class="tagline">Irgendwas mit Punk seit 2022</p>
    </header>

    <main class="election-preview-shell">
      <div class="election-preview-actions election-preview-actions--top">
        <a class="election-preview-exit" href="../index.html">Kein Bock auf den Text? Zum normalen RandaleFUNK</a>
      </div>

      <section class="election-preview-board">
        <article class="review-article column-article election-column">
          <p class="issue-label">Interne Vorschau · Wahlaufruf</p>

          <aside class="election-support-note" aria-label="Hinweis zur Beteiligung von Bands und Musikern">
            <p class="election-support-note__lead"><strong>Bevor hier wieder jemand Unterschriften unter Dinge setzt, die niemand unterschrieben hat:</strong></p>
            <p>Die Bands und Musiker, die einen Song zu dieser Aktion beitragen, machen das aus unterschiedlichen Gründen. Einige sagen: <strong>Geht wählen. FCK AFD.</strong> Andere sagen einfach nur: <strong>FCK AFD.</strong></p>
            <p>Damit unterschreibt niemand automatisch meinen Text. Niemand muss meine Sicht auf Demokratie teilen, meine anarchistischen Gedanken gut finden oder jeden Satz hier abnicken. Der Artikel darunter ist meiner. Für den Scheiß bin auch ich verantwortlich.</p>
            <p>Der gemeinsame Nenner ist kleiner und völlig ausreichend: <strong>Wir wollen keine AfD an der Macht.</strong></p>
          </aside>

          <section class="election-playlist" aria-labelledby="election-playlist-title">
            <div class="election-playlist__intro">
              <img class="election-playlist__cover" src="../assets/kolumnen/fck-afd-randalefunk-playlist-cover-v2.png" alt="Randalf mit erhobener Faust und FCK-AFD-Shirt vor dem RandaleFUNK-Schriftzug" width="1536" height="1536">
              <div class="election-playlist__copy">
                <p class="election-playlist__kicker">Die Bands zum Aufruf</p>
                <h2 id="election-playlist-title">FCK AFD – RandaleFUNK</h2>
                <p>Von Bands und Musikern ausgewählte Songs zur Landtagswahl in Sachsen-Anhalt 2026. Unterschiedliche Gründe, ein gemeinsamer Nenner: <strong>Keine AfD an der Macht.</strong></p>
                <a class="election-playlist__spotify-link" href="https://open.spotify.com/playlist/0Yn7Q7Jtq2B6rHUC3997wY" target="_blank" rel="noopener noreferrer">Playlist direkt bei Spotify öffnen</a>
              </div>
            </div>

            <div class="election-playlist__embed" id="spotify-playlist-embed">
              <div class="election-playlist__consent" id="spotify-playlist-consent">
                <p><strong>Spotify-Playlist hier anhören</strong></p>
                <p>Beim Laden des Players wird eine Verbindung zu Spotify hergestellt.</p>
                <button class="election-playlist__load" id="spotify-playlist-load" type="button">Spotify-Playlist laden</button>
              </div>
            </div>
            <noscript>
              <p class="election-playlist__noscript">Ohne JavaScript bleibt der direkte Link zu Spotify oben erhalten.</p>
            </noscript>
          </section>

          <div class="election-article">
            <h1>Fick das System. Aber geht wählen.</h1>
            <p class="lead">Warum RandaleFUNK vor der Landtagswahl in Sachsen-Anhalt auf Eis liegt.</p>

            <p>„Wer schweigt, stimmt zu“, heißt es.</p>
            <p>Ob der Satz immer stimmt, sei dahingestellt. Manchmal schweigt man schließlich auch, weil man keine Ahnung hat, weil man erst nachdenken möchte oder weil einem das Gegenüber so sehr auf den Sack geht, dass jedes weitere Wort reine Verschwendung wäre.</p>
            <p>Diesmal möchte ich aber nicht schweigen.</p>
            <p>Deshalb wird RandaleFUNK vor der Landtagswahl in Sachsen-Anhalt für ein paar Tage auf Eis liegen. Es erscheinen keine neuen News, Reviews, Interviews oder Kolumnen. Die bisherigen Artikel bleiben selbstverständlich erreichbar. Die Bands, über die hier geschrieben wurde, können schließlich nichts für meinen politischen Dachschaden und müssen deshalb auch nicht vorübergehend aus dem Internet verschwinden.</p>
            <p>Nur die Startseite bleibt bis nach der Wahl am <strong>6. September 2026</strong> diese hier.</p>
            <p>Absichtlich.</p>
            <p>Musik ist nicht plötzlich unwichtig geworden. Gerade gibt es aber etwas, das ich nicht zwischen zwei Plattenkritiken und einer Konzertmeldung hindurchrutschen lassen möchte.</p>
            <p>Andere Menschen können Protestlieder schreiben. Na gut, nicht immer. Aber sie versuchen es wenigstens.</p>
            <p>Und nicht nur Punkbands schreiben Songs gegen Nazis. Dieses Genre haben wir nicht gepachtet. Antifaschistische Lieder gibt es im Hardcore, Metal, Hip-Hop, Folk, Mittelalter und vermutlich sogar irgendwo im Free Jazz. Dort erkenne ich allerdings nicht, wann das Lied angefangen hat und ob der Saxofonist gerade protestiert oder nur sein Instrument eine Treppe hinunterwirft.</p>
            <p>Ich habe keine Band und ganz sicher keine Gesangsstimme, die man unbewaffnet auf Menschen loslassen sollte. Was ich habe, ist RandaleFUNK: eine Website, ein kleines Mikrofon und offenbar tatsächlich ein paar Leute, die lesen, was ich hier hinschreibe.</p>
            <p>Also benutze ich genau das.</p>

            <h2>Ich traue dem Demokratie-Dingsbums nicht</h2>
            <p>Ich werde euch hier nicht erzählen, dass ich ein begeisterter Anhänger unserer repräsentativen Demokratie bin.</p>
            <p>Auf keinen Fall.</p>
            <p>Das ist keine Punkrock-Pose für Menschen, die „Anarchie“ auf ihre Jacke schreiben und anschließend im Kleingartenverein über die Heckenhöhe abstimmen. Ich habe tatsächlich ein Problem mit einem System, in dem wir regelmäßig Menschen auswählen, denen wir danach erhebliche Macht geben, Entscheidungen über das Leben anderer zu treffen.</p>
            <p>Alle paar Jahre darf ich ein Kreuz machen. Danach entscheiden andere.</p>
            <p>Das hat für mich ein Geschmäckle.</p>
            <p>Für mich bedeutet Anarchismus allerdings nicht Chaos, brennende Mülltonnen oder die Ablehnung jeder Form von Organisation. Ich misstraue Herrschaft, erzwungener Hierarchie und Autorität, die sich allein auf Amt, Uniform, Besitz oder Titel beruft. Wissen und Erfahrung können Autorität begründen. Ein Namensschild an einer Bürotür kann das nicht automatisch.</p>
            <p>Entscheidungen sollten möglichst dort getroffen werden, wo die Menschen von ihnen betroffen sind. Macht sollte verteilt, begrenzt und überprüfbar sein. Zusammenarbeit sollte freiwillig entstehen, Solidarität nicht erst dann beginnen, wenn ein Ministerium dafür ein Formular entworfen hat.</p>
            <p>Und nein, ich habe die perfekte Alternative nicht in meiner leeren Bierflasche. Ich könnte euch zu fast jeder meiner Vorstellungen nach fünf Minuten selbst erklären, wo die nächsten Probleme anfangen. Gesellschaft ist komplizierter als ein Aufkleber auf einer Laterne. Wer eine vollkommen fehlerfreie Lösung verspricht, verkauft meistens entweder eine Religion, ein Coachingseminar oder ein Parteiprogramm.</p>
            <p>Meine Schwierigkeiten mit Mehrheitsentscheidungen lassen sich mit einem vollkommen bescheuerten Beispiel erklären: Stellt euch vor, 51 Prozent stimmen dafür, die übrigen 49 Prozent aufzuessen. Die Abstimmung ist ordentlich gelaufen, die Mehrheit steht und trotzdem dürfte spätestens beim Nachtisch auffallen, dass hier etwas grundsätzlich schiefläuft.</p>
            <p>Eine Mehrheit macht eine Entscheidung nicht automatisch richtig, gerecht oder moralisch. Menschenrechte werden nicht dadurch unwichtig, dass genügend Leute dagegen stimmen. Freiheit verliert ihren Wert nicht, weil eine Mehrheit sie einer Minderheit wegnehmen möchte. Und Herrschaft fühlt sich für diejenigen, über die geherrscht wird, nicht plötzlich besser an, nur weil vorher ordnungsgemäß ein Wahlzettel ausgefüllt wurde.</p>
            <p>Deshalb bin ich skeptisch.</p>

            <h2>Und trotzdem: Geht wählen</h2>
            <p>Meine Kritik an diesem System lässt seine Konsequenzen nicht verschwinden.</p>
            <p>Ich kann das Spielfeld scheiße finden. Das Spiel findet trotzdem statt. Anschließend bekommen Menschen reale politische Macht. Sie entscheiden über Schulen, Polizei, Behörden, Kulturförderung, politische Bildung, Rundfunkverträge, öffentliche Gelder und über Strukturen, von denen andere Menschen abhängig sind.</p>
            <p>Meine grundsätzliche Ablehnung von Herrschaft schützt niemanden vor den Entscheidungen derjenigen, die anschließend herrschen.</p>
            <p>Deshalb ist mir verdammt noch mal nicht egal, wer diese Macht bekommt.</p>
            <p>Am <strong>6. September 2026</strong> wird in Sachsen-Anhalt ein neuer Landtag gewählt. Nach den jüngsten großen Erhebungen liegt die AfD deutlich vorn: Die ARD-Erhebung von Infratest dimap sah sie am 26. August bei <strong>42 Prozent</strong>, die Forschungsgruppe Wahlen für das ZDF am 28. August bei <strong>40 Prozent</strong>. Das sind Umfragen, keine Wahlergebnisse. Beide Erhebungen sahen keine sichere absolute Mehrheit für die AfD, und rund um die Fünf-Prozent-Hürde kann sich die Sitzverteilung erheblich verändern.</p>
            <p>Eine AfD-geführte Landesregierung ist damit nicht automatisch beschlossen. Sie ist aber auch kein vollkommen theoretisches Gedankenspiel mehr.</p>
            <p>Der Ministerpräsident wird vom Landtag gewählt. In den ersten beiden Wahlgängen braucht er die Mehrheit aller Mitglieder; in einem möglichen späteren Wahlgang genügt die Mehrheit der abgegebenen Stimmen. Was nach der Wahl passiert, hängt deshalb nicht nur vom stärksten Balken am Fernsehbildschirm ab, sondern auch von der tatsächlichen Sitzverteilung, möglichen Bündnissen, Enthaltungen und den Entscheidungen der Abgeordneten.</p>
            <p>Kurz gesagt: Niemand weiß heute sicher, wer nach der Wahl regiert.</p>
            <p>Aber wir wissen, worüber abgestimmt wird.</p>

            <h2>Man muss der AfD nichts andichten</h2>
            <p>Der AfD-Landesverband Sachsen-Anhalt wird vom Landesverfassungsschutz seit 2023 als <strong>gesichert rechtsextremistische Bestrebung</strong> eingestuft. Die Behörde begründet das unter anderem mit Angriffen auf die Menschenwürde und das Demokratieprinzip sowie mit einem völkisch-abstammungsmäßigen Volksbegriff. Die AfD weist solche Bewertungen zurück.</p>
            <p>Für meine Ablehnung brauche ich trotzdem weder Glaskugel noch Schauergeschichte. Es reicht, das Regierungsprogramm der Partei zu lesen.</p>
            <p>Dort kündigt die AfD unter anderem an, mit Steuergeld „vorwiegend solche Kunst“ fördern zu wollen, die zur „deutschen Identitätsfindung“ beiträgt. Als Vorbild und Inspiration nennt das Programm ausdrücklich die kulturpolitische Wende unter Viktor Orbán in Ungarn.</p>
            <p>Die Landeszentrale für politische Bildung bezeichnet die Partei als „linke Indoktrinationsanstalt“. Sie soll in ihrer heutigen Form abgeschafft und durch ein „Landesinstitut für staatspolitische Bildung und kulturelle Identität“ ersetzt werden.</p>
            <p>Auch bei den Medien wird es konkret. Die Rundfunkstaatsverträge sollen laut Programm als erste Amtshandlung gekündigt werden. Beim MDR würde das nicht bedeuten, dass am Montagmorgen jemand den Stecker aus Sachsen-Anhalt zieht. Eine Kündigung hätte eine Frist von zwei Jahren; nach aktueller Rechtslage müssten außerdem staatsferner öffentlich-rechtlicher Rundfunk und dessen Finanzierung weiterhin verfassungsgemäß organisiert werden. Aber die Landesprogramme von MDR Sachsen-Anhalt könnten nach einer wirksamen Kündigung in ihrer heutigen Form verschwinden.</p>
            <p>Das sind keine Behauptungen irgendeines linken Fanzines. Das sind angekündigte Ziele aus dem Programm der Partei und die rechtliche Einordnung der zuständigen Stellen.</p>
            <p>Natürlich darf man den öffentlich-rechtlichen Rundfunk kritisieren. Ich tue das selbst. Man darf Kulturförderung hinterfragen, Behörden umbauen und politische Bildung prüfen. Autorität muss sich begründen lassen, auch wenn sie „Landeszentrale“ oder „MDR“ heißt.</p>
            <p>Aber Kritik ist nicht dasselbe wie der Versuch, eine politische Bildungsinstitution durch ein Institut für „kulturelle Identität“ zu ersetzen. Reform ist nicht dasselbe wie eine Förderpolitik, die Kunst danach sortiert, ob sie einer staatlich gewünschten deutschen Identität dient. Und Medienkritik ist nicht dasselbe wie die Drohung, Verträge zu kündigen, bis das System politisch in die gewünschte Richtung gedrückt wird.</p>
            <p>An diesem Punkt beginnt meine Sorge.</p>
            <p>Nicht bei der albernen Vorstellung, am Morgen nach der Wahl stehe ein Rollkommando vor meinem kleinen Fanzine. RandaleFUNK ist dafür wahrscheinlich nicht einmal wichtig genug, um in einem besonders gelangweilten Ministerium auf einer Excel-Liste zu landen.</p>
            <p>Meine Sorge beginnt früher: bei Förderentscheidungen, Behörden, Schulen, Kulturhäusern, Jugendzentren, politischer Bildung und unabhängigen Medien. Politische Macht entscheidet mit darüber, welche Räume größer werden und welche langsam austrocknen. Sie beeinflusst auch die Sprache, mit der eine Regierung über Menschen spricht und welche Gruppen sie überhaupt als selbstverständlich zugehörig betrachtet.</p>
            <p>Deshalb sage ich es ohne Verrenkungen:</p>
            <p>Ich möchte nicht, dass die AfD politische Macht in Sachsen-Anhalt übernimmt.</p>
            <p>RandaleFUNK ist ein linkes Punk-Fanzine. Wer jetzt vollkommen schockiert vom Stuhl fällt, hat in den vergangenen Jahren vermutlich eine andere Website gelesen.</p>

            <h2>Euer Kreuz gehört euch</h2>
            <p>Ich habe keine Partei für euch, weil ich mich aktuell von keiner Partei vertreten fühle. Und ich werde euch auch nicht erzählen, ihr müsstet unbedingt „strategisch“ wählen. Dieses Argument kotzt mich schon lange an.</p>
            <p>Trotzdem gehört zur Wahrheit: Parteien, die nicht mehr als fünf Prozent der gültigen Zweitstimmen erreichen und kein Direktmandat gewinnen, werden bei der Sitzverteilung nicht berücksichtigt. Das kann die Mehrheitsverhältnisse im Landtag verändern. Wer euch taktisches Wählen empfiehlt, erfindet diese Rechnung also nicht vollständig.</p>
            <p>Aber die Entscheidung bleibt eure.</p>
            <p>Wenn ihr eine kleine Partei gut findet, schaut sie euch an. Lest ihr Programm. Prüft, wer dahintersteht, und schaut im Zweifel zweimal hin, ob sich hinter einem harmlos klingenden Namen nicht doch irgendwelche Vollpfosten verstecken. Wenn ihr danach sagt: „Die da vertritt meine Überzeugungen am ehesten“, dann wählt sie.</p>
            <p>Wenn ihr taktisch wählen möchtet, tut es bewusst. Wenn ihr nach Überzeugung wählen möchtet, tut auch das bewusst. Euer Kreuz gehört euch, nicht mir, keinem Wahlkampfstrategen und keiner großen Partei, die es bereits als ihr Eigentum betrachtet.</p>
            <p>Ich sage euch nicht, wo ihr es machen sollt.</p>
            <p>Ich sage euch nur: Macht eines.</p>

            <h2>Ein Kreuz ist kein Widerstand im Komplettpaket</h2>
            <p>Wählen ersetzt keine politische Arbeit. Es ersetzt keinen Protest, keine Gewerkschaft, keine antifaschistische Initiative, kein unabhängiges Medium, kein Jugendzentrum und keine gegenseitige Hilfe. Es schützt keinen Menschen, wenn danach alle wieder fünf Jahre die Füße hochlegen und Demokratie für eine Dienstleistung halten, die sonntags zwischen Frühstück und Mittagessen erledigt wird.</p>
            <p>Ein Kreuz ist ein Werkzeug.</p>
            <p>Eines von vielen.</p>
            <p>Vielleicht ist der Widerspruch zwischen meiner anarchistischen Haltung und diesem Wahlaufruf deshalb kleiner, als er zunächst aussieht. Ich möchte möglichst wenig Herrschaft über Menschen. Darum kann mir nicht egal sein, wer die vorhandenen Instrumente dieser Herrschaft in die Hände bekommt.</p>
            <p>Autorität muss sich begründen lassen. Macht muss hinterfragt werden dürfen. Und Menschen müssen widersprechen können, zur Not mit den Füßen.</p>
            <p>Die Straße ist schließlich ebenfalls ein politischer Ort.</p>
            <p>Am 7. September wird Sachsen-Anhalt nicht plötzlich herrschaftsfrei sein. Der Kapitalismus löst sich nicht über Nacht auf, und kein Amt verschickt morgens einen freundlichen Brief mit dem Hinweis, dass die Bürokratie nach reiflicher Überlegung abgeschafft wurde.</p>
            <p>Schade eigentlich.</p>
            <p>Aber am 7. September werden Menschen wissen, wer im neuen Landtag sitzt. Diese Menschen werden Macht bekommen. Vielleicht weniger, als sie sich wünschen. Vielleicht mehr, als uns lieb sein kann.</p>
            <p>Darum liegt RandaleFUNK bis dahin auf Eis.</p>
            <p>Nicht als Schweigen, sondern als Unterbrechung. Ich möchte nicht am Dienstag über irgendeine neue Punkplatte schreiben, am Mittwoch ein Musikvideo posten und am Donnerstag so tun, als wäre das gerade eine vollkommen gewöhnliche Woche.</p>
            <p>Informiert euch. Lest Programme. Schaut euch Kandidaten an. Prüft Behauptungen. Lest nicht ausschließlich Medien, die euch ohnehin erzählen, was ihr hören möchtet. Fragt euch, wer etwas entscheidet, wem die Entscheidung nutzt und wer ihre Folgen tragen muss.</p>
            <p>Dann entscheidet selbst.</p>
            <p>Ich hatte dieses kleine Mikrofon. Also benutze ich es.</p>
            <p class="election-final-line"><strong>Fick das System. Aber denkt vorher darüber nach, wem ihr die Schlüssel dafür gebt.</strong></p>
            <p><strong>Burg<br>RandaleFUNK</strong></p>

            <p class="article-source-note"><strong>Quellencheck, Stand 29.08.2026:</strong> Wahltermin, Wahlsystem und Fünf-Prozent-Hürde: <a href="https://wahlen.sachsen-anhalt.de/zu-den-wahlen/landtagswahl/faq-zur-landtagswahl-2026" target="_blank" rel="noopener noreferrer">Landeswahlleiterin Sachsen-Anhalt</a>. Aktuelle Umfragen: <a href="https://www.infratest-dimap.de/umfragen-analysen/bundeslaender/sachsen-anhalt/laendertrend/2026/august/" target="_blank" rel="noopener noreferrer">Sachsen-AnhaltTREND von Infratest dimap</a> und <a href="https://presseportal.zdf.de/pressemitteilung/zdf-politbarometer-extra-i-sachsen-anhalt-august-2026" target="_blank" rel="noopener noreferrer">ZDF-Politbarometer Extra vom 28.08.2026</a>. Wahl des Ministerpräsidenten: <a href="https://www.landtag.sachsen-anhalt.de/fileadmin/Downloads/Broschueren/Verfassung_und_Grundgesetz_web_2025_10.pdf" target="_blank" rel="noopener noreferrer">Artikel 65 der Landesverfassung</a>. Einstufung des AfD-Landesverbandes: <a href="https://mi.sachsen-anhalt.de/verfassungsschutz/themenfelder/page/rechtsextremismus" target="_blank" rel="noopener noreferrer">Verfassungsschutz Sachsen-Anhalt</a>. Genannte Vorhaben: <a href="https://afd-regierungsprogramm.de/" target="_blank" rel="noopener noreferrer">Regierungsprogramm der AfD Sachsen-Anhalt 2026</a>. Mögliche Folgen einer Kündigung des MDR-Staatsvertrags: <a href="https://www.mdr.de/nachrichten/sachsen-anhalt/landtagswahl/afd-kuendigung-zukunft-mdr-108,rundfunkstaatsvertrag-oeffentlich-rechtlicher-rundfunk-100.html" target="_blank" rel="noopener noreferrer">MDR Sachsen-Anhalt</a>. Meinung, Schlussfolgerungen und politische Haltung sind meine.</p>
          </div>
        </article>
      </section>

      <div class="election-preview-actions election-preview-actions--bottom">
        <a class="election-preview-exit" href="../index.html">Zur normalen RandaleFUNK-Seite</a>
      </div>
    </main>

    <footer class="site-footer">
      <p>© <span id="current-year">2026</span> RandaleFUNK.de</p>
      <p>Interne Vorschau · nicht öffentlich verlinkt</p>
    </footer>

    <script>
      (() => {
        const loadButton = document.getElementById('spotify-playlist-load');
        const embedHost = document.getElementById('spotify-playlist-embed');

        if (!loadButton || !embedHost) return;

        loadButton.addEventListener('click', () => {
          const iframe = document.createElement('iframe');
          iframe.src = 'https://open.spotify.com/embed/playlist/0Yn7Q7Jtq2B6rHUC3997wY?utm_source=generator&theme=0';
          iframe.title = 'Spotify-Playlist: FCK AFD – RandaleFUNK';
          iframe.width = '100%';
          iframe.height = '500';
          iframe.loading = 'lazy';
          iframe.allow = 'autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture';
          iframe.referrerPolicy = 'strict-origin-when-cross-origin';
          iframe.setAttribute('allowfullscreen', '');

          embedHost.replaceChildren(iframe);
          embedHost.classList.add('is-loaded');
        }, { once: true });
      })();
    </script>
  </body>
</html>
