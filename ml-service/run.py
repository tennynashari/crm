"""
CRM ML Service Runner
Start the FastAPI server
"""
import uvicorn
from dotenv import load_dotenv
import os

# Load environment variables
load_dotenv()

if __name__ == "__main__":
    host = os.getenv("ML_SERVICE_HOST", "127.0.0.1")
    port = int(os.getenv("ML_SERVICE_PORT", 5000))
    
    print(f"Starting CRM ML Service on {host}:{port}")
    
    uvicorn.run(
        "app.main:app",
        host=host,
        port=port,
        reload=True,  # Enable auto-reload during development
        log_level="info"
    )
