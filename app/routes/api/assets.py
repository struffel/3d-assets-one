from fastapi import APIRouter, Request
from fastapi.responses import JSONResponse

from app.asset.stored_asset_query import StoredAssetQuery
from app.thumbnail.thumbnail_format import ThumbnailFormat

router = APIRouter()


@router.get("/assets")
async def get_assets(request: Request) -> JSONResponse:
    query = StoredAssetQuery.from_request(request.query_params)
    assets = query.execute()

    thumbnail_format = ThumbnailFormat.try_from(
        request.query_params.get("thumbnailFormat", "")
    ) or ThumbnailFormat.PNG_256

    base_url = str(request.base_url).rstrip("/")
    output = [asset.api_representation(thumbnail_format, base_url=base_url) for asset in assets]
    return JSONResponse(content=output)
