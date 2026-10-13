# Tawsila - Delivery Management API

A RESTful delivery management API built with Laravel 12 and MySQL. Tawsila provides a structured backend for managing customers, stores, products, orders, and delivery drivers through secure authentication, role-based authorization, and controlled order processing.

The project demonstrates layered architecture, service classes, contracts, dependency injection, Laravel Policies, PHP Enums, Eloquent relationships, query scopes, global scopes, request validation, and database transactions.

## Table of Contents

1. [Project Overview](#project-overview)
2. [Features](#features)
3. [Technology Stack](#technology-stack)
4. [System Architecture](#system-architecture)
5. [Design Patterns and Design Decisions](#design-patterns-and-design-decisions)
6. [Database Design](#database-design)
7. [Eloquent Relationships](#eloquent-relationships)
8. [Authentication and Authorization](#authentication-and-authorization)
9. [Order Lifecycle and Business Rules](#order-lifecycle-and-business-rules)
10. [Validation and Error Handling](#validation-and-error-handling)
11. [Query Scopes and Global Scopes](#query-scopes-and-global-scopes)
12. [Database Transactions and Data Integrity](#database-transactions-and-data-integrity)
13. [Performance and Reliability](#performance-and-reliability)
14. [API Endpoints](#api-endpoints)
15. [Installation and Setup](#installation-and-setup)
16. [Project Structure](#project-structure)
17. [Design Trade-offs](#design-trade-offs)
18. [Future Improvements](#future-improvements)

---

## Project Overview

Tawsila is a delivery management system that connects customers, store managers, and delivery drivers through a RESTful API.

The application separates HTTP handling, validation, authorization, business logic, and data access responsibilities into dedicated components. This organization helps keep the code maintainable and makes business rules easier to understand and extend.

### User Roles

| Role | Responsibilities |
|---|---|
| Customer | Browse stores and products, place orders, view personal orders, and cancel eligible orders. |
| Store Manager | Manage authorized stores and products, view store orders, accept orders, and assign delivery drivers. |
| Driver | View assigned orders and update delivery progress. |
| Administrator | Perform explicitly authorized administrative operations. |

Access to each operation depends on the authentication status, role, resource ownership, and applicable business rules.

## Features

- User registration, login, and logout.
- Token-based authentication using Laravel Sanctum.
- Role-based access control and account activity checks.
- Store creation, listing, updating, suspension, and reactivation.
- Product management and availability control.
- Order placement with multiple products.
- Order acceptance and driver assignment.
- Delivery progress tracking.
- Order cancellation according to business rules.
- Form Request validation.
- Authorization through Laravel Policies.
- Business logic organized in service classes.
- Service contracts and dependency injection.
- PHP Enums for user roles and order statuses.
- Eloquent relationships and reusable query scopes.
- A global scope for excluding suspended stores from ordinary queries.
- Database transactions for critical multi-step operations.
- Lazy-loading prevention during development.

## Technology Stack

| Technology | Purpose |
|---|---|
| PHP | Server-side programming language. |
| Laravel 12 | Application framework, routing, middleware, validation, and application services. |
| MySQL | Relational database for persistent application data. |
| Laravel Sanctum | API token authentication. |
| Eloquent ORM | Database queries, relationships, model casts, and scopes. |
| Form Requests | Centralized validation of incoming request data. |
| Laravel Policies | Resource-level authorization. |
| PHP Enums | Explicit, type-safe role and status values. |
| Service Classes | Business operations and workflow coordination. |
| Contracts | Interfaces defining service capabilities. |
| Dependency Injection | Supplying required services through Laravel's service container. |

---

## System Architecture

The project follows a **layered application architecture**. Each component has a specific responsibility rather than placing all application logic inside controllers.

### Request Flow

```text
Client
  |
  v
API Routes
  |
  v
Authentication and Middleware
  |
  v
Controller
  |
  +----> Form Request Validation
  |
  +----> Policy Authorization
  |
  v
Service Layer
  |
  v
Eloquent Models and Relationships
  |
  v
MySQL Database
  |
  v
HTTP JSON Response
```

This diagram represents the logical responsibilities of the application. The exact execution order of validation and authorization depends on the route and controller implementation.

### Responsibilities of Each Layer

**1. Routes**

The API routes define the available endpoints, HTTP methods, controller actions, and middleware requirements.

Keeping routes declarative makes the API easier to inspect and maintain.

**2. Middleware**

Middleware handles request-level concerns such as authentication and account activity checks before protected operations proceed.

**3. Controllers**

Controllers receive requests, coordinate validation and authorization, call the appropriate service, and return HTTP responses.

They should not contain large amounts of business logic or complicated database workflows.

**4. Form Requests**

Form Requests validate incoming data, such as required fields, data types, numeric values, and permitted input formats.

This keeps input validation separate from business operations.

**5. Policies**

Policies determine whether the authenticated user is authorized to perform an action on a resource.

For example, a customer should not access another customer's private order.

**6. Services**

Services implement business operations, coordinate related model changes, and enforce workflow rules.

**7. Contracts**

Contracts define the operations expected from a service without specifying how those operations are implemented.

**8. Models**

Eloquent models represent application entities, define relationships, provide query scopes, and support database operations.

**9. Database**

MySQL stores persistent application data and enforces relational constraints defined by the database schema.

### Why Use a Layered Architecture?

Without clear separation, controllers can become responsible for validation, permissions, database queries, order calculations, and status transitions all at once.

Separating these concerns provides several benefits:

- Easier code review and debugging.
- Less duplicated business logic.
- Better organization of authorization rules.
- More reusable business operations.
- Easier testing of individual responsibilities.
- Greater flexibility when requirements change.

---

## Design Patterns and Design Decisions

### 1. Service Layer Pattern

The project uses dedicated service classes, including:

- `OrderService`
- `StoreService`
- `ProductService`

The service layer contains application operations that coordinate business rules and data changes.

For example, order processing may involve checking product availability, creating an order, creating its order items, and updating the order status.

**Why use the Service Layer?**

- Keeps controllers focused on HTTP concerns.
- Centralizes business operations.
- Reduces duplication across controllers.
- Makes multi-step workflows easier to understand.
- Allows the same business operations to be reused by different entry points.

**Why not put everything in controllers?**

Controllers are responsible for handling HTTP requests and responses. Large business workflows inside controllers make them harder to maintain and encourage duplicated logic.

The service layer gives these workflows a dedicated place.

### 2. Contract and Dependency Injection

The project defines `OrderServiceInterface` as a contract for order-related operations.

The concrete `OrderService` class implements the interface. Laravel's service container can resolve the implementation through a binding in `AppServiceProvider`.

Example:

```php
$this->app->bind(
    OrderServiceInterface::class,
    OrderService::class
);
```

A controller can then receive the interface through constructor injection:

```php
public function __construct(
    private OrderServiceInterface $orderService
) {}
```

The examples illustrate the pattern; constructor signatures and method names should match the actual implementation.

**Why use a Contract?**

- Reduces direct coupling to a concrete service class.
- Makes the expected service operations explicit.
- Allows an alternative implementation to be supplied when needed.
- Makes it easier to substitute a mock or fake in suitable tests.
- Helps define a clear boundary between a service's public capabilities and its internal implementation.

**Why not create a contract for every service?**

An interface is not automatically beneficial just because a class exists. It is most useful when abstraction, substitution, or a clearly defined service boundary provides a real advantage.

The project uses a contract where that abstraction is useful rather than adding interfaces without a specific purpose.

### 3. Policy Pattern

The application uses Laravel Policies such as:

- `StorePolicy`
- `ProductPolicy`
- `OrderPolicy`

Policies group authorization rules associated with particular resources.

Examples include:

- Checking whether a manager owns or controls a store.
- Checking whether a customer owns an order.
- Checking whether a driver is assigned to an order.
- Determining whether a user may update a resource.

**Why use Policies?**

- Keeps access-control logic separate from business operations.
- Avoids repeating ownership checks throughout controllers.
- Makes permissions easier to review.
- Integrates with Laravel's authorization mechanisms.
- Helps apply consistent rules to resource operations.

**Why not rely only on role checks?**

A role answers what category of user someone belongs to. It does not necessarily establish permission to access a specific resource.

For example, two customers may have the same role, but one customer must not automatically be allowed to view the other customer's order. Authorization may require both role and ownership checks.

### 4. Enum Pattern

The project uses PHP Enums such as `UserRole` and `OrderStatus`.

`UserRole` represents the application's defined user roles, while `OrderStatus` represents the permitted states of an order.

Example:

```php
enum OrderStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case PICKED_UP = 'picked_up';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
}
```

This is an illustrative example. The actual case names and values are defined by the project's enum classes.

An Eloquent model can cast its status attribute to an enum:

```php
protected function casts(): array
{
    return [
        'status' => OrderStatus::class,
    ];
}
```

**Why use Enums?**

- Avoids scattering arbitrary strings throughout the application.
- Makes valid values explicit.
- Improves readability and type safety.
- Helps prevent accidental spelling inconsistencies.
- Works with Eloquent model casting.

**Why not use ordinary strings everywhere?**

Using strings such as `'pending'`, `'accepted'`, and `'delivered'` throughout the code makes typos easier and valid states harder to discover.

Enums centralize the allowed values and make the code's intention clearer.

**Why not use Enums for every field?**

Enums are useful when a field represents a fixed set of meaningful values. They are unnecessary for unrestricted text, descriptions, addresses, or other fields whose values are not a fixed set of alternatives.

### 5. Eloquent ORM and Active Record

The application uses Laravel Eloquent models to represent database entities such as users, stores, products, orders, and order items.

Eloquent follows the Active Record approach: models combine the representation of a record with methods for querying and persisting it.

For example, an order model can define relationships to its customer, store, driver, and order items.

**Why use Eloquent?**

- Provides readable database operations.
- Supports relationships and eager loading.
- Integrates with Laravel validation and authorization.
- Supports model casts and query scopes.
- Reduces repetitive low-level database code.

**Why not write raw SQL for every operation?**

Raw SQL is useful for specialized or performance-sensitive queries, but writing every routine operation manually adds repetition. Eloquent provides a consistent abstraction for common CRUD operations and relational queries.

The use of Eloquent does not eliminate the need to understand SQL, database indexes, foreign keys, or transaction behavior.

### 6. Repository Pattern: Why a Separate Repository Layer Is Not Required

The current architecture uses Eloquent models and service classes for database-backed business operations.

A separate repository layer is not automatically required for every Laravel application. Eloquent already provides query-building and persistence capabilities.

Adding repositories that merely forward every call to an Eloquent model can introduce extra classes without meaningful separation.

A repository may become useful if the application later needs to support multiple data sources, complicated query abstractions, or a persistence boundary that benefits from independent implementations.

The design should introduce that layer when there is a concrete need, rather than adding it solely to increase the number of architectural patterns.

### 7. Why No Custom Trait?

The project does not need a custom Trait simply to demonstrate the use of Traits.

PHP Traits are useful for sharing reusable methods across classes that do not share a suitable inheritance relationship.

In this project, responsibilities already have natural locations:

- Business workflows belong in services.
- Authorization belongs in policies.
- Reusable query conditions belong in scopes.
- Database relationships belong in models.
- Authentication token functionality comes from Laravel Sanctum.

Moving these responsibilities into Traits would not necessarily improve the design.

**The decision is based on responsibility and reuse, not on avoiding Traits as a rule.** If genuinely shared behavior emerges across multiple unrelated classes, a Trait may be appropriate.

### 8. Dependency Injection and the Service Container

Laravel's service container resolves dependencies and supplies them to classes that declare what they need.

For example, constructor injection allows a controller to receive an order service rather than manually constructing one.

**Benefits:**

- Reduces manual object construction.
- Makes dependencies explicit.
- Works with interface-to-implementation bindings.
- Supports substituting dependencies in suitable tests.
- Keeps object creation separate from application behavior.

Dependency injection complements the service layer and contracts; it is not a replacement for either.

### 9. Middleware-Based Request Protection

Middleware handles checks that apply to requests before the relevant controller action executes.

Authentication middleware verifies the request's identity, while account-status or role middleware can apply additional restrictions where configured.

**Why use Middleware?**

- Applies common request rules consistently.
- Avoids repeating basic access checks in every controller.
- Keeps request-level concerns separate from resource-specific policies.
- Makes protected route groups easier to understand.

Middleware and Policies serve related but different purposes: middleware is suitable for broad request requirements, while Policies are designed for authorization decisions about resources and actions.

---

## Database Design

The main application entities are represented by the following tables:

- `users`
- `stores`
- `products`
- `orders`
- `order_items`

The exact column definitions, foreign keys, nullability, indexes, and constraints are defined in the database migrations.

### Users

The `users` table stores user information and account attributes.

Depending on the schema, these include:

- Name.
- Email address.
- Password hash.
- Phone number.
- Role.
- Account activity status.

A user may act as a customer, store manager, driver, or administrator according to the assigned role and applicable permissions.

### Stores

The `stores` table represents stores managed by users.

The `owner_id` field associates a store with its owner or manager.

Store records also contain the information required to describe and manage a store, including its suspension or activity state where defined by the schema.

### Products

The `products` table represents products offered by stores.

A product belongs to a store and contains product information such as its name, price, and availability.

The store relationship makes it possible to retrieve products for a particular store and enforce rules about which products may be ordered together.

### Orders

The `orders` table represents customer orders.

It connects an order to its customer and store and may also reference an assigned driver.

Order records contain delivery information and the current order status.

### Order Items

The `order_items` table represents the individual product entries inside an order.

Each item associates an order with a product and stores values such as quantity and unit price, as defined by the schema.

Separating order items from orders allows an order to contain multiple products without adding repeated product columns to the orders table.

### Why Separate Orders and Order Items?

An order can contain several products, each with its own quantity and price.

If every product were stored directly on the order record, the schema would become difficult to extend and would impose an arbitrary limit on the number of products per order.

A separate `order_items` table provides a normalized relational structure and supports orders containing multiple line items.

### Why Store the Unit Price in an Order Item?

The unit price recorded when an order is placed can preserve the historical price of the product.

For example, if a product costs 100 when ordered and its price later changes to 120, the existing order should still reflect the agreed price of 100.

Storing the order-time price prevents historical order calculations from depending solely on the product's current price.

This is a data-integrity decision that protects the meaning of past transactions.

---

## Eloquent Relationships

Eloquent relationships describe how the application's models relate to one another and allow related records to be retrieved through expressive methods.

| Relationship | Type | Meaning |
|---|---|---|
| User → Stores | One-to-many | A user may manage multiple stores. |
| User → Customer Orders | One-to-many | A customer may place multiple orders. |
| User → Assigned Orders | One-to-many | A driver may be assigned multiple orders. |
| Store → Products | One-to-many | A store may contain multiple products. |
| Store → Orders | One-to-many | A store may receive multiple orders. |
| Order → Customer | Many-to-one | Each order belongs to a customer. |
| Order → Store | Many-to-one | Each order belongs to a store. |
| Order → Driver | Optional many-to-one | An order may have an assigned driver. |
| Order → Order Items | One-to-many | An order contains multiple line items. |
| Order Item → Product | Many-to-one | Each line item refers to a product. |

These relationships correspond to the intended domain model. The exact foreign-key names and relationship methods should match the model classes and migrations.

### Why Define Relationships in Models?

Without Eloquent relationships, controllers and services would repeatedly construct manual queries to retrieve related records.

Relationships provide:

- Reusable relationship definitions.
- More readable queries.
- Convenient access to related models.
- Support for eager loading.
- A clear representation of the database structure in application code.

For example, loading an order with its customer and order items can be expressed using Eloquent relationship loading instead of issuing separate manual queries for every related record.

### Foreign Keys and Referential Integrity

Foreign keys help prevent invalid references between related tables.

For example, an order should reference an existing customer and store, and an order item should reference its parent order and product.

The exact foreign-key constraints and deletion behavior depend on the database migrations.

Application validation and authorization complement these constraints. They solve different problems: validation checks request data, authorization checks permissions, and database constraints protect relational integrity.

---

## Authentication and Authorization

### Authentication with Laravel Sanctum

Laravel Sanctum provides token-based authentication for API requests.

The authentication workflow includes:

1. A user registers or logs in.
2. The application validates the submitted credentials and request data.
3. The application issues a token according to its authentication implementation.
4. The client sends the token with protected API requests.
5. Logout revokes the current token according to the configured implementation.

Protected routes use authentication middleware to reject requests that do not have valid authentication.

### Authentication vs. Authorization

**Authentication** answers: Who is making the request?

**Authorization** answers: Is this user permitted to perform the requested action?

A valid token does not automatically grant permission to every endpoint.

For example, an authenticated customer should not be able to manage another user's store or update a delivery assigned to a different driver.

### Role-Based Access Control

The application distinguishes users by their assigned roles.

Role checks help restrict operations to appropriate categories of users. For example, store-management operations should not be generally available to customers.

However, role checks alone are not sufficient for every action. Ownership, resource state, and assignment rules may also need to be verified.

### Resource Ownership and Policies

Policies provide resource-level authorization.

Examples include:

- Customers accessing only their permitted orders.
- Store managers modifying stores they are authorized to manage.
- Drivers updating delivery progress only for orders assigned to them.

This combination of authentication, role checks, resource ownership, and business rules creates multiple layers of protection.

---

## Order Lifecycle and Business Rules

The order lifecycle represents the permitted progression of an order through its delivery process.

```text
Pending
   |
   v
Accepted
   |
   v
Picked Up
   |
   v
Delivered
```

Cancellation is a separate terminal outcome for eligible orders. The exact transitions are enforced by the application's service and authorization logic.

### Order Statuses

| Status | Meaning |
|---|---|
| `pending` | The order has been created and is awaiting acceptance. |
| `accepted` | The order has been accepted by the store. |
| `picked_up` | The driver has collected the order. |
| `delivered` | The order has reached its delivered state. |
| `cancelled` | The order has been cancelled according to the permitted rules. |

### Business Rules

The application is designed to enforce rules such as:

- Orders must contain valid products and quantities.
- Products must belong to the selected store.
- Unavailable products must not be ordered.
- Orders must reference eligible stores.
- Only authorized store managers may accept orders or assign drivers.
- Driver assignment must use an eligible driver.
- An order must be accepted before it can be picked up.
- An order must be picked up before it can be delivered.
- Customers may cancel only orders that satisfy the cancellation rules.
- Users must not access resources outside their permitted ownership or assignment.

The precise conditions and HTTP responses depend on the implementation of the relevant requests, policies, services, and controllers.

### Why Control State Transitions?

If any endpoint could set an order's status to any arbitrary value, the application could enter invalid states. For example, an order might become delivered before a driver picks it up.

Centralizing status-transition rules helps keep the workflow consistent and prevents invalid operations from being performed through different endpoints.

---

## Validation and Error Handling

### Form Request Validation

Form Requests define validation rules for incoming API data.

Typical validation concerns include:

- Required fields.
- Valid email addresses.
- Correct data types.
- Positive quantities.
- Valid resource identifiers.
- Required order items.
- Allowed field values.

Keeping validation rules in dedicated request classes makes them easier to review and maintain.

### Validation vs. Business Rules

Not every rule belongs in a Form Request.

Validation checks whether submitted data has an acceptable structure and value. Business logic determines whether an operation is valid in the current application state.

For example:

- A quantity must be a positive integer: input validation.
- A product must belong to the store selected for an order: a business consistency rule.
- A driver must be assigned before updating a delivery: a workflow and authorization rule.

Some rules can involve both validation and business logic, but they should be implemented at the appropriate layer and enforced consistently.

### HTTP Response Semantics

The API uses HTTP responses to communicate request outcomes. Common status codes include:

| Status | Meaning |
|---|---|
| `200 OK` | A request completed successfully. |
| `201 Created` | A resource was created successfully. |
| `204 No Content` | A request succeeded without a response body. |
| `401 Unauthorized` | Authentication is missing or invalid. |
| `403 Forbidden` | The authenticated user is not permitted to perform the action. |
| `404 Not Found` | The resource cannot be found through the applicable lookup. |
| `422 Unprocessable Entity` | The request failed validation or an applicable business rule. |

These are standard meanings, not a claim that every endpoint currently returns every listed status. Actual status codes and response bodies are determined by the application's implementation.

---

## Query Scopes and Global Scopes

Eloquent scopes allow reusable query conditions to be defined alongside the models to which they belong.

### Local Query Scopes

The `Order` model defines reusable scopes such as:

- `active`
- `forDriver`
- `unassignedOlderThanThirtyMinutes`
- `today`

These scopes encapsulate commonly used query conditions.

For example, a driver-specific scope can centralize the condition used to retrieve orders associated with a particular driver rather than repeating the same filtering logic across multiple queries.

A time-based scope can also centralize a frequently used date condition.

**Why use Local Scopes?**

- Reduce repeated query conditions.
- Improve readability.
- Keep model-related query logic close to the model.
- Allow conditions to be combined with other Eloquent queries.
- Make common queries easier to maintain.

**Why not place every query in a service?**

Services should coordinate business operations. Reusable model-level query conditions are often more naturally expressed as scopes.

This distinction keeps services from accumulating repeated query fragments while allowing them to remain responsible for business workflows.

### ActiveStoreScope: Global Scope

`ActiveStoreScope` is a global Eloquent scope designed to exclude suspended stores from ordinary store queries.

A global scope is applied automatically to queries for the model unless the scope is explicitly removed or bypassed.

**Why use a Global Scope?**

- Establishes a consistent default filtering rule.
- Reduces the risk of accidentally listing suspended stores.
- Avoids repeating the same condition in every ordinary store query.
- Keeps default store visibility behavior centralized.

### Why Must Some Operations Bypass the Global Scope?

Management operations sometimes need to retrieve a suspended store in order to reactivate it.

If every query automatically excluded suspended stores, a normal lookup might not find the record that needs to be reactivated.

In those specific cases, the application can intentionally bypass the scope, provided that authorization and the intended state transition are still enforced.

A global scope should not be treated as a complete security boundary. Operations that require exceptional visibility must still apply appropriate permissions.

### Local Scopes vs. Global Scopes

| Aspect | Local Scope | Global Scope |
|---|---|---|
| Application | Applied when explicitly requested | Applied automatically by default |
| Purpose | Reusable query conditions | Default model-wide filtering |
| Flexibility | Easy to opt into only where needed | Must be considered when a query needs exceptions |
| Example | Retrieving orders for a particular driver | Excluding suspended stores from ordinary queries |

The choice depends on whether a condition should apply only to selected queries or should be the default behavior across the model.

---

## Database Transactions and Data Integrity

Some operations require several related database writes to succeed together.

For example, placing an order may involve creating an order record and multiple order-item records.

A database transaction groups those operations so that they can be committed together or rolled back if an operation fails.

### Why Use Transactions?

Without a transaction, a failure halfway through a multi-step operation could leave incomplete data. An order might exist without all its intended order items.

Transactions help preserve consistency across related writes.

**Benefits include:**

- Atomic execution of related database operations.
- Reduced risk of incomplete order records.
- A consistent database state after failures.
- Clearer boundaries around critical operations.

Transactions do not replace validation or authorization. All three are needed for different reasons.

- Validation checks the submitted data.
- Authorization determines whether the operation is permitted.
- Transactions protect the consistency of related database changes.

---

## Performance and Reliability

### Lazy-Loading Prevention

The application enables Eloquent lazy-loading prevention outside production through its model configuration.

Lazy loading occurs when accessing a relationship causes an additional query to be executed implicitly.

Preventing or detecting accidental lazy loading during development helps reveal places where database access may be less efficient than intended.

### Eager Loading

When an API response needs related models, eager loading can retrieve those relationships in advance.

This can reduce repeated queries when returning collections of orders, products, or other related resources.

### Why Performance Matters

As the amount of data grows, inefficient queries can increase response times and database load.

The project's use of Eloquent relationships, scopes, and lazy-loading prevention provides a foundation for readable and more predictable database access.

Further optimization should be guided by actual query patterns, database indexes, pagination needs, and performance measurements.

---

## API Endpoints

All endpoints below use the `/api` prefix.

### Authentication

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/auth/register` | Register a user. |
| `POST` | `/api/auth/login` | Authenticate a user. |
| `POST` | `/api/auth/logout` | Log out and revoke the current token. |

Registration and login are public operations. Logout requires authentication.

### Stores

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/stores` | List stores. |
| `GET` | `/api/stores/{store}` | Retrieve a store. |
| `POST` | `/api/stores` | Create a store. |
| `PUT` | `/api/stores/{store}` | Update a store. |
| `POST` | `/api/stores/{id}/suspend` | Suspend a store. |
| `POST` | `/api/stores/{id}/unsuspend` | Reactivate a store. |
| `GET` | `/api/stores/{store}/products` | List products for a store. |

### Products

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/products/{product}` | Retrieve a product. |
| `POST` | `/api/stores/{store}/products` | Create a product for a store. |
| `PUT` | `/api/products/{product}` | Update a product. |
| `POST` | `/api/products/{product}/toggle-availability` | Toggle product availability. |

### Orders

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/orders` | Place an order. |
| `GET` | `/api/orders/my` | List the authenticated customer's orders. |
| `GET` | `/api/orders/store` | List orders for a store manager. |
| `GET` | `/api/orders/driver` | List orders assigned to a driver. |
| `GET` | `/api/orders/{order}` | Retrieve an order. |
| `POST` | `/api/orders/{order}/accept` | Accept an order. |
| `POST` | `/api/orders/{order}/assign-driver` | Assign a driver. |
| `POST` | `/api/orders/{order}/picked-up` | Mark an order as picked up. |
| `POST` | `/api/orders/{order}/delivered` | Mark an order as delivered. |
| `POST` | `/api/orders/{order}/cancel` | Cancel an eligible order. |

Protected routes use the configured authentication and account-activity middleware. Resource-specific permissions are enforced by the relevant authorization logic.

The route list should be checked against `routes/api.php` if endpoints are changed in the future.

---

## Installation and Setup

### Requirements

- PHP version compatible with Laravel 12.
- Composer.
- MySQL.
- Required PHP extensions for the Laravel application.

### 1. Clone the Repository

```bash
git clone <repository-url>
cd <project-directory>
```

Replace the placeholders with the actual repository URL and directory.

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure Environment Variables

Create a local `.env` file from `.env.example` if one does not already exist.

On Windows, the file can be copied manually.

Configure the database connection in `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tawsila
DB_USERNAME=root
DB_PASSWORD=
```

Use the actual MySQL credentials for your environment.

### 4. Generate the Application Key

For a new local environment:

```bash
php artisan key:generate
```

Do not regenerate the key in an existing environment without understanding the consequences for encrypted data and sessions.

### 5. Run Migrations

For a new, empty development database:

```bash
php artisan migrate
```

**Important:** Do not run `php artisan migrate:fresh` on a database containing data you want to preserve. It drops existing tables before recreating them.

If the database already contains the application's schema and data, inspect its migration state before running any schema-changing command.

### 6. Start the Development Server

```bash
php artisan serve
```

The local application is normally available at:

```text
http://127.0.0.1:8000
```

API requests use the `/api` prefix.

### 7. Inspect Registered API Routes

```bash
php artisan route:list --path=api
```

This command lists registered API routes and helps verify the available endpoints.

---

## Project Structure

The following is a conceptual overview of the main application components. Actual files and subdirectories should be checked against the repository.

```text
app/
├── Contracts/
│   └── OrderServiceInterface.php
├── Enums/
│   ├── OrderStatus.php
│   └── UserRole.php
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/
├── Models/
├── Policies/
├── Providers/
│   └── AppServiceProvider.php
└── Services/
    ├── OrderService.php
    ├── ProductService.php
    └── StoreService.php

database/
├── factories/
├── migrations/
└── seeders/

routes/
└── api.php
```

### Why Organize Files This Way?

- **Contracts:** Define service interfaces.
- **Enums:** Define fixed sets of valid values.
- **Controllers:** Handle HTTP requests and responses.
- **Middleware:** Apply request-level checks.
- **Requests:** Validate incoming data.
- **Models:** Represent database entities and relationships.
- **Policies:** Centralize resource authorization.
- **Providers:** Register application bindings and configuration.
- **Services:** Implement business operations.
- **Migrations:** Define and evolve the database schema.
- **Routes:** Declare API endpoints and their middleware.

This organization helps developers locate each responsibility without needing to search through a single large file.

---

## Design Trade-offs

The project uses a pragmatic architecture: it introduces abstractions where they provide clear value and relies on Laravel's existing features for common framework responsibilities.

| Decision | Reason |
|---|---|
| Service classes | Keep business workflows separate from controllers. |
| `OrderServiceInterface` | Define a clear service contract and enable alternative implementations where needed. |
| Policies | Centralize resource-level authorization. |
| Enums | Represent fixed role and status values explicitly. |
| Eloquent relationships | Express relational data access clearly. |
| Local query scopes | Reuse selected query conditions without repeating them. |
| Global store scope | Exclude suspended stores from ordinary queries by default. |
| Database transactions | Protect multi-step database operations from partial writes. |
| No custom Trait by default | Avoid introducing shared behavior abstractions without a genuine reuse requirement. |
| No mandatory repository layer | Avoid duplicating Eloquent's persistence interface without a concrete architectural need. |

These decisions are not universal rules for every Laravel application. They are choices intended to fit the current responsibilities and complexity of Tawsila.

---

## Future Improvements

Potential enhancements as the application grows include:

- Automated feature and unit tests using Laravel's testing framework.
- API documentation using OpenAPI or Swagger.
- Consistent API response resources.
- Pagination for large collections.
- Additional database indexes based on observed query patterns.
- More comprehensive eager loading where needed.
- Rate limiting for authentication endpoints.
- Centralized logging and monitoring.
- Continuous integration to run automated checks.
- More detailed order history and delivery tracking.
- Notifications for order status changes.
- Additional integration tests for authorization and transaction behavior.

These are possible future enhancements, not claims that the functionality is already implemented.

---

## Conclusion

Tawsila demonstrates a structured approach to building a delivery management API with Laravel 12 and MySQL.

Its architecture separates HTTP handling, validation, authorization, business operations, and persistence responsibilities. Service classes organize business workflows, contracts define service boundaries, Policies centralize access-control decisions, Enums represent fixed domain values, and Eloquent relationships and scopes organize database access.

The database design models customers, stores, products, orders, and order items as related entities. Transactions help protect critical multi-step operations, while the order lifecycle defines the permitted progression of delivery workflows.

The overall design aims to keep business rules explicit, reduce unnecessary duplication, preserve data consistency, and provide a maintainable foundation for future development.
