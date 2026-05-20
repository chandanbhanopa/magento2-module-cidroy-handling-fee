# Cidroy_HandlingFee — Magento 2 Module

Adds a percentage-based **Handling Fee** to orders. The fee is calculated on the order subtotal, displayed across all touchpoints (cart, minicart, checkout, order success, customer account, admin), and exposed via REST API extension attributes.

---

## Requirements

- Magento 2.4.x
- PHP 8.1+

---

## Installation

### Option 1 — Manual (recommended for development)

```bash
# 1. Copy the module into your Magento installation
cp -r Cidroy/HandlingFee /var/www/html/your-magento/app/code/Cidroy/HandlingFee

# 2. Enable the module
php bin/magento module:enable Cidroy_HandlingFee

# 3. Run setup to create database columns and compile DI
php bin/magento setup:upgrade
php bin/magento setup:di:compile

# 4. Deploy static content (production mode only)
php bin/magento setup:static-content:deploy

# 5. Flush cache
php bin/magento cache:flush
```

### Option 2 — Composer (if hosted as a package)

```bash
composer require cidroy/module-handling-fee
php bin/magento module:enable Cidroy_HandlingFee
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

---

## Admin Configuration

Navigate to **Stores → Configuration → Cidroy → Handling Fee**

| Field | Description | Default |
|---|---|---|
| Enable | Turn the fee on/off | No |
| Enable Log | Write debug logs to `var/log/handling_fee.log` | No |
| Label | Display name shown to customers | Handling Fee |
| Handling Fee % | Percentage applied to the order subtotal | 7 |
| Exceed Limit | Orders above this base amount are exempt (0 = no limit) | 50000 |
| Restricted Customer Groups | Selected groups are never charged the fee | — |

---

## How It Works — Full Flow

```
Customer adds product to cart
        │
        ▼
Model/Total/HandlingFee::collect()
  - Runs as a quote total collector (sales.xml)
  - Reads config: enabled, fee %, exceed limit, restricted groups
  - fee = subtotal × (fee_percent / 100)
  - Sets fee on quote_address and quote (in-memory)
        │
        ▼
Fee displayed in:
  ├── Minicart          (JS view + KO template)
  ├── Cart page totals  (JS view + KO template)
  └── Checkout summary  (JS view + KO template)
        │
        ▼
Customer proceeds to checkout → places order
        │
        ▼
Observer/TransferHandlingFeeToOrder  [sales_model_service_quote_submit_before]
  - Reads handling_fee from quote shipping address
  - Sets it on the Order object before save
        │
        ▼
Plugin/Quote/QuoteManagementPlugin::afterSubmit
  - After order is created and saved
  - Re-reads from shipping address (survives re-collect that zeros quote-level field)
  - Calls OrderRepository::save() to persist in sales_order table
        │
        ▼
Observer/SyncHandlingFeeToOrderGrid  [sales_order_save_commit_after]
  - Writes handling_fee + base_handling_fee to sales_order_grid table
  - Keeps the admin order grid column in sync
        │
        ▼
Order placed — fee persisted in:
  ├── sales_order.handling_fee
  ├── sales_order.base_handling_fee
  ├── sales_order_grid.handling_fee
  └── sales_order_grid.base_handling_fee
```

---

## Where the Fee Appears

| Location | Implementation |
|---|---|
| Minicart | `Plugin/Block/Cart/SidebarPlugin` + JS view |
| Cart page | `Plugin/Block/Cart/TotalsPlugin` + JS view |
| Checkout summary | JS view (`view/frontend/web/js/view/summary/`) |
| Order success page | `Block/Checkout/Success/OrderSummary` + phtml template |
| Customer account → My Orders → View | `Block/Order/Totals/HandlingFee` via `sales_order_view.xml` |
| Admin order view (Order Totals) | Same block via `adminhtml/layout/sales_order_view.xml` |
| Admin order grid | Column in `sales_order_grid` via `adminhtml/ui_component/sales_order_grid.xml` |

---

## REST API

Extension attributes are declared in `etc/extension_attributes.xml` and populated via plugins.

### GET /V1/carts/mine/totals

```json
{
  "subtotal": 6000.00,
  "grand_total": 6435.00,
  "extension_attributes": {
    "handling_fee": 420.00,
    "base_handling_fee": 420.00
  }
}
```

### GET /V1/orders/{id} or search by increment_id

```
GET /V1/orders?searchCriteria[filter_groups][0][filters][0][field]=increment_id
              &searchCriteria[filter_groups][0][filters][0][value]=000000008
              &searchCriteria[filter_groups][0][filters][0][condition_type]=eq
```

```json
{
  "entity_id": 8,
  "increment_id": "000000008",
  "grand_total": 6435.00,
  "extension_attributes": {
    "handling_fee": 420.00,
    "base_handling_fee": 420.00
  }
}
```

### Complete API Flow (Add to Cart → Place Order)

```
1. POST /V1/integration/customer/token          → get Bearer token
2. POST /V1/carts/mine                          → create cart
3. POST /V1/carts/mine/items                    → add product
4. POST /V1/carts/mine/estimate-shipping-methods → get shipping options
5. POST /V1/carts/mine/shipping-information     → set shipping + billing address
6. GET  /V1/carts/mine/totals                   → verify handling_fee in extension_attributes
7. GET  /V1/carts/mine/payment-methods          → list available payment methods
8. POST /V1/carts/mine/payment-information      → place order → returns order_id (int)
9. GET  /V1/orders/{order_id}                   → full order with handling_fee in extension_attributes
```

---

## Database Schema

```sql
-- Added columns (db_schema.xml)
ALTER TABLE quote           ADD handling_fee DECIMAL(20,4), ADD base_handling_fee DECIMAL(20,4);
ALTER TABLE quote_address   ADD handling_fee DECIMAL(20,4), ADD base_handling_fee DECIMAL(20,4);
ALTER TABLE sales_order     ADD handling_fee DECIMAL(20,4), ADD base_handling_fee DECIMAL(20,4);
ALTER TABLE sales_order_grid ADD handling_fee DECIMAL(20,4), ADD base_handling_fee DECIMAL(20,4);
```

---

## File Structure

```
app/code/Cidroy/HandlingFee/
├── Block/
│   ├── Checkout/Success/OrderSummary.php       # Success page order summary block
│   └── Order/Totals/HandlingFee.php            # initTotals() for frontend + admin order view
├── Logger/
│   ├── Handler.php
│   └── Logger.php
├── Model/
│   ├── Config.php                              # Admin config reader
│   └── Total/HandlingFee.php                  # Quote total collector
├── Observer/
│   ├── SyncHandlingFeeToOrderGrid.php          # Syncs fee to sales_order_grid on order save
│   └── TransferHandlingFeeToOrder.php          # Copies fee from quote to order before submit
├── Plugin/
│   ├── Api/
│   │   ├── CartTotalRepositoryPlugin.php       # Injects fee into cart totals API response
│   │   └── OrderRepositoryPlugin.php           # Injects fee into order API response
│   ├── Block/Cart/
│   │   ├── SidebarPlugin.php                   # Minicart display
│   │   └── TotalsPlugin.php                    # Cart page display
│   ├── CustomerData/CartPlugin.php             # Customer section cart data
│   └── Quote/QuoteManagementPlugin.php         # Persists fee after order save
├── etc/
│   ├── adminhtml/system.xml                    # Admin config fields
│   ├── config.xml                              # Default config values
│   ├── db_schema.xml                           # Database columns
│   ├── di.xml                                  # Plugin registrations
│   ├── events.xml                              # Observer registrations
│   ├── extension_attributes.xml               # REST API extension attributes
│   ├── module.xml
│   └── sales.xml                              # Total collector registration
├── view/
│   ├── adminhtml/
│   │   ├── layout/sales_order_view.xml         # Admin order view totals
│   │   └── ui_component/sales_order_grid.xml   # Admin order grid column
│   └── frontend/
│       ├── layout/
│       │   ├── checkout_cart_index.xml
│       │   ├── checkout_index_index.xml
│       │   ├── checkout_onepage_success.xml
│       │   ├── default.xml
│       │   └── sales_order_view.xml
│       ├── templates/checkout/success/
│       │   └── order-summary.phtml
│       └── web/
│           ├── js/view/                        # KnockoutJS view models
│           └── template/                       # KnockoutJS HTML templates
└── registration.php
```

---

## Disabling the Module

```bash
php bin/magento module:disable Cidroy_HandlingFee
php bin/magento setup:upgrade
php bin/magento cache:flush
```
