# Controller refactor — Next Level / Saleh Farid

Branch: `next-level-saleh-farid`

## Scope

Only these controllers are refactored: Offer, Order, Payment, Profile, Review,
Setting, and SmartList. Supporting services, contracts, strategies, factories,
Form Requests, and tests are added. Existing routes and other controllers are
unchanged.

Controllers receive validated input, delegate work, and format HTTP responses.
Services receive users, identifiers, arrays, or uploaded files, and return models
or plain data. Services do not receive HTTP requests or construct HTTP responses.
Rejected operations use `OperationRejected`; HTTP status handling stays in the
controller or `OperationResponder`.

## Where the patterns are used

| Controller | Strategy | Factory |
| --- | --- | --- |
| Offer | Column, minimum purchase, featured, and search query filters | QueryFilterFactory |
| Order | Immediate placement versus waiting for hosted checkout | OrderPlacementStrategyFactory |
| Payment | Configured payment labels versus humanized fallback | PaymentMethodLabelFactory |
| Profile | Public disk image storage | UploadStrategyFactory |
| Review | Column and approval query filters | QueryFilterFactory |
| Setting | Public disk image storage | UploadStrategyFactory |
| SmartList | Add, replace, and remove meals; public directory image storage | SmartListMealStrategyFactory and UploadStrategyFactory |

The factories resolve implementations through Laravel's container using the
registry in `config/controller_strategies.php`. Each implementation must satisfy
its contract. Unknown operations and incorrectly registered implementations are
rejected; payment labels deliberately retain the original fallback for unknown
payment methods.

### Open/Closed example

To introduce an additional offer filter:

1. Add a class implementing `App\Contracts\QueryFilter`.
2. Register its class and optional constructor parameters under
   `controller_strategies.filters.offers`.
3. Add validation for the new public input in `OfferIndexRequest`.

The controller, service, factory, pipeline, and existing strategies remain
unchanged. The regression test registers an extra filter at runtime and verifies
its behavior against SQLite, demonstrating the extension point. Validation is
still explicit at the HTTP boundary; unknown client inputs are not trusted.

Adding a storage implementation follows the same approach through `UploadStrategy`.
The `directory` parameter comes from configuration, not user input. Profile and
settings retain public disk paths; smart lists retain filenames under
`public/images/smart-lists` so existing image URLs continue to work.

## Compatibility and fixes

- Existing successful response envelopes, messages, pagination defaults, and
  checkout states are preserved.
- `min_purchase` now groups its nullable condition, preventing it from bypassing
  active-offer and type constraints.
- Offer sort columns and pagination are validated. Unsupported sort columns or
  page sizes outside 1–100 return validation errors.
- Order listing is scoped to the authenticated user. The existing `{id}` show
  route is accepted explicitly and resolves only that user's order. Missing or
  foreign orders return 404.
- PDF invoice responses no longer have an incorrect JSON-only return type.
- Missing review/settings requests are supplied and the review resource import
  points to its existing namespace. Settings writes require an admin. Review
  writes allow only validated fields; approval cannot be supplied by a client.
- Profile validation retains its custom error envelope and single-image message.
- Smart-list creation, replacement, and deletion use transactions. Omitting
  `meal_ids` leaves membership untouched; `meal_ids: []` clears it; adding an
  already attached meal remains idempotent.
- Smart-list uploaded filenames use random hashes to avoid timestamp collisions.
- Order writes use a transaction with automatic rollback.

The original order controller had card charging disabled. This refactor preserves
that behavior: it does not initiate a Stripe charge. The placement strategy selects
the initial order state; it is not a payment gateway implementation.

## Verification

Requires the repository's PHP version constraint (8.3+) and Composer dependencies.

```sh
composer install
php vendor/phpunit/phpunit
```

The suite contains 28 tests and 98 assertions. It uses SQLite in memory and focused
schema fixtures, covering filter composition and extension, discount validation,
request errors, strategy resolution, ownership, smart-list membership and rollback,
order/receipt responses, profile images, settings, and dependency resolution.

The invoice test mocks the PDF renderer and verifies the download response boundary.
No live Stripe transactions or production database migrations are exercised. The
existing application has no routes for ReviewController or settings updates;
these services are tested directly, with a test-only route for settings validation.
Production routes are not added as part of this assignment.

## شرح مختصر للمناقشة

- **Strategy:** كل سلوك قابل للتبديل له Interface وتنفيذات منفصلة، مثل إضافة عناصر
  SmartList أو استبدالها أو حذفها.
- **Factory:** تختار التنفيذ المناسب من الإعدادات وتطلب إنشاءه من Laravel Container.
- **Open/Closed:** نضيف تنفيذًا جديدًا ونسجله، بدل إضافة شروط جديدة داخل الكنترولر
  أو تعديل الخوارزميات الموجودة. قواعد المدخلات الجديدة تظل مسؤولية Form Request.
- **Controller:** يستقبل الطلب ويستدعي الخدمة ويرجع الاستجابة؛ تفاصيل قواعد العمل
  والاستعلامات أصبحت في الخدمات والاستراتيجيات.
