from langchain_openai import ChatOpenAI
from langchain.prompts import ChatPromptTemplate
from langchain.schema import HumanMessage, SystemMessage
import logging
from typing import Optional, Dict, List
from config.settings import settings

logger = logging.getLogger(__name__)

class OpenAIService:
    """Service for interacting with OpenAI via LangChain"""
    
    def __init__(self):
        if not settings.openai_api_key:
            logger.warning("OpenAI API key not configured!")
            self.llm = None
        else:
            self.llm = ChatOpenAI(
                model=settings.openai_model,
                temperature=0.7,
                openai_api_key=settings.openai_api_key,
                max_tokens=500
            )
            logger.info(f"OpenAI service initialized with model: {settings.openai_model}")
    
    async def check_health(self) -> bool:
        """Check if OpenAI API is accessible"""
        if not self.llm:
            return False
        try:
            # Simple test call
            response = self.llm.invoke([HumanMessage(content="test")])
            return True
        except Exception as e:
            logger.error(f"OpenAI health check failed: {str(e)}")
            return False
    
    async def generate_response(
        self,
        question: str,
        context: Dict,
        max_tokens: int = 500
    ) -> str:
        """
        Generate AI response using OpenAI
        
        Args:
            question: User's question
            context: Context data (biens, baux, etc.)
            max_tokens: Maximum response length
        
        Returns:
            Generated response text
        """
        if not self.llm:
            return self._get_degraded_mode_message(question, context)
        
        try:
            # Build prompt with context
            system_prompt = self._build_system_prompt()
            user_prompt = self._build_user_prompt(question, context)
            
            logger.info(f"Generating response for: {question[:50]}...")
            
            # Call OpenAI via LangChain
            messages = [
                SystemMessage(content=system_prompt),
                HumanMessage(content=user_prompt)
            ]
            
            response = self.llm.invoke(messages)
            answer = response.content.strip()
            
            logger.info(f"Response generated: {len(answer)} characters")
            
            return answer
            
        except Exception as e:
            error_msg = str(e)
            logger.error(f"Error generating response: {error_msg}")
            
            # Check if it's a quota error
            if 'insufficient_quota' in error_msg or '429' in error_msg or 'quota' in error_msg.lower():
                return self._get_degraded_mode_message(question, context)
            
            return f"Désolé, une erreur est survenue. Pour les questions simples, essayez : 'Combien de biens ai-je ?', 'Liste des locataires en retard', 'Revenus du mois'."
    
    def _build_system_prompt(self) -> str:
        """Build system prompt for the LLM"""
        return """Tu es un assistant IA spécialisé dans la gestion immobilière pour Noor Immo.
Tu aides les agences immobilières à gérer leurs biens, locataires, et paiements.

INSTRUCTIONS:
- Réponds en français
- Sois concis et précis
- Utilise les données du contexte fourni
- Si tu ne sais pas, dis-le clairement
- Formate tes réponses avec des listes à puces quand approprié
- Utilise des émojis pour rendre les réponses plus agréables"""
    
    def _build_user_prompt(self, question: str, context: Dict) -> str:
        """Build user prompt with context"""
        
        prompt_parts = ["CONTEXTE DE L'UTILISATEUR:"]
        
        # Add user context
        if context.get('agence_name'):
            prompt_parts.append(f"- Agence: {context['agence_name']}")
        
        if context.get('total_biens'):
            prompt_parts.append(f"- Nombre de biens: {context['total_biens']}")
        
        if context.get('total_locataires'):
            prompt_parts.append(f"- Nombre de locataires: {context['total_locataires']}")
        
        if context.get('revenus_mois'):
            prompt_parts.append(f"- Revenus du mois: {context['revenus_mois']:,.0f} FCFA")
        
        # Add specific data if available
        if context.get('biens'):
            prompt_parts.append("\nBIENS (échantillon):")
            for bien in context['biens'][:5]:  # Limit to 5
                prompt_parts.append(f"- {bien.get('adresse', 'N/A')}: {bien.get('statut', 'N/A')}")
        
        if context.get('impayés'):
            impaye_count = context['impayés'][0].get('count', 0) if context['impayés'] else 0
            if impaye_count > 0:
                prompt_parts.append(f"\n⚠️ IMPAYÉS: {impaye_count} paiement(s) en retard")
        
        prompt_parts.extend([
            "",
            f"QUESTION: {question}",
            "",
            "Réponds de manière claire et utile en te basant sur le contexte ci-dessus."
        ])
        
        return "\n".join(prompt_parts)
    
    
    def _get_degraded_mode_message(self, question: str, context: Dict) -> str:
        """
        Generate a helpful message when OpenAI is unavailable
        Uses context data to provide useful information
        """
        # Build a helpful response using available context
        response_parts = ["🔧 **Mode dégradé activé** (quota OpenAI dépassé)\n"]
        
        # Add context summary
        if context.get('total_biens'):
            response_parts.append(f"\n📊 **Vos données actuelles :**")
            response_parts.append(f"- Biens : {context['total_biens']}")
            
            if context.get('total_locataires'):
                response_parts.append(f"- Locataires actifs : {context['total_locataires']}")
            
            if context.get('revenus_mois'):
                response_parts.append(f"- Revenus du mois : {context['revenus_mois']:,.0f} FCFA")
        
        # Add helpful suggestions
        response_parts.append("\n💡 **Questions disponibles (réponses rapides) :**")
        response_parts.append("- Combien de biens ai-je ?")
        response_parts.append("- Liste des locataires en retard")
        response_parts.append("- Revenus du mois")
        response_parts.append("- Biens disponibles")
        
        response_parts.append("\n⚠️ Les analyses IA complexes nécessitent un crédit OpenAI actif.")
        
        return "\n".join(response_parts)
    
    async def generate_summary(self, data: Dict, summary_type: str) -> str:
        """Generate a summary of data"""
        prompts = {
            'biens': "Fais un résumé concis de l'état des biens immobiliers",
            'revenus': "Fais un résumé de la situation financière",
            'locataires': "Fais un résumé de la situation des locataires"
        }
        
        prompt = prompts.get(summary_type, "Fais un résumé")
        return await self.generate_response(prompt, data)
