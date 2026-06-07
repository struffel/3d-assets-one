from __future__ import annotations

import io
import json
from dataclasses import dataclass
from typing import Optional, TYPE_CHECKING
from xml.etree import ElementTree

from bs4 import BeautifulSoup
from PIL import Image

if TYPE_CHECKING:
    from app.fetch.web_item_reference import WebItemReference


@dataclass
class FetchedWebItem:
    reference: "WebItemReference"
    content: Optional[bytes]
    http_status_code: Optional[int]

    def parse_as_json(self) -> Optional[dict | list]:
        if self.content is None:
            return None
        try:
            return json.loads(self.content.decode("utf-8"))
        except (json.JSONDecodeError, UnicodeDecodeError):
            return None

    def parse_as_sitemap(self, filter_newer_than=None) -> Optional[list["WebItemReference"]]:
        from app.fetch.web_item_reference import WebItemReference
        from datetime import datetime, timezone

        if self.content is None:
            return None
        try:
            root = ElementTree.fromstring(self.content)
            ns = {"sm": "http://www.sitemaps.org/schemas/sitemap/0.9"}
            urls: list[WebItemReference] = []
            for url_el in root.findall("sm:url", ns) or root.findall("url"):
                loc_el = url_el.find("sm:loc", ns) or url_el.find("loc")
                if loc_el is None or loc_el.text is None:
                    continue
                loc = loc_el.text.strip()

                if filter_newer_than is not None:
                    lastmod_el = url_el.find("sm:lastmod", ns) or url_el.find("lastmod")
                    if lastmod_el is not None and lastmod_el.text:
                        try:
                            lastmod = datetime.fromisoformat(lastmod_el.text.strip())
                            if lastmod.tzinfo is None:
                                lastmod = lastmod.replace(tzinfo=timezone.utc)
                            if filter_newer_than.tzinfo is None:
                                filter_newer_than = filter_newer_than.replace(tzinfo=timezone.utc)
                            if lastmod < filter_newer_than:
                                continue
                        except ValueError:
                            pass

                urls.append(WebItemReference(url=loc))
            return urls
        except ElementTree.ParseError:
            return None

    def parse_as_xml_element(self) -> Optional[ElementTree.Element]:
        if self.content is None:
            return None
        try:
            return ElementTree.fromstring(self.content)
        except ElementTree.ParseError:
            return None

    def parse_as_comma_separated_list(self) -> list[str]:
        if self.content is None:
            return []
        text = self.content.decode("utf-8", errors="replace").replace("\n", "").replace("\r", "")
        parts = [p.strip() for p in text.split(",") if p.strip()]
        return parts

    def parse_as_dom(self) -> Optional[BeautifulSoup]:
        if self.content is None:
            return None
        return BeautifulSoup(self.content, "lxml")

    def parse_html_meta_tags(self) -> Optional[dict[str, str]]:
        soup = self.parse_as_dom()
        if soup is None:
            return None
        output: dict[str, str] = {}
        for tag in soup.find_all("meta"):
            name = tag.get("name") or tag.get("property") or ""
            content = tag.get("content", "")
            if name:
                output[name] = content
        return output

    def parse_as_image(self) -> Optional[Image.Image]:
        if self.content is None:
            return None
        try:
            img = Image.open(io.BytesIO(self.content))
            img.load()  # Force decode
            return img.convert("RGBA")
        except Exception:
            return None
