# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Payrello is a self-hosted, plugin-based payment automation platform (PHP 8.2 + MariaDB 10.11) that unifies MFS wallets (bKash, Nagad, Rocket, etc.), payment gateways, and SMS-based verification into one system. The core feature is the **SMS sync pipeline**: Android devices forward MFS confirmation SMS messages to the server, which parses them and auto-reconciles pending transactions.

## Development Commands

```bash
# Start the full stack (web + MariaDB)
docker compose up -d

# Rebuild image after code changes to Dockerfile or docker/ configs
docker compose up -d --build

# Tail application and PHP error logs
docker compose exec web tail -f /var/log/apache2/error.log /var/log/apache2/php_error.log

# Access phpMyAdmin (port from .env, default 8181)
docker compose --profile tools up -d

# Restart web container only (picks up PHP file changes immediately — no rebuild needed)
docker compose restart web

# Run the cron endpoint manually (replace TOKEN with value from DB settings)
curl https://your-domain/cron/TOKEN
```

Default login after first boot: `http://localhost:5060/login` → `admin` / `Admin@1234`

## Architecture

### Request Lifecycle

All traffic enters through `index.php`, which:
1. Requires `pp-content/pp-include/pp-functions.php` (utility + DB layer)
2. Requires `pp-content/pp-include/pp-adapter.php` (session, DB connection, all AJAX action handlers, addon loading)
3. Routes via a `switch($route)` on the first URL segment

URL paths are configurable in DB settings (`geneal-application-settings-*Path`), with defaults:

| Route segment | Handled by |
|---|---|
| `payment/{ref}` | Checkout/receipt page via active theme |
| `payment/{ref}?receipt` | PDF download via FPDF |
| `invoice/{ref}` | Invoice display page |
| `payment-link/{ref}` | Payment link page |
| `api/checkout` | REST API — create payment |
| `api/verify-payment` | REST API — verify payment status |
| `api/refund-payment` | REST API — refund |
| `ipn/{gateway_id}` | IPN/webhook receiver for API gateways |
| `admin` | Admin SPA shell — inner pages driven by AJAX |
| `cron/{token}` | Cron job endpoint — reconciles SMS vs pending transactions |
| `login` / `forgot` / `2fa` | Auth pages |

### Admin Panel (SPA Pattern)

`pp-content/pp-admin/index.php` renders a single shell. Inner pages are loaded via `load_content()` AJAX calls that hit `pp-content/pp-include/pp-adapter.php`. All admin mutations are POST requests to `pp-adapter.php` with an `action` parameter (e.g. `action=sms-data-create`, `action=sms-transmit-bulk`). The adapter file is large (~9500+ lines) and handles all AJAX actions in a chain of `if($action == "...")` blocks.

### Database Layer (`pp-functions.php`)

All DB access goes through four functions — never raw PDO elsewhere:

```php
getData($table, $whereClause, '* FROM', $params)   // returns JSON string: {status, response[]}
insertData($table, $columns[], $values[])
updateData($table, $columns[], $values[], $condition)
deleteData($table, $condition)
```

`getData` always returns `json_encode(['status' => bool, 'response' => array])`. NULL columns are normalised to `'--'` on read. Always `json_decode(..., true)` the result before use.

Settings (key-value store) use `get_env($key, $brand_id)` / `set_env($key, $value, $brand_id)` which read/write the `pp_env` table.

### REST API Authentication

All `/api/*` requests require header `MHS-PAYRELLO-API-KEY: <key>`. The key is looked up in the `pp_api` table. API scopes (e.g. `create_payment`, `verify_payment`, `refund_payment`) are stored as JSON on the API key row.

### SMS Sync Pipeline

This is the core automation flow:

1. **Android Companion App** pairs with the server by scanning a QR code. The server generates a `device_id` + `otp` token stored in `pp_device`.
2. **Device authenticates** all subsequent requests with the `otp` token (POST field `token`).
3. **SMS forwarding** — the app POSTs raw SMS messages to `action=sms-transmit-bulk` (bulk) or individual transmit actions. The server:
   - Calls `senderWhitelist($sender)` to map the SMS sender number to a provider key (e.g. `bkash`, `nagad`, `rocket`)
   - Calls `MFSMessageVerified($providerKey, $message)` which runs regex patterns against known MFS message formats to extract `{type, amount, balance, sender, trxid, datetime}`
   - Inserts the parsed record into `pp_sms_data` with `status=approved`
4. **Cron reconciliation** — the `/cron/{token}` endpoint cross-references `pp_transaction` rows with `status=pending` against `pp_sms_data` rows with matching `sender_key` + `trx_id` + `status=approved`. On match (within tolerance), it marks the transaction `completed`, fires the `transactions.updated` hook, and calls the return URL / webhook.

`reconcileByLongestChain()` handles ambiguous matches when multiple SMS records could match a pending transaction.

### Plugin Systems

#### Gateways (`pp-content/pp-modules/pp-gateways/{slug}/class.php`)

Every gateway class must implement:

```php
class MyGateway {
    public function info(): array      // title, logo, currency, tab, gateway_type
    public function color(): array     // primary_color, text_color, btn_color, btn_text_color
    public function fields(): array    // configuration fields shown in admin
    public function process_payment($data): void  // called inside checkout iframe
    // Optional:
    public function lang_text(): array
    public function supported_languages(): array
}
```

`gateway_type` is `'api'` for automated gateways, `'manual'` for MFS/wallet gateways that rely on SMS confirmation. `tab` groups the gateway in the UI (e.g. `'mfs'`, `'card'`).

#### Themes (`pp-content/pp-modules/pp-themes/{slug}/class.php`)

Themes control the customer-facing checkout UI. Every theme class must implement:

```php
class MyTheme {
    public function info(): array
    public function fields(): array            // theme config fields
    public function supported_languages(): array
    public function lang_text(): array
    public function renderCheckout($data): void
    public function renderInvoice($data): void
    public function renderPaymentLink($data): void
    public function renderPaymentLinkDefault($data): void
}
```

The `$data` array passed to render methods contains: `transaction` or `invoice`, `brand`, `faqs`, `options` (theme field values), `lang`.

#### Addons (`pp-content/pp-modules/pp-addons/`)

Addons are loaded at boot from `pp_addon` table rows with `status=active`. They hook into the event system.

#### Hook System

Lightweight WordPress-style hooks in `pp-functions.php`:

```php
add_action('hook.name', callable $cb, int $priority = 10);
do_action('hook.name', ...$args);

add_filter('hook.name', callable $cb, int $priority = 10);
apply_filters('hook.name', $value, ...$args);
```

Key hooks: `transactions.updated`, `invoice.updated`, `invoice.total`, `system.update.available`.

### HTTPS / Reverse Proxy

The app runs on HTTP inside Docker. When behind an SSL-terminating proxy, Apache must receive `X-Forwarded-Proto: https` and the config `docker/apache/payrello.conf` must have `SetEnvIf X-Forwarded-Proto https HTTPS=on`. The `pp_site_url()` function detects HTTPS via `$_SERVER['HTTPS']`, port 443, or `HTTP_X_FORWARDED_PROTO`. Logos and assets are stored in DB as full URLs — if the base URL changes (HTTP→HTTPS), assets must be re-uploaded.

### File Storage

User uploads (logos, brand media) are stored under `pp-media/storage/` and served directly by Apache. This directory is a Docker named volume (`payrello_media`) so it persists across container recreates. The stored URL pattern is `{site_url}pp-media/storage/{filename}`.

### PDF Receipts

`pp_downloadReceiptPDF($data)` in `pp-functions.php` uses the bundled FPDF library (`pp-media/sdk/fpdf/fpdf.php`). It is called when `?receipt` is appended to any payment URL. FPDF's `Image()` fetches the brand logo via HTTP — if the logo URL is unreachable from inside the container, the image is silently skipped. Always call `exit()` after `$pdf->Output('D', ...)`.

### Key Conventions

- **Empty/null values** are stored as the string `'--'` in the DB, never PHP `null`. Always compare against `'--'` when checking for missing config.
- **Money** — always use `money_add()`, `money_sub()`, `money_mul()`, `money_div()`, `money_round()`. These wrap BCMath at 8 decimal scale. Never use float arithmetic for amounts.
- **Input sanitisation** — use `escape_string()` for all user input before DB use.
- **IDs** — generated via `generateItemID($length, $maxLength)`, which returns a random alphanumeric string.
- All datetimes are stored in UTC. Use `getCurrentDatetime('Y-m-d H:i:s')` for inserts.
- The `Payrello_INIT` constant must be defined before any include file is loaded directly — all files check for it and return 403 otherwise.
