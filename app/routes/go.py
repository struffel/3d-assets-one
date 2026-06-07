from fastapi import APIRouter, Request
from fastapi.responses import RedirectResponse, PlainTextResponse

from app.asset.stored_asset_query import StoredAssetQuery
from app.database.database import Database

router = APIRouter()


@router.get("/go")
async def go(request: Request) -> RedirectResponse | PlainTextResponse:
    asset_id_str = request.query_params.get("id", "0")
    try:
        asset_id = int(asset_id_str)
    except ValueError:
        asset_id = 0

    query = StoredAssetQuery(offset=0, limit=1, filter_asset_id=[asset_id])
    result = query.execute()
    asset = result[0] if len(result) > 0 else None

    if asset and asset.url:
        Database.add_asset_click_by_id(asset_id)
        return RedirectResponse(url=asset.url, status_code=302)
    else:
        return PlainTextResponse("3Dassets.one\nURL could not be resolved.", status_code=404)
