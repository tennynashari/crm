#!/usr/bin/env python3
"""Test database connection"""
import sys
import os
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

print("Testing database connection...")
print(f"DB_HOST: {os.getenv('DB_HOST')}")
print(f"DB_PORT: {os.getenv('DB_PORT')}")
print(f"DB_NAME: {os.getenv('DB_NAME')}")
print(f"DB_USER: {os.getenv('DB_USER')}")
print(f"DB_PASSWORD: {'***' if os.getenv('DB_PASSWORD') else 'NOT SET'}")
print()

try:
    from sqlalchemy import create_engine, text
    
    db_config = {
        'host': os.getenv('DB_HOST', 'localhost'),
        'port': os.getenv('DB_PORT', '5432'),
        'database': os.getenv('DB_NAME', 'crm'),
        'user': os.getenv('DB_USER', 'crm'),
        'password': os.getenv('DB_PASSWORD', '')
    }
    
    db_url = f"postgresql://{db_config['user']}:{db_config['password']}@{db_config['host']}:{db_config['port']}/{db_config['database']}"
    
    print("Creating database engine...")
    engine = create_engine(db_url)
    
    print("Testing connection...")
    with engine.connect() as conn:
        result = conn.execute(text("SELECT 1"))
        print("✓ Database connection successful!")
        
        # Test if customers table exists
        result = conn.execute(text("SELECT COUNT(*) FROM customers"))
        count = result.scalar()
        print(f"✓ Customers table exists with {count} records")
        
        # Test if interactions table exists
        result = conn.execute(text("SELECT COUNT(*) FROM interactions"))
        count = result.scalar()
        print(f"✓ Interactions table exists with {count} records")
        
        # Test if invoices table exists
        result = conn.execute(text("SELECT COUNT(*) FROM invoices"))
        count = result.scalar()
        print(f"✓ Invoices table exists with {count} records")
        
    print("\n✓ All database checks passed!")
    sys.exit(0)
    
except Exception as e:
    print(f"\n✗ Database connection failed!")
    print(f"Error: {type(e).__name__}: {e}")
    sys.exit(1)
