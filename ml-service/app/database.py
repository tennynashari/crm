"""
Database Connection and Query Functions with Multi-Tenant Support
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
DB_PORT = os.getenv("DB_PORT", "5432")
DB_NAME = os.getenv("DB_NAME", "crm")  # Default database
DB_USER = os.getenv("DB_USER", "crm")
DB_PASSWORD = os.getenv("DB_PASSWORD", "crm123")

# Cache for database engines (per tenant)
_engines = {}


def get_engine(database: str = None):
    """
    Get or create database engine for specified database
    
    Args:
        database: Database name (e.g., 'crm', 'crm_ecogreen')
                 If None, uses default from env
    
    Returns:
        SQLAlchemy engine
    """
    if database is None:
        database = DB_NAME
    
    # Return cached engine if exists
    if database in _engines:
        return _engines[database]
    
    # Create new engine
    database_url = f"postgresql://{DB_USER}:{DB_PASSWORD}@{DB_HOST}:{DB_PORT}/{database}"
    
    engine = create_engine(
        database_url,
        pool_pre_ping=True,
        pool_recycle=3600,
        echo=False
    )
    
    # Cache it
    _engines[database] = engine
    
    return engine


# Default engine (for backward compatibility)
engine = get_engine()

# Create session factory
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)


def get_db_session() -> Session:
    """Get database session"""
    db = SessionLocal()
    try:
        return db
    finally:
        db.close()


def fetch_customers_data(database: str = None) -> pd.DataFrame:
    """
    Fetch all customers with their basic info
    
    Args:
        database: Database name for multi-tenant support
    
    Returns:
        DataFrame with customers data
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
    
    eng = get_engine(database)
    with eng.connect() as conn:
        df = pd.read_sql(query, conn)
    
    return df


def fetch_interactions_data(database: str = None) -> pd.DataFrame:
    """
    Fetch all interactions
    
    Args:
        database: Database name for multi-tenant support
    
    Returns:
        DataFrame with interactions data
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
    
    eng = get_engine(database)
    with eng.connect() as conn:
        df = pd.read_sql(query, conn)
    
    return df


def fetch_invoices_data(database: str = None) -> pd.DataFrame:
    """
    Fetch all invoices
    
    Args:
        database: Database name for multi-tenant support
    
    Returns:
        DataFrame with invoices data
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
    
    eng = get_engine(database)
    with eng.connect() as conn:
        df = pd.read_sql(query, conn)
    
    return df


def test_connection(database: str = None) -> bool:
    """
    Test database connection
    
    Args:
        database: Database name to test
    
    Returns:
        True if connection successful
    """
    try:
        eng = get_engine(database)
        with eng.connect() as conn:
            result = conn.execute(text("SELECT 1"))
            return True
    except Exception as e:
        print(f"Database connection error (db={database}): {e}")
        return False
