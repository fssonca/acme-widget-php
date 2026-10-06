# Acme Widget Co

Laravel and React/TypeScript basket quotation exercise. **Current checkpoint: Phases 1–2.** The containerized scaffold and PHP domain are implemented; the API and shopping screen await the model review required by the implementation plan. The browser currently shows a small placeholder.

Start here: [`Basket.php`](backend/app/Domain/Basket/Basket.php), its [domain types](backend/app/Domain/Basket), and [acceptance tests](backend/tests/Unit/Domain/Basket/BasketTest.php). The [Phase 2 review](docs/phase-2-review.md) explains the model, calculations, alternatives, and direct usage.

## Run

Docker Desktop or Docker Engine with Compose v2 supporting `--wait` is the only application-runtime prerequisite. The first build needs access to Docker Hub, Packagist, and npm.

```sh
docker compose up --build --wait
```

Open **http://localhost:8080**. Docker installs locked dependencies, compiles React, generates a startup encryption key, caches Laravel configuration, and serves built assets and Laravel with Apache. No host PHP, Composer, Node, `.env`, key generation, database, or second server is needed. Readiness checks both the built HTML and Laravel's `/up` route. Only `app` starts by default.

```sh
docker compose ps
docker compose logs -f app
docker compose down
# Rebuild after editing source:
docker compose up --build --wait
# Choose another host port (APP_URL follows it):
APP_PORT=8081 docker compose up --build --wait
```

An optional `APP_KEY` environment variable supplies a fixed Laravel key. Otherwise a fresh valid key is generated for each startup; this quotation POC is stateless.

## Checks

Run from the repository root. Tools install development dependencies in their images, honor the supplied command, and do not start the app. They copy source during the build, so use `--build` after edits.

```sh
docker compose config
docker compose --profile tools run --rm --build backend-tools php artisan test
docker compose --profile tools run --rm --build backend-tools vendor/bin/pint --test
docker compose --profile tools run --rm --build backend-tools vendor/bin/phpstan analyse
docker compose --profile tools run --rm --build frontend-tools npm run typecheck
docker compose --profile tools run --rm --build frontend-tools npm run lint
docker compose --profile tools run --rm --build frontend-tools npm run build
```

The domain tests extend PHPUnit's `TestCase` and never boot Laravel. Scaffold HTTP tests extend Laravel's `TestCase`; neither suite needs a database or migrations. PHPStan runs at level 8, without a baseline. [Version and architecture details](docs/versions.md) record the pinned official images and locked packages.

## Pricing decisions

Money is nonnegative integer USD cents. `Basket` receives a catalogue, a delivery policy, and a list of offers; `add(code)` adds one unit and `total()` returns a two-decimal string. Quantities are stored by case-sensitive code. Calculations create immutable line snapshots and do not change the basket.

| Basket | Total |
| --- | ---: |
| B01, G01 | $37.85 |
| R01, R01 | $54.37 |
| R01, G01 | $60.85 |
| B01, B01, R01, R01, R01 | $98.27 |

Each complete red pair has one full-price $32.95 unit and one $16.47 unit. The half-price is rounded down **per discounted unit**, giving a $16.48 saving per pair. An unmatched red remains full price. Eligibility repeats and is independent of insertion order.

Delivery follows the discounted merchandise subtotal: below $50 costs $4.95; $50 to below $90 costs $2.95; $90 or more is free. The two-red example establishes this ordering: $49.42 merchandise + $4.95 delivery = $54.37. An empty basket has all-zero amounts.

The examples do not uniquely determine rounding beyond one pair:

| Policy | Four R01 | Six R01 |
| --- | ---: | ---: |
| Aggregate discount, half-up | $98.85 | $148.27 |
| **Chosen: each half-price unit rounded down** | **$98.84** | **$148.26** |
| Aggregate discount, half-even | $98.85 | $148.28 |

These alternatives are compared with integer arithmetic in the tests. Rounding, repeating pairs, case sensitivity, empty-basket behavior, USD-only pricing, and no taxes are documented assumptions for candidate review. Prices remain fixed for a basket's lifetime. Discounts from independent offers are summed; overlapping-offer priority and best-offer selection are outside scope. Returns/refunds, persistence, accounts, checkout, payment, and inventory are outside scope.

## Boundaries and next stage

The domain uses ordinary PHP with no Laravel facades, configuration access, HTTP types, or Eloquent. Catalogue products, offer targets, and delivery policies can be replaced through constructor injection. Laravel will handle configuration, request validation, dependency construction, and JSON responses after the checkpoint is approved. React will display authoritative API quotations, with cancellation and protection against stale responses.

Phases 3–5 remain pending: Laravel quotation endpoints/validation and feature tests, the functional React screen and actual UI screenshot, then CI and complete end-to-end verification. No quotation endpoints exist at this checkpoint.

Implementation used AI assistance. No recruiter's AI-use policy was supplied; the candidate must resolve any applicable assessment policy before submission. Local commits use the existing Git identity and no optional AI co-author trailer. That attribution choice and any external disclosure remain for candidate review. Nothing has been published or submitted.
