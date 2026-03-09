"""
FastAPI Main Application
"""
from fastapi import FastAPI, HTTPException, Depends
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import List, Optional
import os
from datetime import datetime

from .predictor import CustomerPredictor
from .database import get_db_session

app = FastAPI(
    title="CRM ML Service",
    description="AI-powered customer prediction service",
    version="1.0.0"
)

# CORS configuration
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # In production, specify Laravel domain
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Global predictor instance
predictor = CustomerPredictor()

# Response Models
class HealthResponse(BaseModel):
    status: str
    service: str
    version: str
    model_loaded: bool
    model_info: Optional[dict] = None

class TrainResponse(BaseModel):
    success: bool
    message: str
    trained_at: str
    customers_count: int
    model_path: Optional[str] = None

class PredictionResult(BaseModel):
    customer_id: int
    company: str
    email: str
    score: float
    rank: int
    reason: str
    details: dict

class PredictResponse(BaseModel):
    success: bool
    predictions: List[PredictionResult]
    generated_at: str
    model_trained_at: Optional[str] = None


@app.get("/", response_model=HealthResponse)
async def root():
    """Health check endpoint"""
    model_info = predictor.get_model_info()
    return {
        "status": "running",
        "service": "CRM ML Service",
        "version": "1.0.0",
        "model_loaded": predictor.model_exists(),
        "model_info": model_info
    }


@app.get("/health", response_model=HealthResponse)
async def health_check():
    """Detailed health check"""
    model_info = predictor.get_model_info()
    return {
        "status": "healthy" if predictor.model_exists() else "no_model",
        "service": "CRM ML Service",
        "version": "1.0.0",
        "model_loaded": predictor.model_exists(),
        "model_info": model_info
    }


@app.post("/train", response_model=TrainResponse)
async def train_model():
    """
    Train the ML model with current customer data
    Fetches data from database, engineers features, trains model
    """
    try:
        result = predictor.train()
        
        if result["success"]:
            return TrainResponse(
                success=True,
                message="Model trained successfully",
                trained_at=result["trained_at"],
                customers_count=result["customers_count"],
                model_path=result.get("model_path")
            )
        else:
            raise HTTPException(status_code=500, detail=result.get("error", "Training failed"))
            
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/predict", response_model=PredictResponse)
async def predict_top_customers():
    """
    Predict top 7 potential customers
    Returns customers ranked by potential score
    """
    try:
        if not predictor.model_exists():
            raise HTTPException(
                status_code=400,
                detail="Model not trained yet. Please train the model first using /train endpoint"
            )
        
        predictions = predictor.predict_top_customers(top_n=7)
        
        return PredictResponse(
            success=True,
            predictions=predictions,
            generated_at=datetime.now().isoformat(),
            model_trained_at=predictor.get_model_info().get("trained_at")
        )
        
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@app.get("/model-info")
async def get_model_info():
    """Get information about the current model"""
    if not predictor.model_exists():
        return {
            "model_exists": False,
            "message": "No model trained yet"
        }
    
    return {
        "model_exists": True,
        "info": predictor.get_model_info()
    }


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="127.0.0.1", port=5000)
