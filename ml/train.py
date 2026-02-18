#!/usr/bin/env python3
import argparse
import csv
import json
import math
import random
from datetime import datetime, timezone
from pathlib import Path


def sigmoid(x: float) -> float:
    if x < -35:
        return 0.0
    if x > 35:
        return 1.0
    return 1.0 / (1.0 + math.exp(-x))


def load_dataset(path: Path):
    with path.open("r", newline="", encoding="utf-8") as f:
        reader = csv.DictReader(f)
        if reader.fieldnames is None:
            raise ValueError("CSV has no header")
        if "label" not in reader.fieldnames:
            raise ValueError("CSV must contain a 'label' column")

        feature_names = [name for name in reader.fieldnames if name != "label"]
        rows = []
        for row in reader:
            try:
                y = int(float(row["label"]))
            except Exception:
                continue
            if y not in (0, 1):
                continue

            x = []
            ok = True
            for feature in feature_names:
                try:
                    x.append(float(row.get(feature, 0.0)))
                except Exception:
                    ok = False
                    break
            if ok:
                rows.append((x, y))

    if not rows:
        raise ValueError("Dataset is empty after parsing.")
    return feature_names, rows


def compute_stats(xs):
    n = len(xs)
    d = len(xs[0])
    means = [0.0] * d
    stds = [0.0] * d

    for x in xs:
        for j, value in enumerate(x):
            means[j] += value
    means = [m / n for m in means]

    for x in xs:
        for j, value in enumerate(x):
            stds[j] += (value - means[j]) ** 2
    stds = [math.sqrt(v / n) if v > 0 else 1.0 for v in stds]
    stds = [s if s > 1e-9 else 1.0 for s in stds]
    return means, stds


def normalize(xs, means, stds):
    out = []
    for x in xs:
        out.append([(value - means[j]) / stds[j] for j, value in enumerate(x)])
    return out


def train_logreg(xs, ys, epochs=1200, lr=0.05, l2=0.001):
    d = len(xs[0])
    w = [0.0] * d
    b = 0.0
    n = len(xs)

    for _ in range(epochs):
        grad_w = [0.0] * d
        grad_b = 0.0

        for x, y in zip(xs, ys):
            z = b + sum(wj * xj for wj, xj in zip(w, x))
            p = sigmoid(z)
            err = p - y
            for j in range(d):
                grad_w[j] += err * x[j]
            grad_b += err

        for j in range(d):
            grad_w[j] = (grad_w[j] / n) + (l2 * w[j])
            w[j] -= lr * grad_w[j]
        b -= lr * (grad_b / n)

    return w, b


def evaluate(xs, ys, w, b):
    correct = 0
    for x, y in zip(xs, ys):
        z = b + sum(wj * xj for wj, xj in zip(w, x))
        pred = 1 if sigmoid(z) >= 0.5 else 0
        if pred == y:
            correct += 1
    return correct / max(1, len(xs))


def main():
    parser = argparse.ArgumentParser(description="Train recommendation model (logistic regression).")
    parser.add_argument("--input", required=True, help="Path to CSV dataset.")
    parser.add_argument("--output", required=True, help="Path to output model JSON.")
    parser.add_argument("--epochs", type=int, default=1200)
    parser.add_argument("--lr", type=float, default=0.05)
    args = parser.parse_args()

    input_path = Path(args.input)
    output_path = Path(args.output)
    output_path.parent.mkdir(parents=True, exist_ok=True)

    feature_names, rows = load_dataset(input_path)
    random.seed(42)
    random.shuffle(rows)

    split_idx = int(len(rows) * 0.8)
    train_rows = rows[:split_idx] or rows
    valid_rows = rows[split_idx:] or rows

    x_train = [r[0] for r in train_rows]
    y_train = [r[1] for r in train_rows]
    x_valid = [r[0] for r in valid_rows]
    y_valid = [r[1] for r in valid_rows]

    means, stds = compute_stats(x_train)
    x_train_norm = normalize(x_train, means, stds)
    x_valid_norm = normalize(x_valid, means, stds)

    w, b = train_logreg(x_train_norm, y_train, epochs=args.epochs, lr=args.lr)
    train_acc = evaluate(x_train_norm, y_train, w, b)
    valid_acc = evaluate(x_valid_norm, y_valid, w, b)

    model = {
        "model_type": "logistic_regression",
        "feature_names": feature_names,
        "weights": w,
        "bias": b,
        "means": means,
        "stds": stds,
        "metrics": {
            "train_accuracy": train_acc,
            "valid_accuracy": valid_acc,
            "train_rows": len(train_rows),
            "valid_rows": len(valid_rows),
        },
        "trained_at": datetime.now(timezone.utc).isoformat(),
    }

    with output_path.open("w", encoding="utf-8") as f:
        json.dump(model, f, indent=2)

    print(
        json.dumps(
            {
                "status": "ok",
                "output": str(output_path),
                "train_accuracy": round(train_acc, 4),
                "valid_accuracy": round(valid_acc, 4),
                "rows": len(rows),
            }
        )
    )


if __name__ == "__main__":
    main()
