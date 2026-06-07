"""Pytest configuration and shared fixtures."""
import pytest
from pathlib import Path


@pytest.fixture(autouse=True)
def load_env():
    """Load .env if present."""
    from dotenv import load_dotenv
    env_path = Path(__file__).parent.parent / ".env"
    load_dotenv(dotenv_path=env_path, override=False)
