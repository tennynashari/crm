"""
Database Connection and Query Functions
"""
from sqlalchemy import create_engine, text
from sqlalchemy.orm import sessionmaker, Session
from sqlalchemy.pool import StaticPool
import os
from dotenv import load_dotenv
import pandas as pd
from typing import Optional

load_dotenv()

# Database configuration
DB_HOST = os.getenv("DB_HOST", "127.0.0.1")
DB_PORT = os.getenv("DB_PORT", "3306")
DB_NAME = os.getenv("DB_NAME", "crm_db")
DB_USER = os.getenv("DB_USER", "root")
DB_PASSWORD = os.getenv("DB_PASSWORD", "")

# Create database URL
DATABASE_URL = f"mysql+pymysql://{DB_USER}:{DB_PASSWORD}@{DB_HOST}:{DB_PORT}/{DB_NAME}"

# Create engine
engine = create_engine(
    DATABASE_URL,
    pool_pre_ping=True,
    pool_recycle=3600,
    echo=False
)

# Create session factory
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)


def get_db_session() -> Session:
    """Get database session"""
    db = SessionLocal()
    try:
        return db
    finally:
        db.close()


def fetch_customers_data() -> pd.DataFrame:
    """
    Fetch all customers with their basic info
    """
    query = """
    SELECT 
        c.id,
        c.company,
        c.email,
        c.phone,
        c.source,
        c.area_id,
        c.lead_status_id,
        c.assigned_sales_id,
        c.next_action_date,
        c.created_at,
        ls.name as lead_status_name,
        ls.is_active as lead_status_active,
        a.name as area_name
    FROM customers c
    LEFT JOIN lead_statuses ls ON c.lead_status_id = ls.id
    LEFT JOIN areas a ON c.area_id = a.id
    ORDER BY c.id
    """
    
    with engine.connect() as conn:
        df = pd.read_sql(query, conn)
    
    return df


def fetch_interactions_data() -> pd.DataFrame:
    """
    Fetch all interactions
    """
    query = """
    SELECT 
        id,
        customer_id,
        interaction_type,
        channel,
        interaction_at,
        created_at
    FROM interactions
    ORDER BY customer_id, interaction_at DESC
    """
    
    with engine.connect() as conn:
        df = pd.read_sql(query, conn)
    
    return df


def fetch_invoices_data() -> pd.DataFrame:
    """
    Fetch all invoices
    """
    query = """
    SELECT 
        id,
        customer_id,
        invoice_number,
        invoice_date,
        total,
        status,
        created_at
    FROM invoices
    WHERE status IN ('paid', 'sent')
    ORDER BY customer_id, invoice_date DESC
    """
    
    with engine.connect() as conn:
        df = pd.read_sql(query, conn)
    
    return df


def test_connection() -> bool:
    """
    Test database connection
    """
    try:
        with engine.connect() as conn:
            result = conn.execute(text("SELECT 1"))
            return True
    except Exception as e:
        print(f"Database connection error: {e}")
        return False
