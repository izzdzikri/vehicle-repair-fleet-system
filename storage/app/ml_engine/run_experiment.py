#!/usr/bin/env python3
"""
run_experiment.py — Teraju Setia Fleet Predictive Analytics ML Engine
======================================================================
Accepts a JSON payload via --payload or as first CLI argument.
Runs 4 models on a shared 80/20 stratified split + 5-fold CV and
outputs a structured JSON result to stdout.

Usage:
  python3 run_experiment.py --payload '{"dataset_path":"/path/to/data.csv",...}'
  python3 run_experiment.py /path/to/payload.json

Required payload keys:
  dataset_path   : absolute path to the CSV file
  dataset_type   : one of synthetic|kaggle_benchmark|system_live|unlabeled_test
  models_to_run  : list of model keys, e.g. ["rule_based","decision_tree","random_forest","svm"]
  output_dir     : directory to save .pkl model files
  split_ratio    : float, e.g. 0.80
  cv_folds       : int, e.g. 5
  hyperparams    : dict of hyperparameter overrides (optional)
"""

import sys
import json
import os
import warnings
import argparse
import traceback
from datetime import datetime, timedelta, date

warnings.filterwarnings("ignore")

import numpy as np
import pandas as pd

from sklearn.model_selection import train_test_split, StratifiedKFold, cross_validate
from sklearn.tree import DecisionTreeClassifier
from sklearn.ensemble import RandomForestClassifier
from sklearn.svm import SVC
from sklearn.preprocessing import LabelEncoder, StandardScaler, OneHotEncoder
from sklearn.pipeline import Pipeline
from sklearn.compose import ColumnTransformer
from sklearn.metrics import (
    accuracy_score, precision_score, recall_score, f1_score,
    confusion_matrix, classification_report
)
import joblib

# ─────────────────────────── Constants ────────────────────────────────────────
LABEL_ORDER = ["LOW", "MEDIUM", "HIGH"]
TERRAIN_SEVERITY = {
    "urban": 1.15, "highway": 0.90, "rural": 1.20,
    "mountain": 1.30, "mixed": 1.00
}
REQUIRED_FEATURES = [
    "days_since_service", "km_since_service", "interval_km", "interval_months",
    "daily_km", "terrain_severity", "vehicle_age", "total_mileage",
    "fuel_type", "owner_type"
]
NUMERIC_FEATURES = [
    "days_since_service", "km_since_service", "interval_km", "interval_months",
    "daily_km", "terrain_severity", "vehicle_age", "total_mileage"
]
CATEGORICAL_FEATURES = ["fuel_type", "owner_type"]
TARGET_COL = "urgency_label"


# ─────────────────────────── Data Loading & Validation ────────────────────────
def load_and_validate(dataset_path: str) -> pd.DataFrame:
    ext = os.path.splitext(dataset_path)[1].lower()
    if ext in (".xlsx", ".xls"):
        df = pd.read_excel(dataset_path)
    else:
        df = pd.read_csv(dataset_path)

    # Normalize column names: lowercase, strip, replace spaces with underscores
    df.columns = [c.strip().lower().replace(" ", "_") for c in df.columns]

    # Map common alternative column names
    aliases = {
        "urgency": TARGET_COL,
        "label": TARGET_COL,
        "class": TARGET_COL,
        "maintenance_urgency": TARGET_COL,
    }
    for old, new in aliases.items():
        if old in df.columns and new not in df.columns:
            df.rename(columns={old: new}, inplace=True)

    # Normalize label to uppercase
    if TARGET_COL in df.columns:
        df[TARGET_COL] = df[TARGET_COL].astype(str).str.strip().str.upper()
        df = df[df[TARGET_COL].isin(LABEL_ORDER)]

    # Ensure required feature columns exist with defaults
    defaults = {
        "terrain_severity": 1.0,
        "vehicle_age": 5,
        "total_mileage": 50000,
        "fuel_type": "petrol",
        "owner_type": "individual",
        "interval_km": 5000,
        "interval_months": 6,
    }
    for col, default in defaults.items():
        if col not in df.columns:
            df[col] = default

    # Coerce numeric columns
    for col in NUMERIC_FEATURES:
        if col in df.columns:
            df[col] = pd.to_numeric(df[col], errors="coerce").fillna(0)

    # Normalize categoricals
    for col in CATEGORICAL_FEATURES:
        if col in df.columns:
            df[col] = df[col].astype(str).str.strip().str.lower().fillna("unknown")

    return df


# ──────────────────────── Rule-Based Model ────────────────────────────────────
class RuleBasedModel:
    """
    Deterministic hybrid time/mileage + severity rule.
    Uses raw feature columns to compute d (days remaining) and applies
    the 3-class threshold (d<=0=HIGH, d<=14=MEDIUM, else LOW).
    """

    def predict(self, df: pd.DataFrame):
        predictions = []
        confidences = []
        days_remaining_list = []

        for _, row in df.iterrows():
            try:
                interval_km = max(float(row.get("interval_km", 5000)), 1)
                interval_months = float(row.get("interval_months", 6))
                km_since = float(row.get("km_since_service", 0))
                days_since = float(row.get("days_since_service", 0))
                daily_km = max(float(row.get("daily_km", 40)), 1)
                terrain = float(row.get("terrain_severity", 1.0))

                # Effective km interval adjusted for terrain
                eff_km = interval_km / max(terrain, 0.1)
                remaining_km = eff_km - km_since
                days_via_km = remaining_km / daily_km

                # Days remaining via time interval
                days_via_time = (interval_months * 30.44) - days_since

                # Take the more conservative (earlier) due date
                d = min(days_via_km, days_via_time)
                d = round(d)

            except Exception:
                d = 0

            # Classify
            if d <= 0:
                pred = "HIGH"
            elif d <= 14:
                pred = "MEDIUM"
            else:
                pred = "LOW"

            # Confidence: scaled distance from nearest threshold
            # HIGH: 0 to -∞  → clamp at -60; confidence = 50 + 50*(|d|/60)
            # MEDIUM: 0–14   → confidence = 50 + 50*(distance_from_threshold/14)
            # LOW: >14        → confidence = 50 + 50*(min(d-14,60)/60)
            if pred == "HIGH":
                margin = min(abs(d), 60)
                conf = 50 + 50 * (margin / 60)
            elif pred == "MEDIUM":
                # Distance from nearest threshold (0 or 14)
                margin = min(d, 14 - d)
                conf = 50 + 50 * (margin / 7)
            else:
                margin = min(d - 14, 60)
                conf = 50 + 50 * (margin / 60)

            conf = round(min(max(conf, 50.0), 100.0), 2)
            predictions.append(pred)
            confidences.append(conf)
            days_remaining_list.append(int(d))

        return predictions, confidences, days_remaining_list

    def score(self, df: pd.DataFrame, y_true):
        preds, _, _ = self.predict(df)
        return accuracy_score(y_true, preds)


# ──────────────────────── Metrics Helper ──────────────────────────────────────
def compute_metrics(y_true, y_pred, label_order=LABEL_ORDER):
    acc = accuracy_score(y_true, y_pred)
    prec = precision_score(y_true, y_pred, average="macro", zero_division=0, labels=label_order)
    rec = recall_score(y_true, y_pred, average="macro", zero_division=0, labels=label_order)
    f1 = f1_score(y_true, y_pred, average="macro", zero_division=0, labels=label_order)

    # Per-class recall for HIGH urgency
    present_labels = list(set(y_true) | set(y_pred))
    per_class_rec = recall_score(
        y_true, y_pred, average=None, zero_division=0, labels=label_order
    )
    high_idx = label_order.index("HIGH") if "HIGH" in label_order else -1
    high_recall = float(per_class_rec[high_idx]) if high_idx >= 0 else 0.0

    # Confusion matrix rows/cols = LOW, MEDIUM, HIGH
    cm = confusion_matrix(y_true, y_pred, labels=label_order).tolist()

    return {
        "accuracy": round(float(acc), 6),
        "precision_macro": round(float(prec), 6),
        "recall_macro": round(float(rec), 6),
        "f1_macro": round(float(f1), 6),
        "high_urgency_recall": round(high_recall, 6),
        "confusion_matrix": cm,
    }


def compute_yearly_accuracy(df_test: pd.DataFrame, y_true, y_pred) -> dict:
    """Breaks accuracy down by scan_year column if present."""
    result = {}
    if "year" in df_test.columns or "scan_year" in df_test.columns:
        year_col = "year" if "year" in df_test.columns else "scan_year"
        df_copy = df_test.copy()
        df_copy["__true"] = list(y_true)
        df_copy["__pred"] = list(y_pred)
        for yr, grp in df_copy.groupby(year_col):
            if len(grp) > 0:
                yr_acc = accuracy_score(grp["__true"], grp["__pred"])
                result[str(int(yr))] = round(float(yr_acc) * 100, 2)
    result["overall"] = round(accuracy_score(y_true, y_pred) * 100, 2)
    return result


def cv_score(estimator, X, y, cv_folds=5):
    """Returns mean CV accuracy."""
    skf = StratifiedKFold(n_splits=cv_folds, shuffle=True, random_state=42)
    scores = cross_validate(estimator, X, y, cv=skf, scoring="accuracy", error_score=0)
    return float(np.mean(scores["test_score"]))


# ─────────────────────────── Main Training Loop ───────────────────────────────
def run_experiment(payload: dict) -> dict:
    dataset_path = payload["dataset_path"]
    models_to_run = payload.get("models_to_run", ["rule_based", "decision_tree", "random_forest", "svm"])
    output_dir = payload.get("output_dir", "/tmp/ml_models")
    split_ratio = float(payload.get("split_ratio", 0.80))
    cv_folds = int(payload.get("cv_folds", 5))
    hp = payload.get("hyperparams", {})

    os.makedirs(output_dir, exist_ok=True)

    # ── Load data ──
    df = load_and_validate(dataset_path)
    has_labels = TARGET_COL in df.columns and df[TARGET_COL].notna().any()

    result = {
        "status": "completed",
        "row_count": len(df),
        "has_labels": has_labels,
        "models": {},
        "predictions": [],
        "errors": [],
    }

    # Prepare features
    feature_cols = [c for c in REQUIRED_FEATURES if c in df.columns]
    X_all = df[feature_cols].copy()

    if has_labels:
        y_all = df[TARGET_COL].values
        X_train, X_test, y_train, y_test, idx_train, idx_test = train_test_split(
            X_all, y_all, np.arange(len(df)),
            test_size=(1.0 - split_ratio),
            stratify=y_all,
            random_state=42,
        )
        df_test = df.iloc[idx_test].copy().reset_index(drop=True)
    else:
        # Unlabeled: run inference on entire dataset
        X_test = X_all.copy()
        df_test = df.copy().reset_index(drop=True)
        y_test = None
        y_train = None
        X_train = X_all.copy()  # Still needed for fitting

    # ── Build sklearn preprocessor ──
    num_cols = [c for c in NUMERIC_FEATURES if c in feature_cols]
    cat_cols = [c for c in CATEGORICAL_FEATURES if c in feature_cols]

    preprocessor = ColumnTransformer(transformers=[
        ("num", StandardScaler(), num_cols),
        ("cat", OneHotEncoder(handle_unknown="ignore", sparse_output=False), cat_cols),
    ], remainder="drop")

    # ── Fit preprocessor on TRAIN only ──
    if has_labels:
        preprocessor.fit(X_train)
        X_train_pp = preprocessor.transform(X_train)
        X_test_pp = preprocessor.transform(X_test)
    else:
        preprocessor.fit(X_all)
        X_test_pp = preprocessor.transform(X_test)
        X_train_pp = X_test_pp  # for unlabeled, no separate train

    joblib.dump(preprocessor, os.path.join(output_dir, "preprocessor.pkl"))

    # Prediction columns per row
    pred_rows = []

    # ── RULE-BASED ──
    if "rule_based" in models_to_run:
        try:
            rb = RuleBasedModel()
            rb_preds, rb_confs, rb_days = rb.predict(X_test)

            model_result = {"algorithm": "rule_based", "cv_accuracy": None}
            if has_labels:
                metrics = compute_metrics(y_test, rb_preds)
                yearly = compute_yearly_accuracy(df_test, y_test, rb_preds)
                model_result.update(metrics)
                model_result["yearly_accuracy"] = yearly
                model_result["extra_meta"] = {"note": "Deterministic — no CV applicable"}

            result["models"]["rule_based"] = model_result

            for i, (p, c, d) in enumerate(zip(rb_preds, rb_confs, rb_days)):
                while len(pred_rows) <= i:
                    pred_rows.append({})
                pred_rows[i].update({
                    "rb_prediction": p,
                    "rb_confidence": c,
                    "rb_days_remaining": d,
                })
        except Exception as e:
            result["errors"].append({"model": "rule_based", "error": str(e)})

    # ── DECISION TREE ──
    if "decision_tree" in models_to_run:
        try:
            max_depth = hp.get("dt_max_depth", None)
            dt = DecisionTreeClassifier(
                criterion="gini",
                max_depth=int(max_depth) if max_depth else None,
                random_state=42,
            )
            if has_labels:
                dt.fit(X_train_pp, y_train)
                dt_preds = dt.predict(X_test_pp)
                dt_proba = dt.predict_proba(X_test_pp).max(axis=1) * 100
                cv_acc = cv_score(dt, preprocessor.transform(X_train) if has_labels else X_train_pp, y_train, cv_folds) if has_labels else None

                metrics = compute_metrics(y_test, dt_preds)
                yearly = compute_yearly_accuracy(df_test, y_test, dt_preds)
                model_result = {
                    "algorithm": "decision_tree",
                    "cv_accuracy": round(cv_acc, 6) if cv_acc else None,
                    "extra_meta": {
                        "tree_depth": dt.get_depth(),
                        "n_leaves": dt.get_n_leaves(),
                        "feature_importances": dict(zip(
                            num_cols + [f"{c}_{v}" for c, v in zip(
                                cat_cols,
                                [str(x) for sublist in
                                 [preprocessor.named_transformers_["cat"].categories_[i]
                                  for i in range(len(cat_cols))] for x in sublist]
                            )],
                            dt.feature_importances_.tolist()
                        )) if hasattr(dt, "feature_importances_") else {},
                    }
                }
                model_result.update(metrics)
                model_result["yearly_accuracy"] = yearly
            else:
                dt.fit(X_train_pp, y_all)  # fit on all for unlabeled inference
                dt_preds = dt.predict(X_test_pp)
                dt_proba = dt.predict_proba(X_test_pp).max(axis=1) * 100
                model_result = {"algorithm": "decision_tree", "cv_accuracy": None}

            result["models"]["decision_tree"] = model_result
            joblib.dump(dt, os.path.join(output_dir, "decision_tree.pkl"))

            for i, (p, c) in enumerate(zip(dt_preds, dt_proba)):
                while len(pred_rows) <= i:
                    pred_rows.append({})
                pred_rows[i].update({
                    "dt_prediction": str(p),
                    "dt_confidence": round(float(c), 2),
                })
        except Exception as e:
            result["errors"].append({"model": "decision_tree", "error": str(e), "tb": traceback.format_exc()})

    # ── RANDOM FOREST ──
    if "random_forest" in models_to_run:
        try:
            n_estimators = int(hp.get("rf_n_estimators", 100))
            max_depth_rf = hp.get("rf_max_depth", None)
            rf = RandomForestClassifier(
                n_estimators=n_estimators,
                max_features="sqrt",
                max_depth=int(max_depth_rf) if max_depth_rf else None,
                oob_score=True,
                random_state=42,
                n_jobs=-1,
            )
            if has_labels:
                rf.fit(X_train_pp, y_train)
                rf_preds = rf.predict(X_test_pp)
                rf_proba = rf.predict_proba(X_test_pp).max(axis=1) * 100
                cv_acc = cv_score(rf, X_train_pp, y_train, cv_folds)

                metrics = compute_metrics(y_test, rf_preds)
                yearly = compute_yearly_accuracy(df_test, y_test, rf_preds)

                # Feature importances (top 10)
                fi = dict(sorted(
                    zip(num_cols + list(preprocessor.named_transformers_["cat"].get_feature_names_out(cat_cols)),
                        rf.feature_importances_.tolist()),
                    key=lambda x: x[1], reverse=True
                )[:10])

                model_result = {
                    "algorithm": "random_forest",
                    "cv_accuracy": round(cv_acc, 6),
                    "extra_meta": {
                        "oob_score": round(float(rf.oob_score_), 6),
                        "n_estimators": n_estimators,
                        "feature_importances": fi,
                    },
                }
                model_result.update(metrics)
                model_result["yearly_accuracy"] = yearly
            else:
                rf.fit(X_train_pp, y_all)
                rf_preds = rf.predict(X_test_pp)
                rf_proba = rf.predict_proba(X_test_pp).max(axis=1) * 100
                model_result = {"algorithm": "random_forest", "cv_accuracy": None}

            result["models"]["random_forest"] = model_result
            joblib.dump(rf, os.path.join(output_dir, "random_forest.pkl"))

            for i, (p, c) in enumerate(zip(rf_preds, rf_proba)):
                while len(pred_rows) <= i:
                    pred_rows.append({})
                pred_rows[i].update({
                    "rf_prediction": str(p),
                    "rf_confidence": round(float(c), 2),
                })
        except Exception as e:
            result["errors"].append({"model": "random_forest", "error": str(e), "tb": traceback.format_exc()})

    # ── SVM ──
    if "svm" in models_to_run:
        try:
            svm_c = float(hp.get("svm_c", 1.0))
            svm_gamma = hp.get("svm_gamma", "scale")
            svm_pipeline = Pipeline([
                ("svm", SVC(
                    kernel="rbf",
                    C=svm_c,
                    gamma=svm_gamma,
                    probability=True,
                    decision_function_shape="ovo",
                    random_state=42,
                ))
            ])
            if has_labels:
                svm_pipeline.fit(X_train_pp, y_train)
                svm_preds = svm_pipeline.predict(X_test_pp)
                svm_proba = svm_pipeline.predict_proba(X_test_pp).max(axis=1) * 100
                cv_acc = cv_score(svm_pipeline, X_train_pp, y_train, cv_folds)

                metrics = compute_metrics(y_test, svm_preds)
                yearly = compute_yearly_accuracy(df_test, y_test, svm_preds)
                model_result = {
                    "algorithm": "svm",
                    "cv_accuracy": round(cv_acc, 6),
                    "extra_meta": {
                        "kernel": "rbf",
                        "C": svm_c,
                        "gamma": str(svm_gamma),
                        "n_support_vectors": int(sum(svm_pipeline.named_steps["svm"].n_support_)),
                    },
                }
                model_result.update(metrics)
                model_result["yearly_accuracy"] = yearly
            else:
                svm_pipeline.fit(X_train_pp, y_all)
                svm_preds = svm_pipeline.predict(X_test_pp)
                svm_proba = svm_pipeline.predict_proba(X_test_pp).max(axis=1) * 100
                model_result = {"algorithm": "svm", "cv_accuracy": None}

            result["models"]["svm"] = model_result
            joblib.dump(svm_pipeline, os.path.join(output_dir, "svm.pkl"))

            for i, (p, c) in enumerate(zip(svm_preds, svm_proba)):
                while len(pred_rows) <= i:
                    pred_rows.append({})
                pred_rows[i].update({
                    "svm_prediction": str(p),
                    "svm_confidence": round(float(c), 2),
                })
        except Exception as e:
            result["errors"].append({"model": "svm", "error": str(e), "tb": traceback.format_exc()})

    # ── Build prediction rows ──
    for i, row_preds in enumerate(pred_rows):
        try:
            df_row = df_test.iloc[i]
        except IndexError:
            continue

        # Consensus: majority vote among models that ran
        all_preds = [v for k, v in row_preds.items() if k.endswith("_prediction")]
        if all_preds:
            from collections import Counter
            counts = Counter(all_preds)
            consensus = counts.most_common(1)[0][0]
            n_agree = counts.most_common(1)[0][1]
            agreement_rate = f"{n_agree}/{len(all_preds)}"
        else:
            consensus = None
            agreement_rate = "0/0"

        row_preds["consensus_label"] = consensus
        row_preds["agreement_rate"] = agreement_rate
        row_preds["vehicle_identifier"] = str(df_row.get("vehicle_id", df_row.get("plate_number", f"ROW_{i}")))
        row_preds["service_type"] = str(df_row.get("service_type", df_row.get("job_type", "Unknown")))
        row_preds["scan_year"] = int(df_row["year"]) if "year" in df_row and pd.notna(df_row.get("year")) else None
        row_preds["ground_truth_label"] = str(df_row[TARGET_COL]) if has_labels else None
        row_preds["features"] = {
            "vehicle_id": str(df_row.get("vehicle_id", "")),
            "plate_number": str(df_row.get("plate_number", "")),
            "service_type": str(df_row.get("service_type", df_row.get("job_type", ""))),
            "days_since_service": int(df_row.get("days_since_service", 0)) if pd.notna(df_row.get("days_since_service")) else 0,
            "km_since_service": int(df_row.get("km_since_service", 0)) if pd.notna(df_row.get("km_since_service")) else 0,
            "interval_km": int(df_row.get("interval_km", 10000)) if pd.notna(df_row.get("interval_km")) else 10000,
            "interval_months": int(df_row.get("interval_months", 12)) if pd.notna(df_row.get("interval_months")) else 12,
            "daily_km": round(float(df_row.get("daily_km", 40.0)), 2) if pd.notna(df_row.get("daily_km")) else 40.0,
            "terrain_severity": round(float(df_row.get("terrain_severity", 1.0)), 4) if pd.notna(df_row.get("terrain_severity")) else 1.0,
            "total_mileage": int(df_row.get("total_mileage", 0)) if pd.notna(df_row.get("total_mileage")) else 0,
            "vehicle_age": int(df_row.get("vehicle_age", 5)) if pd.notna(df_row.get("vehicle_age")) else 5,
            "fuel_type": str(df_row.get("fuel_type", "diesel")),
            "owner_type": str(df_row.get("owner_type", "fleet")),
            "scan_date": str(df_row.get("scan_date", "")) if pd.notna(df_row.get("scan_date")) else "",
        }

        pred_rows[i] = row_preds

    result["predictions"] = pred_rows
    return result


# ─────────────────────────── Entry Point ──────────────────────────────────────
def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--payload", type=str, help="JSON payload string")
    parser.add_argument("payload_file", nargs="?", help="Path to JSON payload file")
    args = parser.parse_args()

    if args.payload:
        payload = json.loads(args.payload)
    elif args.payload_file:
        with open(args.payload_file) as f:
            payload = json.load(f)
    else:
        # Read from stdin
        payload = json.load(sys.stdin)

    try:
        result = run_experiment(payload)
    except Exception as e:
        result = {
            "status": "failed",
            "error": str(e),
            "traceback": traceback.format_exc(),
        }

    print(json.dumps(result, default=str))


if __name__ == "__main__":
    main()
