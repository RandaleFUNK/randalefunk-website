"""Build the public runtime package. Never upload or remove server files."""
import argparse
import html
from html.parser import HTMLParser
import re
import shutil
from pathlib import Path
from urllib.parse import unquote, urljoin, urlsplit

ROOT = Path(__file__).resolve().parents[1]
REQUIRED = {
    "index.html", "style.css", "script.js", "track.php", "poll.php",
    "monthly-polls.php", "monthly-polls-admin.php", "site.webmanifest",
    "robots.txt", "datenschutz.html", "warum-unterstuetzen.html", "der-rostige-kronkorken.html",
    "stats/index.php", "stats/auth.php", "stats/lib.php", "stats/config.php", "stats/.htaccess",
}
OPTIONAL = {".htaccess", "sitemap.xml", "404.html"}
MEDIA = {".png", ".jpg", ".jpeg", ".gif", ".webp", ".avif", ".svg", ".ico",
         ".woff", ".woff2", ".ttf", ".otf", ".mp3", ".wav", ".ogg", ".m4a",
         ".flac", ".mp4", ".webm", ".pdf"}
PUBLIC = {".html", ".css", ".js", ".json", ".webmanifest"} | MEDIA
DIRECTORIES = {
    "assets": MEDIA, "data": {".json"},
    **{name: PUBLIC for name in ("interviews", "kolumnen", "randalf", "reviews", "rotte", "vorab-gehoert", "wuerfel")},
}
WORK_FILE = re.compile(r"(?:^|[._-])(?:source|quelle|preview|vorschau|backup|bak|tmp)(?:[._-]|$)", re.I)
MARKER = b"<!-- generated-by: publish-reviews.ps1 -->"
TEXT = {".html", ".css", ".js", ".php", ".json", ".webmanifest", ".svg"}


def is_license(path):
    return path.suffix.lower() in {"", ".txt", ".md"} and (
        path.stem.lower().startswith(("license", "licence", "copying", "notice"))
        or path.stem.lower().endswith("-ofl")
    )


def runtime_files(root):
    missing = sorted(name for name in REQUIRED if not (root / name).is_file())
    if missing:
        raise ValueError("Missing required runtime files: " + ", ".join(missing))
    selected = set(REQUIRED) | {name for name in OPTIONAL if (root / name).is_file()}
    for folder, suffixes in DIRECTORIES.items():
        for path in (root / folder).rglob("*"):
            if not path.is_file():
                continue
            relative = path.relative_to(root)
            if any(part.startswith(".") for part in relative.parts) or WORK_FILE.search(path.name):
                continue
            if path.suffix.lower() in suffixes or is_license(path):
                selected.add(relative.as_posix())
    selected.update(path.name for path in root.iterdir() if path.is_file() and is_license(path))
    for name in selected:
        path = root / name
        if path.is_symlink() or not path.resolve().is_relative_to(root.resolve()):
            raise ValueError("Runtime symlink or path outside source: " + name)
    return sorted(selected)


class Links(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.values = []

    def handle_starttag(self, tag, attrs):
        for key, value in attrs:
            if not value:
                continue
            if key in {"href", "src", "poster", "action", "data-src", "data-poster"}:
                self.values.append(value)
            elif key == "srcset" and not value.startswith("data:"):
                self.values.extend(part.strip().split()[0] for part in value.split(",") if part.strip())
            elif tag == "meta" and key == "content" and value.startswith(("/", "https://randalefunk.de/", "https://www.randalefunk.de/")):
                self.values.append(value)


def references(root, names):
    result, missing = set(), set()
    for name in names:
        path = root / name
        if path.suffix.lower() not in TEXT:
            continue
        document = path.read_text(encoding="utf-8")
        values = []
        if path.suffix.lower() in {".html", ".php", ".svg"}:
            parser = Links()
            parser.feed(document)
            values.extend(parser.values)
        values.extend(re.findall(r"url\(\s*['\"]?([^)'\"]+)", document))
        if path.suffix.lower() in {".js", ".json", ".webmanifest", ".php"}:
            for value in re.findall(r"""["']([^"'\r\n<>]+)["']""", document):
                if re.search(r"\.(?:html|css|js|php|json|webmanifest|png|jpe?g|webp|svg|avif|ico|woff2?|ttf|otf|mp3|wav|ogg|mp4|webm|pdf)(?:[?#].*)?$", value, re.I):
                    values.append(value)
        for value in values:
            value = html.unescape(value.strip())
            if not value or value.startswith(("#", "data:", "mailto:", "javascript:", "tel:")) or any(c in value for c in "{}$\\\n"):
                continue
            parsed = urlsplit(urljoin("https://randalefunk.de/" + name, value))
            if parsed.hostname not in {"randalefunk.de", "www.randalefunk.de"}:
                continue
            target = unquote(parsed.path).lstrip("/")
            # PHP __DIR__ . '/file.php' is relative to the containing PHP file.
            if path.suffix.lower() == ".php" and value.startswith("/") and (path.parent / value.lstrip("/")).is_file():
                target = (path.parent / value.lstrip("/")).relative_to(root).as_posix()
            if (root / target).is_dir():
                target += ("" if target.endswith("/") or not target else "/") + "index.html"
            if (root / target).is_file():
                result.add((name, target))
            else:
                missing.add((name, target))
    return result, missing


def build(root, output):
    names = runtime_files(root)
    links, missing = references(root, names)
    omitted = sorted((source, target) for source, target in links if target not in names)
    if omitted:
        raise ValueError("Referenced runtime files excluded: " + repr(omitted))
    if output.exists():
        raise ValueError("Output already exists; choose a new empty destination.")
    output.mkdir(parents=True)
    stripped = 0
    for name in names:
        source, target = root / name, output / name
        target.parent.mkdir(parents=True, exist_ok=True)
        if source.suffix.lower() == ".html" and MARKER in source.read_bytes():
            # Generator ownership stays in the source, not in public copies.
            target.write_bytes(source.read_bytes().replace(MARKER, b""))
            stripped += 1
        else:
            shutil.copy2(source, target)
    print(f"Runtime files: {len(names)}; generator markers removed from {stripped} public HTML files.")
    for source, target in sorted(missing):
        print(f"Existing/unresolved reference (not introduced by packaging): {source} -> {target}")
    return names, links, missing


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--output", type=Path, default=ROOT / "deploy")
    args = parser.parse_args()
    try:
        build(ROOT, args.output.resolve())
    except (OSError, ValueError) as error:
        parser.exit(1, str(error) + "\n")

