"""
Predict delivery time from JSON input (stdin or first argument).
Output: JSON with predicted_delivery_min and success flag.
"""
import json
import sys
from pathlib import Path

import joblib
import pandas as pd

ML_DIR = Path(__file__).resolve().parent
PIPELINE_PATH = ML_DIR / "pipeline.joblib"
META_PATH = ML_DIR / "model_meta.json"

REQUIRED = [
    "distance_km",
    "weather",
    "traffic_level",
    "time_of_day",
    "vehicle_type",
    "preparation_time_min",
    "courier_experience_yrs",
]


def load_meta() -> dict:
    if META_PATH.exists():
        return json.loads(META_PATH.read_text(encoding="utf-8"))
    return {"defaults": {}}


def normalize_input(data: dict, meta: dict) -> dict:
    defaults = meta.get("defaults", {})
    mapping = {
        "distance_km": ("Distance_km", float),
        "weather": ("Weather", str),
        "traffic_level": ("Traffic_Level", str),
        "time_of_day": ("Time_of_Day", str),
        "vehicle_type": ("Vehicle_Type", str),
        "preparation_time_min": ("Preparation_Time_min", int),
        "courier_experience_yrs": ("Courier_Experience_yrs", float),
    }
    default_keys = {
        "weather": "Weather",
        "traffic_level": "Traffic_Level",
        "time_of_day": "Time_of_Day",
        "vehicle_type": "Vehicle_Type",
        "courier_experience_yrs": "Courier_Experience_yrs",
    }
    row = {}
    for api_key, (col, cast) in mapping.items():
        val = data.get(api_key)
        if val is None or (isinstance(val, str) and val.strip() == ""):
            dk = default_keys.get(api_key)
            val = defaults.get(dk) if dk else None
        if val is None:
            raise ValueError(f"Missing required field: {api_key}")
        row[col] = cast(val)
    return row


def predict(data: dict) -> dict:
    if not PIPELINE_PATH.exists():
        return {
            "success": False,
            "error": "Model not trained. Run: python ml/train_model.py",
        }

    meta = load_meta()
    try:
        row = normalize_input(data, meta)
    except (ValueError, TypeError) as e:
        return {"success": False, "error": str(e)}

    pipeline = joblib.load(PIPELINE_PATH)
    X = pd.DataFrame([row])
    pred = float(pipeline.predict(X)[0])
    pred = max(5.0, round(pred, 1))

    return {
        "success": True,
        "predicted_delivery_min": pred,
        "inputs": row,
    }


def main() -> int:
    raw = ""
    if len(sys.argv) > 1:
        raw = sys.argv[1]
    else:
        raw = sys.stdin.read()

    if not raw.strip():
        print(json.dumps({"success": False, "error": "No input JSON provided"}))
        return 1

    try:
        data = json.loads(raw)
    except json.JSONDecodeError as e:
        print(json.dumps({"success": False, "error": f"Invalid JSON: {e}"}))
        return 1

    result = predict(data)
    print(json.dumps(result))
    return 0 if result.get("success") else 1


if __name__ == "__main__":
    sys.exit(main())
