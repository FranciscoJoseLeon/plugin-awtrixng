#!/usr/bin/env python3
"""
Monitors AWTRIX NG and AWTRIX 3 releases and API/documentation commits
so the Jeedom awtrixng plugin stays compatible with firmware/API changes.
"""

import json
import os
import urllib.request
from pathlib import Path

REPOS = [
    ("AWTRIX NG", "Blueforcer/awtrix-ng"),
    ("AWTRIX 3", "Blueforcer/awtrix3"),
]

ROOT_DIR = Path(__file__).resolve().parent.parent
STATE_FILE = ROOT_DIR / "tools" / ".api_state.json"
REPORT_FILE = ROOT_DIR / "tools" / "last_api_report.md"


def fetch_json(url: str) -> dict | list:
    headers = {
        "User-Agent": "Jeedom-AwtrixNG-Watcher/1.0",
        "Accept": "application/vnd.github.v3+json",
    }
    token = os.environ.get("GITHUB_TOKEN")
    if token:
        headers["Authorization"] = f"token {token}"
    req = urllib.request.Request(url, headers=headers)
    with urllib.request.urlopen(req, timeout=15) as resp:
        return json.loads(resp.read().decode("utf-8"))


def load_state() -> dict:
    if STATE_FILE.exists():
        try:
            return json.loads(STATE_FILE.read_text(encoding="utf-8"))
        except Exception:
            return {}
    return {}


def save_state(state: dict):
    STATE_FILE.parent.mkdir(parents=True, exist_ok=True)
    STATE_FILE.write_text(json.dumps(state, indent=2), encoding="utf-8")


def set_gh_output(name: str, value: str):
    out = os.environ.get("GITHUB_OUTPUT")
    if out:
        with open(out, "a", encoding="utf-8") as f:
            f.write(f"{name}={value}\n")


def main():
    state = load_state()
    changes_detected = False
    report = []
    latest_tags = []

    for label, repo in REPOS:
        repo_state = state.setdefault(repo, {})

        # 1. Latest Release
        try:
            rel = fetch_json(f"https://api.github.com/repos/{repo}/releases/latest")
            tag = rel.get("tag_name", "")
            if tag:
                latest_tags.append(f"{label}: {tag}")
            if tag and tag != repo_state.get("last_release_tag"):
                changes_detected = True
                repo_state["last_release_tag"] = tag
                body = (rel.get("body") or "").strip()
                report.append(f"### 🚀 Nueva versión en {label} (`{repo}`): `{tag}`\n{body[:1200]}")
        except Exception as e:
            print(f"Error checking {repo} release: {e}")

        # 2. Recent commits (API / docs / endpoints)
        try:
            commits = fetch_json(f"https://api.github.com/repos/{repo}/commits?per_page=10")
            last_sha = repo_state.get("last_commit_sha")
            new_commits = []
            for c in commits:
                if c["sha"] == last_sha:
                    break
                msg = c["commit"]["message"].splitlines()[0]
                new_commits.append(f"- [`{c['sha'][:7]}`]({c['html_url']}) {msg}")
            if commits:
                repo_state["last_commit_sha"] = commits[0]["sha"]
            if new_commits and last_sha:
                changes_detected = True
                report.append(f"### 📝 Commits recientes en {label} (`{repo}`):\n" + "\n".join(new_commits))
        except Exception as e:
            print(f"Error checking {repo} commits: {e}")

    save_state(state)

    if changes_detected:
        REPORT_FILE.write_text("\n\n".join(report) + "\n", encoding="utf-8")
        print("Changes detected in AWTRIX API / firmware.")
    else:
        print("No new AWTRIX API changes.")

    set_gh_output("changes_detected", "true" if changes_detected else "false")
    set_gh_output("summary_tags", ", ".join(latest_tags) or "AWTRIX")


if __name__ == "__main__":
    main()
