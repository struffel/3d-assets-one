from fastapi import APIRouter
from fastapi.responses import JSONResponse

from app.creator.creator import Creator

router = APIRouter()


@router.get("/creators")
async def get_creators() -> JSONResponse:
    output = [
        {
            "id": c.value,
            "slug": c.slug(),
            "name": c.title(),
            "licenseUrl": c.license_url(),
            "description": c.description(),
        }
        for c in Creator
    ]
    return JSONResponse(content=output)
