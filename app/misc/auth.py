import os
import secrets
from typing import Optional

from fastapi import HTTPException, Request
from fastapi.security import HTTPBasic, HTTPBasicCredentials


def require_auth(request: Request) -> None:
    admin_token = os.environ.get("3D1_ADMIN_TOKEN", "")
    import base64

    auth_header = request.headers.get("Authorization", "")
    if not auth_header.startswith("Basic "):
        raise HTTPException(
            status_code=401,
            detail="Unauthorized",
            headers={"WWW-Authenticate": 'Basic realm="Access denied"'},
        )
    try:
        decoded = base64.b64decode(auth_header[6:]).decode("utf-8")
        username, _, password = decoded.partition(":")
    except Exception:
        raise HTTPException(
            status_code=401,
            detail="Unauthorized",
            headers={"WWW-Authenticate": 'Basic realm="Access denied"'},
        )

    valid_user = secrets.compare_digest(username, "admin")
    valid_pass = secrets.compare_digest(password, admin_token)
    if not (valid_user and valid_pass):
        raise HTTPException(
            status_code=401,
            detail="Unauthorized",
            headers={"WWW-Authenticate": 'Basic realm="Access denied"'},
        )
