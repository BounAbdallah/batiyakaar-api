import redis
import json
import logging
from typing import Optional, Any
from datetime import timedelta

from config.settings import settings

logger = logging.getLogger(__name__)

class CacheService:
    """Service for caching responses using Redis"""
    
    def __init__(self):
        try:
            self.redis_client = redis.Redis(
                host=settings.redis_host,
                port=settings.redis_port,
                db=settings.redis_db,
                decode_responses=True
            )
            logger.info("Redis cache service initialized")
        except Exception as e:
            logger.warning(f"Redis connection failed: {str(e)}. Caching disabled.")
            self.redis_client = None
    
    def check_connection(self) -> bool:
        """Check if Redis is connected"""
        if not self.redis_client:
            return False
        try:
            self.redis_client.ping()
            return True
        except Exception as e:
            logger.error(f"Redis connection check failed: {str(e)}")
            return False
    
    def _generate_cache_key(self, question: str, user_id: int) -> str:
        """Generate cache key from question and user"""
        # Normalize question (lowercase, strip)
        normalized = question.lower().strip()
        return f"chat:{user_id}:{hash(normalized)}"
    
    def get_cached_response(
        self,
        question: str,
        user_id: int
    ) -> Optional[dict]:
        """
        Get cached response for a question
        
        Returns:
            Cached response dict or None
        """
        if not self.redis_client:
            return None
        
        try:
            cache_key = self._generate_cache_key(question, user_id)
            cached = self.redis_client.get(cache_key)
            
            if cached:
                logger.info(f"Cache hit for user {user_id}")
                return json.loads(cached)
            
            return None
            
        except Exception as e:
            logger.error(f"Error getting cached response: {str(e)}")
            return None
    
    def cache_response(
        self,
        question: str,
        user_id: int,
        response: Any,
        ttl_seconds: int = 3600  # 1 hour default
    ) -> bool:
        """
        Cache a response
        
        Args:
            question: User's question
            user_id: User ID
            response: Response to cache
            ttl_seconds: Time to live in seconds
        
        Returns:
            True if cached successfully
        """
        if not self.redis_client:
            return False
        
        try:
            cache_key = self._generate_cache_key(question, user_id)
            
            # Convert response to JSON
            if hasattr(response, 'dict'):
                response_json = json.dumps(response.dict())
            else:
                response_json = json.dumps(response)
            
            # Set with expiration
            self.redis_client.setex(
                cache_key,
                timedelta(seconds=ttl_seconds),
                response_json
            )
            
            logger.info(f"Cached response for user {user_id} (TTL: {ttl_seconds}s)")
            return True
            
        except Exception as e:
            logger.error(f"Error caching response: {str(e)}")
            return False
    
    def invalidate_user_cache(self, user_id: int) -> int:
        """
        Invalidate all cache for a user
        
        Returns:
            Number of keys deleted
        """
        if not self.redis_client:
            return 0
        
        try:
            pattern = f"chat:{user_id}:*"
            keys = self.redis_client.keys(pattern)
            
            if keys:
                deleted = self.redis_client.delete(*keys)
                logger.info(f"Invalidated {deleted} cache entries for user {user_id}")
                return deleted
            
            return 0
            
        except Exception as e:
            logger.error(f"Error invalidating cache: {str(e)}")
            return 0
    
    def get_stats(self) -> dict:
        """Get cache statistics"""
        if not self.redis_client:
            return {"status": "disabled"}
        
        try:
            info = self.redis_client.info()
            return {
                "status": "active",
                "used_memory": info.get("used_memory_human"),
                "total_keys": self.redis_client.dbsize(),
                "hits": info.get("keyspace_hits", 0),
                "misses": info.get("keyspace_misses", 0)
            }
        except Exception as e:
            logger.error(f"Error getting cache stats: {str(e)}")
            return {"status": "error", "error": str(e)}
