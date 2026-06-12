# Payroll Computation Reference

This document explains **how every payslip figure is computed and the law it follows**. The
engine implements the **Philippine DOLE Labor Code**, the **BIR TRAIN Law (RA 10963)**, and the
current **SSS / PhilHealth / Pag-IBIG** issuances. Figures here are reproduced from the live
engine ([`PayrollCalculationService`](../app/Modules/Payroll/Services/PayrollCalculationService.php)).

> Out of scope of the per-cut-off run: the **annual withholding-tax true-up** and **13th-month pay**
> are separate year-end processes.

---

## 1. Flow

```
Biometric / manual clock-ins        AttendanceService     → attendance_records
        │
        ▼
Daily Time Record (DTR)             DtrService            → undertime per day
        │
        ▼
Payroll run for a cut-off           PayrollCalculationService
        ├── Earnings    (basic, OT, night diff, holiday, rest day, allowances)
        ├── Deductions  (tardiness/undertime, absence, SSS, PhilHealth, Pag-IBIG, tax, loans)
        ▼
PayrollItem (basic, gross, deductions, net) + PayrollEarning[] + PayrollDeduction[]
```

Each employee's `PayrollItem` is computed in a single DB transaction. A period moves
**Draft → For Review → Finalized**: running it generates payslips (status *For Review*); an
admin then finalizes it, which locks the period and commits loan-balance deductions once.

---

## 2. Rate bases

| Quantity | Formula |
|---|---|
| Monthly basic (monthly-paid) | `salary` |
| Monthly basic (daily-paid) | `daily rate × work_days_per_month` (default **26**) |
| Cut-off basic | `monthly basic ÷ cut-offs` (semi-monthly = 2) |
| **Daily rate** | `monthly basic ÷ work_days_per_month` |
| **Hourly rate** | `daily rate ÷ standard_hours_per_day` (default **8**) |

Basic pay is the **full cut-off value**; absences/undertime are taken as deductions (never by
silently shrinking basic), so a payslip always shows the full basic plus explicit deductions.

---

## 3. Earnings & legal basis

### Overtime — Labor Code Art. 87 (DOLE-compounded)
OT pay = `hours × hourly rate × multiplier`:

| Day type | Multiplier | Basis |
|---|---|---|
| Ordinary day | **125%** | base + 25% |
| Rest day / special day | **169%** | 130% × 130% |
| Regular holiday | **260%** | 200% × 130% |

### Night differential — Art. 86
A premium of the hourly rate for each hour worked between **10:00 PM and 6:00 AM**, **net of breaks**
and never exceeding hours actually worked. The rate is **configurable by the admin** in Payroll
Settings (`PayrollSetting.night_differential_rate`); **10%** is the DOLE minimum, and some companies
set a higher rate.

### Holiday pay — Art. 94, DO 28-30
Holiday pay applies to **scheduled work days**:

| Holiday | Worked | Not worked |
|---|---|---|
| Regular | 200% | 100% (paid) |
| Special non-working | 130% | no work, no pay |

Because a monthly salary already contains the base day, the engine adds only the **premium**:
regular holiday worked → **+100%**, special holiday worked → **+30%**.

### Rest-day pay — Art. 93
A rest day is outside the employee's scheduled days, so it is **not** in basic; the **full**
statutory rate is paid for hours worked:

| Situation | Rate |
|---|---|
| Plain rest day | **130%** |
| Rest day + regular holiday | **260%** |
| Rest day + special holiday | **150%** |

### Allowances
Each allowance carries an **`is_taxable`** flag set by HR (Settings → Allowance Types). Taxable
allowances are added to the withholding-tax base; non-taxable (de-minimis) ones are not.

---

## 4. Deductions & legal basis

| Deduction | Formula / basis |
|---|---|
| **Tardiness / undertime** | `minutes ÷ 60 × hourly rate` (late arrival + early departure; night shifts included) |
| **Absence** | `days × daily rate`, counted **once**, and only for work days **up to today** (in-progress cut-offs never dock future days) |
| **SSS** | 2025 contribution table, employee share (SSS Circular 2024-006), split per cut-off |
| **PhilHealth** | UHC Law — 5% premium (₱500 floor / ₱5,000 ceiling), employee share ½, split per cut-off |
| **Pag-IBIG** | 1% (≤ ₱1,500) or 2% (> ₱1,500), capped at **₱200/month**, split per cut-off |
| **Withholding tax** | BIR/TRAIN revised **semi-monthly** table, applied to taxable earnings **net of** the mandatory contributions above |
| **Loans** | monthly amortization, deducted once per month (last cut-off) |

### BIR semi-monthly withholding table

| Compensation (per cut-off) | Tax |
|---|---|
| ≤ ₱10,417 | 0 |
| ₱10,417 – ₱16,666 | 15% of excess over ₱10,417 |
| ₱16,667 – ₱33,332 | ₱937.50 + 20% of excess over ₱16,667 |
| ₱33,333 – ₱83,332 | ₱4,270.70 + 25% of excess over ₱33,333 |
| ₱83,333 – ₱333,332 | ₱16,770.70 + 30% of excess over ₱83,333 |
| ≥ ₱333,333 | ₱91,770.70 + 35% of excess over ₱333,333 |

---

## 5. Leave

- **Paid leave** (leave type `is_paid = true`): the day is **not** docked.
- **Leave without pay**: docked as an absence (`days × daily rate`).

Only days on the employee's scheduled work days count; days already covered by attendance are not
double-counted.

---

## 6. Net pay

```
Net pay = Gross earnings − Total deductions
```

---

## 7. Worked example — monthly ₱26,000, semi-monthly, fully worked

| Step | Value |
|---|---|
| Cut-off basic | 26,000 ÷ 2 = **₱13,000.00** |
| Daily rate | 26,000 ÷ 26 = ₱1,000.00 |
| Hourly rate | 1,000 ÷ 8 = ₱125.00 |
| SSS (per cut-off) | ₱650.00 |
| PhilHealth (per cut-off) | ₱325.00 |
| Pag-IBIG (per cut-off) | ₱100.00 |
| Taxable | 13,000 − 1,075 = ₱11,925.00 |
| Withholding tax | 15% × (11,925 − 10,417) = **₱226.20** |
| **Net pay** | 13,000 − (650 + 325 + 100 + 226.20) = **₱11,698.80** |

**Add-ons (illustrative):**
- Worked an 8-hour **regular holiday** → premium `125 × 8 × 1.00 = ₱1,000.00` on top of basic.
- 8 hours of **night-shift** work → night differential `125 × 8 × 0.10 = ₱100.00`.
- Clocked in **30 min late** → tardiness `30 ÷ 60 × 125 = ₱62.50`.

---

## 8. Legal references

- **DOLE** — Labor Code of the Philippines, Arts. 86 (night shift), 87 (overtime), 93 (rest day),
  94 (holidays); DOLE Handbook on Workers' Statutory Monetary Benefits (DO 28-30).
- **BIR** — TRAIN Law (RA 10963), revised withholding-tax tables.
- **SSS** — Circular No. 2024-006 (2025 contribution schedule).
- **PhilHealth** — Universal Health Care Act premium schedule.
- **Pag-IBIG** — HDMF contribution rates.
