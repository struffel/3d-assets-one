from pathlib import Path

from fastapi import APIRouter, Request
from fastapi.responses import HTMLResponse
from fastapi.templating import Jinja2Templates

from app.asset.asset_sorting import AssetSorting
from app.creator.creator import Creator

router = APIRouter()
templates = Jinja2Templates(directory=str(Path(__file__).parent.parent.parent / "templates"))


@router.get("/about-site", response_class=HTMLResponse)
async def about_site(request: Request) -> HTMLResponse:
    return templates.TemplateResponse(
        "about_site.html",
        {
            "request": request,
            "sortings": list(AssetSorting),
        },
    )


@router.get("/about-creators", response_class=HTMLResponse)
async def about_creators(request: Request) -> HTMLResponse:
    return templates.TemplateResponse(
        "about_creators.html",
        {
            "request": request,
            "creators": list(Creator),
        },
    )
