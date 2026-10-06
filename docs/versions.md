# Versions and portability

Official image manifests were inspected on 2026-10-06. The Dockerfile pins multi-architecture index digests, allowing Docker to select the host architecture; no `platform: linux/amd64` override is used.

| Image tag | Resolved version | Index digest |
| --- | --- | --- |
| `php:8.4-apache-bookworm` | PHP 8.4.26 | `sha256:9e811795a606ca7f8586dee48d32acf444985c19ac252d70d181cd71a4603898` |
| `composer:2` | Composer 2.10.3 | `sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac` |
| `node:24-bookworm-slim` | Node 24.21.0 | `sha256:d6aa754f16b3197301076f047b5def2f02ea1dbbc2ca920407d46d7ec7f87b20` |

All three published manifests include both `linux/arm64` and `linux/amd64`. Execution checks run on Docker Desktop's `linux/arm64` engine on Apple Silicon. AMD64 execution is untested.

The PHP base already includes Laravel's required extensions and PHPUnit's DOM/XML/mbstring extensions; `unzip` is installed for Composer package extraction. Both runtime and tools check actual platform requirements with Composer, without suppressing them. Node, Composer, its build-only superuser environment flag, and all npm/development PHP dependencies stay out of the final PHP/Apache application image. A PHP tools stage adds Composer for dependency installation and checks; the application derives from the same PHP base without that addition.

Composer and npm lockfiles are committed. Locked versions verified during the containerized checks:

| Package | Version |
| --- | --- |
| Laravel framework | 13.35.0 |
| PHPUnit | 12.5.38 |
| Laravel Pint | 1.32.1 |
| PHPStan / Larastan | 2.3.0 / 3.12.3 |
| React / React DOM | 19.3.0 |
| TypeScript | 6.0.3 |
| Vite | 8.3.3 |
| Oxlint | 1.87.0 |

The tools image creates an empty `.env` automatically to avoid Collision reporting Dotenv's suppressed missing-file warning. It supplies no settings or key, does not cache configuration before tests, and is separate from the runtime image, which has no `.env`. All actual settings come from environment variables; PHPUnit can override them for tests.

Executed checks are recorded in the [final verification](verification.md), with the historical domain checkpoint in the [Phase 2 review](phase-2-review.md). CI pins the commit behind [actions/checkout v7](https://github.com/actions/checkout) and runs application tooling in Docker; no host PHP/Composer/Node setup is used.

Compatibility references: [Laravel 13 releases](https://laravel.com/docs/13.x/releases), [Laravel server requirements](https://laravel.com/docs/13.x/deployment), [PHPUnit supported versions](https://phpunit.de/supported-versions.html), [Vite requirements](https://vite.dev/guide/), [Larastan](https://github.com/larastan/larastan).
