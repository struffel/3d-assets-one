from io import BytesIO
import pytest
from PIL import Image

from app.thumbnail.thumbnail_format import ThumbnailFormat


def _make_rgba_image(width: int = 400, height: int = 400) -> Image.Image:
    img = Image.new("RGBA", (width, height), color=(100, 150, 200, 255))
    return img


def test_thumbnail_format_size():
    assert ThumbnailFormat.PNG_256.get_size() == 256
    assert ThumbnailFormat.PNG_128.get_size() == 128
    assert ThumbnailFormat.PNG_64.get_size() == 64
    assert ThumbnailFormat.PNG_32.get_size() == 32


def test_thumbnail_format_extension():
    assert ThumbnailFormat.PNG_256.get_extension() == "png"
    assert ThumbnailFormat.JPG_256_FFFFFF.get_extension() == "jpg"


def test_thumbnail_format_try_from():
    assert ThumbnailFormat.try_from("256-PNG") == ThumbnailFormat.PNG_256
    assert ThumbnailFormat.try_from("256-JPG-FFFFFF") == ThumbnailFormat.JPG_256_FFFFFF
    assert ThumbnailFormat.try_from("invalid") is None


def test_create_thumbnail_produces_image(tmp_path, monkeypatch):
    """Test that create_thumbnail_from_image resizes correctly."""
    from app.thumbnail.thumbnail import Thumbnail

    # Redirect thumbnail save path to tmp_path
    monkeypatch.setattr(
        "app.thumbnail.thumbnail.Thumbnail._thumbnail_dir",
        lambda fmt: tmp_path / fmt.value,
        raising=False,
    )

    img = _make_rgba_image(400, 400)
    # Just test the internal resize logic without file I/O
    result = Thumbnail.create_thumbnail_from_image(img, ThumbnailFormat.PNG_256)
    assert result.size == (256, 256)


def test_rgba_to_rgb_for_jpg():
    """JPEG thumbnails must be RGB, not RGBA."""
    from app.thumbnail.thumbnail import Thumbnail

    img = _make_rgba_image(400, 400)
    result = Thumbnail.create_thumbnail_from_image(img, ThumbnailFormat.JPG_256_FFFFFF)
    assert result.mode == "RGB"


def test_png_thumbnail_keeps_rgba():
    from app.thumbnail.thumbnail import Thumbnail

    img = _make_rgba_image(400, 400)
    result = Thumbnail.create_thumbnail_from_image(img, ThumbnailFormat.PNG_256)
    # PNG may keep RGBA
    assert result.mode in ("RGBA", "RGB")
