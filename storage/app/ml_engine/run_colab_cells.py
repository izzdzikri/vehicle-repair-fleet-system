#!/usr/bin/env python3
"""
run_colab_cells.py — Interactive Colab Notebook Execution Engine
================================================================
Executes custom/user-edited Python code for the 4-model pipeline
and returns captured stdout, stderr, execution time, and any
extracted model evaluation metrics.

Usage:
  python3 run_colab_cells.py --payload '{"action":"run_all", ...}'
"""

import sys
import io
import time
import json
import os
import traceback
import warnings
from contextlib import redirect_stdout, redirect_stderr

warnings.filterwarnings("ignore")

import numpy as np
import pandas as pd
from sklearn.model_selection import train_test_split, StratifiedKFold, cross_validate
from sklearn.tree import DecisionTreeClassifier
from sklearn.ensemble import (
    RandomForestClassifier,
    HistGradientBoostingClassifier,
    GradientBoostingClassifier,
    AdaBoostClassifier,
    ExtraTreesClassifier,
)
from sklearn.neighbors import KNeighborsClassifier
from sklearn.linear_model import LogisticRegression
from sklearn.neural_network import MLPClassifier
from sklearn.naive_bayes import GaussianNB
from sklearn.svm import SVC
from sklearn.preprocessing import StandardScaler, OneHotEncoder
from sklearn.compose import ColumnTransformer
from sklearn.pipeline import Pipeline
from sklearn.metrics import (
    accuracy_score, precision_score, recall_score, f1_score,
    confusion_matrix, classification_report
)


def format_confusion_matrix_ascii(cm, labels=["LOW", "MED", "HIGH"]):
    header = f"{'Pred \\ Actual':<14} | " + " | ".join(f"{l:>6}" for l in labels)
    sep = "-" * len(header)
    lines = [header, sep]
    for i, row in enumerate(cm):
        row_str = f"{labels[i]:<14} | " + " | ".join(f"{val:>6}" for val in row)
        lines.append(row_str)
    return "\n".join(lines)


def execute_notebook(payload: dict) -> dict:
    dataset_path = payload.get("dataset_path")
    cells = payload.get("cells", {})
    action = payload.get("action", "run_all")
    single_cell_key = payload.get("cell_key", None)

    if not dataset_path or not os.path.exists(dataset_path):
        return {
            "status": "error",
            "stderr": f"Dataset file not found at path: {dataset_path}",
            "stdout": "",
            "execution_time_ms": 0,
        }

    # Shared execution environment
    notebook_globals = {
        "__name__": "__main__",
        "DATASET_PATH": dataset_path,
        "pd": pd,
        "np": np,
        "train_test_split": train_test_split,
        "StratifiedKFold": StratifiedKFold,
        "cross_validate": cross_validate,
        "DecisionTreeClassifier": DecisionTreeClassifier,
        "RandomForestClassifier": RandomForestClassifier,
        "SVC": SVC,
        "HistGradientBoostingClassifier": HistGradientBoostingClassifier,
        "GradientBoostingClassifier": GradientBoostingClassifier,
        "KNeighborsClassifier": KNeighborsClassifier,
        "LogisticRegression": LogisticRegression,
        "AdaBoostClassifier": AdaBoostClassifier,
        "MLPClassifier": MLPClassifier,
        "ExtraTreesClassifier": ExtraTreesClassifier,
        "GaussianNB": GaussianNB,
        "StandardScaler": StandardScaler,
        "OneHotEncoder": OneHotEncoder,
        "ColumnTransformer": ColumnTransformer,
        "Pipeline": Pipeline,
        "accuracy_score": accuracy_score,
        "precision_score": precision_score,
        "recall_score": recall_score,
        "f1_score": f1_score,
        "confusion_matrix": confusion_matrix,
        "classification_report": classification_report,
    }

    results_by_cell = {}
    overall_start = time.time()
    extracted_models = {}
    extracted_predictions = []

    # Dynamic cell order from payload, or default sequence
    cell_order = payload.get("cell_order")
    if not cell_order or not isinstance(cell_order, list):
        cell_order = list(cells.keys())

    if action == "run_single_cell" and single_cell_key:
        cells_to_run = []
        target_idx = cell_order.index(single_cell_key) if single_cell_key in cell_order else 0
        for i in range(target_idx + 1):
            k = cell_order[i]
            if k in cells:
                cells_to_run.append((k, cells[k], i == target_idx))
    else:
        cells_to_run = [(k, cells.get(k, ""), True) for k in cell_order if k in cells]

    for cell_key, code, capture in cells_to_run:
        code_str = str(code or "").strip()
        if not code_str:
            continue

        f_out = io.StringIO()
        f_err = io.StringIO()
        t0 = time.time()
        err_msg = None

        try:
            with redirect_stdout(f_out), redirect_stderr(f_err):
                exec(code_str, notebook_globals)
        except Exception as e:
            err_msg = traceback.format_exc()
        t1 = time.time()

        if capture:
            results_by_cell[cell_key] = {
                "stdout": f_out.getvalue(),
                "stderr": err_msg if err_msg else f_err.getvalue(),
                "execution_time_ms": round((t1 - t0) * 1000, 2),
                "status": "error" if err_msg else "success",
            }

    overall_time = round((time.time() - overall_start) * 1000, 2)

    # Check if final evaluation models exist in notebook_globals
    eval_summary = notebook_globals.get("EVALUATION_SUMMARY", None)
    eval_predictions = notebook_globals.get("PREDICTION_RESULTS", None)

    return {
        "status": "success",
        "action": action,
        "overall_time_ms": overall_time,
        "cell_results": results_by_cell,
        "evaluation_summary": eval_summary,
        "prediction_results": eval_predictions,
    }


def main():
    if len(sys.argv) > 1 and sys.argv[1] == "--payload":
        raw = sys.argv[2]
        payload = json.loads(raw)
    else:
        payload = json.load(sys.stdin)

    try:
        res = execute_notebook(payload)
    except Exception as e:
        res = {
            "status": "error",
            "stderr": traceback.format_exc(),
            "stdout": "",
            "execution_time_ms": 0,
        }

    print(json.dumps(res, default=str))


if __name__ == "__main__":
    main()
