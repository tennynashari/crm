# CRM ML Service - Customer Prediction

AI/ML service untuk prediksi top potential customers berdasarkan interaction dan sales history.

## Setup

1. Install Python 3.12+
2. Create virtual environment:
   ```bash
   python -m venv venv
   source venv/bin/activate  # Linux/Mac
   venv\Scripts\activate     # Windows
   ```

3. Install dependencies:
   ```bash
   pip install -r requirements.txt
   ```

4. Copy `.env.example` to `.env` dan configure database

5. Run service:
   ```bash
   python run.py
   ```

## API Endpoints

- `GET /` - Health check
- `GET /health` - Service status & model info
- `POST /train` - Train ML model dengan current data
- `POST /predict` - Predict top 7 potential customers

## Features

Model menggunakan features:
- Total interactions & recency
- Invoice history (especially last 3 months)
- Revenue patterns
- Customer engagement score
- Communication channel diversity
