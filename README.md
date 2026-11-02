# ShopHub — E-commerce REST API

A backend-only Laravel API for a small online store: product catalog, cart, coupons, checkout, orders and reviews.

**Stack:** Laravel 13 · PHP 8.4 · Sanctum · MySQL (SQLite for tests) · Pest

## Features

- **Auth:** register, login and logout with Sanctum API tokens. Login and register are rate-limited, and new accounts are always customers (the client can't set its own role).
- **Catalog:** products and categories with search, category and price filters. Only admins can create, update or delete.
- **Cart:** one cart per user. Quantities are checked against stock.
- **Coupons:** percentage or fixed discounts with expiry dates and usage limits.
- **Checkout:** creates the order, payment (mocked) and order items, lowers stock, redeems the coupon and clears the cart.
- **Orders:** customers see only their own orders. Admins see all orders and update their status.
- **Reviews:** customers can review products.

## How it is built

- Business logic lives in service classes (`CartService`, `CouponService`, `OrderService`), not in controllers.
- Input is validated with Form Requests. Responses are shaped with API Resources, so internal fields like password hashes are never returned.
- Authorization uses Policies (for example, order ownership) and an `admin` middleware.

### Checkout and concurrent requests

Checkout runs inside one database transaction, so the order, payment, stock and coupon changes are saved together or not at all.

Stock and coupon usage are updated with **conditional updates**. The limit check and the change happen in the same SQL statement:

```php
Product::where('id', $id)->where('stock', '>=', $qty)->decrement('stock', $qty);
```

If two customers try to buy the last item at the same time, only one update succeeds. The other checkout throws an error, its transaction is rolled back, and the API returns `422`. Coupon usage limits work the same way.

## API

All routes are under `/api/v1`. Send the token as `Authorization: Bearer <token>`.

| Method | Route | Access |
|---|---|---|
| POST | `/register`, `/login` | Public |
| GET | `/products`, `/products/{id}`, `/categories` | Public |
| GET / POST | `/cart`, `/cart/items` | Customer |
| PATCH / DELETE | `/cart/items/{id}` | Customer (own cart) |
| POST | `/coupons/preview` | Customer |
| GET / POST | `/orders` | Customer (own orders) |
| GET | `/orders/{id}` | Owner or admin |
| POST / PATCH / DELETE | `/products`, `/categories`, `/coupons` | Admin |
| PATCH | `/orders/{id}/status` | Admin |

## Running locally

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## Tests

```bash
./vendor/bin/pest
```

The feature tests cover:

- auth, plus blocking clients from setting their own role
- cart stock limits and cart ownership
- checkout, including the last-item race condition and a full rollback when it fails
- coupon rules, including the usage-limit race condition
- order access control for customers and admins
