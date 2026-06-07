from pathlib import Path
from typing import Optional

from PIL import Image, ImageDraw

from app.log.log import Log
from app.log.log_level import LogLevel
from app.thumbnail.thumbnail_format import ThumbnailFormat


class Thumbnail:
    @staticmethod
    def _get_store_path() -> Path:
        return Path(__file__).parent.parent.parent / "public" / "thumbnail"

    @classmethod
    def delete_orphaned_thumbnails(cls) -> None:
        from app.database.database import Database

        store_path = cls._get_store_path()
        if not store_path.is_dir():
            return

        result = Database.run_query("SELECT id FROM Asset")
        existing_ids = {row[0] for row in result.fetchall()}

        for variation_dir in store_path.iterdir():
            if not variation_dir.is_dir():
                continue
            for file in variation_dir.iterdir():
                try:
                    asset_id = int(file.stem)
                    if asset_id not in existing_ids:
                        file.unlink()
                        Log.write("Deleted orphaned thumbnail", str(file), LogLevel.DEBUG)
                except (ValueError, OSError):
                    pass

    @classmethod
    def save_thumbnail_variations(cls, asset_id: int, original_image: Image.Image) -> None:
        cls._validate_image(original_image)

        for fmt in ThumbnailFormat:
            thumb = cls.create_thumbnail_from_image(original_image, fmt)
            ext = fmt.get_extension().lower()
            file_path = cls._get_store_path() / fmt.value / f"{asset_id}.{ext}"
            file_path.parent.mkdir(parents=True, exist_ok=True)

            if fmt.get_extension() == "JPG":
                # Convert RGBA to RGB with background fill before saving as JPEG
                rgb_img = Image.new("RGB", thumb.size, (255, 255, 255))
                if thumb.mode == "RGBA":
                    rgb_img.paste(thumb, mask=thumb.split()[3])
                else:
                    rgb_img.paste(thumb)
                rgb_img.save(str(file_path), "JPEG", quality=95)
            else:
                thumb.save(str(file_path), "PNG", compress_level=6)

            cls._validate_image_file(file_path)
            Log.write("Saved thumbnail", {"asset_id": asset_id, "file": str(file_path)}, LogLevel.DEBUG)

    @classmethod
    def _validate_image_file(cls, file_path: Path) -> None:
        if not file_path.exists():
            raise RuntimeError(f"Thumbnail file does not exist: {file_path}")
        if file_path.stat().st_size == 0:
            raise RuntimeError(f"Thumbnail file is empty: {file_path}")
        try:
            img = Image.open(str(file_path))
            img.verify()
        except Exception as e:
            raise RuntimeError(f"Thumbnail file is invalid: {file_path}: {e}") from e
        # Re-open after verify (verify closes the image)
        img = Image.open(str(file_path)).convert("RGBA")
        cls._validate_image(img)

    @staticmethod
    def _validate_image(image: Image.Image) -> None:
        img = image.convert("RGBA")
        width, height = img.size
        check_interval = 4
        first_pixel = img.getpixel((0, 0))
        all_same = True
        for x in range(0, width, check_interval):
            for y in range(0, height, check_interval):
                if img.getpixel((x, y)) != first_pixel:
                    all_same = False
                    break
            if not all_same:
                break
        if all_same:
            raise RuntimeError("Thumbnail image is uniformly colored and likely invalid")

    @staticmethod
    def create_thumbnail_from_image(raw_image: Image.Image, fmt: ThumbnailFormat) -> Image.Image:
        size = fmt.get_size()
        orig_w, orig_h = raw_image.size

        ratio = min(size / orig_w, size / orig_h)
        new_w = int(orig_w * ratio)
        new_h = int(orig_h * ratio)

        offset_x = (size - new_w) // 2
        offset_y = (size - new_h) // 2

        bg_hex = fmt.get_background_color_hex()
        if bg_hex:
            r = int(bg_hex[0:2], 16)
            g = int(bg_hex[2:4], 16)
            b = int(bg_hex[4:6], 16)
            output = Image.new("RGB", (size, size), (r, g, b))
        else:
            output = Image.new("RGBA", (size, size), (0, 0, 0, 0))

        resized = raw_image.resize((new_w, new_h), Image.LANCZOS)
        if bg_hex:
            if resized.mode == "RGBA":
                output.paste(resized.convert("RGB"), (offset_x, offset_y))
            else:
                output.paste(resized, (offset_x, offset_y))
        else:
            if resized.mode != "RGBA":
                resized = resized.convert("RGBA")
            output.paste(resized, (offset_x, offset_y), resized)

        return output
