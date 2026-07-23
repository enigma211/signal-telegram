#!/usr/bin/env python3
"""Minimal client for Nova Signal Laravel signal API."""

from __future__ import annotations

import json
import os
import sys
import urllib.error
import urllib.request


BASE_URL = os.environ.get("SIGNAL_API_URL", "http://signal-telegram.test/api").rstrip("/")
TOKEN = os.environ.get("SIGNAL_API_TOKEN", "")


def request(method: str, path: str, payload: dict | None = None) -> dict:
    if not TOKEN:
        raise SystemExit("Set SIGNAL_API_TOKEN (same as Laravel API_TOKEN).")

    data = None if payload is None else json.dumps(payload).encode("utf-8")
    req = urllib.request.Request(
        f"{BASE_URL}{path}",
        data=data,
        method=method,
        headers={
            "Authorization": f"Bearer {TOKEN}",
            "Content-Type": "application/json",
            "Accept": "application/json",
        },
    )
    try:
        with urllib.request.urlopen(req, timeout=30) as resp:
            body = resp.read().decode("utf-8")
            return json.loads(body) if body else {}
    except urllib.error.HTTPError as exc:
        err = exc.read().decode("utf-8", errors="replace")
        raise SystemExit(f"HTTP {exc.code}: {err}") from exc


def create_signal() -> dict:
    return request(
        "POST",
        "/signals",
        {
            "market_type": "forex",
            "symbol": "XAUUSD",
            "entry_price": "2350.50",
            "tp1": "2360.00",
            "tp2": "2370.00",
            "stop_loss": "2340.00",
            "target_audience": "all",
        },
    )


def update_signal(signal_id: int) -> dict:
    return request(
        "POST",
        "/signals/update",
        {
            "signal_id": signal_id,
            "update_message_fa": "استاپ به ورود منتقل شد",
            "update_message_en": "SL moved to entry",
        },
    )


def result_signal(signal_id: int) -> dict:
    return request(
        "POST",
        "/signals/result",
        {
            "signal_id": signal_id,
            "result": "win",
            "pips_gained": 95,
        },
    )


def main() -> None:
    created = create_signal()
    print("created:", json.dumps(created, ensure_ascii=False, indent=2))
    signal_id = int(created["data"]["id"])
    print("update:", json.dumps(update_signal(signal_id), ensure_ascii=False, indent=2))
    print("result:", json.dumps(result_signal(signal_id), ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
    sys.exit(0)
