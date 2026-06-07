from fastapi import APIRouter
from fastapi.responses import JSONResponse

from app.asset.asset_type import AssetType

router = APIRouter()


@router.get("/types")
async def get_types() -> JSONResponse:
    output = [{"id": t.value, "slug": t.slug(), "name": t.label()} for t in AssetType]
    return JSONResponse(content=output)
