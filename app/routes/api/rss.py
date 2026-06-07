from datetime import timezone

from fastapi import APIRouter, Request
from fastapi.responses import Response

from app.asset.asset_sorting import AssetSorting
from app.asset.stored_asset_query import StoredAssetQuery
from app.thumbnail.thumbnail_format import ThumbnailFormat

router = APIRouter()


@router.get("/assets-rss")
async def get_assets_rss(request: Request) -> Response:
    query = StoredAssetQuery.from_request(request.query_params)
    query.sort = AssetSorting.LATEST
    assets = query.execute()

    base_url = str(request.base_url).rstrip("/")
    host = request.headers.get("host", "3Dassets.one")

    items_xml = ""
    for asset in assets:
        pub_date = asset.date.astimezone(timezone.utc).strftime("%a, %d %b %Y %H:%M:%S +0000")
        thumbnail_url = asset.get_thumbnail_url(ThumbnailFormat.JPG_256_FFFFFF, full_url=True, base_url=base_url)
        tags_str = ",".join(asset.tags)
        items_xml += f"""
        <item>
            <title><![CDATA[{asset.title}]]></title>
            <media:thumbnail url="{thumbnail_url}" height="256" width="256" />
            <description><![CDATA[{asset.title} by {asset.creator.title()} / Type: {asset.type.label()} / Tags: {tags_str}]]></description>
            <link>https://{host}/go?id={asset.id}</link>
            <guid isPermaLink="false">3D1-{asset.id}</guid>
            <pubDate>{pub_date}</pubDate>
        </item>"""

    rss_content = f"""<?xml version="1.0" encoding="UTF-8" ?>
<rss xmlns:media="http://search.yahoo.com/mrss/" version="2.0">
    <channel>
        <title>3Dassets.one Auto-Generated Asset Feed</title>
        <link>https://3Dassets.one</link>
        <description>RSS feed containing all newly released 3D models, materials, HDRIs and other resources from creators tracked by 3Dassets.one.</description>
        {items_xml}
    </channel>
</rss>"""

    return Response(content=rss_content, media_type="application/rss+xml")
