#!/usr/bin/env python3
"""
generate_sample_datasets.py — Teraju Setia Fleet Analytics Dataset Generator
=============================================================================
Generates two realistic CSV datasets for immediate ML testing:
  1. synthetic_fleet_data.csv    — Synthetic system data with stochastic noise
  2. kaggle_benchmark_data.csv   — Simulated Kaggle-style benchmark dataset

Both datasets use the exact feature schema required by run_experiment.py:
  vehicle_id, plate_number, service_type, year, scan_date,
  days_since_service, km_since_service, interval_km, interval_months,
  daily_km, terrain_severity, vehicle_age, total_mileage,
  fuel_type, owner_type, urgency_label

Usage:
  python3 generate_sample_datasets.py --output_dir /path/to/ml_datasets --rows 800
"""

import argparse
import os
import random
import json
import numpy as np
import pandas as pd
from datetime import datetime, timedelta, date

random.seed(42)
np.random.seed(42)

TERRAIN_SEVERITY = {
    "urban": 1.15, "highway": 0.90, "rural": 1.20,
    "mountain": 1.30, "mixed": 1.00
}
SERVICE_TYPES = [
    "Oil & Filter Change",
    "Full Engine Service",
    "Tyre Rotation & Balancing",
    "Air Conditioning Service",
    "Suspension Inspection",
    "Transmission Fluid Change",
    "Full Vehicle Inspection",
]
SERVICE_INTERVALS = {
    "Oil & Filter Change":           {"km": 5000,  "months": 6},
    "Full Engine Service":           {"km": 10000, "months": 12},
    "Tyre Rotation & Balancing":     {"km": 10000, "months": 6},
    "Air Conditioning Service":      {"km": None,  "months": 24},
    "Suspension Inspection":         {"km": 20000, "months": 24},
    "Transmission Fluid Change":     {"km": 40000, "months": 24},
    "Full Vehicle Inspection":       {"km": None,  "months": 12},
}
FUEL_TYPES = ["petrol", "diesel", "hybrid", "electric"]
OWNER_TYPES = ["individual", "corporate"]
BRANDS = ["Proton", "Perodua", "Toyota", "Honda", "Hyundai", "Nissan", "Mazda", "Ford"]
TERRAINS = list(TERRAIN_SEVERITY.keys())

BASE_DATE = date(2024, 1, 1)
END_DATE = date(2026, 10, 31)


def compute_label(d: float) -> str:
    """3-class label from days remaining d."""
    if d <= 0:
        return "HIGH"
    elif d <= 14:
        return "MEDIUM"
    else:
        return "LOW"


def add_noise(val: float, noise_pct: float = 0.08) -> float:
    """Add stochastic wear noise so ML models don't trivially hit 100%."""
    return val * (1 + np.random.uniform(-noise_pct, noise_pct))


def generate_row(vehicle_id: int, plate: str, is_kaggle: bool = False) -> dict:
    brand = random.choice(BRANDS)
    fuel_type = random.choice(FUEL_TYPES)
    owner_type = random.choice(OWNER_TYPES)
    vehicle_year = random.randint(2010, 2024)
    terrain = random.choice(TERRAINS)
    terrain_sev = TERRAIN_SEVERITY[terrain]

    # Scan date — spread across 2024–2026
    scan_date = BASE_DATE + timedelta(days=random.randint(0, (END_DATE - BASE_DATE).days))
    year = scan_date.year

    # Vehicle age in years at scan date
    vehicle_age = scan_date.year - vehicle_year

    # Daily KM usage (varies by owner type)
    base_daily_km = 60 if owner_type == "corporate" else 35
    daily_km = round(add_noise(base_daily_km, 0.30), 1)
    daily_km = max(10, min(daily_km, 180))

    # Pick service type
    service_type = random.choice(SERVICE_TYPES)
    interval = SERVICE_INTERVALS[service_type]
    interval_km = interval["km"] if interval["km"] else 999999
    interval_months = interval["months"]

    # Total mileage at scan date
    total_mileage = round(daily_km * 365 * max(vehicle_age, 1) * add_noise(1.0, 0.15))

    # Compute km_since_service from last service date
    # Randomly place last service within 0–130% of interval
    pct_elapsed = random.uniform(0.05, 1.30)
    km_since_service = round(interval_km * pct_elapsed * add_noise(1.0, 0.10)) if interval["km"] else 0
    days_since_service = round(interval_months * 30.44 * pct_elapsed * add_noise(1.0, 0.10))

    # Kaggle benchmark uses slightly different distributions (urban bias, newer vehicles)
    if is_kaggle:
        terrain_sev = TERRAIN_SEVERITY.get("urban", 1.15) * add_noise(1.0, 0.05)
        daily_km = round(add_noise(45, 0.20), 1)
        vehicle_age = max(1, vehicle_age - 2)
    else:
        terrain_sev = terrain_sev * add_noise(1.0, 0.03)

    # Compute effective due date using rule-based logic
    eff_km = max(interval_km / max(terrain_sev, 0.1), 1)
    remaining_km = eff_km - km_since_service
    days_via_km = remaining_km / max(daily_km, 1)
    days_via_time = (interval_months * 30.44) - days_since_service
    d = min(days_via_km, days_via_time)

    # Inject boundary noise for MEDIUM zone (makes the problem harder)
    if 0 < d <= 21:
        d += np.random.normal(0, 3)

    label = compute_label(round(d))

    return {
        "vehicle_id": f"VH{vehicle_id:04d}",
        "plate_number": plate,
        "service_type": service_type,
        "year": year,
        "scan_date": scan_date.isoformat(),
        "days_since_service": max(0, days_since_service),
        "km_since_service": max(0, km_since_service),
        "interval_km": interval_km if interval["km"] else 0,
        "interval_months": interval_months,
        "daily_km": daily_km,
        "terrain_severity": round(terrain_sev, 4),
        "vehicle_age": max(1, vehicle_age),
        "total_mileage": max(1000, total_mileage),
        "fuel_type": fuel_type,
        "owner_type": owner_type,
        "urgency_label": label,
    }


def generate_plate() -> str:
    letters = "".join(random.choices("ABCDEFGHJKLMNPRSTUVWXY", k=random.randint(2, 3)))
    digits = "".join(random.choices("0123456789", k=random.randint(3, 4)))
    suffix = random.choice(["", "A", "B", "C", "D"])
    return f"{letters}{digits}{suffix}"


def generate_dataset(n_rows: int, is_kaggle: bool = False) -> pd.DataFrame:
    rows = []
    n_vehicles = max(50, n_rows // 5)
    plates = [generate_plate() for _ in range(n_vehicles)]

    for i in range(n_rows):
        vid = random.randint(1, n_vehicles)
        row = generate_row(vid, plates[vid - 1], is_kaggle=is_kaggle)
        rows.append(row)

    df = pd.DataFrame(rows)

    # Ensure class balance is reasonable (not perfectly balanced to simulate real data)
    # Synthetic: ~30% HIGH, 30% MEDIUM, 40% LOW
    # This is enforced by the stochastic generation, but let's log distribution
    return df


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--output_dir", default="/tmp/ml_datasets",
                        help="Directory to save generated CSV files")
    parser.add_argument("--rows", type=int, default=800,
                        help="Number of rows per dataset (default 800)")
    args = parser.parse_args()

    os.makedirs(args.output_dir, exist_ok=True)

    # Generate synthetic dataset
    print(f"[*] Generating synthetic dataset ({args.rows} rows)...", flush=True)
    syn_df = generate_dataset(args.rows, is_kaggle=False)
    syn_path = os.path.join(args.output_dir, "synthetic_fleet_data.csv")
    syn_df.to_csv(syn_path, index=False)
    syn_dist = syn_df["urgency_label"].value_counts().to_dict()
    print(f"    Saved: {syn_path}")
    print(f"    Distribution: {syn_dist}")

    # Generate kaggle benchmark dataset
    print(f"[*] Generating Kaggle benchmark dataset ({args.rows} rows)...", flush=True)
    kag_df = generate_dataset(args.rows, is_kaggle=True)
    kag_path = os.path.join(args.output_dir, "kaggle_benchmark_data.csv")
    kag_df.to_csv(kag_path, index=False)
    kag_dist = kag_df["urgency_label"].value_counts().to_dict()
    print(f"    Saved: {kag_path}")
    print(f"    Distribution: {kag_dist}")

    # Output JSON for Laravel to parse
    output = {
        "synthetic": {
            "path": syn_path,
            "row_count": len(syn_df),
            "distribution": syn_dist,
        },
        "kaggle_benchmark": {
            "path": kag_path,
            "row_count": len(kag_df),
            "distribution": kag_dist,
        },
    }
    print("\n[JSON_OUTPUT]")
    print(json.dumps(output))


if __name__ == "__main__":
    main()
