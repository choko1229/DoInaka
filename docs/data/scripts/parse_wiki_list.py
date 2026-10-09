"""Wikipedia「香川県の廃止市町村一覧」を構造化 JSON にする。

使い方: python3 -I parse_wiki_list.py <html> <out_json>
"""
from __future__ import annotations

import json
import re
import sys
from urllib.parse import unquote

from bs4 import BeautifulSoup

ITEM_RE = re.compile(
    r"^(?P<gun>\S+?郡)?(?:\(旧\)|（旧）|旧・)?(?P<name>[^（(]+?)\s*（(?P<y>\d{4})年(?P<m>\d{1,2})月(?P<d>\d{1,2})日）(?P<rest>.+)$"
)


def main() -> None:
    html, out = sys.argv[1], sys.argv[2]
    soup = BeautifulSoup(open(html, encoding="utf-8"), "lxml")
    content = soup.select_one(".mw-parser-output")
    items = []
    for sec in content.find_all("section", recursive=False):
        h = sec.find(["h2"])
        if not h:
            continue
        section = h.get_text(strip=True)
        if section in ("関連項目",):
            continue
        for li in sec.find_all("li"):
            if li.find_parent("table") or li.find_parent(class_="navbox"):
                continue
            text = li.get_text("", strip=True)
            raw_text = text
            is_old = "（旧）" in text or "(旧)" in text or "旧・" in text
            text = text.replace("（旧）", "").replace("(旧)", "").replace("旧・", "")
            m = ITEM_RE.match(text)
            if not m:
                print("UNPARSED:", raw_text)
                continue
            links = []
            for a in li.find_all("a"):
                href = unquote(a.get("href", ""))
                if "/wiki/" in href:
                    links.append({"text": a.get_text(strip=True), "title": a.get("title") or href.split("/wiki/", 1)[1],
                                  "redlink": "new" in (a.get("class") or []) or "redlink=1" in href})
            items.append({
                "section": section,
                "gun": m.group("gun") or "",
                "name": m.group("name").strip(),
                "date": f"{int(m.group('y')):04d}-{int(m.group('m')):02d}-{int(m.group('d')):02d}",
                "rest": m.group("rest"),
                "is_old_prefixed": is_old,
                "raw": raw_text,
                "links": links,
            })
    json.dump(items, open(out, "w", encoding="utf-8"), ensure_ascii=False, indent=1)
    print("items:", len(items))


if __name__ == "__main__":
    main()
