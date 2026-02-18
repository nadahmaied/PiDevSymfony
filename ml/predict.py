#!/usr/bin/env python3
import argparse
import json
import math
from pathlib import Path


def sigmoid(x: float) -> float:
    if x < -35:
        return 0.0
    if x > 35:
        return 1.0
    return 1.0 / (1.0 + math.exp(-x))


def main():
    parser = argparse.ArgumentParser(description="Predict recommendation probability from trained model.")
    parser.add_argument("--model", required=True, help="Path to trained model JSON.")
    parser.add_argument("--features", required=True, help="JSON string of feature values.")
    args = parser.parse_args()

    model_path = Path(args.model)
    with model_path.open("r", encoding="utf-8") as f:
        model = json.load(f)

    features = json.loads(args.features)
    names = model.get("feature_names", [])
    means = model.get("means", [])
    stds = model.get("stds", [])
    weights = model.get("weights", [])
    bias = float(model.get("bias", 0.0))

    z = bias
    for i, name in enumerate(names):
        raw = float(features.get(name, 0.0))
        mean = float(means[i] if i < len(means) else 0.0)
        std = float(stds[i] if i < len(stds) and stds[i] != 0 else 1.0)
        z += float(weights[i] if i < len(weights) else 0.0) * ((raw - mean) / std)

    prob = sigmoid(z)
    print(json.dumps({"probability": prob}))


if __name__ == "__main__":
    main()
