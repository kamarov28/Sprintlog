# Kilat Hitam Project Flow

## Overview

This project is a Laravel 13 logistics application with four main layers:

1. Public frontend for homepage, rate check, tracking, login, register, and public pickup requests.
2. Authenticated customer area for dashboard, profile, and shipment booking.
3. Public JSON API for cascading location dropdowns, rate calculation, and tracking lookup.
4. Staff backend under `/be` for operational processing by manager, cashier, and courier roles.

Core runtime flow:

- `bootstrap/app.php` registers the app, routes, and middleware aliases.
- `routes/web.php` is the main traffic controller for all web and API endpoints.
- Controllers orchestrate validation and business rules.
- Eloquent models persist logistics data such as `shipments`, `pickup_requests`, `payments`, and `shipment_trackings`.
- Blade views render both the frontend and backend interfaces.

## Main Actors

- Guest: can browse home, check rates, track shipments, submit pickup requests, login, and register.
- Customer: can access dashboard, update profile, and submit shipment booking requests.
- Cashier: can create shipments directly in backend and manage payment capture.
- Manager: can supervise branch shipments, pickups, staff, and reports.
- Courier: can receive pickup assignments and update shipment or pickup status.

## High-Level Request Flow

```mermaid
flowchart TD
    A["HTTP Request"] --> B["Laravel bootstrap/app.php"]
    B --> C["routes/web.php"]

    C --> D["Public frontend routes"]
    C --> E["Authenticated customer routes"]
    C --> F["API routes (/api)"]
    C --> G["Backend staff routes (/be)"]

    D --> H["FE Controllers"]
    E --> H
    F --> I["API Controllers"]
    G --> J["BE Controllers"]

    H --> K["Eloquent Models"]
    I --> K
    J --> K

    K --> L["SQLite / database tables"]

    H --> M["Blade views (resources/views/fe)"]
    J --> N["Blade views (resources/views/be)"]
    I --> O["JSON responses"]
```

## Route Groups

### 1. Public Frontend

Main entry points:

- `/` -> `Fe\HomeController@index`
- `/track` -> `Fe\TrackingController@show`
- `/pickup-request` -> `Be\PickupController@store`
- `/login` and `/register`

Purpose:

- Show homepage.
- Perform embedded tracking lookup from query string.
- Perform embedded rate calculation from selected origin, destination, and weight.
- Allow public pickup requests even without login.

### 2. Customer Area

Protected by `auth` middleware:

- `/dashboard`
- `/profile`
- `/order/create`
- `/order/store`

Purpose:

- Show customer shipment and pickup history.
- Maintain customer profile and password.
- Submit customer-originated shipment booking requests.

### 3. API Layer

Public API endpoints:

- `/api/locations/provinsi`
- `/api/locations/kota`
- `/api/calculate-rate`
- `/api/public/track/{trackingNumber}`

Purpose:

- Feed frontend dropdowns.
- Compute shipping rates from zones and service type.
- Expose shipment timeline data in JSON.

### 4. Backend Staff Area

Protected by `auth` + `be.staff`, then narrowed by role-specific middleware:

- `shipment.hub` for shipment operations
- `pickup.hub` for pickup operations
- `personnel.manager` for staff management

Purpose:

- Operate shipment manifests.
- Assign couriers and update shipment status.
- Process pickup queue.
- View finance and reports.
- Manage branches and users.

## Authentication and Role Flow

```mermaid
flowchart TD
    A["Guest opens login/register"] --> B["AuthController or RegisterController"]
    B --> C{"Credentials / registration valid?"}

    C -- "No" --> D["Return with validation errors"]
    C -- "Yes" --> E["User session created"]

    E --> F{"User role"}
    F -- "customer" --> G["Frontend flow"]
    F -- "manager / cashier / courier" --> H["Backend /be flow"]
```

## Customer Booking Flow

This is the flow for authenticated customers using `Fe\OrderController`.

```mermaid
flowchart TD
    A["Customer opens /order/create"] --> B["Load user + province list"]
    B --> C["Customer fills sender, receiver, weight, service, proof"]
    C --> D["POST /order/store"]
    D --> E["Validate request"]
    E --> F{"Valid?"}

    F -- "No" --> G["Return to form with errors"]
    F -- "Yes" --> H["Attach auth user_id"]

    H --> I["Upload payment proof to public storage"]
    I --> J{"Sender coordinates available?"}
    J -- "Yes" --> K["Find nearest branch with Haversine distance"]
    J -- "No" --> L["Leave branch_id null or unchanged"]

    K --> M["Create pickup_requests row"]
    L --> M

    M --> N["Redirect to customer dashboard"]
```

Important note:

- Customer booking currently creates a `pickup_requests` record, not a `shipments` record.
- That means customer-submitted orders enter the pickup pipeline first and are later operationally handled by staff.

## Public Pickup Request Flow

This is handled by `Be\PickupController@store`, even though the route is public.

```mermaid
flowchart TD
    A["Guest or customer submits pickup request"] --> B["Validate pickup data"]
    B --> C{"Use profile address?"}
    C -- "Yes + logged in" --> D["Use user address and coordinates"]
    C -- "No" --> E["Use submitted address and coordinates"]

    D --> F["Resolve branch_id"]
    E --> F

    F --> G{"How branch is resolved?"}
    G -- "staff branch" --> H["Use auth user's branch"]
    G -- "coordinates" --> I["Pick nearest branch"]
    G -- "address fallback" --> J["Infer by city name"]
    G -- "none" --> K["branch_id remains null"]

    H --> L["Create pickup_requests row"]
    I --> L
    J --> L
    K --> L

    L --> M["Return success state to page"]
```

## Pickup Operations Flow

Handled in backend by `Be\PickupController`.

```mermaid
flowchart TD
    A["Staff opens /be/pickups"] --> B["Load pickup queue scoped by branch/role"]
    B --> C{"Action?"}

    C -- "Assign courier" --> D["Validate courier belongs to branch"]
    D --> E["Set courier_id + status=assigned"]

    C -- "Courier marks picked_up" --> F["Require proof image"]
    F --> G["Store proof image"]
    G --> H["Update status=picked_up"]

    C -- "Manager/cashier marks hub_received" --> I["Update status=hub_received"]
    C -- "Cancel" --> J["Update status=cancelled"]

    E --> K["Pickup record updated"]
    H --> K
    I --> K
    J --> K
```

## Backend Shipment Creation Flow

This is the direct staff shipment flow handled by `Be\ShipmentController@store`.

```mermaid
flowchart TD
    A["Cashier opens /be/shipments/create"] --> B["Load branches, couriers, rates, bank accounts"]
    B --> C["Submit shipment form"]
    C --> D["Validate shipment + payment fields"]
    D --> E{"Valid and authorized?"}

    E -- "No" --> F["Abort or return with errors"]
    E -- "Yes" --> G["Begin DB transaction"]

    G --> H["Find or create sender customer"]
    H --> I["Find or create receiver customer"]
    I --> J["Choose rate and service multiplier"]
    J --> K["Create shipments row"]
    K --> L["Create payments row"]
    L --> M["Create shipment_items row"]
    M --> N["Create initial shipment_trackings row"]
    N --> O["Create shipment_status_audits row"]
    O --> P["Commit transaction"]
    P --> Q["Redirect to shipment detail"]
```

## Shipment Tracking and Status Flow

```mermaid
flowchart TD
    A["Shipment exists"] --> B["Staff opens shipment detail"]
    B --> C["POST /be/shipments/{shipment}/status"]
    C --> D["Validate status, location, description, optional photo"]
    D --> E["Update shipment.status"]
    E --> F["Optional: store shipment photo"]
    F --> G["Create shipment_trackings entry"]
    G --> H["Create shipment_status_audits entry"]
    H --> I["Timeline visible in backend and public tracking"]
```

Public tracking reads from the shipment timeline:

- `Fe\TrackingController@show` returns the tracking page.
- `Fe\TrackingController@apiShow` returns JSON timeline data.
- Both load `Shipment` with `originBranch`, `destinationBranch`, and ordered `trackings`.

## Core Data Relationships

```mermaid
flowchart LR
    U["users"] -->|hasMany| PR["pickup_requests"]
    U -->|hasMany| S["shipments"]
    U -->|belongsTo branch| B["branches"]

    PR -->|belongsTo| B
    PR -->|belongsTo courier| U

    S -->|belongsTo sender| C1["customers"]
    S -->|belongsTo receiver| C2["customers"]
    S -->|belongsTo origin branch| B
    S -->|belongsTo destination branch| B
    S -->|belongsTo courier| U
    S -->|hasOne| P["payments"]
    S -->|hasMany| SI["shipment_items"]
    S -->|hasMany| ST["shipment_trackings"]
    S -->|hasMany| SA["shipment_status_audits"]
```

## Practical Interpretation of the System

The project has two operational entry paths:

1. Customer/self-service path:
   Customer registers or logs in -> submits booking or pickup request -> record lands in `pickup_requests` -> branch staff and courier process it -> later the shipment lifecycle is handled operationally.

2. Counter/staff path:
   Cashier directly creates a shipment in backend -> payment is captured immediately -> tracking timeline starts at shipment creation -> manager/courier continue the status updates.

## Main Design Observation

The most important architectural detail is that the system is split into two related but separate pipelines:

- `pickup_requests` for intake, assignment, and doorstep collection.
- `shipments` for branch-level logistics, payment records, tracking timeline, and delivery status.

That separation is a solid operational idea, but it also means there is a handoff point between pickup intake and shipment creation. If you want, the next useful step would be to map exactly where a `pickup_request` is converted into a `shipment`, because that bridge does not appear to be automated in the currently inspected controllers.
