# BEARSAMPP DUAL-PHP ARCHITECTURE

This constraint applies to ALL code generation, debugging, path-switching scripts,
environment variable handling, and extension fixes (e.g. ext-ldap missing) in this repo.

Note: `[Drive]:\Bearsampp-development\sandbox` varies per user install.

## 1. INTERNAL CORE ENGINE (Hidden from User)
- Path: `[Drive]:\Bearsampp-development\sandbox\core\libs\php\php.exe`
- Ini: `[Drive]:\Bearsampp-development\sandbox\core\libs\php\php.ini`
- Purpose: Internal Bearsampp control panel engine only. Used exclusively by
  application code to run the dashboard, background tasks, and service switches.
- Constraint: The user's terminal/CLI must NEVER see or load this PHP binary or
  its php.ini.

## 2. USER DEVELOPMENT RUNTIME (Visible to User)
- Path: `[Drive]:\Bearsampp-development\sandbox\bin\php\php<version>\php.exe`
  (e.g. `...\bin\php\php8.5.9\php.exe`)
- Ini: `[Drive]:\Bearsampp-development\sandbox\bin\php\php<version>\php.ini`
- Purpose: The actual PHP version selected by the user in the Bearsampp UI.
- Expected Behavior: When a user opens their standard system terminal or the
  Bearsampp built-in console and types `php --ini`, they must strictly see the
  path to this versioned php.ini (e.g. `...\bin\php\php8.5.9\php.ini`).

## RULE
Any fix targeting the USER's terminal must modify or point to the dynamic
`bin\php\php<version>\` paths, NOT the static `core\libs\php\` path.
