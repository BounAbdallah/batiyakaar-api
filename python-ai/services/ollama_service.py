import ollama
import logging
from typing import Optional, Dict, List
from config.settings import settings

logger = logging.getLogger(__name__)

class OllamaService:
    """Service for interacting with Ollama LLM"""
    
    def __init__(self):
        self.client = ollama.Client(host=settings.ollama_host)
        self.model = settings.model_name
        logger.info(f"Ollama service initialized with model: {self.model}")
    
    async def check_health(self) -> bool:
        """Check if Ollama is running and model is available"""
        try:
            models = self.client.list()
            available_models = [m['name'] for m in models.get('models', [])]
            return self.model in available_models
        except Exception as e:
            logger.error(f"Ollama health check failed: {str(e)}")
            return False
    
    async def generate_response(
        self,
        question: str,
        context: Dict,
        max_tokens: int = 500
    ) -> str:
        """
        Generate AI response using Ollama
        
        Args:
            question: User's question
            context: Context data (biens, baux, etc.)
            max_tokens: Maximum response length
        
        Returns:
            Generated response text
        """
        try:
            # Build prompt with context
            prompt = self._build_prompt(question, context)
            
            logger.info(f"Generating response for: {question[:50]}...")
            
            # Call Ollama
            response = self.client.generate(
                model=self.model,
                prompt=prompt,
                options={
                    'num_predict': max_tokens,
                    'temperature': 0.7,
                    'top_p': 0.9,
                }
            )
            
            answer = response['response'].strip()
            logger.info(f"Response generated: {len(answer)} characters")
            
            return answer
            
        except Exception as e:
            logger.error(f"Error generating response: {str(e)}")
            return f"Désolé, une erreur s'est produite lors de la génération de la réponse: {str(e)}"
    
    def _build_prompt(self, question: str, context: Dict) -> str:
        """Build prompt with context for the LLM"""
        
        prompt_parts = [
            "Tu es un assistant IA spécialisé dans la gestion immobilière pour Noor Immo.",
            "Tu aides les agences immobilières à gérer leurs biens, locataires, et paiements.",
            "",
            "CONTEXTE DE L'UTILISATEUR:",
        ]
        
        # Add user context
        if context.get('agence_name'):
            prompt_parts.append(f"- Agence: {context['agence_name']}")
        
        if context.get('total_biens'):
            prompt_parts.append(f"- Nombre de biens: {context['total_biens']}")
        
        if context.get('total_locataires'):
            prompt_parts.append(f"- Nombre de locataires: {context['total_locataires']}")
        
        if context.get('revenus_mois'):
            prompt_parts.append(f"- Revenus du mois: {context['revenus_mois']} FCFA")
        
        # Add specific data if available
        if context.get('biens'):
            prompt_parts.append("\nBIENS:")
            for bien in context['biens'][:5]:  # Limit to 5
                prompt_parts.append(f"- {bien.get('adresse', 'N/A')}: {bien.get('statut', 'N/A')}")
        
        if context.get('impayés'):
            prompt_parts.append(f"\nIMPAYÉS: {len(context['impayés'])} locataires en retard")
        
        prompt_parts.extend([
            "",
            "INSTRUCTIONS:",
            "- Réponds en français",
            "- Sois concis et précis",
            "- Utilise les données du contexte",
            "- Si tu ne sais pas, dis-le",
            "",
            f"QUESTION: {question}",
            "",
            "RÉPONSE:"
        ])
        
        return "\n".join(prompt_parts)
    
    async def generate_summary(self, data: Dict, summary_type: str) -> str:
        """Generate a summary of data"""
        prompts = {
            'biens': "Fais un résumé de l'état des biens immobiliers",
            'revenus': "Fais un résumé de la situation financière",
            'locataires': "Fais un résumé de la situation des locataires"
        }
        
        prompt = prompts.get(summary_type, "Fais un résumé")
        return await self.generate_response(prompt, data)
