# Python Signal API Contract

Base URL: `{APP_URL}/api`  
Auth: one of:

```http
Authorization: Bearer {API_TOKEN}
X-API-Token: {API_TOKEN}
```

`API_TOKEN` must match Laravel `.env`. Missing/invalid token → `401`.

All successful JSON bodies include `success: true`. Errors use Laravel validation (`422`) or HTTP status codes.

---

## 1. Create signal

`POST /signals`

```json
{
  "market_type": "forex",
  "symbol": "XAUUSD",
  "entry_price": "2350.50",
  "tp1": "2360.00",
  "tp2": "2370.00",
  "tp3": "2380.00",
  "stop_loss": "2340.00",
  "image_path": null,
  "target_audience": "all"
}
```

| Field | Required | Values |
|-------|----------|--------|
| `market_type` | yes | `forex`, `crypto` |
| `symbol` | yes | string ≤ 50 |
| `entry_price`, `tp1`, `stop_loss` | yes | string |
| `tp2`, `tp3`, `image_path` | no | string |
| `target_audience` | no | `all` (everyone / promo), `vip_only` (default if omitted in older clients may be `vip_only`) |

Response `201`:

```json
{
  "success": true,
  "message": "Signal created successfully.",
  "data": { "id": 1, "symbol": "XAUUSD", "status": "pending", "...": "..." }
}
```

Side effect: dispatches `BroadcastSignalJob` → per-language queues `telegram-fa` / `telegram-en`.

---

## 2. Signal update

`POST /signals/update`

```json
{
  "signal_id": 1,
  "update_message_fa": "استاپ به ورود منتقل شد",
  "update_message_en": "SL moved to entry"
}
```

Response `201` with `data.signal` + `data.update`.

---

## 3. Signal result

`POST /signals/result`

```json
{
  "signal_id": 1,
  "result": "win",
  "pips_gained": 85
}
```

| Field | Required | Values |
|-------|----------|--------|
| `result` | yes | `pending`, `win`, `loss`, `neutral` |
| `pips_gained` | no | integer |

Response `200`.

---

## Minimal Python client

See `examples/python/signal_client.py`.

```bash
export SIGNAL_API_URL=https://your-domain.com/api
export SIGNAL_API_TOKEN=your-token
python examples/python/signal_client.py
```

---

## Notes for the AI engine

1. Prefer `target_audience=all` for public/promo signals; `vip_only` for paid content.
2. Keep `image_path` as a public URL or a path the Laravel app can read when sending photos.
3. Do not call Telegram directly from Python for user broadcast — Laravel owns rate limits and FA/EN queues.
4. Idempotency is not built-in: avoid double-POSTing the same signal.
