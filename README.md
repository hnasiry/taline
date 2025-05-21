# TLYN Gold Trading

This repository demonstrates and tests the **buy/sell matching flow** for partial gold trading between users, based on the second TLYN scenario.

It includes:
- Placing buy/sell orders
- Matching same-priced orders
- Fee calculation
- Gold and wallet balance tracking
- Transaction logging (ledger)

---

## 📦 Scenario Coverage

Feature tests include:
- ✅ Successful buy matched with an existing sell
- ❌ Attempt to sell without enough gold (returns JSON error)
- (More scenarios to be added)

---

## 🧪 Running the Tests

```bash
php artisan test
```

## 👤 Author

**Hossein Nasiri** ([nasiry.hossein@gmail.com](mailto:nasiry.hossein@gmail.com))
