from sqlalchemy import create_engine, text
from sqlalchemy.orm import sessionmaker
from typing import Dict, List, Optional
import logging
from datetime import datetime, timedelta

from config.settings import settings

logger = logging.getLogger(__name__)

class DatabaseService:
    """Service for database operations"""
    
    def __init__(self):
        # Create MySQL connection string
        connection_string = (
            f"mysql+pymysql://{settings.db_username}:{settings.db_password}"
            f"@{settings.db_host}:{settings.db_port}/{settings.db_database}"
        )
        
        self.engine = create_engine(connection_string, pool_pre_ping=True)
        self.SessionLocal = sessionmaker(bind=self.engine)
        logger.info("Database service initialized")
    
    def check_connection(self) -> bool:
        """Check if database connection is working"""
        try:
            with self.engine.connect() as conn:
                conn.execute(text("SELECT 1"))
            return True
        except Exception as e:
            logger.error(f"Database connection check failed: {str(e)}")
            return False
    
    def get_user_context(self, user_id: int, agence_id: Optional[int] = None) -> Dict:
        """
        Get comprehensive context data for a user
        
        Returns:
            Dictionary with complete user statistics (not samples)
        """
        try:
            with self.SessionLocal() as session:
                context = {}
                
                # Get agence info
                if agence_id:
                    agence_query = text("""
                        SELECT raison_sociale, adresse
                        FROM agences
                        WHERE id = :agence_id
                    """)
                    agence = session.execute(agence_query, {"agence_id": agence_id}).fetchone()
                    if agence:
                        context['agence_name'] = agence[0]
                
                # Get complete biens statistics by status
                biens_stats_query = text("""
                    SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN statut = 'loue' THEN 1 ELSE 0 END) as loues,
                        SUM(CASE WHEN statut = 'disponible' THEN 1 ELSE 0 END) as disponibles,
                        SUM(CASE WHEN statut = 'maintenance' THEN 1 ELSE 0 END) as maintenance,
                        COUNT(DISTINCT type) as types_count,
                        SUM(CASE WHEN statut = 'loue' THEN loyer_mensuel ELSE 0 END) as loyers_actifs
                    FROM biens
                    WHERE agence_id = :agence_id
                """)
                biens_stats = session.execute(biens_stats_query, {"agence_id": agence_id}).fetchone()
                
                context['total_biens'] = int(biens_stats[0] or 0)
                context['biens_loues'] = int(biens_stats[1] or 0)
                context['biens_disponibles'] = int(biens_stats[2] or 0)
                context['biens_maintenance'] = int(biens_stats[3] or 0)
                context['loyers_mensuels_actifs'] = float(biens_stats[5] or 0)
                
                # Get property types breakdown
                types_query = text("""
                    SELECT type, COUNT(*) as count
                    FROM biens
                    WHERE agence_id = :agence_id
                    GROUP BY type
                """)
                types_results = session.execute(types_query, {"agence_id": agence_id}).fetchall()
                context['types_biens'] = {t[0]: int(t[1]) for t in types_results}
                
                # Get total locataires actifs
                locataires_query = text("""
                    SELECT COUNT(DISTINCT l.id)
                    FROM locataires l
                    JOIN baux b ON b.locataire_id = l.id
                    JOIN biens bi ON bi.id = b.bien_id
                    WHERE bi.agence_id = :agence_id AND b.statut = 'actif'
                """)
                total_locataires = session.execute(locataires_query, {"agence_id": agence_id}).scalar()
                context['total_locataires'] = int(total_locataires or 0)
                
                # Get revenus du mois actuel
                revenus_mois_query = text("""
                    SELECT COALESCE(SUM(pl.montant), 0)
                    FROM paiements_loyer pl
                    JOIN baux b ON b.id = pl.bail_id
                    JOIN biens bi ON bi.id = b.bien_id
                    WHERE bi.agence_id = :agence_id
                    AND pl.statut = 'paye'
                    AND MONTH(pl.date_paiement) = MONTH(CURRENT_DATE())
                    AND YEAR(pl.date_paiement) = YEAR(CURRENT_DATE())
                """)
                revenus_mois = session.execute(revenus_mois_query, {"agence_id": agence_id}).scalar()
                context['revenus_mois'] = float(revenus_mois or 0)
                
                # Get revenus du mois précédent pour comparaison
                revenus_mois_precedent_query = text("""
                    SELECT COALESCE(SUM(pl.montant), 0)
                    FROM paiements_loyer pl
                    JOIN baux b ON b.id = pl.bail_id
                    JOIN biens bi ON bi.id = b.bien_id
                    WHERE bi.agence_id = :agence_id
                    AND pl.statut = 'paye'
                    AND MONTH(pl.date_paiement) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
                    AND YEAR(pl.date_paiement) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
                """)
                revenus_mois_precedent = session.execute(revenus_mois_precedent_query, {"agence_id": agence_id}).scalar()
                context['revenus_mois_precedent'] = float(revenus_mois_precedent or 0)
                
                # Get impayés détaillés
                impayes_query = text("""
                    SELECT 
                        COUNT(*) as nb_paiements,
                        COALESCE(SUM(montant), 0) as montant_total
                    FROM paiements_loyer pl
                    JOIN baux b ON b.id = pl.bail_id
                    JOIN biens bi ON bi.id = b.bien_id
                    WHERE bi.agence_id = :agence_id
                    AND pl.statut IN ('en_attente', 'en_retard')
                """)
                impayes_result = session.execute(impayes_query, {"agence_id": agence_id}).fetchone()
                context['nb_impayes'] = int(impayes_result[0] or 0)
                context['montant_impayes'] = float(impayes_result[1] or 0)
                
                # Get taux d'occupation
                if context['total_biens'] > 0:
                    context['taux_occupation'] = round((context['biens_loues'] / context['total_biens']) * 100, 1)
                else:
                    context['taux_occupation'] = 0.0
                
                return context
                
        except Exception as e:
            logger.error(f"Error getting user context: {str(e)}")
            return {}
    
    async def execute_natural_query(
        self,
        question: str,
        user_id: int,
        agence_id: Optional[int] = None
    ) -> str:
        """
        Execute a natural language query and return formatted response
        
        This handles simple SQL queries like counts, lists, etc.
        """
        try:
            question_lower = question.lower()
            
            # Liste des immeubles avec étages (plus spécifique, doit être avant le count)
            if 'immeuble' in question_lower and ('etage' in question_lower or 'étage' in question_lower):
                return await self._list_immeubles_with_floors(agence_id)
            
            # Combien d'immeubles ?
            if 'combien' in question_lower and 'immeuble' in question_lower:
                return await self._count_immeubles(agence_id)
            
            # Combien de biens ?
            if 'combien' in question_lower and 'bien' in question_lower:
                return await self._count_biens(agence_id)
            
            # Liste des locataires en retard
            if 'locataire' in question_lower and ('retard' in question_lower or 'impayé' in question_lower):
                return await self._list_unpaid_tenants(agence_id)
            
            # Revenus du mois
            if 'revenu' in question_lower and 'mois' in question_lower:
                return await self._get_monthly_revenue(agence_id)
            
            # Biens disponibles
            if 'bien' in question_lower and 'disponible' in question_lower:
                return await self._list_available_properties(agence_id)
            
            # Biens occupés/loués
            if 'bien' in question_lower and ('occupe' in question_lower or 'occupé' in question_lower or 'loue' in question_lower or 'loué' in question_lower):
                return await self._list_occupied_properties(agence_id)
            
            # Default response
            return "Je peux vous aider avec des questions comme : Combien de biens ai-je ? Combien d'immeubles ? Liste des locataires en retard, Revenus du mois, etc."
            
        except Exception as e:
            logger.error(f"Error executing natural query: {str(e)}")
            return f"Erreur lors de l'exécution de la requête: {str(e)}"
    
    async def _count_biens(self, agence_id: int) -> str:
        """Count total properties with detailed breakdown"""
        with self.SessionLocal() as session:
            # Get overall stats
            query = text("""
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN statut = 'loue' THEN 1 ELSE 0 END) as loues,
                    SUM(CASE WHEN statut = 'disponible' THEN 1 ELSE 0 END) as disponibles
                FROM biens
                WHERE agence_id = :agence_id
            """)
            result = session.execute(query, {"agence_id": agence_id}).fetchone()
            
            # Get breakdown by type
            type_query = text("""
                SELECT type, COUNT(*) as count
                FROM biens
                WHERE agence_id = :agence_id
                GROUP BY type
            """)
            types = session.execute(type_query, {"agence_id": agence_id}).fetchall()
            
            response = f"Vous gerez {result[0]} biens au total.\n\n"
            response += f"Statut:\n"
            response += f"- {result[1]} loues\n"
            response += f"- {result[2]} disponibles\n"
            
            if types:
                response += f"\nRepartition par type:\n"
                for t in types:
                    response += f"- {t[1]} {t[0]}\n"
            
            return response.strip()
    
    async def _count_immeubles(self, agence_id: int) -> str:
        """Count total buildings (immeubles)"""
        with self.SessionLocal() as session:
            query = text("""
                SELECT COUNT(*) as total
                FROM immeubles
                WHERE agence_id = :agence_id
            """)
            result = session.execute(query, {"agence_id": agence_id}).fetchone()
            
            total_immeubles = result[0] or 0
            
            if total_immeubles == 0:
                return "Vous ne gerez aucun immeuble actuellement."
            elif total_immeubles == 1:
                return "Vous gerez 1 immeuble."
            else:
                return f"Vous gerez {total_immeubles} immeubles."
    
    async def _list_immeubles_with_floors(self, agence_id: int) -> str:
        """List buildings with their floor counts"""
        with self.SessionLocal() as session:
            query = text("""
                SELECT nom, adresse, nombre_etages
                FROM immeubles
                WHERE agence_id = :agence_id
                ORDER BY nom
            """)
            results = session.execute(query, {"agence_id": agence_id}).fetchall()
            
            if not results:
                return "Vous ne gerez aucun immeuble actuellement."
            
            response = f"Vous gerez {len(results)} immeuble(s) :\n\n"
            for r in results:
                nom = r[0]
                adresse = r[1]
                nb_etages = r[2] or 0
                
                if nb_etages == 0:
                    response += f"- {nom} ({adresse}) : Aucun etage enregistre\n"
                elif nb_etages == 1:
                    response += f"- {nom} ({adresse}) : 1 etage\n"
                else:
                    response += f"- {nom} ({adresse}) : {nb_etages} etages\n"
            
            return response.strip()
    
    async def _list_unpaid_tenants(self, agence_id: int) -> str:
        """List tenants with unpaid rent"""
        with self.SessionLocal() as session:
            query = text("""
                SELECT DISTINCT u.nom, u.prenom, COUNT(pl.id) as nb_impayes
                FROM users u
                JOIN locataires l ON l.user_id = u.id
                JOIN baux b ON b.locataire_id = l.id
                JOIN biens bi ON bi.id = b.bien_id
                JOIN paiements_loyer pl ON pl.bail_id = b.id
                WHERE bi.agence_id = :agence_id
                AND pl.statut IN ('en_attente', 'en_retard')
                GROUP BY u.id, u.nom, u.prenom
                ORDER BY nb_impayes DESC
                LIMIT 10
            """)
            results = session.execute(query, {"agence_id": agence_id}).fetchall()
            
            if not results:
                return "Aucun locataire en retard de paiement."
            
            response = f"{len(results)} locataire(s) en retard :\n\n"
            for r in results:
                response += f"- {r[0]} {r[1]} : {r[2]} paiement(s) en retard\n"
            
            return response
    
    async def _get_monthly_revenue(self, agence_id: int) -> str:
        """Get current month revenue"""
        with self.SessionLocal() as session:
            query = text("""
                SELECT COALESCE(SUM(montant), 0)
                FROM paiements_loyer pl
                JOIN baux b ON b.id = pl.bail_id
                JOIN biens bi ON bi.id = b.bien_id
                WHERE bi.agence_id = :agence_id
                AND pl.statut = 'paye'
                AND MONTH(pl.date_paiement) = MONTH(CURRENT_DATE())
                AND YEAR(pl.date_paiement) = YEAR(CURRENT_DATE())
            """)
            revenue = session.execute(query, {"agence_id": agence_id}).scalar()
            
            return f"Revenus du mois en cours : {revenue:,.0f} FCFA"
    
    async def _list_available_properties(self, agence_id: int) -> str:
        """List available properties"""
        with self.SessionLocal() as session:
            query = text("""
                SELECT adresse, type, loyer_mensuel
                FROM biens
                WHERE agence_id = :agence_id
                AND statut = 'disponible'
                ORDER BY loyer_mensuel DESC
                LIMIT 10
            """)
            results = session.execute(query, {"agence_id": agence_id}).fetchall()
            
            if not results:
                return "Aucun bien disponible actuellement."
            
            response = f"{len(results)} bien(s) disponible(s) :\n\n"
            for r in results:
                response += f"- {r[0]} ({r[1]}) - {r[2]:,.0f} FCFA/mois\n"
            
            return response
    
    async def _list_occupied_properties(self, agence_id: int) -> str:
        """List occupied/rented properties"""
        with self.SessionLocal() as session:
            query = text("""
                SELECT b.adresse, b.type, b.loyer_mensuel, u.nom, u.prenom
                FROM biens b
                LEFT JOIN baux ba ON ba.bien_id = b.id AND ba.statut = 'actif'
                LEFT JOIN locataires l ON l.id = ba.locataire_id
                LEFT JOIN users u ON u.id = l.user_id
                WHERE b.agence_id = :agence_id
                AND b.statut = 'loue'
                ORDER BY b.loyer_mensuel DESC
                LIMIT 10
            """)
            results = session.execute(query, {"agence_id": agence_id}).fetchall()
            
            if not results:
                return "Aucun bien occupe actuellement."
            
            response = f"{len(results)} bien(s) occupe(s) :\n\n"
            for r in results:
                adresse = r[0]
                type_bien = r[1]
                loyer = r[2]
                nom = r[3]
                prenom = r[4]
                
                if nom and prenom:
                    response += f"- {adresse} ({type_bien}) - {loyer:,.0f} FCFA/mois - Locataire: {prenom} {nom}\n"
                else:
                    response += f"- {adresse} ({type_bien}) - {loyer:,.0f} FCFA/mois - Locataire: Non renseigne\n"
            
            return response.strip()
