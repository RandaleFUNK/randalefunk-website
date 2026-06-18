from __future__ import annotations

import html
import re
from pathlib import Path
from urllib.parse import urljoin


SITE_URL = "https://randalefunk.de/"
SITE_NAME = "RandaleFUNK.de"
DEFAULT_IMAGE = "assets/randalefunk-logo.png"
ROOT = Path(__file__).resolve().parents[1]

SOCIAL_START = "    <!-- social-meta:start -->"
SOCIAL_END = "    <!-- social-meta:end -->"


def clean_text(value: str) -> str:
    value = html.unescape(value)
    value = re.sub(r"\s+", " ", value).strip()
    return value


def attr_value(tag: str, attr: str) -> str:
    match = re.search(rf'{attr}\s*=\s*(["\'])(.*?)\1', tag, re.IGNORECASE | re.DOTALL)
    return html.unescape(match.group(2)) if match else ""


def find_title(document: str) -> str:
    match = re.search(r"<title>(.*?)</title>", document, re.IGNORECASE | re.DOTALL)
    return clean_text(match.group(1)) if match else SITE_NAME


def find_description(document: str) -> str:
    match = re.search(
        r'<meta\s+name=["\']description["\']\s+content=(["\'])(.*?)\1\s*/?>',
        document,
        re.IGNORECASE | re.DOTALL,
    )

    if match:
        return clean_text(match.group(2))

    first_paragraph = re.search(r"<p[^>]*>(.*?)</p>", document, re.IGNORECASE | re.DOTALL)

    if not first_paragraph:
        return "RandaleFUNK.de - Punk, Fanzine, Reviews, Interviews, News und anderer Krach."

    text = re.sub(r"<[^>]+>", "", first_paragraph.group(1))
    return clean_text(text)[:220]


def is_content_image(src: str) -> bool:
    ignored = (
        "assets/favicon/",
        "randalefunk-logo",
        "randalf-head",
        "bg-paper",
    )

    return src and not any(part in src for part in ignored)


def find_image(document: str, page: Path) -> str:
    for match in re.finditer(r"<img\b[^>]*>", document, re.IGNORECASE | re.DOTALL):
        src = attr_value(match.group(0), "src")

        if is_content_image(src):
            return absolute_url(src, page)

    return absolute_url(DEFAULT_IMAGE, page)


def absolute_url(value: str, page: Path) -> str:
    value = value.strip()

    if value.startswith(("http://", "https://")):
        return value

    if value.startswith("/"):
        return urljoin(SITE_URL, value.lstrip("/"))

    page_dir = page.parent.relative_to(ROOT).as_posix()
    base = SITE_URL if page_dir == "." else urljoin(SITE_URL, f"{page_dir}/")

    return urljoin(base, value)


def page_url(page: Path) -> str:
    relative = page.relative_to(ROOT).as_posix()

    if relative == "index.html":
        return SITE_URL

    if relative.endswith("/index.html"):
        return urljoin(SITE_URL, relative.removesuffix("index.html"))

    return urljoin(SITE_URL, relative)


def og_type_for(page: Path) -> str:
    relative = page.relative_to(ROOT).as_posix()

    if relative.startswith(("reviews/", "vorab-gehoert/", "kolumnen/")) and not relative.endswith("/index.html"):
        return "article"

    return "website"


def social_block(page: Path, document: str) -> str:
    title = find_title(document)
    description = find_description(document)
    image = find_image(document, page)
    url = page_url(page)
    og_type = og_type_for(page)

    def esc(value: str) -> str:
        return html.escape(value, quote=True)

    return "\n".join(
        [
            SOCIAL_START,
            f'    <meta property="og:title" content="{esc(title)}">',
            f'    <meta property="og:description" content="{esc(description)}">',
            f'    <meta property="og:image" content="{esc(image)}">',
            f'    <meta property="og:url" content="{esc(url)}">',
            f'    <meta property="og:type" content="{esc(og_type)}">',
            f'    <meta property="og:site_name" content="{esc(SITE_NAME)}">',
            '    <meta name="twitter:card" content="summary_large_image">',
            f'    <meta name="twitter:title" content="{esc(title)}">',
            f'    <meta name="twitter:description" content="{esc(description)}">',
            f'    <meta name="twitter:image" content="{esc(image)}">',
            SOCIAL_END,
        ]
    )


def remove_existing_block(document: str) -> str:
    pattern = re.compile(
        rf"\n?\s*<!-- social-meta:start -->.*?<!-- social-meta:end -->\n?",
        re.IGNORECASE | re.DOTALL,
    )
    return pattern.sub("\n", document)


def insert_block(document: str, block: str) -> str:
    description_pattern = re.compile(
        r'(\s*<meta\s+name=["\']description["\']\s+content=(["\']).*?\2\s*/?>)',
        re.IGNORECASE | re.DOTALL,
    )

    if description_pattern.search(document):
        return description_pattern.sub(lambda match: f"{match.group(1)}\n{block}", document, count=1)

    title_pattern = re.compile(r"(\s*<title>.*?</title>)", re.IGNORECASE | re.DOTALL)

    return title_pattern.sub(lambda match: f"{match.group(1)}\n{block}", document, count=1)


def should_process(page: Path) -> bool:
    relative = page.relative_to(ROOT).as_posix()
    ignored_prefixes = ("stats/", "wuerfel/Netlify-Upload/")

    return not relative.startswith(ignored_prefixes)


def update_page(page: Path) -> bool:
    document = page.read_text(encoding="utf-8")
    without_block = remove_existing_block(document)
    updated = insert_block(without_block, social_block(page, without_block))

    if updated == document:
        return False

    page.write_text(updated, encoding="utf-8", newline="\n")
    return True


def main() -> None:
    changed = []

    for page in sorted(ROOT.rglob("*.html")):
        if should_process(page) and update_page(page):
            changed.append(page.relative_to(ROOT).as_posix())

    for item in changed:
        print(item)

    print(f"Updated {len(changed)} HTML files.")


if __name__ == "__main__":
    main()
