"""香川県の合併前の旧市町村（平成・昭和の大合併）CSV を作り、検証する。

使い方:
  python3 -I build_kagawa_former.py <data_dir>

入力（<data_dir>/raw/ 以下）:
  work/wiki_list.json      … Wikipedia「香川県の廃止市町村一覧」を parse_wiki_list.py で構造化したもの
  work/articles.json       … 各旧市町村記事の読み・インフォボックス（parse_articles.py）
  work/uub_events.json     … uub.jp 市区町村変遷情報（parse_uub.py）
  soumu_gapei/gapei_h11iko.html, soumu_gapei/conv/000283317.xlsx … 総務省 市町村合併資料集
  city/sakaide_shiikihensen.html … 坂出市「市域の変遷」
  ../municipalities_jp.csv
出力:
  <data_dir>/kagawa_former_municipalities.csv
  <data_dir>/raw/work/kagawa_report.json（README 用の集計・不一致一覧）
"""
from __future__ import annotations

import csv
import json
import os
import re
import sys
from collections import Counter, defaultdict
from datetime import date
from urllib.parse import quote

import openpyxl
from bs4 import BeautifulSoup

HEISEI = ("1999-04-01", "2010-03-31")
SHOWA = ("1953-10-01", "1961-06-30")

WIKI_LIST_URL = "https://ja.wikipedia.org/wiki/" + quote("香川県の廃止市町村一覧")
UUB_LIST_URL = "https://uub.jp/upd/kagawa.html"
MIC_H11_URL = "https://www.soumu.go.jp/gapei/gapei_h11iko.html"
MIC_HENSEN_URL = "https://www.soumu.go.jp/gapei/hensen_kagawa.html"
MIC_XLS_URL = "https://www.soumu.go.jp/main_content/000283317.xls"
SAKAIDE_URL = "https://www.city.sakaide.lg.jp/soshiki/seisaku/shiikihensen.html"

# 表記ゆれ（比較用の正規化のみ。出力は Wikipedia 一覧の表記）
VARIANTS = {"龍": "竜"}

# 出典間で日付が食い違い、第3の出典で確定したもの。
# key=(旧名, Wikipedia一覧の日付) → (採用日付, 根拠)
DATE_OVERRIDES: dict[tuple[str, str], tuple[str, str]] = {
    ("松山村", "1955-07-01"): ("1956-07-01", "Wikipedia一覧は1955-07-01。uub.jp・坂出市「市域の変遷」・Wikipedia個別記事は1956-07-01（昭和31年7月1日）。後者を採用"),
    ("王越村", "1955-07-01"): ("1956-07-01", "Wikipedia一覧は1955-07-01。uub.jp・坂出市「市域の変遷」・Wikipedia個別記事は1956-07-01（昭和31年7月1日）。後者を採用"),
}


# 合併後の境界変更で区域の一部が別の現市町に移ったもの（Wikipedia 個別記事の記述。事実のみ要約）
EXTRA_NOTES: dict[str, str] = {
    "神野村": "Wikipedia記事によると、大字五條の大部分は満濃町発足後の1956年・1957年に満濃町から琴平町へ割譲。current_city は合併経路の値のみ",
    "井戸村": "Wikipedia記事によると、1959年11月1日に旧村域の一部が大川郡長尾町（現さぬき市）へ編入。current_city は合併経路の値のみ",
}


def norm(s: str) -> str:
    for a, b in VARIANTS.items():
        s = s.replace(a, b)
    return s


def in_window(d: str, w: tuple[str, str]) -> bool:
    return w[0] <= d <= w[1]


def wiki_url(title: str) -> str:
    return "https://ja.wikipedia.org/wiki/" + quote(title.replace(" ", "_"))


def split_gun(s: str) -> tuple[str, str]:
    m = re.match(r"^(\S+?郡)?(.+)$", s)
    assert m
    return (m.group(1) or "", m.group(2))


def kind_of(name: str) -> str:
    return name[-1] if name[-1] in "市町村" else "?"


def parse_rest(rest: str) -> dict:
    """Wikipedia 一覧の『…新設のため』『…に編入のため』を解釈する。"""
    m = re.match(r"^(.+?)（即日改称、(.+?)）新設のため$", rest)
    if m:
        g, n = split_gun(m.group(1))
        return {"type": "新設合併", "targets": [n], "renamed_same_day": m.group(2), "split": False}
    m = re.match(r"^(.+?)新設のため$", rest)
    if m:
        return {"type": "新設合併", "targets": [split_gun(m.group(1))[1]], "split": False}
    m = re.match(r"^(.+?)に分割編入のため$", rest)
    if m:
        return {"type": "編入", "targets": [split_gun(x)[1] for x in m.group(1).split("・")], "split": True}
    m = re.match(r"^(.+?)に編入のため$", rest)
    if m:
        return {"type": "編入", "targets": [split_gun(m.group(1))[1]], "split": False}
    raise ValueError(rest)


def load_mic_h11(path: str) -> list[dict]:
    soup = BeautifulSoup(open(path, "rb").read().decode("cp932", errors="replace"), "html.parser")
    out = []
    for tr in soup.find_all("tr"):
        cells = [c.get_text(" ", strip=True) for c in tr.find_all(["td", "th"])]
        if len(cells) >= 5 and cells[1] == "香川県":
            out.append({"date_jp": cells[0], "new": cells[2], "members": cells[3], "type": cells[4]})
    return out


def jp_date(s: str) -> str:
    m = re.match(r"平成\s*(\d+)年\s*(\d+)月\s*(\d+)日", s)
    assert m, s
    return date(1988 + int(m.group(1)), int(m.group(2)), int(m.group(3))).isoformat()


def mic_member_readings(members: str) -> dict[str, str]:
    """'大川郡津田町（おおかわぐんつだちょう）、同郡大川町（おおかわちょう）' → {津田町: つだちょう, ...}"""
    out = {}
    for part in re.split(r"[、,]", members):
        m = re.match(r"^\s*(?:(\S+?郡)|同郡)?(\S+?)\s*[（(]([ぁ-ゖー]+)[）)]\s*$", part.strip())
        if not m:
            continue
        name, yomi = m.group(2), m.group(3)
        if part.strip().startswith("同郡"):
            pass
        elif m.group(1):
            idx = yomi.find("ぐん")
            if idx < 0:
                raise ValueError(part)
            yomi = yomi[idx + 2:]
        out[name] = yomi
    return out


def load_mic_xls(path: str) -> list[dict]:
    wb = openpyxl.load_workbook(path, read_only=True)
    out = []
    for r in wb.worksheets[0].iter_rows(values_only=True):
        if len(r) > 5 and r[1] == "香川県":
            members = []
            gun = ""
            for tok in str(r[5]).split("、"):
                g, n = split_gun(tok.replace("同郡", ""))
                members.append(n)
            out.append({"date": r[2].date().isoformat(), "new": r[3], "type": r[4], "members": members})
    return out


def main() -> None:
    data = sys.argv[1]
    raw = os.path.join(data, "raw")
    wl = json.load(open(os.path.join(raw, "work/wiki_list.json"), encoding="utf-8"))
    arts = json.load(open(os.path.join(raw, "work/articles.json"), encoding="utf-8"))
    uub = json.load(open(os.path.join(raw, "work/uub_events.json"), encoding="utf-8"))
    mic_h11 = load_mic_h11(os.path.join(raw, "soumu_gapei/gapei_h11iko.html"))
    mic_xls = load_mic_xls(os.path.join(raw, "soumu_gapei/conv/000283317.xlsx"))
    sakaide_txt = re.sub(r"<[^>]+>", " ", open(os.path.join(raw, "city/sakaide_shiikihensen.html"), encoding="utf-8", errors="replace").read())
    sakaide_txt = re.sub(r"\s+", " ", sakaide_txt)

    muni = list(csv.DictReader(open(os.path.join(data, "municipalities_jp.csv"), encoding="utf-8")))
    kagawa_now = {d["city_name"]: d for d in muni if d["pref_code"] == "37" and d["kind"] != "pref"}
    assert len(kagawa_now) == 17, len(kagawa_now)

    report: dict = {"mismatches": [], "notes": []}

    # ---- 1. Wikipedia 一覧の全項目（日付補正込み）----
    items = []
    for x in wl:
        d = x["date"]
        ov = DATE_OVERRIDES.get((x["name"], d))
        x = dict(x)
        x["wiki_date"] = d
        if ov:
            x["date"] = ov[0]
            x["date_note"] = ov[1]
        x.update(parse_rest(x["rest"]))
        items.append(x)

    # ---- 2. uub.jp 側の廃止イベントを (名前, 日付) で引けるようにする ----
    uub_abol: dict[tuple[str, str], dict] = {}
    uub_renames = []  # (旧名, 新名, 日付)
    for e in uub:
        k = e["kind"]
        tg = e["targets"]
        if not tg:
            continue
        if k.startswith("新設"):
            for t in tg:
                uub_abol[(norm(t["name"]), e["date"])] = {"ev": e, "into": e["name"], "type": "新設合併", "part": t["part"]}
        elif k.startswith("編入"):
            for t in tg[1:]:
                key = (norm(t["name"]), e["date"])
                rec = {"ev": e, "into": e["name"], "type": "編入", "part": t["part"]}
                if key in uub_abol:  # 分割編入（同日に複数の編入先）
                    prev = uub_abol[key]
                    prev.setdefault("also", []).append(rec)
                else:
                    uub_abol[key] = rec
        elif k in ("町制", "町制/改称", "改称", "市制") and len(tg) == 1:
            uub_renames.append((norm(tg[0]["name"]), norm(e["name"]), e["date"]))

    # ---- 3. 網羅性チェック（対象期間で両出典の集合を比較）----
    def scope(d: str) -> bool:
        return in_window(d, HEISEI) or in_window(d, SHOWA)

    wiki_keys = {(norm(x["name"]), x["date"]) for x in items if scope(x["date"])}
    uub_keys = {k for k in uub_abol if scope(k[1])}
    report["only_in_wiki"] = sorted(wiki_keys - uub_keys)
    report["only_in_uub"] = sorted(uub_keys - wiki_keys)

    # 総務省（平成）の集合
    mic_abol = {}
    for r in mic_xls:
        mem = r["members"] if r["type"] == "新設" else r["members"][1:]
        for n in mem:
            mic_abol[(n, r["date"])] = {"into": r["new"], "type": "新設合併" if r["type"] == "新設" else "編入"}
    wiki_heisei = {(x["name"], x["date"]) for x in items if in_window(x["date"], HEISEI)}
    report["heisei_only_in_wiki"] = sorted(wiki_heisei - set(mic_abol))
    report["heisei_only_in_mic"] = sorted(set(mic_abol) - wiki_heisei)

    # 総務省 H11 ページの読み
    mic_yomi: dict[tuple[str, str], str] = {}
    for r in mic_h11:
        d = jp_date(r["date_jp"])
        for n, y in mic_member_readings(r["members"]).items():
            mic_yomi[(n, d)] = y

    # ---- 4. 後継をたどって現在の市町と平成の親を求める ----
    by_name = defaultdict(list)
    for x in items:
        by_name[norm(x["name"])].append(x)

    def next_step(name: str, after: str):
        cands = [x for x in by_name.get(norm(name), []) if x["date"] > after]
        rn = [r for r in uub_renames if r[0] == norm(name) and r[2] > after]
        ab = min(cands, key=lambda x: x["date"]) if cands else None
        rr = min(rn, key=lambda r: r[2]) if rn else None
        if ab and (not rr or ab["date"] <= rr[2]):
            return ("abolish", ab)
        if rr:
            return ("rename", rr)
        return None

    def resolve(name: str, after: str, path: list) -> list[tuple[str, list]]:
        """name が after 以降どうなったか。[(現在名, 経路)]"""
        step = next_step(name, after)
        if step is None:
            return [(name, path)]
        if step[0] == "rename":
            _, new, d = step[1]
            return resolve(new, d, path + [("rename", name, new, d)])
        ab = step[1]
        tgt = ab.get("renamed_same_day") or None
        targets = [tgt] if tgt else ab["targets"]
        out = []
        for t in targets:
            out += resolve(t, ab["date"], path + [("abolish", ab["name"], ab["date"], t)])
        return out

    # ---- 5. 行を作る ----
    rows = []
    for x in sorted([x for x in items if scope(x["date"])], key=lambda x: (x["date"], x["name"])):
        era = "heisei" if in_window(x["date"], HEISEI) else "showa"
        name = x["name"]
        notes: list[str] = []
        urls = [WIKI_LIST_URL]
        own = [l for l in x["links"] if l["text"] == name and not l["redlink"]]
        art = arts.get(own[0]["title"]) if own else None
        if own:
            urls.append(wiki_url(own[0]["title"]))
        if x["gun"]:
            notes.append(f"旧郡: {x['gun']}")
        if x.get("is_old_prefixed"):
            notes.append("Wikipedia一覧では『（旧）』付き（同日に同名の新自治体が成立）")
        if x.get("date_note"):
            notes.append(x["date_note"])

        # 事実の照合
        fact_sources = 1
        u = uub_abol.get((norm(name), x["date"]))
        if u:
            urls.append(UUB_LIST_URL)
            if u["ev"].get("detail"):
                urls.append("https://uub.jp/upd/u/" + os.path.basename(u["ev"]["detail"]))
            uub_into = [u["into"]] + [a["into"] for a in u.get("also", [])]
            if u["type"] != x["type"] or sorted(map(norm, uub_into)) != sorted(map(norm, x["targets"])):
                report["mismatches"].append({"name": name, "date": x["date"], "wiki": [x["type"], x["targets"]], "uub": [u["type"], uub_into]})
                notes.append(f"uub.jp と合併先/方式が不一致: uub={u['type']} {uub_into}")
            else:
                fact_sources += 1
            if norm(u["ev"]["target_text"]) != u["ev"]["target_text"] or any(t["name"] != norm(t["name"]) and norm(t["name"]) == norm(name) for t in u["ev"]["targets"]):
                alt = [t["name"] for t in u["ev"]["targets"] if norm(t["name"]) == norm(name) and t["name"] != name]
                if alt:
                    notes.append(f"表記ゆれ: uub.jp では『{alt[0]}』")
            parts = [(u["into"], u["part"])] + [(a["into"], a["part"]) for a in u.get("also", [])]
            if any(p for _, p in parts):
                notes.append("分割の内訳(uub.jp): " + "、".join(f"{i}={p or '?'}" for i, p in parts) + "（本=大部分、微=一部）")
            if u["ev"].get("kanpo"):
                notes.append("官報告示(uub.jp記載): " + "; ".join(u["ev"]["kanpo"]))
        else:
            notes.append("uub.jp に該当イベントなし")
        if era == "heisei":
            m = mic_abol.get((name, x["date"]))
            if m:
                urls += [MIC_XLS_URL, MIC_H11_URL, MIC_HENSEN_URL]
                if m["type"] == x["type"] and m["into"] == x["targets"][0]:
                    fact_sources += 1
                else:
                    report["mismatches"].append({"name": name, "date": x["date"], "wiki": [x["type"], x["targets"]], "mic": [m["type"], m["into"]]})
                    notes.append(f"総務省資料と不一致: {m}")
            else:
                notes.append("総務省資料に該当なし")
        if x.get("date_note"):
            urls.append(SAKAIDE_URL)
        elif "坂出市" in x["targets"] and era == "showa":
            # 坂出市の公式「市域の変遷」でも確認
            y = int(x["date"][:4]) - 1925
            if f"昭和{y}" in sakaide_txt.translate(str.maketrans("０１２３４５６７８９", "0123456789")) and name in sakaide_txt:
                urls.append(SAKAIDE_URL)
                fact_sources += 1
        # Wikipedia 個別記事のインフォボックスと日付照合（同じ出典系なので独立とは数えない）
        if art and art["infobox"].get("廃止日"):
            ib = re.sub(r"\s", "", art["infobox"]["廃止日"])
            mm = re.match(r"(\d{4})年(\d{1,2})月(\d{1,2})日", ib)
            if mm:
                ibd = f"{int(mm.group(1)):04d}-{int(mm.group(2)):02d}-{int(mm.group(3)):02d}"
                if ibd != x["date"] and not x.get("is_old_prefixed"):
                    notes.append(f"Wikipedia個別記事の廃止日は{ibd}")
                    report["mismatches"].append({"name": name, "date": x["date"], "article_date": ibd})

        # 現在の市町・平成の親
        branches = []
        for t in ([x["renamed_same_day"]] if x.get("renamed_same_day") else x["targets"]):
            branches += resolve(t, x["date"], [("abolish", name, x["date"], t)])
        chain_now = []
        branch_parents: list[tuple[str, tuple[str, str]]] = []
        for now, path in branches:
            if now not in chain_now:
                chain_now.append(now)
            for st in path[1:]:
                if st[0] == "abolish" and in_window(st[2], HEISEI):
                    branch_parents.append((now, (st[1], st[2])))
                    break
        bad = [c for c in chain_now if c not in kagawa_now]
        if bad:
            raise ValueError(f"{name}: chain ended at non-current {bad}")
        ib_now = []
        if art and art["infobox"].get("現在の自治体"):
            ib_now = [norm(s.strip()) for s in re.split(r"[、,・]", art["infobox"]["現在の自治体"]) if s.strip()]
        current = chain_now
        cur_conf = "high"
        if ib_now and sorted(ib_now) != sorted(chain_now):
            if set(ib_now) < set(chain_now):
                current = [c for c in chain_now if c in ib_now]
                notes.append(f"合併経路からは{'・'.join(chain_now)}。Wikipedia記事の現在の自治体は{'・'.join(ib_now)}。記事側を採用（区域の行き先を他出典で未確認）")
                cur_conf = "medium"
            else:
                notes.append(f"Wikipedia記事の現在の自治体は{'・'.join(ib_now)}（合併経路からは{'・'.join(chain_now)}。後年の境界変更等の可能性、未確認）")
                cur_conf = "medium"
            report["mismatches"].append({"name": name, "date": x["date"], "chain_now": chain_now, "infobox_now": ib_now})
        if name in EXTRA_NOTES and era == "showa":
            notes.append(EXTRA_NOTES[name])
        heisei_parents: list[tuple[str, str]] = []
        for now, par in branch_parents:
            if now in current and par not in heisei_parents:
                heisei_parents.append(par)
        dropped = [par for now, par in branch_parents if now not in current]
        if dropped:
            notes.append("採用しなかった経路上の平成の旧自治体: " + "・".join(f"{a}({b})" for a, b in dropped))
        routes = []
        for now, path in branches:
            if now not in current or len(path) < 2:
                continue
            seg = [path[0][3]]
            for st in path[1:]:
                if st[0] == "abolish":
                    seg.append(f"{st[2]}に{st[3]}へ")
                else:
                    seg.append(f"{st[3]}に{st[2]}へ（町制等）")
            r = " → ".join(seg)
            if r not in routes:
                routes.append(r)
        if routes:
            notes.append("その後の経路: " + " / ".join(routes))
        if len(current) > 1:
            notes.append("区域が複数の現市町にまたがるため current_city_name/current_city_code はセミコロン区切り")
        if x["split"]:
            notes.append("分割編入（merged_into はセミコロン区切り）")

        # 読み
        wiki_y = None
        if art:
            ys = [r[1] for r in art["readings"] if r[0] == name]
            if ys:
                wiki_y = ys[0]
        mic_y = mic_yomi.get((name, x["date"])) if era == "heisei" else None
        now_kana = None
        if era == "heisei" and name in ("丸亀市", "観音寺市"):
            # 旧市の読みは現行市と同じ表記。全国地方公共団体コードの現行市の読みで補強
            k = kagawa_now[name]["city_kana"]
            now_kana = "".join(chr(ord(c) - 0x60) if "ァ" <= c <= "ヶ" else c for c in k)
        cands = [y for y in (mic_y, wiki_y, now_kana) if y]
        kana = ""
        if not cands:
            read_conf = "low"
            notes.append("読み: 出典に記載なし（空欄）")
        else:
            kana = cands[0]
            if len(set(cands)) > 1:
                read_conf = "low"
                notes.append(f"読みが出典間で不一致: 総務省={mic_y} Wikipedia={wiki_y}")
                report["mismatches"].append({"name": name, "date": x["date"], "mic_yomi": mic_y, "wiki_yomi": wiki_y})
            elif len(cands) >= 2:
                read_conf = "high"
            else:
                read_conf = "medium"
                src = "Wikipedia記事のみ" if wiki_y else "総務省資料のみ"
                notes.append(f"読み: {src}（1出典）")
        if wiki_y and x.get("is_old_prefixed"):
            notes.append("読みは同名の後身自治体の記事による")

        fact_conf = "high" if fact_sources >= 2 else ("medium" if fact_sources == 1 else "low")
        order = {"low": 0, "medium": 1, "high": 2}
        conf = min([fact_conf, read_conf, cur_conf], key=lambda c: order[c])

        rows.append({
            "era": era, "former_name": name, "former_kana": kana, "former_kind": kind_of(name),
            "abolished_on": x["date"], "merger_type": x["type"],
            "merged_into": ";".join(x["targets"]) + (f"（即日改称 {x['renamed_same_day']}）" if x.get("renamed_same_day") else ""),
            "current_city_name": ";".join(current),
            "current_city_code": ";".join(kagawa_now[c]["code"] for c in current),
            "_heisei_parents": heisei_parents, "_chain_now": chain_now,
            "source_urls": " ".join(dict.fromkeys(urls)),
            "confidence": conf, "notes": "。".join(notes),
            "_fact_conf": fact_conf, "_read_conf": read_conf, "_cur_conf": cur_conf,
        })

    # id: 平成 → 昭和 の順、各々日付順
    rows.sort(key=lambda r: (0 if r["era"] == "heisei" else 1, r["abolished_on"], r["former_name"]))
    for i, r in enumerate(rows, 1):
        r["id"] = i
    hid = {(r["former_name"], r["abolished_on"]): r["id"] for r in rows if r["era"] == "heisei"}
    for r in rows:
        if r["era"] == "showa":
            ids = []
            for p in r["_heisei_parents"]:
                # 採用した現在市町に到達する枝の親だけ残す
                ids.append(hid[p])
            r["parent_former_id"] = ";".join(str(i) for i in dict.fromkeys(ids))
            if len(ids) > 1:
                r["notes"] += "。平成の親が複数（分割編入のため。セミコロン区切り）"
        else:
            r["parent_former_id"] = ""

    cols = ["id", "era", "former_name", "former_kana", "former_kind", "abolished_on", "merger_type", "merged_into",
            "current_city_name", "current_city_code", "parent_former_id", "source_urls", "confidence", "notes"]
    with open(os.path.join(data, "kagawa_former_municipalities.csv"), "w", encoding="utf-8", newline="") as f:
        w = csv.DictWriter(f, fieldnames=cols, lineterminator="\n", extrasaction="ignore")
        w.writeheader()
        w.writerows(rows)

    # ---- 6. 検証 ----
    cnt = Counter(r["era"] for r in rows)
    print("rows by era:", dict(cnt), " total:", len(rows))
    print("kind by era:", Counter((r["era"], r["former_kind"]) for r in rows))
    print("merger_type by era:", Counter((r["era"], r["merger_type"]) for r in rows))
    print("confidence:", Counter(r["confidence"] for r in rows), " by era:", Counter((r["era"], r["confidence"]) for r in rows))
    print("fact/read/cur conf:", Counter(r["_fact_conf"] for r in rows), Counter(r["_read_conf"] for r in rows), Counter(r["_cur_conf"] for r in rows))
    codes = {d["code"] for d in muni}
    bad_codes = [r["id"] for r in rows if any(c not in codes for c in r["current_city_code"].split(";"))]
    print("current_city_code not in File1:", bad_codes)
    dup = [k for k, v in Counter((r["former_name"], r["abolished_on"]) for r in rows).items() if v > 1]
    print("duplicate (former_name, abolished_on):", dup)
    need_parent = [r for r in rows if r["era"] == "showa" and r["_heisei_parents"]]
    missing_parent = [r["id"] for r in need_parent if not r["parent_former_id"]]
    print("showa rows whose chain passes a heisei abolition:", len(need_parent), " missing parent:", missing_parent)
    no_parent = [r for r in rows if r["era"] == "showa" and not r["parent_former_id"]]
    print("showa rows without parent:", len(no_parent), Counter(r["current_city_name"] for r in no_parent))
    # 平成の数の説明
    heisei_rows = [r for r in rows if r["era"] == "heisei"]
    new_succ = sorted({r["merged_into"] for r in heisei_rows if r["merger_type"] == "新設合併"})
    print("heisei new-merger successors:", len(new_succ), new_succ)
    print("43 - 17 + successors =", 43 - 17 + len(new_succ), " heisei rows =", len(heisei_rows))
    print("only_in_wiki:", report["only_in_wiki"])
    print("only_in_uub:", report["only_in_uub"])
    print("heisei_only_in_wiki:", report["heisei_only_in_wiki"], " heisei_only_in_mic:", report["heisei_only_in_mic"])
    print("mismatches:")
    for m in report["mismatches"]:
        print("  ", m)
    print("multi-valued rows:")
    for r in rows:
        if ";" in r["current_city_name"] or ";" in r["merged_into"] or ";" in r["parent_former_id"]:
            print("  ", r["id"], r["former_name"], r["abolished_on"], r["merged_into"], r["current_city_name"], r["parent_former_id"])
    print("non-high rows:")
    for r in rows:
        if r["confidence"] != "high":
            print("  ", r["id"], r["era"], r["former_name"], r["abolished_on"], r["confidence"], "| fact", r["_fact_conf"], "read", r["_read_conf"], "cur", r["_cur_conf"], "|", r["former_kana"])
    report["rows"] = [{k: v for k, v in r.items()} for r in rows]
    report["out_of_scope"] = [{"date": x["date"], "gun": x["gun"], "name": x["name"], "rest": x["rest"]} for x in items if not scope(x["date"])]
    json.dump(report, open(os.path.join(raw, "work/kagawa_report.json"), "w", encoding="utf-8"), ensure_ascii=False, indent=1)


if __name__ == "__main__":
    main()
