"""uub.jp「履歴情報 香川県（市区町村変遷情報）」を構造化し、対象期間の詳細ページを取得する。

使い方: python3 -I parse_uub.py <kagawa.html> <detail_dir> <out_json>
"""
from __future__ import annotations

import json
import os
import re
import subprocess
import sys
import time

from bs4 import BeautifulSoup

DATE_RE = re.compile(r"(\d{4})\([A-Z]\d+\)\s*\.(\d{1,2})\.(\d{1,2})")


def in_scope(d: str) -> bool:
    return "1953-10-01" <= d <= "1961-06-30" or "1999-04-01" <= d <= "2010-03-31"


def parse_targets(text: str) -> list[dict[str, str]]:
    """'高松市, 木田郡 前田村, 川添村, 香川郡 仏生山町' → [{gun, name, part}]"""
    out = []
    gun = ""
    for tok in [t.strip() for t in text.split(",")]:
        m = re.match(r"^(?:(\S+郡)\s+)?(\S+?)(\((本|微)\)|の一部)?$", tok)
        if not m:
            out.append({"gun": gun, "name": tok, "part": "?"})
            continue
        if m.group(1):
            gun = m.group(1)
        name = m.group(2)
        if name.endswith("市"):
            g = ""
        else:
            g = gun
        part = m.group(4) or ("一部" if m.group(3) == "の一部" else "")
        out.append({"gun": g, "name": name, "part": part})
    return out


def main() -> None:
    src, ddir, out = sys.argv[1], sys.argv[2], sys.argv[3]
    os.makedirs(ddir, exist_ok=True)
    soup = BeautifulSoup(open(src, encoding="utf-8"), "lxml")
    table = max(soup.find_all("table"), key=lambda t: len(t.find_all("tr")))
    events = []
    cur_date = ""
    for tr in table.find_all("tr")[1:]:
        tds = tr.find_all("td")
        if not tds:
            continue
        texts = [td.get_text(" ", strip=True) for td in tds]
        # 日付セル（rowspan）有無で列がずれる
        m = DATE_RE.search(texts[1]) if len(texts) > 1 else None
        if m:
            cur_date = f"{int(m.group(1)):04d}-{int(m.group(2)):02d}-{int(m.group(3)):02d}"
            cells = texts[2:]
        else:
            cells = texts[1:]
        if not texts[0].strip().isdigit():
            continue  # 見出し行
        if len(cells) < 4:
            print("SKIP", texts)
            continue
        kind, gun, name, target = cells[0], cells[1], cells[2], cells[3]
        a = tr.find("a", href=True)
        href = a["href"] if a else ""
        ev = {"no": int(texts[0]), "date": cur_date, "kind": kind, "gun": gun, "name": name,
              "target_text": target, "targets": parse_targets(target) if "所属とする" not in target else [],
              "detail": href}
        if in_scope(cur_date) and href:
            fn = os.path.join(ddir, os.path.basename(href))
            if not os.path.exists(fn):
                url = "https://uub.jp/upd/u/" + os.path.basename(href)
                subprocess.run(["curl", "-sS", "-L", "--retry", "5", "--retry-all-errors", "--retry-delay", "2", "--max-time", "60", "-o", fn, url], check=False)
                time.sleep(0.5)
            t = open(fn, encoding="utf-8", errors="replace").read()
            k = re.findall(r"官報告示[：:]\s*([^<\n]+)", t)
            ev["kanpo"] = [re.sub(r"\s+", " ", x).strip() for x in k]
            yomi = re.findall(r"変更後 読み.*?</tr>(.*?)</table>", t, re.S)
            ev["detail_text"] = re.sub(r"\s+", " ", re.sub(r"<[^>]+>", " ", yomi[0]))[:300] if yomi else ""
        events.append(ev)
    json.dump(events, open(out, "w", encoding="utf-8"), ensure_ascii=False, indent=1)
    print("events:", len(events), " in scope:", sum(in_scope(e["date"]) for e in events))


if __name__ == "__main__":
    main()
