from pathlib import Path
from shutil import copyfile

from PIL import Image, ImageDraw, ImageFilter


ROOT = Path(__file__).resolve().parents[1]
ASSET_DIR = ROOT / "public" / "templates" / "welding-school" / "assets" / "images"
SOURCE_DIR = ASSET_DIR / "source"

ORIGINAL_LOGO = Path(
    "C:/Users/ilono/Downloads/WhatsApp Image 2026-08-03 at 08.58.47 (1).jpeg"
)
ORIGINAL_WORDMARK = Path(
    "C:/Users/ilono/Downloads/WhatsApp Image 2026-08-03 at 08.58.47.jpeg"
)

SOURCE_LOGO = SOURCE_DIR / "alpha-teknik-pratama-logo-original.jpeg"
SOURCE_WORDMARK = SOURCE_DIR / "alpha-teknik-pratama-wordmark-original.jpeg"
LOGO_TRANSPARENT_HD = ASSET_DIR / "alpha-teknik-pratama-logo-hd.png"
LOGO_WHITE_HD = ASSET_DIR / "alpha-teknik-pratama-logo-hd-white.png"
WORDMARK_HD = ASSET_DIR / "alpha-teknik-pratama-brand-hd.png"
FAVICON = ROOT / "public" / "favicon.ico"


def lightly_sharpen(image):
    """Sharpen existing pixels only; no reconstruction or generated detail."""
    if image.mode == "RGBA":
        red, green, blue, alpha = image.split()
        rgb = Image.merge("RGB", (red, green, blue)).filter(
            ImageFilter.UnsharpMask(radius=1.0, percent=35, threshold=4)
        )
        red, green, blue = rgb.split()
        return Image.merge("RGBA", (red, green, blue, alpha))

    return image.filter(ImageFilter.UnsharpMask(radius=1.0, percent=35, threshold=4))


def extract_connected_white_background(image):
    """Remove only the near-white area connected to the outer canvas."""
    work = image.convert("RGB")
    marker = (255, 0, 255)
    for corner in (
        (0, 0),
        (work.width - 1, 0),
        (0, work.height - 1),
        (work.width - 1, work.height - 1),
    ):
        if work.getpixel(corner) != marker:
            ImageDraw.floodfill(work, corner, marker, thresh=42)

    alpha = Image.new("L", work.size, 255)
    alpha_pixels = alpha.load()
    work_pixels = work.load()
    for y in range(work.height):
        for x in range(work.width):
            if work_pixels[x, y] == marker:
                alpha_pixels[x, y] = 0

    rgba = image.convert("RGBA")
    rgba.putalpha(alpha)
    bounds = alpha.getbbox()
    if not bounds:
        raise RuntimeError("Logo tidak terdeteksi setelah pemisahan latar.")
    return rgba.crop(bounds)


def fit_on_transparent_square(image, size=2048, padding=72):
    available = size - (padding * 2)
    ratio = min(available / image.width, available / image.height)
    dimensions = (
        max(1, round(image.width * ratio)),
        max(1, round(image.height * ratio)),
    )
    resized = image.resize(dimensions, Image.Resampling.LANCZOS)
    resized = lightly_sharpen(resized)
    canvas = Image.new("RGBA", (size, size), (255, 255, 255, 0))
    position = ((size - dimensions[0]) // 2, (size - dimensions[1]) // 2)
    canvas.alpha_composite(resized, position)
    return canvas


def upscale_exact(image, factor=4):
    dimensions = (image.width * factor, image.height * factor)
    resized = image.resize(dimensions, Image.Resampling.LANCZOS)
    return lightly_sharpen(resized)


def main():
    if not ORIGINAL_LOGO.exists() or not ORIGINAL_WORDMARK.exists():
        raise FileNotFoundError("File WhatsApp asli tidak ditemukan di folder Downloads.")

    SOURCE_DIR.mkdir(parents=True, exist_ok=True)
    copyfile(ORIGINAL_LOGO, SOURCE_LOGO)
    copyfile(ORIGINAL_WORDMARK, SOURCE_WORDMARK)

    logo = Image.open(SOURCE_LOGO).convert("RGB")
    wordmark = Image.open(SOURCE_WORDMARK).convert("RGB")

    extracted_logo = extract_connected_white_background(logo)
    transparent_logo = fit_on_transparent_square(extracted_logo)
    transparent_logo.save(LOGO_TRANSPARENT_HD, optimize=True)

    white_logo = upscale_exact(logo)
    white_logo.save(LOGO_WHITE_HD, optimize=True)

    enhanced_wordmark = upscale_exact(wordmark)
    enhanced_wordmark.save(WORDMARK_HD, optimize=True)

    favicon_source = transparent_logo.resize((256, 256), Image.Resampling.LANCZOS)
    favicon_source.save(
        FAVICON,
        sizes=[(16, 16), (32, 32), (48, 48), (64, 64), (128, 128), (256, 256)],
    )

    print(f"Original logo: {logo.size} -> white HD: {white_logo.size}")
    print(f"App logo: {transparent_logo.size}")
    print(f"Original wordmark: {wordmark.size} -> HD: {enhanced_wordmark.size}")


if __name__ == "__main__":
    main()
