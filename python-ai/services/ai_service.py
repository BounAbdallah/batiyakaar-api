from langchain_google_genai import ChatGoogleGenerativeAI
from langchain_openai import ChatOpenAI
from langchain_groq import ChatGroq
from langchain.schema import HumanMessage, SystemMessage
import logging
from typing import Optional, Dict
from config.settings import settings

logger = logging.getLogger(__name__)

class AIService:
    """Unified AI service supporting OpenAI, Google Gemini, and Groq"""
    
    def __init__(self):
        self.provider = settings.ai_provider
        self.llm = None
        
        if self.provider == "groq" and settings.groq_api_key:
            try:
                self.llm = ChatGroq(
                    model=settings.groq_model,
                    groq_api_key=settings.groq_api_key,
                    temperature=0.7,
                    max_tokens=500
                )
                logger.info(f"Groq service initialized with model: {settings.groq_model}")
            except Exception as e:
                logger.error(f"Failed to initialize Groq: {e}")
                
        elif self.provider == "gemini" and settings.gemini_api_key:
            try:
                self.llm = ChatGoogleGenerativeAI(
                    model=settings.gemini_model,
                    google_api_key=settings.gemini_api_key,
                    temperature=0.7,
                    max_output_tokens=500
                )
                logger.info(f"Gemini service initialized with model: {settings.gemini_model}")
            except Exception as e:
                logger.error(f"Failed to initialize Gemini: {e}")
                
        elif self.provider == "openai" and settings.openai_api_key:
            try:
                self.llm = ChatOpenAI(
                    model=settings.openai_model,
                    temperature=0.7,
                    openai_api_key=settings.openai_api_key,
                    max_tokens=500
                )
                logger.info(f"OpenAI service initialized with model: {settings.openai_model}")
            except Exception as e:
                logger.error(f"Failed to initialize OpenAI: {e}")
        
        if not self.llm:
            logger.warning(f"No AI provider configured (provider: {self.provider})")
    
    async def check_health(self) -> bool:
        """Check if AI service is accessible"""
        if not self.llm:
            return False
        try:
            response = self.llm.invoke([HumanMessage(content="test")])
            return True
        except Exception as e:
            logger.error(f"AI health check failed: {str(e)}")
            return False
    
    async def generate_response(
        self,
        question: str,
        context: Dict,
        max_tokens: int = 500
    ) -> str:
        """Generate AI response"""
        if not self.llm:
            return self._get_degraded_mode_message(question, context)
        
        try:
            system_prompt = self._build_system_prompt()
            user_prompt = self._build_user_prompt(question, context)
            
            logger.info(f"Generating response with {self.provider} for: {question[:50]}...")
            
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
            
            if 'insufficient_quota' in error_msg or '429' in error_msg or 'quota' in error_msg.lower():
                return self._get_degraded_mode_message(question, context)
            
            return f"Désolé, une erreur est survenue. Pour les questions simples, essayez : 'Combien de biens ai-je ?', 'Liste des locataires en retard', 'Revenus du mois'."
    
    def _build_system_prompt(self) -> str:
        """Build system prompt for the LLM"""
        return """Tu es un assistant IA professionnel spécialisé dans la gestion immobilière pour Noor Immo.
Tu aides les agences immobilières à gérer leurs biens, locataires, et paiements.

INSTRUCTIONS IMPORTANTES:
- Réponds en français professionnel
- Sois concis, précis et factuel
- N'utilise JAMAIS d'émojis
- N'utilise PAS de formatage Markdown (pas de *, **, #, etc.)
- Utilise des tirets simples (-) pour les listes si nécessaire
- Reste formel et professionnel dans ton ton
- Utilise les données du contexte fourni pour répondre
- Si tu ne sais pas, dis-le clairement
- Structure tes réponses de manière claire avec des paragraphes courts"""
    
    def _build_user_prompt(self, question: str, context: Dict) -> str:
        """Build user prompt with comprehensive context"""
        prompt_parts = ["CONTEXTE COMPLET DE L'UTILISATEUR:"]
        
        if context.get('agence_name'):
            prompt_parts.append(f"\nAgence: {context['agence_name']}")
        
        # Statistiques des biens
        if context.get('total_biens'):
            prompt_parts.append(f"\nPORTEFEUILLE IMMOBILIER:")
            prompt_parts.append(f"- Total de biens: {context['total_biens']}")
            prompt_parts.append(f"- Biens loues: {context.get('biens_loues', 0)}")
            prompt_parts.append(f"- Biens disponibles: {context.get('biens_disponibles', 0)}")
            if context.get('biens_maintenance', 0) > 0:
                prompt_parts.append(f"- Biens en maintenance: {context['biens_maintenance']}")
            prompt_parts.append(f"- Taux d'occupation: {context.get('taux_occupation', 0)}%")
            
            if context.get('types_biens'):
                types_str = ", ".join([f"{count} {type}" for type, count in context['types_biens'].items()])
                prompt_parts.append(f"- Repartition par type: {types_str}")
        
        # Locataires
        if context.get('total_locataires'):
            prompt_parts.append(f"\nLOCATAIRES:")
            prompt_parts.append(f"- Locataires actifs: {context['total_locataires']}")
        
        # Revenus
        if context.get('revenus_mois') is not None:
            prompt_parts.append(f"\nREVENUS:")
            prompt_parts.append(f"- Revenus mois en cours: {context['revenus_mois']:,.0f} FCFA")
            
            if context.get('revenus_mois_precedent') is not None:
                prompt_parts.append(f"- Revenus mois precedent: {context['revenus_mois_precedent']:,.0f} FCFA")
                
                # Calculer l'évolution
                if context['revenus_mois_precedent'] > 0:
                    evolution = ((context['revenus_mois'] - context['revenus_mois_precedent']) / context['revenus_mois_precedent']) * 100
                    prompt_parts.append(f"- Evolution: {evolution:+.1f}%")
            
            if context.get('loyers_mensuels_actifs'):
                prompt_parts.append(f"- Loyers mensuels actifs (biens loues): {context['loyers_mensuels_actifs']:,.0f} FCFA")
        
        # Impayés
        if context.get('nb_impayes', 0) > 0:
            prompt_parts.append(f"\nIMPAYES:")
            prompt_parts.append(f"- Nombre de paiements en retard: {context['nb_impayes']}")
            prompt_parts.append(f"- Montant total des impayes: {context.get('montant_impayes', 0):,.0f} FCFA")
        
        prompt_parts.extend([
            "",
            f"QUESTION: {question}",
            "",
            "Reponds de maniere precise en utilisant UNIQUEMENT les donnees ci-dessus. Ne mentionne pas d'echantillons ou de donnees partielles."
        ])
        
        return "\n".join(prompt_parts)
    
    def _get_degraded_mode_message(self, question: str, context: Dict) -> str:
        """Generate helpful message when AI is unavailable"""
        response_parts = ["🔧 **Mode dégradé activé** (IA non disponible)\n"]
        
        if context.get('total_biens'):
            response_parts.append(f"\n📊 **Vos données actuelles :**")
            response_parts.append(f"- Biens : {context['total_biens']}")
            
            if context.get('total_locataires'):
                response_parts.append(f"- Locataires actifs : {context['total_locataires']}")
            
            if context.get('revenus_mois'):
                response_parts.append(f"- Revenus du mois : {context['revenus_mois']:,.0f} FCFA")
        
        response_parts.append("\n💡 **Questions disponibles (réponses rapides) :**")
        response_parts.append("- Combien de biens ai-je ?")
        response_parts.append("- Liste des locataires en retard")
        response_parts.append("- Revenus du mois")
        response_parts.append("- Biens disponibles")
        
        response_parts.append("\n⚠️ Les analyses IA complexes nécessitent une clé API active.")
        
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
