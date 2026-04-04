package model

import "strings"

// ThumbnailFormat represents a specific thumbnail size/format combination.
type ThumbnailFormat struct {
	value              string
	size               int
	extension          string
	backgroundColorHex string // empty string means transparent (PNG)
}

var (
	ThumbJPG32  = ThumbnailFormat{"32-JPG-FFFFFF", 32, "jpg", "FFFFFF"}
	ThumbJPG64  = ThumbnailFormat{"64-JPG-FFFFFF", 64, "jpg", "FFFFFF"}
	ThumbJPG128 = ThumbnailFormat{"128-JPG-FFFFFF", 128, "jpg", "FFFFFF"}
	ThumbJPG256 = ThumbnailFormat{"256-JPG-FFFFFF", 256, "jpg", "FFFFFF"}
	ThumbPNG32  = ThumbnailFormat{"32-PNG", 32, "png", ""}
	ThumbPNG64  = ThumbnailFormat{"64-PNG", 64, "png", ""}
	ThumbPNG128 = ThumbnailFormat{"128-PNG", 128, "png", ""}
	ThumbPNG256 = ThumbnailFormat{"256-PNG", 256, "png", ""}
)

var allThumbnailFormats = []ThumbnailFormat{
	ThumbJPG32, ThumbJPG64, ThumbJPG128, ThumbJPG256,
	ThumbPNG32, ThumbPNG64, ThumbPNG128, ThumbPNG256,
}

func AllThumbnailFormats() []ThumbnailFormat { return allThumbnailFormats }

func (t ThumbnailFormat) Value() string              { return t.value }
func (t ThumbnailFormat) Size() int                  { return t.size }
func (t ThumbnailFormat) Extension() string          { return t.extension }
func (t ThumbnailFormat) BackgroundColorHex() string { return t.backgroundColorHex }
func (t ThumbnailFormat) HasBackground() bool        { return t.backgroundColorHex != "" }

func ThumbnailFormatFromValue(v string) (ThumbnailFormat, bool) {
	for _, f := range allThumbnailFormats {
		if strings.EqualFold(f.value, v) {
			return f, true
		}
	}
	return ThumbnailFormat{}, false
}
