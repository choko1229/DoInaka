"""wiki_list.json の各項目の自団体記事（と合併先記事）を取得して保存する。

使い方: python3 -I fetch_articles.py <wiki_list.json> <out_dir>
curl を使う（プロキシ設定は環境変数から）。
"""
from __future__ import annotations

import hashlib
import json
import os
import subprocess
import sys
import time
from urllib.parse import quote


def main() -> None:
    src, out_dir = sys.argv[1], sys.argv[2]
    os.makedirs(out_dir, exist_ok=True)
    items = json.load(open(src, encoding="utf-8"))
    titles: list[str] = []
    for x in items:
        in_scope = "1953-10-01" <= x["date"] <= "1961-06-30" or "1999-04-01" <= x["date"] <= "2010-03-31"
        if not in_scope:
            continue
        for l in x["links"]:
            if l["title"] and not l["redlink"] and l["title"] not in titles:
                titles.append(l["title"])
    index = {}
    for t in titles:
        fn = hashlib.sha1(t.encode()).hexdigest()[:16] + ".html"
        path = os.path.join(out_dir, fn)
        index[t] = fn
        if os.path.exists(path) and os.path.getsize(path) > 1000:
            continue
        url = "https://ja.wikipedia.org/wiki/" + quote(t.replace(" ", "_"))
        r = subprocess.run(["curl", "-sS", "-L", "--max-time", "60", "-A",
                            "doinaka-net-seed/0.1 (research; contact via project)", "-o", path, url])
        if r.returncode != 0:
            print("FAIL", t)
        time.sleep(0.4)
    json.dump(index, open(os.path.join(out_dir, "index.json"), "w", encoding="utf-8"), ensure_ascii=False, indent=1)
    print("titles:", len(titles))


if __name__ == "__main__":
    main()
