# ML Recommendation Trainer

This folder contains Python scripts for recommendation model training.

## 1) Export dataset from Symfony

```bash
php bin/console app:ml:export-recommendation-data
```

Default output:

`var/ml/recommendation_training.csv`

## 2) Train model

```bash
php bin/console app:ml:train-recommendation-model --python-bin python
```

Default model output:

`var/ml/recommendation_model.json`

## 3) Optional local prediction test

```bash
python ml/predict.py --model var/ml/recommendation_model.json --features "{\"skills\":0.8,\"geo\":0.7,\"availability\":0.6,\"history\":0.4,\"social\":0.5,\"urgency\":0.9,\"difficulty\":0.5,\"duration_days\":0.8}"
```

The Symfony runtime reads `var/ml/recommendation_model.json` automatically when present.
