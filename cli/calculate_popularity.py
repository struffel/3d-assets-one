#!/usr/bin/env python3
"""CLI: Run database migrations."""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent.parent))

from dotenv import load_dotenv
load_dotenv()

from app.database.database import Database


def main() -> None:
    Database.migrate()
    print("Migration complete.")


if __name__ == "__main__":
    main()
