<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.5. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Conventions & Rules

- **Strict No-Emojis Rule**: Never use emojis in UI copy, documentation, commit messages, flash messages, or code comments. Use clean SVG icons (Heroicons/Lucide) instead.
- Use descriptive names for variables and methods.
- Check for existing components to reuse before writing a new one.
- Keep UI modern, clean, and highly responsive.
- Keyboard-first architecture: All primary game tracking operations must be completely operable without a mouse.

## Frontend Bundling

- Ensure assets are built with Vite (`npm run build`).

## Pint Code Formatter

- Run `vendor/bin/pint --dirty --format agent` or `vendor/bin/pint` to format PHP code.

## Pest Testing

- Tests are written with Pest (`php artisan test` or `vendor/bin/pest`).
</laravel-boost-guidelines>
