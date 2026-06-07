from pathlib import Path

from fastapi import APIRouter, Request
from fastapi.responses import HTMLResponse
from fastapi.templating import Jinja2Templates

from app.asset.asset_sorting import AssetSorting
from app.asset.asset_type import AssetType
from app.asset.stored_asset_query import StoredAssetQuery
from app.creator.creator import Creator
from app.creator.creator_license_type import CreatorLicenseType

router = APIRouter()
templates = Jinja2Templates(directory=str(Path(__file__).parent.parent.parent / "templates"))


@router.get("/", response_class=HTMLResponse)
async def index(request: Request) -> HTMLResponse:
    asset_count_by_creator = StoredAssetQuery.asset_count_by_creator()
    return templates.TemplateResponse(
        "index.html",
        {
            "request": request,
            "creators": list(Creator),
            "asset_types": list(AssetType),
            "sortings": [AssetSorting.POPULAR, AssetSorting.LATEST, AssetSorting.OLDEST, AssetSorting.RANDOM],
            "license_types": list(CreatorLicenseType),
            "asset_count_by_creator": asset_count_by_creator,
            "get": dict(request.query_params),
        },
    )


@router.get("/render/asset-list", response_class=HTMLResponse)
async def render_asset_list(request: Request) -> HTMLResponse:
    query = StoredAssetQuery.from_request(request.query_params)
    assets = query.execute()
    count_by_creator = query.execute_count_by_creator()

    show_welcome = (
        not query.filter_asset_id
        and query.filter_license_type.value == CreatorLicenseType.ANY_LICENSE.value
        and not query.filter_creator
        and not query.filter_type
        and query.offset == 0
        and not query.filter_tag
    )
    asset_count_total = StoredAssetQuery.asset_count_total() if show_welcome else 0

    from app.thumbnail.thumbnail_format import ThumbnailFormat

    response = templates.TemplateResponse(
        "partials/asset_list.html",
        {
            "request": request,
            "assets": assets,
            "query": query,
            "count_by_creator": count_by_creator,
            "creators": list(Creator),
            "show_welcome": show_welcome,
            "asset_count_total": asset_count_total,
            "thumbnail_format": ThumbnailFormat.JPG_256_FFFFFF,
            "query_string": str(request.url.query),
        },
    )
    response.headers["HX-Replace-Url"] = f"?{request.url.query}"
    return response
