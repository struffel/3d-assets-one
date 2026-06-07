package thumbnail

import (
	"bytes"
	"context"
	"fmt"
	"image"
	"image/color"
	"image/draw"
	"image/jpeg"
	"image/png"
	"strconv"

	"github.com/disintegration/imaging"
	"github.com/struffel/3d-assets-one/internal/model"
	"github.com/struffel/3d-assets-one/internal/storage"
)

// ThumbnailProcessor handles thumbnail generation and upload.
type ThumbnailProcessor struct {
	store storage.ObjectStorage
}

// NewThumbnailProcessor creates a thumbnail processor backed by the given storage.
func NewThumbnailProcessor(store storage.ObjectStorage) *ThumbnailProcessor {
	return &ThumbnailProcessor{store: store}
}

// SaveVariations generates all 8 thumbnail variants and uploads them.
func (p *ThumbnailProcessor) SaveVariations(ctx context.Context, assetID int64, src image.Image) error {
	if err := ValidateImage(src); err != nil {
		return fmt.Errorf("source image invalid: %w", err)
	}

	for _, f := range model.AllThumbnailFormats() {
		thumb := createVariation(src, f)

		data, err := encode(thumb, f)
		if err != nil {
			return fmt.Errorf("encode %s: %w", f.Value(), err)
		}

		remotePath := "thumbnail/" + f.Value() + "/" + strconv.FormatInt(assetID, 10) + "." + f.Extension()
		ct := "image/png"
		if f.Extension() == "jpg" {
			ct = "image/jpeg"
		}

		if err := p.store.Upload(ctx, remotePath, data, ct); err != nil {
			return fmt.Errorf("upload %s: %w", f.Value(), err)
		}
	}
	return nil
}

// DeleteVariations removes all 8 thumbnail variants for an asset.
func (p *ThumbnailProcessor) DeleteVariations(ctx context.Context, assetID int64) {
	for _, f := range model.AllThumbnailFormats() {
		remotePath := "thumbnail/" + f.Value() + "/" + strconv.FormatInt(assetID, 10) + "." + f.Extension()
		p.store.Delete(ctx, remotePath) // best-effort
	}
}

// ValidateImage checks that the image isn't uniformly colored.
func ValidateImage(img image.Image) error {
	bounds := img.Bounds()
	w := bounds.Dx()
	h := bounds.Dy()
	if w == 0 || h == 0 {
		return fmt.Errorf("image has zero dimensions")
	}

	const checkInterval = 4
	fr, fg, fb, fa := img.At(bounds.Min.X, bounds.Min.Y).RGBA()
	for x := bounds.Min.X; x < bounds.Max.X; x += checkInterval {
		for y := bounds.Min.Y; y < bounds.Max.Y; y += checkInterval {
			r, g, b, a := img.At(x, y).RGBA()
			if r != fr || g != fg || b != fb || a != fa {
				return nil // found a different pixel — image is valid
			}
		}
	}
	return fmt.Errorf("image is uniformly colored and likely invalid")
}

// createVariation resizes and places the image centered on a square canvas.
func createVariation(src image.Image, f model.ThumbnailFormat) image.Image {
	size := f.Size()
	srcW := src.Bounds().Dx()
	srcH := src.Bounds().Dy()

	// Calculate dimensions maintaining aspect ratio
	ratio := min(float64(size)/float64(srcW), float64(size)/float64(srcH))
	newW := int(float64(srcW) * ratio)
	newH := int(float64(srcH) * ratio)

	// Resize using Lanczos
	resized := imaging.Resize(src, newW, newH, imaging.Lanczos)

	// Create square canvas
	var canvas *image.NRGBA
	if f.HasBackground() {
		bgHex := f.BackgroundColorHex()
		r := hexByte(bgHex[0:2])
		g := hexByte(bgHex[2:4])
		b := hexByte(bgHex[4:6])
		canvas = imaging.New(size, size, color.NRGBA{R: r, G: g, B: b, A: 255})
	} else {
		canvas = imaging.New(size, size, color.NRGBA{0, 0, 0, 0})
	}

	// Paste resized image centered
	offsetX := (size - newW) / 2
	offsetY := (size - newH) / 2
	canvas = imaging.Paste(canvas, resized, image.Pt(offsetX, offsetY))

	return canvas
}

func encode(img image.Image, f model.ThumbnailFormat) ([]byte, error) {
	var buf bytes.Buffer
	switch f.Extension() {
	case "jpg":
		// Draw onto opaque image for JPEG
		bounds := img.Bounds()
		opaque := image.NewRGBA(bounds)
		draw.Draw(opaque, bounds, img, bounds.Min, draw.Over)
		if err := jpeg.Encode(&buf, opaque, &jpeg.Options{Quality: 95}); err != nil {
			return nil, err
		}
	case "png":
		if err := png.Encode(&buf, img); err != nil {
			return nil, err
		}
	default:
		return nil, fmt.Errorf("unsupported extension: %s", f.Extension())
	}
	return buf.Bytes(), nil
}

func hexByte(s string) uint8 {
	v, _ := strconv.ParseUint(s, 16, 8)
	return uint8(v)
}

func min(a, b float64) float64 {
	if a < b {
		return a
	}
	return b
}
