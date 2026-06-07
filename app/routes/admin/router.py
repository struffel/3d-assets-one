from pathlib import Path

from fastapi import APIRouter, Request
from fastapi.responses import HTMLResponse
from fastapi.templating import Jinja2Templates

from app.asset.stored_asset_query import StoredAssetQuery
from app.asset.stored_asset_status import StoredAssetStatus
from app.creator.creator import Creator
from app.misc.auth import require_auth

router = APIRouter()
templates = Jinja2Templates(directory=str(Path(__file__).parent.parent.parent.parent / "templates"))


@router.get("", response_class=HTMLResponse)
@router.get("/", response_class=HTMLResponse)
async def admin_index(request: Request) -> HTMLResponse:
    require_auth(request)
    return templates.TemplateResponse("admin/index.html", {"request": request})


@router.get("/editor", response_class=HTMLResponse)
async def admin_editor(request: Request) -> HTMLResponse:
    require_auth(request)
    query = StoredAssetQuery.from_request(request.query_params, filter_status=None)
    assets = query.execute()
    return templates.TemplateResponse(
        "admin/editor.html",
        {
            "request": request,
            "assets": assets,
            "query": query,
            "creators": list(Creator),
            "statuses": list(StoredAssetStatus),
            "sortings": list(AssetSorting),
            "get": dict(request.query_params),
        },
    )


@router.get("/availability", response_class=HTMLResponse)
async def admin_availability(request: Request) -> HTMLResponse:
    require_auth(request)
    from app.database.database import Database

    rows = Database.run_query(
        "SELECT creatorId, lastChecked, lastAvailable, failedAttempts FROM CreatorAvailability ORDER BY creatorId"
    ).fetchall()
    availability = [
        {
            "creator": Creator(row[0]),
            "last_checked": row[1],
            "last_available": row[2],
            "failed_attempts": row[3],
        }
        for row in rows
    ]
    return templates.TemplateResponse(
        "admin/availability.html",
        {"request": request, "availability": availability, "creators": list(Creator)},
    )


@router.get("/logs", response_class=HTMLResponse)
async def admin_logs(request: Request) -> HTMLResponse:
    require_auth(request)
    log_dir = Path(__file__).parent.parent.parent.parent.parent / "data" / "log"
    log_files: list[dict] = []
    if log_dir.exists():
        for f in sorted(log_dir.rglob("*.log"), reverse=True)[:100]:
            log_files.append(
                {
                    "name": str(f.relative_to(log_dir)),
                    "size": f.stat().st_size,
                    "success": f.suffix == ".ok.log" if f.name.endswith(".ok.log") else (False if f.name.endswith(".err.log") else None),
                }
            )
    return templates.TemplateResponse(
        "admin/logs.html",
        {"request": request, "log_files": log_files},
    )
