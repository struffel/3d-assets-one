from __future__ import annotations

from dataclasses import dataclass, field
from typing import Optional

import httpx

from app.log.log import Log
from app.log.log_level import LogLevel


@dataclass
class WebItemReference:
    url: str
    method: str = "GET"
    headers: dict[str, str] = field(default_factory=dict)
    request_body: str = ""
    query_parameters: dict[str, str] = field(default_factory=dict)

    DEFAULT_HEADERS: dict[str, str] = field(
        default_factory=lambda: {"User-Agent": "3dassets.one / Fetching"},
        init=False,
        repr=False,
    )

    def __post_init__(self) -> None:
        default_hdrs = {"User-Agent": "3dassets.one / Fetching"}
        self.headers = {**default_hdrs, **self.headers}

    def fetch(self) -> "FetchedWebItem":
        from app.fetch.fetched_web_item import FetchedWebItem

        Log.write("Fetching", {"url": self.url, "method": self.method}, LogLevel.INFO)
        try:
            kwargs: dict = {
                "headers": self.headers,
                "follow_redirects": True,
                "timeout": 30.0,
            }
            if self.request_body:
                kwargs["content"] = self.request_body.encode()
            elif self.query_parameters:
                kwargs["data"] = self.query_parameters

            with httpx.Client() as client:
                response = client.request(self.method, self.url, **kwargs)
            content: Optional[bytes] = response.content
            status_code: Optional[int] = response.status_code
            Log.write("Request completed", {"status": status_code, "length": len(content or b"")}, LogLevel.INFO)
        except httpx.HTTPStatusError as e:
            Log.write("HTTP error", {"code": e.response.status_code, "message": str(e)}, LogLevel.ERROR)
            content = None
            status_code = e.response.status_code
        except Exception as e:
            Log.write("Request error", str(e), LogLevel.ERROR)
            content = None
            status_code = None

        return FetchedWebItem(reference=self, content=content, http_status_code=status_code)

    def fetch_cookie(self, target_cookie_name: str) -> Optional[str]:
        Log.write("Fetching cookie", {"target": target_cookie_name, "url": self.url}, LogLevel.INFO)
        try:
            kwargs: dict = {
                "headers": self.headers,
                "follow_redirects": True,
                "timeout": 30.0,
            }
            with httpx.Client() as client:
                client.request(self.method, self.url, **kwargs)
                cookie_value = client.cookies.get(target_cookie_name)
            Log.write("Cookie fetched", {"cookie": cookie_value}, LogLevel.DEBUG)
            return cookie_value
        except Exception as e:
            Log.write("Cookie request error", str(e), LogLevel.ERROR)
            return None
