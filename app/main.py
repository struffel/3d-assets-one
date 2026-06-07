from pathlib import Path

from fastapi import FastAPI
from fastapi.staticfiles import StaticFiles

from app.routes.index import router as index_router
from app.routes.go import router as go_router
from app.routes.about import router as about_router
from app.routes.api.assets import router as api_assets_router
from app.routes.api.creators import router as api_creators_router
from app.routes.api.types import router as api_types_router
from app.routes.api.rss import router as api_rss_router
from app.routes.admin.router import router as admin_router

app = FastAPI(title="3Dassets.one")

# Static files
public_dir = Path(__file__).parent.parent / "public"
app.mount("/thumbnail", StaticFiles(directory=str(public_dir / "thumbnail")), name="thumbnail")
app.mount("/static", StaticFiles(directory=str(public_dir / "static")), name="static")
app.mount("/css", StaticFiles(directory=str(public_dir / "css")), name="css")
app.mount("/js", StaticFiles(directory=str(public_dir / "js")), name="js")

# Routes
app.include_router(index_router)
app.include_router(go_router)
app.include_router(about_router)
app.include_router(api_assets_router, prefix="/api/v2")
app.include_router(api_creators_router, prefix="/api/v2")
app.include_router(api_types_router, prefix="/api/v2")
app.include_router(api_rss_router, prefix="/api/v2")
app.include_router(admin_router, prefix="/admin")
