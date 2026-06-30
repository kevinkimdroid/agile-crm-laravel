# ERP Clients API – Restart after updating app.py

After pulling new code, **restart erp-clients-api** or new routes return **404**.

## Endpoints that need a current `app.py`

| Route | Purpose |
|-------|---------|
| `/maturities`, `/clients/maturities`, `/api/clients/maturities` | Partial maturities (`LMS_POLICY_PRTL_MATURITIES`) |
| `/investment-maturities`, `/clients/investment-maturities`, `/api/clients/investment-maturities` | Investment maturities (`LMS_POLICIES.POL_MATURITY_DATE`) |

The old CRM-view maturities query fails with `ORA-00932`. Investment maturities is a **separate** route — if you see:

`Investment maturities endpoint not found at http://localhost:5000/api/clients/investment-maturities`

the API process is still running an old `app.py`.

## Steps (server)

1. **Pull latest code** (or copy `erp-clients-api/app.py` from git onto the server).

2. **Stop the current API** (Ctrl+C in its terminal, or kill the Python process on port 5000).

3. **Start again:**
   ```bash
   cd erp-clients-api
   pip install -r requirements.txt
   python app.py
   ```
   On Windows you can use `start.bat` instead.

4. **Verify routes** (must include `investment-maturities`):
   ```bash
   curl "http://localhost:5000/routes"
   ```

5. **Test investment maturities:**
   ```bash
   curl "http://localhost:5000/api/clients/investment-maturities?from=2026-06-01&to=2026-06-30&limit=5"
   ```
   Expect JSON with `"data": [...]` (or `"error"` if Oracle is down — not 404).

6. **Optional – Laravel maturities sync:**
   ```bash
   php artisan maturities:sync
   ```

7. Open **Support → Investment maturities** in the CRM.

## Verify partial maturities

```bash
curl "http://localhost:5000/maturities?from=2026-03-01&to=2026-06-30"
```
