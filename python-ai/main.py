from fastapi import FastAPI, HTTPException, Depends, Header
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Optional, List
import logging
import time

from config.settings import settings
from services.ai_service import AIService
from services.query_router import QueryRouter
from services.database_service import DatabaseService
from services.cache_service import CacheService

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# Initialize FastAPI
app = FastAPI(
    title="Noor Immo AI Assistant",
    description="Assistant IA intelligent pour la gestion immobilière",
    version="1.0.0",
    debug=settings.debug
)

# CORS Middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=settings.cors_origins,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Initialize services
ai_service = AIService()
query_router = QueryRouter()
db_service = DatabaseService()
cache_service = CacheService()

# Pydantic Models
class ChatRequest(BaseModel):
    message: str
    conversation_id: Optional[str] = None
    user_id: int
    agence_id: Optional[int] = None

class ChatResponse(BaseModel):
    response: str
    conversation_id: str
    sources: Optional[List[dict]] = None
    query_type: str  # "sql" or "ai"

class HealthResponse(BaseModel):
    status: str
    ollama_status: str
    database_status: str
    redis_status: str

# Dependency for auth
async def verify_token(authorization: str = Header(None)):
    """Verify JWT token from Laravel"""
    if not authorization or not authorization.startswith("Bearer "):
        raise HTTPException(status_code=401, detail="Missing or invalid authorization header")
    
    token = authorization.replace("Bearer ", "")
    # TODO: Implement JWT verification with Laravel's key
    return token

# Routes
@app.get("/", response_model=dict)
async def root():
    """Root endpoint"""
    return {
        "service": "Noor Immo AI Assistant",
        "version": "1.0.0",
        "status": "running"
    }

@app.get("/health", response_model=HealthResponse)
async def health_check():
    """Health check endpoint"""
    try:
        # Check OpenAI
        openai_status = "ok" if await ai_service.check_health() else "error"
        
        # Check Database
        db_status = "ok" if db_service.check_connection() else "error"
        
        # Check Redis
        redis_status = "ok" if cache_service.check_connection() else "error"
        
        overall_status = "healthy" if all([
            openai_status == "ok",
            db_status == "ok",
            redis_status == "ok"
        ]) else "degraded"
        
        return HealthResponse(
            status=overall_status,
            ollama_status=openai_status,  # Keep field name for compatibility
            database_status=db_status,
            redis_status=redis_status
        )
    except Exception as e:
        logger.error(f"Health check failed: {str(e)}")
        raise HTTPException(status_code=500, detail="Health check failed")

@app.post("/chat", response_model=ChatResponse)
async def chat(
    request: ChatRequest,
    token: str = Depends(verify_token)
):
    """
    Main chat endpoint
    Routes query to either SQL or AI based on complexity
    """
    try:
        logger.info(f"Chat request from user {request.user_id}: {request.message}")
        
        # Check cache first
        cached_response = cache_service.get_cached_response(
            request.message,
            request.user_id
        )
        if cached_response:
            logger.info("Returning cached response")
            return cached_response
        
        # Route query
        query_type = query_router.determine_query_type(request.message)
        logger.info(f"Query type determined: {query_type}")
        
        if query_type == "sql":
            # Simple SQL query
            response = await db_service.execute_natural_query(
                request.message,
                request.user_id,
                request.agence_id
            )
            sources = None
        else:
            # Complex AI query
            context = db_service.get_user_context(
                request.user_id,
                request.agence_id
            )
            response = await ai_service.generate_response(
                request.message,
                context
            )
            sources = context.get("sources", [])

        
        # Create response
        chat_response = ChatResponse(
            response=response,
            conversation_id=request.conversation_id or f"conv_{request.user_id}_{int(time.time())}",
            sources=sources,
            query_type=query_type
        )
        
        # Cache response
        cache_service.cache_response(
            request.message,
            request.user_id,
            chat_response
        )
        
        return chat_response
        
    except Exception as e:
        logger.error(f"Chat error: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Error processing chat: {str(e)}")

@app.get("/suggestions")
async def get_suggestions(
    user_id: int,
    agence_id: Optional[int] = None,
    token: str = Depends(verify_token)
):
    """Get suggested questions based on user context"""
    try:
        suggestions = query_router.get_contextual_suggestions(user_id, agence_id)
        return {"suggestions": suggestions}
    except Exception as e:
        logger.error(f"Suggestions error: {str(e)}")
        raise HTTPException(status_code=500, detail="Error getting suggestions")

if __name__ == "__main__":
    import uvicorn
    import time
    
    uvicorn.run(
        "main:app",
        host="0.0.0.0",
        port=settings.service_port,
        reload=settings.debug,
        log_level=settings.log_level.lower()
    )
