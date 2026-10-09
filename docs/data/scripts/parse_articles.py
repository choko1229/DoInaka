"""取得済み Wikipedia 記事から、冒頭の読み・インフォボックス（廃止日/廃止理由/現在の自治体/郡）を抜き出す。

使い方: python3 -I parse_articles.py <articles_dir> <out_json>
"""
from __future__ import annotations

import json
import os
import re
import sys

from bs4 import BeautifulSoup

READ_RE = re.compile(r"^(?P<name>[^（(]+?)[（(](?P<yomi>[ぁ-ゖー・\s]+)[）),、]")


def main() -> None:
    adir, out = sys.argv[1], sys.argv[2]
    idx = json.load(open(os.path.join(adir, "index.json"), encoding="utf-8"))
    res = {}
    for title, fn in idx.items():
        soup = BeautifulSoup(open(os.path.join(adir, fn), encoding="utf-8"), "lxml")
        c = soup.select_one(".mw-parser-output")
        info = {"title": title, "leads": [], "infobox": {}, "readings": []}
        if c is None:
            res[title] = info
            continue
        for p in c.find_all("p"):
            txt = p.get_text("", strip=True)
            if not txt:
                continue
            info["leads"].append(txt[:300])
            if len(info["leads"]) >= 3:
                break
        for txt in info["leads"]:
            for m in re.finditer(r"([一-龥々ヶヵぁ-ゖァ-ヺ]+)[（(]([ぁ-ゖー]+)[）)、,]", txt):
                info["readings"].append([m.group(1), m.group(2)])
        ib = c.find("table", class_=re.compile("infobox"))
        if ib:
            for tr in ib.find_all("tr"):
                th, td = tr.find("th"), tr.find("td")
                if th and td:
                    k = th.get_text("", strip=True)
                    if k in ("廃止日", "廃止理由", "現在の自治体", "郡", "市町村コード", "都道府県"):
                        info["infobox"][k] = td.get_text(" ", strip=True)
        res[title] = info
    json.dump(res, open(out, "w", encoding="utf-8"), ensure_ascii=False, indent=1)
    print("articles:", len(res))


if __name__ == "__main__":
    main()
