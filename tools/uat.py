#!/usr/bin/env python3
"""OtoGo UAT runner - black-box checks against a running server.

Usage:
    python tools/uat.py --base http://127.0.0.1:8822 \
        --email superadmin@gmail.com --password '...'

    (credentials default to SUPERADMIN_EMAIL / SUPERADMIN_PASSWORD from .env)

What it checks (stdlib only, no installs):
  1. Public pages, sitemap, robots, error pages respond as expected.
  2. Security headers on HTML (CSP, XFO, XCTO, Referrer-Policy, Permissions-Policy)
     and the lock-down CSP on JSON.
  3. Every same-origin asset referenced by a visited page (<link>, <script>, <img>)
     returns 200 - nothing blocked or missing.
  4. Superadmin login, then a crawl of every admin link (GET only, destructive
     and logout links skipped) plus the DataTables JSON feeds.
  5. API v1 end to end: register -> login -> me -> vehicles CRUD -> master data ->
     blog -> assistant -> logout, and the Swagger UI + JSON.
  6. A burst of requests to confirm normal browsing never hits 429.
  7. New ERROR entries in storage/logs/laravel.log during the run.

Exit code 0 when every check passes.
"""
import argparse
import html.parser
import http.cookiejar
import json
import os
import re
import sys
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
HTML_HEADERS = ["Content-Security-Policy", "X-Frame-Options", "X-Content-Type-Options",
                "Referrer-Policy", "Permissions-Policy"]
SKIP_LINK = re.compile(r"(logout|/delete|/destroy|mass-delete|/ban|/unban|/auth/[a-z]+/redirect|\.(zip|pdf)$)", re.I)


def read_env():
    env = {}
    try:
        with open(os.path.join(ROOT, ".env"), encoding="utf-8") as fh:
            for line in fh:
                m = re.match(r"^([A-Z0-9_]+)=(.*)$", line.strip())
                if m:
                    env[m.group(1)] = m.group(2).strip().strip('"')
    except OSError:
        pass
    return env


class Links(html.parser.HTMLParser):
    def __init__(self):
        super().__init__()
        self.links, self.assets = set(), set()
        self.csrf = None

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == "a" and a.get("href"):
            self.links.add(a["href"])
        elif tag == "link" and a.get("href") and a.get("rel") in ("stylesheet", "icon", "manifest", "apple-touch-icon"):
            self.assets.add(a["href"])
        elif tag in ("script", "img") and a.get("src"):
            self.assets.add(a["src"])
        elif tag == "meta" and a.get("name") == "csrf-token":
            self.csrf = a.get("content")
        elif tag == "input" and a.get("name") == "_token":
            self.csrf = self.csrf or a.get("value")


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        return None


class Client:
    def __init__(self, base):
        self.base = base.rstrip("/")
        self.host = urllib.parse.urlparse(self.base).netloc
        self.jar = http.cookiejar.CookieJar()
        self.follow = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar))
        self.nofollow = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar), NoRedirect)

    def url(self, path):
        return path if path.startswith("http") else self.base + path

    def request(self, method, path, data=None, headers=None, follow=True, json_body=None):
        headers = dict(headers or {})
        body = None
        if json_body is not None:
            body = json.dumps(json_body).encode()
            headers.setdefault("Content-Type", "application/json")
        elif data is not None:
            body = urllib.parse.urlencode(data).encode()
            headers.setdefault("Content-Type", "application/x-www-form-urlencoded")
        headers.setdefault("User-Agent", "OtoGo-UAT/1.0")
        req = urllib.request.Request(self.url(path), data=body, method=method, headers=headers)
        opener = self.follow if follow else self.nofollow
        start = time.time()
        try:
            resp = opener.open(req, timeout=60)
            status, hdrs, raw = resp.status, resp.headers, resp.read()
        except urllib.error.HTTPError as e:
            status, hdrs, raw = e.code, e.headers, e.read()
        except Exception as e:  # connection errors
            return 0, {}, str(e).encode(), time.time() - start
        return status, hdrs, raw, time.time() - start


class Report:
    def __init__(self):
        self.rows, self.failures = [], 0

    def check(self, area, name, ok, detail=""):
        self.rows.append((area, name, ok, detail))
        if not ok:
            self.failures += 1
        mark = "PASS" if ok else "FAIL"
        print(f"  [{mark}] {area:<10} {name}{('  -> ' + detail) if detail else ''}")


def same_origin_path(client, href):
    if not href or href.startswith(("#", "mailto:", "tel:", "javascript:", "data:")):
        return None
    parsed = urllib.parse.urlparse(urllib.parse.urljoin(client.base + "/", href))
    if parsed.netloc != client.host:
        return None
    return parsed.path + (("?" + parsed.query) if parsed.query else "")


def log_size():
    try:
        return os.path.getsize(os.path.join(ROOT, "storage", "logs", "laravel.log"))
    except OSError:
        return 0


def new_log_errors(offset):
    path = os.path.join(ROOT, "storage", "logs", "laravel.log")
    try:
        with open(path, encoding="utf-8", errors="replace") as fh:
            fh.seek(offset)
            text = fh.read()
    except OSError:
        return []
    return re.findall(r"^\[[^\]]+\] \w+\.(?:ERROR|CRITICAL|ALERT|EMERGENCY): (.{0,200})", text, re.M)


def main():
    env = read_env()
    ap = argparse.ArgumentParser()
    ap.add_argument("--base", default=env.get("APP_URL", "http://127.0.0.1:8822"))
    ap.add_argument("--email", default=env.get("SUPERADMIN_EMAIL", "superadmin@gmail.com"))
    ap.add_argument("--password", default=env.get("SUPERADMIN_PASSWORD", ""))
    ap.add_argument("--skip-ai", action="store_true", help="do not call the live AI assistant")
    ap.add_argument("--max-pages", type=int, default=120)
    args = ap.parse_args()

    rep = Report()
    log_offset = log_size()
    guest = Client(args.base)
    checked_assets = {}

    def check_assets(client, assets, area):
        for href in sorted(assets):
            path = same_origin_path(client, href)
            if not path or path in checked_assets:
                continue
            status, _, _, _ = client.request("GET", path)
            checked_assets[path] = status
            if status != 200:
                rep.check(area, f"asset {path}", False, f"HTTP {status}")

    def page(client, path, area, expect=(200,), follow=True, headers_check=True):
        status, hdrs, raw, secs = client.request("GET", path, follow=follow)
        ok = status in expect
        rep.check(area, f"GET {path}", ok, f"HTTP {status} ({secs:.2f}s)" if not ok or secs > 2 else "")
        ctype = hdrs.get("Content-Type", "") if hdrs else ""
        if status == 200 and "text/html" in ctype:
            missing = [h for h in HTML_HEADERS if not hdrs.get(h)]
            if headers_check and missing:
                rep.check(area, f"headers {path}", False, "missing " + ", ".join(missing))
            parser = Links()
            try:
                parser.feed(raw.decode("utf-8", "replace"))
            except Exception:
                pass
            check_assets(client, parser.assets, area)
            return status, parser
        return status, None

    print(f"\nOtoGo UAT against {args.base}\n")

    # 1. Public site -----------------------------------------------------------
    print("Public")
    _, home = page(guest, "/", "public")
    page(guest, "/blog", "public")
    if home:
        posts = sorted({l for l in home.links if re.search(r"/blog/[a-z0-9-]+$", l)})
        for link in posts[:3]:
            path = same_origin_path(guest, link)
            if path:
                page(guest, path, "public")
    page(guest, "/sitemap.xml", "public", headers_check=False)
    page(guest, "/robots.txt", "public", headers_check=False)
    page(guest, "/admin/login", "public")
    page(guest, "/halaman-yang-tidak-ada", "public", expect=(404,))
    page(guest, "/admin/dashboard", "public", expect=(302,), follow=False)
    page(guest, "/favicon.ico", "public", headers_check=False)
    page(guest, "/assets/media/branding/site.webmanifest", "public", headers_check=False)

    # 2. Throttle: normal browsing burst must never 429 -----------------------
    print("Throttle")
    codes = [guest.request("GET", "/blog")[0] for _ in range(40)]
    rep.check("throttle", "40x GET /blog", 429 not in codes and all(c == 200 for c in codes),
              "" if all(c == 200 for c in codes) else f"codes={sorted(set(codes))}")
    codes = [guest.request("GET", "/api/health")[0] for _ in range(40)]
    rep.check("throttle", "40x GET /api/health", all(c == 200 for c in codes), "" if all(c == 200 for c in codes) else f"codes={sorted(set(codes))}")

    # 3. Admin -------------------------------------------------------------------
    print("Admin")
    admin = Client(args.base)
    status, _, raw, _ = admin.request("GET", "/admin/login")
    parser = Links()
    parser.feed(raw.decode("utf-8", "replace"))
    status, hdrs, raw, _ = admin.request("POST", "/admin/login", data={
        "_token": parser.csrf or "", "email": args.email, "password": args.password,
    }, follow=False)
    location = (hdrs.get("Location", "") if hdrs else "")
    logged_in = status in (302, 303) and "login" not in location.rsplit("/", 1)[-1]
    rep.check("admin", "superadmin login", logged_in, f"HTTP {status} -> {location}")

    if logged_in:
        queue, seen = ["/admin/dashboard"], set()
        while queue and len(seen) < args.max_pages:
            path = queue.pop(0)
            if path in seen:
                continue
            seen.add(path)
            st, p = page(admin, path, "admin")
            if p:
                for link in p.links:
                    lp = same_origin_path(admin, link)
                    if lp and lp.startswith("/admin") and not SKIP_LINK.search(lp) and lp not in seen:
                        queue.append(lp.split("#")[0])
        rep.check("admin", f"crawled {len(seen)} admin pages", True)

        # DataTables JSON feeds (server-side)
        xhr = {"X-Requested-With": "XMLHttpRequest", "Accept": "application/json"}
        dt = "draw=1&start=0&length=10&search%5Bvalue%5D=&order%5B0%5D%5Bcolumn%5D=0&order%5B0%5D%5Bdir%5D=desc"
        for feed in ["/admin/get-datauser", "/admin/get-datarole", "/admin/get-datalogactivity",
                     "/admin/mget-my-activity", "/admin/mget-my-login-session"]:
            st, h, body, secs = admin.request("GET", f"{feed}?{dt}", headers=xhr)
            ok = st == 200 and body.strip().startswith(b"{")
            rep.check("admin", f"XHR {feed}", ok, f"HTTP {st} ({secs:.2f}s)" if not ok or secs > 2 else "")
        st, h, body, _ = admin.request("GET", f"/admin/get-datauser?{dt.replace('search%5Bvalue%5D=', 'search%5Bvalue%5D=super')}", headers=xhr)
        rep.check("admin", "XHR user search 'super'", st == 200 and b"superadmin" in body.lower(), f"HTTP {st}")

    # 4. API -------------------------------------------------------------------
    print("API")
    api = Client(args.base)
    J = {"Accept": "application/json"}
    st, h, body, _ = api.request("GET", "/api/health", headers=J)
    rep.check("api", "GET /api/health", st == 200 and b'"ok"' in body, f"HTTP {st}")
    if h:
        rep.check("api", "JSON lock-down CSP", "default-src 'none'" in (h.get("Content-Security-Policy") or ""))

    stamp = int(time.time())
    email = f"uat.{stamp}@example.test"
    st, h, body, _ = api.request("POST", "/api/v1/auth/register", headers=J, json_body={
        "name": "Pengguna UAT", "email": email, "phone": f"0812{stamp % 100000000:08d}",
        "password": "Rahasia123", "password_confirmation": "Rahasia123", "device_name": "uat",
    })
    token = None
    try:
        token = json.loads(body)["data"]["token"]
    except Exception:
        pass
    rep.check("api", "POST /auth/register", st == 201 and bool(token), f"HTTP {st} {body[:160]!r}" if st != 201 else "")

    st, h, body, _ = api.request("POST", "/api/v1/auth/login", headers=J, json_body={
        "email": email, "password": "Rahasia123", "device_name": "uat"})
    try:
        token = json.loads(body)["data"]["token"] or token
    except Exception:
        pass
    rep.check("api", "POST /auth/login", st == 200, f"HTTP {st}")
    st, *_ = api.request("POST", "/api/v1/auth/login", headers=J, json_body={"email": email, "password": "salah-total"})
    rep.check("api", "login wrong password -> 422", st == 422, f"HTTP {st}")

    A = dict(J, Authorization=f"Bearer {token}")
    st, h, body, _ = api.request("GET", "/api/v1/me", headers=A)
    rep.check("api", "GET /me", st == 200 and email.encode() in body, f"HTTP {st}")
    st, *_ = api.request("GET", "/api/v1/me", headers=J)
    rep.check("api", "GET /me without token -> 401", st == 401, f"HTTP {st}")

    st, h, body, _ = api.request("GET", "/api/v1/vehicle-categories?type=mobil", headers=J)
    cat_id = None
    try:
        cats = json.loads(body)["data"]
        cat_id = next(c["id"] for c in cats if c["code"] == "car_mpv")
    except Exception:
        pass
    rep.check("api", "GET /vehicle-categories", st == 200 and cat_id is not None, f"HTTP {st}")
    for path in ["/api/v1/vehicle-types", "/api/v1/vehicle-brands?type=mobil", "/api/v1/service-areas",
                 "/api/v1/price-estimates?vehicle_category=car_mpv"]:
        st, *_ = api.request("GET", path, headers=J)
        rep.check("api", f"GET {path}", st == 200, f"HTTP {st}")

    vid = None
    if cat_id:
        st, h, body, _ = api.request("POST", "/api/v1/me/vehicles", headers=A, json_body={
            "vehicle_category_id": cat_id, "plate_number": "bk 1234 uat", "color": "Hitam", "nickname": "Mobil UAT"})
        try:
            vid = json.loads(body)["data"]["id"]
            plate = json.loads(body)["data"]["plate_number"]
        except Exception:
            plate = None
        rep.check("api", "POST /me/vehicles", st == 201 and vid is not None, f"HTTP {st} {body[:160]!r}" if st != 201 else f"plate={plate}")
    if vid:
        st, *_ = api.request("PUT", f"/api/v1/me/vehicles/{vid}", headers=A, json_body={
            "vehicle_category_id": cat_id, "plate_number": "BK 1234 UAT", "color": "Putih"})
        rep.check("api", "PUT /me/vehicles/{id}", st == 200, f"HTTP {st}")
        st, *_ = api.request("GET", "/api/v1/me/vehicles", headers=A)
        rep.check("api", "GET /me/vehicles", st == 200, f"HTTP {st}")
        st, *_ = api.request("DELETE", f"/api/v1/me/vehicles/{vid}", headers=A)
        rep.check("api", "DELETE /me/vehicles/{id}", st in (200, 204), f"HTTP {st}")

    st, h, body, _ = api.request("GET", "/api/v1/posts?per_page=3", headers=J)
    slug = None
    try:
        slug = json.loads(body)["data"][0]["slug"]
    except Exception:
        pass
    rep.check("api", "GET /posts", st == 200 and slug is not None, f"HTTP {st}")
    if slug:
        st, *_ = api.request("GET", f"/api/v1/posts/{slug}", headers=J)
        rep.check("api", "GET /posts/{slug}", st == 200, f"HTTP {st}")
    st, *_ = api.request("GET", "/api/v1/post-categories", headers=J)
    rep.check("api", "GET /post-categories", st == 200, f"HTTP {st}")

    if not args.skip_ai:
        st, h, body, secs = api.request("POST", "/api/v1/assistant/chat", headers=A, json_body={
            "messages": [{"role": "user", "content": "Berapa estimasi cuci salju untuk mobil SUV di Medan?"}]})
        ok_flag = None
        try:
            ok_flag = json.loads(body).get("ok")
        except Exception:
            pass
        rep.check("api", "POST /assistant/chat", st == 200 and ok_flag is not None,
                  f"HTTP {st} ok={ok_flag} ({secs:.1f}s)")

    st, *_ = api.request("POST", "/api/v1/auth/logout", headers=A)
    rep.check("api", "POST /auth/logout", st == 200, f"HTTP {st}")
    st, *_ = api.request("GET", "/api/v1/me", headers=A)
    rep.check("api", "token revoked after logout -> 401", st == 401, f"HTTP {st}")

    st, h, body, _ = api.request("GET", "/api/documentation")
    rep.check("api", "GET /api/documentation (Swagger UI)", st == 200, f"HTTP {st}")
    if st == 200:
        p = Links()
        p.feed(body.decode("utf-8", "replace"))
        check_assets(api, p.assets, "swagger")
        m = re.search(r'url:\s*"([^"]+)"', body.decode("utf-8", "replace"))
        if m:
            path = same_origin_path(api, m.group(1).replace("\\/", "/"))
            st2, _, docs, _ = api.request("GET", path)
            try:
                spec = json.loads(docs)
                n_paths = len(spec.get("paths", {}))
            except Exception:
                n_paths = 0
            rep.check("api", "Swagger JSON", st2 == 200 and n_paths > 0, f"HTTP {st2}, {n_paths} paths")

    # 5. Assets & logs ---------------------------------------------------------
    print("Assets & logs")
    bad = {p: s for p, s in checked_assets.items() if s != 200}
    rep.check("assets", f"{len(checked_assets)} unique assets loaded", not bad, ", ".join(f"{p}={s}" for p, s in list(bad.items())[:8]))
    errors = new_log_errors(log_offset)
    rep.check("logs", "no new ERROR in laravel.log", not errors, " | ".join(errors[:3]))

    total = len(rep.rows)
    print(f"\nResult: {total - rep.failures}/{total} checks passed, {rep.failures} failed.\n")
    return 0 if rep.failures == 0 else 1


if __name__ == "__main__":
    sys.exit(main())
