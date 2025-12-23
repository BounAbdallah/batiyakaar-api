import re
import logging
from typing import List, Dict

logger = logging.getLogger(__name__)

class QueryRouter:
    """Routes queries to either SQL (fast) or AI (complex) based on query type"""
    
    # Keywords that indicate simple SQL queries
    SQL_KEYWORDS = [
        'combien', 'nombre', 'liste', 'affiche', 'montre',
        'total', 'somme', 'qui', 'quels', 'quelles',
        'derniers', 'dernières', 'récents', 'récentes',
        'revenus', 'revenu', 'paiements', 'paiement', 'loyers', 'loyer',
        'impayés', 'impayé', 'retard', 'disponibles', 'disponible',
        'immeuble', 'immeubles', 'batiment', 'batiments', 'etage', 'étage', 'etages', 'étages',
        'occupe', 'occupé', 'occupes', 'occupés', 'loue', 'loué', 'loues', 'loués'
    ]
    
    # Keywords that indicate complex AI queries
    AI_KEYWORDS = [
        'résume', 'analyse', 'conseille', 'suggère', 'recommande',
        'pourquoi', 'comment', 'explique', 'compare', 'évalue',
        'prédis', 'anticipe', 'stratégie', 'optimise'
    ]
    
    def determine_query_type(self, question: str) -> str:
        """
        Determine if query should use SQL or AI
        
        Args:
            question: User's question
        
        Returns:
            'sql' or 'ai'
        """
        question_lower = question.lower()
        
        # Check for AI keywords first (more complex)
        for keyword in self.AI_KEYWORDS:
            if keyword in question_lower:
                logger.info(f"AI query detected (keyword: {keyword})")
                return 'ai'
        
        # Check for SQL keywords
        for keyword in self.SQL_KEYWORDS:
            if keyword in question_lower:
                logger.info(f"SQL query detected (keyword: {keyword})")
                return 'sql'
        
        # Check for specific patterns
        if self._is_count_query(question_lower):
            return 'sql'
        
        if self._is_list_query(question_lower):
            return 'sql'
        
        # Default to AI for complex/ambiguous queries
        logger.info("Defaulting to AI query")
        return 'ai'
    
    def _is_count_query(self, question: str) -> bool:
        """Check if question is asking for a count"""
        count_patterns = [
            r'combien\s+(de|d\')',
            r'nombre\s+(de|d\')',
            r'total\s+(de|d\')',
            r'\d+\s+biens?',
            r'\d+\s+locataires?'
        ]
        return any(re.search(pattern, question) for pattern in count_patterns)
    
    def _is_list_query(self, question: str) -> bool:
        """Check if question is asking for a list"""
        list_patterns = [
            r'liste\s+(des?|les)',
            r'affiche\s+(les?|moi)',
            r'montre\s+(les?|moi)',
            r'quels?\s+sont',
            r'qui\s+sont'
        ]
        return any(re.search(pattern, question) for pattern in list_patterns)
    
    def get_contextual_suggestions(
        self,
        user_id: int,
        agence_id: int = None
    ) -> List[str]:
        """
        Get suggested questions based on user context
        
        Returns:
            List of suggested questions
        """
        # Base suggestions for all users
        suggestions = [
            "Combien de biens ai-je ?",
            "Liste des locataires en retard de paiement",
            "Quel est mon revenu du mois ?",
            "Montre-moi les biens disponibles",
            "Résume la situation de mes paiements"
        ]
        
        # TODO: Add contextual suggestions based on user data
        # - If has unpaid rents: "Comment réduire les impayés ?"
        # - If has maintenance issues: "Quels biens nécessitent une maintenance ?"
        # - If end of month: "Prépare le rapport mensuel"
        
        return suggestions[:5]  # Return top 5
    
    def extract_entities(self, question: str) -> Dict:
        """
        Extract entities from question (dates, amounts, property types, etc.)
        
        Returns:
            Dictionary of extracted entities
        """
        entities = {
            'dates': [],
            'amounts': [],
            'property_types': [],
            'statuses': []
        }
        
        # Extract dates
        date_patterns = [
            r'(janvier|février|mars|avril|mai|juin|juillet|août|septembre|octobre|novembre|décembre)\s+\d{4}',
            r'\d{1,2}/\d{1,2}/\d{4}',
            r'(aujourd\'hui|hier|demain|cette semaine|ce mois|cette année)'
        ]
        for pattern in date_patterns:
            matches = re.findall(pattern, question, re.IGNORECASE)
            entities['dates'].extend(matches)
        
        # Extract amounts
        amount_pattern = r'\d+[\s,]?\d*\s*(fcfa|cfa|francs?)'
        entities['amounts'] = re.findall(amount_pattern, question, re.IGNORECASE)
        
        # Extract property types
        property_types = ['appartement', 'maison', 'villa', 'studio', 'bureau', 'local']
        for ptype in property_types:
            if ptype in question.lower():
                entities['property_types'].append(ptype)
        
        # Extract statuses
        statuses = ['disponible', 'loué', 'occupé', 'libre', 'maintenance']
        for status in statuses:
            if status in question.lower():
                entities['statuses'].append(status)
        
        return entities
