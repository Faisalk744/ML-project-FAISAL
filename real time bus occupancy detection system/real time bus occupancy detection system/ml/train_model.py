"""
Train delivery time prediction model from archive/Food_Delivery_Times.csv
"""
import json
import sys
from pathlib import Path

import joblib
import numpy as np
import pandas as pd
from sklearn.compose import ColumnTransformer
from sklearn.ensemble import RandomForestRegressor
from sklearn.impute import SimpleImputer
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
from sklearn.model_selection import train_test_split
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import OneHotEncoder

ROOT = Path(__file__).resolve().parent.parent
DATA_PATH = ROOT / "archive" / "Food_Delivery_Times.csv"
MODEL_DIR = Path(__file__).resolve().parent
MODEL_PATH = MODEL_DIR / "model.joblib"
PIPELINE_PATH = MODEL_DIR / "pipeline.joblib"
META_PATH = MODEL_DIR / "model_meta.json"

FEATURE_COLS = [
    "Distance_km",
    "Weather",
    "Traffic_Level",
    "Time_of_Day",
    "Vehicle_Type",
    "Preparation_Time_min",
    "Courier_Experience_yrs",
]
TARGET_COL = "Delivery_Time_min"
CATEGORICAL = ["Weather", "Traffic_Level", "Time_of_Day", "Vehicle_Type"]
NUMERIC = ["Distance_km", "Preparation_Time_min", "Courier_Experience_yrs"]


def load_and_clean(path: Path) -> pd.DataFrame:
    df = pd.read_csv(path)
    for col in CATEGORICAL:
        df[col] = df[col].astype(str).str.strip()
        df.loc[df[col].isin(["", "nan", "None"]), col] = np.nan
    return df


def build_pipeline() -> Pipeline:
    numeric_transformer = Pipeline(
        steps=[("imputer", SimpleImputer(strategy="median"))]
    )
    categorical_transformer = Pipeline(
        steps=[
            ("imputer", SimpleImputer(strategy="most_frequent")),
            (
                "encoder",
                OneHotEncoder(handle_unknown="ignore", sparse_output=False),
            ),
        ]
    )
    preprocessor = ColumnTransformer(
        transformers=[
            ("num", numeric_transformer, NUMERIC),
            ("cat", categorical_transformer, CATEGORICAL),
        ]
    )
    model = RandomForestRegressor(
        n_estimators=200,
        max_depth=18,
        min_samples_leaf=2,
        random_state=42,
        n_jobs=-1,
    )
    return Pipeline(
        steps=[
            ("preprocessor", preprocessor),
            ("regressor", model),
        ]
    )


def main() -> int:
    if not DATA_PATH.exists():
        print(f"Dataset not found: {DATA_PATH}", file=sys.stderr)
        return 1

    df = load_and_clean(DATA_PATH)
    X = df[FEATURE_COLS]
    y = df[TARGET_COL]

    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, random_state=42
    )

    pipeline = build_pipeline()
    pipeline.fit(X_train, y_train)
    y_pred = pipeline.predict(X_test)

    mae = float(mean_absolute_error(y_test, y_pred))
    rmse = float(np.sqrt(mean_squared_error(y_test, y_pred)))
    r2 = float(r2_score(y_test, y_pred))

    MODEL_DIR.mkdir(parents=True, exist_ok=True)
    joblib.dump(pipeline, PIPELINE_PATH)

    meta = {
        "feature_columns": FEATURE_COLS,
        "target_column": TARGET_COL,
        "categorical_options": {
            "Weather": sorted(df["Weather"].dropna().unique().tolist()),
            "Traffic_Level": sorted(df["Traffic_Level"].dropna().unique().tolist()),
            "Time_of_Day": sorted(df["Time_of_Day"].dropna().unique().tolist()),
            "Vehicle_Type": sorted(df["Vehicle_Type"].dropna().unique().tolist()),
        },
        "defaults": {
            "Weather": "Clear",
            "Traffic_Level": "Medium",
            "Time_of_Day": "Afternoon",
            "Vehicle_Type": "Bike",
            "Courier_Experience_yrs": float(df["Courier_Experience_yrs"].median()),
        },
        "metrics": {
            "mae_minutes": round(mae, 2),
            "rmse_minutes": round(rmse, 2),
            "r2_score": round(r2, 4),
            "train_samples": int(len(X_train)),
            "test_samples": int(len(X_test)),
        },
    }
    META_PATH.write_text(json.dumps(meta, indent=2), encoding="utf-8")

    print("Model trained successfully.")
    print(f"  MAE:  {mae:.2f} minutes")
    print(f"  RMSE: {rmse:.2f} minutes")
    print(f"  R²:   {r2:.4f}")
    print(f"  Saved: {PIPELINE_PATH}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
