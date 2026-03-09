"""
Feature Engineering for Customer Prediction
"""
import pandas as pd
import numpy as np
from datetime import datetime, timedelta
from typing import Dict, List


class FeatureEngineering:
    """
    Extract and engineer features from raw customer data
    """
    
    def __init__(self, customers_df: pd.DataFrame, interactions_df: pd.DataFrame, invoices_df: pd.DataFrame):
        self.customers = customers_df.copy()
        self.interactions = interactions_df.copy()
        self.invoices = invoices_df.copy()
        self.reference_date = datetime.now()
        
        # Convert date columns
        self._prepare_data()
    
    def _prepare_data(self):
        """Prepare and convert date columns"""
        # Customers dates
        if 'created_at' in self.customers.columns:
            self.customers['created_at'] = pd.to_datetime(self.customers['created_at'])
        if 'next_action_date' in self.customers.columns:
            self.customers['next_action_date'] = pd.to_datetime(self.customers['next_action_date'])
        
        # Interactions dates
        if 'interaction_at' in self.interactions.columns:
            self.interactions['interaction_at'] = pd.to_datetime(self.interactions['interaction_at'])
        if 'created_at' in self.interactions.columns:
            self.interactions['created_at'] = pd.to_datetime(self.interactions['created_at'])
        
        # Invoices dates
        if 'invoice_date' in self.invoices.columns:
            self.invoices['invoice_date'] = pd.to_datetime(self.invoices['invoice_date'])
        if 'created_at' in self.invoices.columns:
            self.invoices['created_at'] = pd.to_datetime(self.invoices['created_at'])
    
    def extract_features(self) -> pd.DataFrame:
        """
        Extract all features for each customer
        """
        features_list = []
        
        for _, customer in self.customers.iterrows():
            customer_id = customer['id']
            
            # Get customer-specific data
            customer_interactions = self.interactions[self.interactions['customer_id'] == customer_id]
            customer_invoices = self.invoices[self.invoices['customer_id'] == customer_id]
            
            # Extract features
            features = {
                'customer_id': customer_id,
                'company': customer['company'],
                'email': customer['email'],
                **self._customer_profile_features(customer),
                **self._interaction_features(customer_interactions),
                **self._invoice_features(customer_invoices),
                **self._engagement_features(customer_interactions, customer_invoices)
            }
            
            features_list.append(features)
        
        return pd.DataFrame(features_list)
    
    def _customer_profile_features(self, customer) -> Dict:
        """Extract customer profile features"""
        features = {}
        
        # Customer age in days
        if pd.notna(customer['created_at']):
            features['customer_age_days'] = (self.reference_date - customer['created_at']).days
        else:
            features['customer_age_days'] = 0
        
        # Source encoding (inbound = 1, outbound = 0)
        features['source_inbound'] = 1 if customer['source'] == 'inbound' else 0
        
        # Has next action
        features['has_next_action'] = 1 if pd.notna(customer.get('next_action_date')) else 0
        
        # Days until next action (negative if overdue)
        if pd.notna(customer.get('next_action_date')):
            features['days_to_next_action'] = (customer['next_action_date'] - self.reference_date.date()).days
        else:
            features['days_to_next_action'] = 999
        
        # Lead status active
        features['lead_status_active'] = 1 if customer.get('lead_status_active') == 1 else 0
        
        # Lead status name for bonus scoring
        lead_status_name = str(customer.get('lead_status_name', '')).lower()
        features['lead_status_name'] = lead_status_name
        
        # Lead status bonus flags (HIGH VALUE statuses)
        features['is_hot_lead'] = 1 if 'hot' in lead_status_name else 0
        features['is_warm_lead'] = 1 if 'warm' in lead_status_name else 0
        features['is_qualified'] = 1 if 'qualified' in lead_status_name else 0
        features['is_won'] = 1 if 'won' in lead_status_name else 0
        
        return features
    
    def _interaction_features(self, interactions_df: pd.DataFrame) -> Dict:
        """Extract interaction-based features"""
        features = {}
        
        # Total interactions
        features['total_interactions'] = len(interactions_df)
        
        if len(interactions_df) == 0:
            features['interactions_last_30d'] = 0
            features['interactions_last_90d'] = 0
            features['interactions_last_180d'] = 0
            features['last_interaction_days_ago'] = 999
            features['avg_interactions_per_month'] = 0
            features['email_inbound_count'] = 0
            features['email_outbound_count'] = 0
            features['channel_diversity'] = 0
            return features
        
        # Date ranges
        date_30d = self.reference_date - timedelta(days=30)
        date_90d = self.reference_date - timedelta(days=90)
        date_180d = self.reference_date - timedelta(days=180)
        
        # Interactions in time windows
        features['interactions_last_30d'] = len(interactions_df[interactions_df['interaction_at'] >= date_30d])
        features['interactions_last_90d'] = len(interactions_df[interactions_df['interaction_at'] >= date_90d])
        features['interactions_last_180d'] = len(interactions_df[interactions_df['interaction_at'] >= date_180d])
        
        # Last interaction recency
        last_interaction = interactions_df['interaction_at'].max()
        features['last_interaction_days_ago'] = (self.reference_date - last_interaction).days
        
        # Average interactions per month
        if len(interactions_df) > 0:
            first_interaction = interactions_df['interaction_at'].min()
            months = max(1, (self.reference_date - first_interaction).days / 30)
            features['avg_interactions_per_month'] = len(interactions_df) / months
        else:
            features['avg_interactions_per_month'] = 0
        
        # Interaction types
        features['email_inbound_count'] = len(interactions_df[interactions_df['interaction_type'] == 'email_inbound'])
        features['email_outbound_count'] = len(interactions_df[interactions_df['interaction_type'] == 'email_outbound'])
        
        # Channel diversity
        features['channel_diversity'] = interactions_df['channel'].nunique() if 'channel' in interactions_df.columns else 0
        
        return features
    
    def _invoice_features(self, invoices_df: pd.DataFrame) -> Dict:
        """Extract invoice/sales-based features"""
        features = {}
        
        # Total invoices
        features['total_invoices'] = len(invoices_df)
        
        if len(invoices_df) == 0:
            features['invoices_last_90d'] = 0
            features['invoices_last_180d'] = 0
            features['total_revenue'] = 0
            features['revenue_last_90d'] = 0
            features['revenue_last_180d'] = 0
            features['avg_invoice_value'] = 0
            features['last_invoice_days_ago'] = 999
            features['invoice_frequency'] = 0
            return features
        
        # Date ranges
        date_90d = self.reference_date - timedelta(days=90)
        date_180d = self.reference_date - timedelta(days=180)
        
        # Invoices in time windows (HIGH WEIGHT)
        recent_invoices_90d = invoices_df[invoices_df['invoice_date'] >= date_90d]
        recent_invoices_180d = invoices_df[invoices_df['invoice_date'] >= date_180d]
        
        features['invoices_last_90d'] = len(recent_invoices_90d)
        features['invoices_last_180d'] = len(recent_invoices_180d)
        
        # Revenue
        features['total_revenue'] = invoices_df['total'].sum()
        features['revenue_last_90d'] = recent_invoices_90d['total'].sum() if len(recent_invoices_90d) > 0 else 0
        features['revenue_last_180d'] = recent_invoices_180d['total'].sum() if len(recent_invoices_180d) > 0 else 0
        
        # Average invoice value
        features['avg_invoice_value'] = invoices_df['total'].mean()
        
        # Last invoice recency
        last_invoice = invoices_df['invoice_date'].max()
        features['last_invoice_days_ago'] = (self.reference_date - last_invoice).days
        
        # Invoice frequency
        if len(invoices_df) > 0:
            first_invoice = invoices_df['invoice_date'].min()
            months = max(1, (self.reference_date - first_invoice).days / 30)
            features['invoice_frequency'] = len(invoices_df) / months
        else:
            features['invoice_frequency'] = 0
        
        return features
    
    def _engagement_features(self, interactions_df: pd.DataFrame, invoices_df: pd.DataFrame) -> Dict:
        """Calculate derived engagement scores"""
        features = {}
        
        # Recent interaction engagement
        date_30d = self.reference_date - timedelta(days=30)
        date_90d = self.reference_date - timedelta(days=90)
        
        interactions_30d = len(interactions_df[interactions_df['interaction_at'] >= date_30d]) if len(interactions_df) > 0 else 0
        interactions_90d = len(interactions_df[interactions_df['interaction_at'] >= date_90d]) if len(interactions_df) > 0 else 0
        
        features['recent_engagement_score'] = (interactions_30d * 1.5 + interactions_90d * 1.0)
        
        # Sales momentum (CRITICAL for prediction)
        invoices_90d = len(invoices_df[invoices_df['invoice_date'] >= date_90d]) if len(invoices_df) > 0 else 0
        revenue_90d = invoices_df[invoices_df['invoice_date'] >= date_90d]['total'].sum() if len(invoices_df) > 0 else 0
        
        features['sales_momentum'] = (invoices_90d * 2.0 + revenue_90d / 1000)
        
        return features
