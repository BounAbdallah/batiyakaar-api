from pydantic_settings import BaseSettings
from typing import Optional
import os

class Settings(BaseSettings):
    # AI Configuration (OpenAI or Gemini)
    openai_api_key: str = os.getenv("OPENAI_API_KEY", "")
    openai_model: str = "gpt-3.5-turbo"
    
    # Google Gemini (Free alternative)
    gemini_api_key: str = os.getenv("GEMINI_API_KEY", "")
    gemini_model: str = "gemini-2.0-flash"  # Free and fast model
    
    # Groq (Fast and generous free tier)
    groq_api_key: str = os.getenv("GROQ_API_KEY", "")
    groq_model: str = "llama-3.3-70b-versatile"  # Latest active model
    
    # AI Provider: "openai", "gemini", or "groq"
    ai_provider: str = os.getenv("AI_PROVIDER", "groq")
    
    # MySQL (from Laravel .env)
    db_host: str = os.getenv("DB_HOST", "localhost")
    db_port: int = int(os.getenv("DB_PORT", "3306"))
    db_database: str = os.getenv("DB_DATABASE", "batiyakaar")
    db_username: str = os.getenv("DB_USERNAME", "root")
    db_password: str = os.getenv("DB_PASSWORD", "")
    
    # Redis
    redis_host: str = os.getenv("REDIS_HOST", "localhost")
    redis_port: int = int(os.getenv("REDIS_PORT", "6379"))
    redis_db: int = 0
    
    # Laravel API
    laravel_api_url: str = "http://localhost:8000/api/v1"
    
    # JWT (from Laravel)
    jwt_secret: str = os.getenv("APP_KEY", "base64:your-secret-key-here")
    jwt_algorithm: str = "HS256"
    
    # Service
    service_port: int = 8001
    debug: bool = os.getenv("APP_DEBUG", "true").lower() == "true"
    log_level: str = "INFO"
    
    # CORS
    cors_origins: list = ["http://localhost:5173", "http://localhost:8000"]
    
    class Config:
        env_file = "../.env"  # Utilise le .env de Laravel
        case_sensitive = False
        extra = 'ignore'  # Ignore les variables non définies

settings = Settings()
