# MyAdmin Google Wallet Payments Plugin

Composer plugin integrating Google Wallet into the MyAdmin billing system via Symfony EventDispatcher hooks.

## Commands

```bash
composer install                          # install deps
vendor/bin/phpunit                        # run all tests
vendor/bin/phpunit tests/ -v              # verbose
```

## Structure

- **Plugin class**: `src/Plugin.php` · namespace `Detain\MyAdminGooglewallet\`
- **Tests**: `tests/PluginTest.php` · namespace `Detain\MyAdminGooglewallet\Tests\`
- **Autoload**: PSR-4 via `composer.json` · `src/` → `Detain\MyAdminGooglewallet\`
- **Test config**: `phpunit.xml.dist`
- **CI/CD**: `.github/` · workflows for automated testing and deployment pipelines
- **IDE config**: `.idea/` · `inspectionProfiles/` · `deployment.xml` · `encodings.xml`

## Plugin Architecture

All plugins implement these static methods on a single `Plugin` class:

```php
public static function getHooks(): array  // maps event names → [__CLASS__, 'method']
public static function getSettings(GenericEvent $event): void  // registers admin settings
public static function getMenu(GenericEvent $event): void      // registers admin menu items
public static function getRequirements(GenericEvent $event): void  // registers file deps
```

- Hook registration: return `['event.name' => [__CLASS__, 'methodName']]` from `getHooks()`
- Settings use `$settings->add_radio_setting()`, `add_dropdown_setting()`, `add_text_setting()`, `add_password_setting()`
- Constants follow pattern: `GOOGLE_WALLET_ENABLED`, `GOOGLE_WALLET_SELLER_ID`, `GOOGLE_WALLET_SELLER_SECRET`
- Always guard with `defined('CONSTANT')` before using payment constants

## Conventions

- Static properties: `public static $name`, `$description`, `$help`, `$type = 'plugin'`
- Tabs for indentation (enforced by `.scrutinizer.yml`)
- camelCase for parameters and properties
- Commit messages: lowercase, descriptive
- No PDO — this plugin defers DB access to the host MyAdmin system

## Testing Patterns

```php
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\EventDispatcher\GenericEvent;

// Use ReflectionClass to assert static properties and method signatures
$this->reflection = new ReflectionClass(Plugin::class);
// Assert hook callbacks reference real static methods
// Assert event handler params are typed GenericEvent
```

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->
