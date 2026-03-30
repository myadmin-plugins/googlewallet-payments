---
name: plugin-settings
description: Adds a new admin setting to getSettings() in src/Plugin.php. Use when the user says 'add setting', 'new config option', 'add merchant field', or needs a new GOOGLE_WALLET_* constant wired into the settings panel. Supports add_text_setting, add_password_setting, add_radio_setting, add_dropdown_setting. Do NOT use for adding hooks, menu items, or requirements.
---
# plugin-settings

## Critical

- **Always** guard `add_text_setting` and `add_password_setting` value arguments with `defined('CONSTANT') ? CONSTANT : ''` — never reference an undefined constant directly.
- **Never** guard `add_radio_setting` or `add_dropdown_setting` value arguments with `defined()` — they use bare constants (e.g. `GOOGLE_WALLET_ENABLED`) because the host system defines them before the event fires.
- Setting keys must be `snake_case` with the `google_wallet_` prefix (e.g. `google_wallet_api_key`).
- Constant names must be the `UPPER_SNAKE_CASE` equivalent (e.g. `GOOGLE_WALLET_API_KEY`).
- Use tabs for indentation — spaces will fail `.scrutinizer.yml` checks.

## Instructions

1. **Identify the setting type** needed:
   - Free-text input → `add_text_setting`
   - Secret/credential → `add_password_setting`
   - Boolean toggle → `add_radio_setting`
   - Fixed list of options → `add_dropdown_setting`

2. **Open `src/Plugin.php`** and locate `getSettings(GenericEvent $event)`. All calls go inside this method after the existing `$settings->add_*` lines.

3. **Add the call** using the exact signature pattern from the existing code:

   ```php
   // Text (free input)
   $settings->add_text_setting(_('Billing'), _('Google Wallet'), 'google_wallet_KEY', _('Label'), _('Label'), (defined('GOOGLE_WALLET_KEY') ? GOOGLE_WALLET_KEY : ''));

   // Password / secret
   $settings->add_password_setting(_('Billing'), _('Google Wallet'), 'google_wallet_KEY', _('Label'), _('Label'), (defined('GOOGLE_WALLET_KEY') ? GOOGLE_WALLET_KEY : ''));

   // Radio (boolean)
   $settings->add_radio_setting(_('Billing'), _('Google Wallet'), 'google_wallet_KEY', _('Label'), _('Label'), GOOGLE_WALLET_KEY, [true, false], ['Enabled', 'Disabled']);

   // Dropdown
   $settings->add_dropdown_setting(_('Billing'), _('Google Wallet'), 'google_wallet_KEY', _('Label'), _('Label'), GOOGLE_WALLET_KEY, [false, true], ['Option A', 'Option B']);
   ```

   Replace `KEY` with the specific suffix (e.g. `api_key` → key `google_wallet_api_key`, constant `GOOGLE_WALLET_API_KEY`).

4. **Verify** the new line is inside `getSettings()` and follows the existing block of `$settings->add_*` calls (lines 74–79 of `src/Plugin.php`). Do not add it outside the method.

5. **Run tests** to confirm nothing is broken:
   ```bash
   vendor/bin/phpunit
   ```
   All tests must pass before considering the change complete.

## Examples

**User says:** "Add a text setting for a Google Wallet webhook secret"

**Actions taken:**
- Setting key: `google_wallet_webhook_secret`
- Constant: `GOOGLE_WALLET_WEBHOOK_SECRET`
- Type: password (it's a secret)
- Add inside `getSettings()` in `src/Plugin.php`:

```php
$settings->add_password_setting(_('Billing'), _('Google Wallet'), 'google_wallet_webhook_secret', _('Webhook Secret'), _('Webhook Secret'), (defined('GOOGLE_WALLET_WEBHOOK_SECRET') ? GOOGLE_WALLET_WEBHOOK_SECRET : ''));
```

**Result:** New password field appears in the Google Wallet section of the admin settings panel under Billing.

## Common Issues

- **`PHP Notice: Use of undefined constant GOOGLE_WALLET_FOO`**: You used a bare constant in `add_text_setting` or `add_password_setting`. Wrap it: `(defined('GOOGLE_WALLET_FOO') ? GOOGLE_WALLET_FOO : '')`.
- **`PHP Fatal error: Call to undefined method $settings->add_text_setting()`**: `$settings` was not retrieved via `$event->getSubject()`. Confirm the first line of `getSettings()` is `$settings = $event->getSubject();`.
- **Scrutinizer fails with indentation error**: You used spaces instead of tabs. Re-indent with tabs (`\t`).
- **`vendor/bin/phpunit` fails `testPublicMethodsList`**: A new public method was accidentally added to the class. The test asserts exactly `['__construct', 'getHooks', 'getMenu', 'getRequirements', 'getSettings']` — don't add new public methods.
- **Setting not appearing in admin panel**: The `system.settings` hook must be active in `getHooks()`. Confirm `'system.settings' => [__CLASS__, 'getSettings']` is present and not commented out.