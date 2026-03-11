"""
Customer Prediction Model
Rule-based scoring with option to extend to ML
"""
import pandas as pd
import numpy as np
import joblib
import os
import json
from datetime import datetime
from typing import List, Dict, Optional

from .database import fetch_customers_data, fetch_interactions_data, fetch_invoices_data
from .features import FeatureEngineering


class CustomerPredictor:
    """
    Predicts top potential customers based on interaction and sales history
    Multi-tenant: Each tenant has separate model
    """
    
    def __init__(self):
        self.base_model_dir = os.path.join(os.path.dirname(__file__), '..', 'models')
        
        # Ensure base model directory exists
        os.makedirs(self.base_model_dir, exist_ok=True)
        
        self.features_df = None
        self.metadata = None
    
    def _get_tenant_model_dir(self, database: str = "crm") -> str:
        """Get model directory for specific tenant"""
        tenant_dir = os.path.join(self.base_model_dir, database)
        os.makedirs(tenant_dir, exist_ok=True)
        return tenant_dir
    
    def _get_model_path(self, database: str = "crm") -> str:
        """Get model file path for specific tenant"""
        tenant_dir = self._get_tenant_model_dir(database)
        return os.path.join(tenant_dir, 'customer_predictor.pkl')
    
    def _get_metadata_path(self, database: str = "crm") -> str:
        """Get metadata file path for specific tenant"""
        tenant_dir = self._get_tenant_model_dir(database)
        return os.path.join(tenant_dir, 'model_metadata.json')
    
    def model_exists(self, database: str = "crm") -> bool:
        """Check if trained model exists for tenant"""
        model_path = self._get_model_path(database)
        metadata_path = self._get_metadata_path(database)
        return os.path.exists(model_path) and os.path.exists(metadata_path)
    
    def get_model_info(self, database: str = "crm") -> Optional[Dict]:
        """Get model metadata for tenant"""
        metadata_path = self._get_metadata_path(database)
        
        if not os.path.exists(metadata_path):
            return None
        
        try:
            with open(metadata_path, 'r') as f:
                metadata = json.load(f)
                metadata['database'] = database  # Add tenant info
                return metadata
        except:
            return None
    
    def train(self, database: str = "crm") -> Dict:
        """
        Train the prediction model for specific tenant
        For MVP: Uses rule-based scoring
        Future: Can be extended to ML model (Random Forest, etc.)
        
        Args:
            database: Tenant database name (e.g., 'crm', 'crm_ecogreen')
        
        Returns:
            Dict with training results
        """
        try:
            print(f"Fetching data from database: {database}...")
            customers_df = fetch_customers_data(database)
            interactions_df = fetch_interactions_data(database)
            invoices_df = fetch_invoices_data(database)
            
            if len(customers_df) == 0:
                return {
                    "success": False,
                    "error": f"No customers found in database: {database}"
                }
            
            print(f"Found {len(customers_df)} customers, {len(interactions_df)} interactions, {len(invoices_df)} invoices")
            
            # Feature engineering
            print("Engineering features...")
            fe = FeatureEngineering(customers_df, interactions_df, invoices_df)
            self.features_df = fe.extract_features()
            
            # Calculate scores using rule-based algorithm
            print("Calculating prediction scores...")
            self.features_df['prediction_score'] = self._calculate_score(self.features_df)
            
            # Save model (features + metadata) for this tenant
            print(f"Saving model for tenant: {database}...")
            model_path = self._get_model_path(database)
            metadata_path = self._get_metadata_path(database)
            
            joblib.dump(self.features_df, model_path)
            
            # Save metadata
            metadata = {
                "trained_at": datetime.now().isoformat(),
                "database": database,
                "customers_count": len(customers_df),
                "features_count": len(self.features_df.columns),
                "model_type": "rule_based_scoring",
                "version": "1.0.0"
            }
            
            with open(metadata_path, 'w') as f:
                json.dump(metadata, f, indent=2)
            
            self.metadata = metadata
            
            print(f"Model training complete for {database}")
            
            return {
                "success": True,
                "database": database,
                "trained_at": metadata["trained_at"],
                "customers_count": metadata["customers_count"],
                "model_path": model_path
            }
            
        except Exception as e:
            print(f"Training error: {e}")
            return {
                "success": False,
                "error": str(e)
            }
    
    def _calculate_score(self, features_df: pd.DataFrame) -> pd.Series:
        """
        Calculate potential score using rule-based algorithm
        
        Scoring weights:
        - Sales in last 90 days: VERY HIGH (30 points per invoice)
        - Revenue in last 90 days: VERY HIGH (20 points per $1000)
        - Total invoices: HIGH (5 points each)
        - Recent interactions: MEDIUM (2 points per interaction in 90d)
        - Lead Status (Hot/Warm/Qualified/Won): HIGH BONUS
        - Engagement score: MEDIUM
        - Recency bonuses
        """
        scores = pd.Series(0.0, index=features_df.index)
        
        # CRITICAL: Sales momentum in last 90 days
        scores += features_df['invoices_last_90d'] * 30
        scores += (features_df['revenue_last_90d'] / 1000) * 20
        
        # Total sales history
        scores += features_df['total_invoices'] * 5
        scores += (features_df['total_revenue'] / 1000) * 2
        
        # Recent interactions
        scores += features_df['interactions_last_90d'] * 2
        scores += features_df['interactions_last_30d'] * 1
        
        # Engagement scores
        scores += features_df['recent_engagement_score'] * 0.5
        scores += features_df['sales_momentum'] * 1.5
        
        # LEAD STATUS BONUSES (HIGH VALUE)
        # Hot Lead: Sangat potensial, siap closing
        scores += features_df['is_hot_lead'] * 25
        
        # Warm Lead: Potensial tinggi, perlu nurturing
        scores += features_df['is_warm_lead'] * 20
        
        # Qualified: Sudah qualified, proses deal
        scores += features_df['is_qualified'] * 20
        
        # Won: Customer yang sudah menang/closing
        scores += features_df['is_won'] * 30
        
        # Recency bonuses
        # Bonus if interacted recently (last 7 days)
        recency_bonus = np.where(features_df['last_interaction_days_ago'] <= 7, 10, 0)
        scores += recency_bonus
        
        # Bonus if has revenue in last 90 days
        revenue_bonus = np.where(features_df['revenue_last_90d'] > 0, 15, 0)
        scores += revenue_bonus
        
        # Penalty for very old last interaction (more than 180 days)
        old_penalty = np.where(features_df['last_interaction_days_ago'] > 180, -10, 0)
        scores += old_penalty
        
        # Bonus for active lead status
        scores += features_df['lead_status_active'] * 5
        
        return scores
    
    def predict_top_customers(self, top_n: int = 7, customer_ids: List[int] = None, database: str = "crm") -> List[Dict]:
        """
        Predict top N potential customers for specific tenant
        If top_n is None, return all customers sorted by score
        If customer_ids provided, filter to only those customers (for sales role)
        
        Args:
            top_n: Number of top customers to return
            customer_ids: Optional list to filter (for sales role)
            database: Tenant database name
        
        Returns:
            List of customer predictions
        """
        try:
            # Load model for this tenant
            if not self.model_exists(database):
                raise ValueError(f"Model not trained yet for database: {database}")
            
            model_path = self._get_model_path(database)
            self.features_df = joblib.load(model_path)
            self.metadata = self.get_model_info(database)
            
            print(f"Loaded model for {database}: {len(self.features_df)} customers")
            
            # Start with full dataset
            df = self.features_df.copy()
            
            # Filter by customer_ids if provided (SALES ROLE)
            if customer_ids is not None and len(customer_ids) > 0:
                df = df[df['customer_id'].isin(customer_ids)]
            
            # Check if any customers remain after filter
            if len(df) == 0:
                return []
            
            # Sort by score
            if top_n is None:
                top_customers = df.sort_values('prediction_score', ascending=False)
            else:
                top_customers = df.nlargest(min(top_n, len(df)), 'prediction_score')
            
            # Calculate total for percentile
            total_customers = len(df)
            
            # Format results
            predictions = []
            for rank, (_, row) in enumerate(top_customers.iterrows(), 1):
                # Generate reason text
                reason = self._generate_reason(row)
                
                # Calculate percentile (100 = best, 0 = worst)
                # Percentile represents: "This customer is better than X% of customers"
                percentile = 100 - ((rank / total_customers) * 100)
                percentile_score = round(percentile, 1)
                
                # Extract key details
                details = {
                    "invoices_last_90d": int(row['invoices_last_90d']),
                    "revenue_last_90d": float(row['revenue_last_90d']),
                    "total_invoices": int(row['total_invoices']),
                    "interactions_last_90d": int(row['interactions_last_90d']),
                    "last_interaction_days": int(row['last_interaction_days_ago']),
                    "customer_age_days": int(row['customer_age_days'])
                }
                
                predictions.append({
                    "customer_id": int(row['customer_id']),
                    "company": row['company'],
                    "email": row['email'],
                    "area": row['area_name'] if pd.notna(row.get('area_name')) else 'No Area',
                    "score": float(row['prediction_score']),
                    "percentile_score": percentile_score,  # 0-100 normalized score
                    "rank": rank,
                    "reason": reason,
                    "details": details
                })
            
            return predictions
            
        except Exception as e:
            print(f"Prediction error: {e}")
            raise
    
    def _generate_reason(self, row: pd.Series) -> str:
        """Generate human-readable reason for prediction"""
        reasons = []
        
        # Check lead status FIRST (priority reason)
        if row['is_hot_lead'] == 1:
            reasons.append("🔥 Hot Lead")
        elif row['is_warm_lead'] == 1:
            reasons.append("⭐ Warm Lead")
        elif row['is_qualified'] == 1:
            reasons.append("✓ Qualified")
        elif row['is_won'] == 1:
            reasons.append("🏆 Won")
        
        # Check sales in last 90 days
        if row['invoices_last_90d'] > 0:
            reasons.append(f"{int(row['invoices_last_90d'])} sales dalam 3 bulan")
        
        # Check revenue
        if row['revenue_last_90d'] > 0:
            reasons.append(f"Revenue Rp {row['revenue_last_90d']:,.0f}")
        
        # Check total sales history
        if row['total_invoices'] > 0 and len(reasons) < 3:
            reasons.append(f"{int(row['total_invoices'])} total sales")
        
        # Check recent interactions
        if row['interactions_last_90d'] > 0 and len(reasons) < 3:
            reasons.append(f"{int(row['interactions_last_90d'])} interaksi")
        
        # Check recency
        if row['last_interaction_days_ago'] <= 7 and len(reasons) < 3:
            reasons.append("Interaksi sangat baru")
        elif row['last_interaction_days_ago'] <= 30 and len(reasons) < 3:
            reasons.append("Interaksi recent")
        
        if len(reasons) == 0:
            return "Customer aktif"
        
        return " • ".join(reasons[:3])  # Max 3 reasons
    
    def predict_single_customer(self, customer_id: int, database: str = "crm") -> dict:
        """
        Predict potential score for a single customer
        Returns score, reason, and ranking information
        
        Args:
            customer_id: ID of customer to predict
            database: Tenant database name
        """
        try:
            if not self.model_exists(database=database):
                raise Exception(f"Model not trained yet for database: {database}")
            
            # Get all predictions to calculate ranking
            all_predictions = self.predict_top_customers(database=database, top_n=None)  # Get all (includes percentile_score)
            
            # Find the specific customer
            customer_prediction = None
            for pred in all_predictions:
                if pred["customer_id"] == customer_id:
                    # Use percentile_score from prediction (already calculated correctly)
                    customer_prediction = pred.copy()
                    # Keep percentile_score as is (100 = best, 0 = worst)
                    customer_prediction["percentile"] = customer_prediction["percentile_score"]
                    break
            
            return customer_prediction
            
        except Exception as e:
            print(f"Single customer prediction error: {e}")
            raise
