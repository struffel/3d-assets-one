package thumbnail

import (
	"context"
	"image"
	"image/color"
	"os"
	"path/filepath"
	"runtime"
	"testing"

	_ "image/jpeg"
	_ "image/png"
)

func testDataDir() string {
	_, file, _, _ := runtime.Caller(0)
	return filepath.Join(filepath.Dir(file), "..", "..", "test-files")
}

func decodeFile(t *testing.T, name string) image.Image {
	t.Helper()
	f, err := os.Open(filepath.Join(testDataDir(), name))
	if err != nil {
		t.Fatalf("open %s: %v", name, err)
	}
	defer f.Close()
	img, _, err := image.Decode(f)
	if err != nil {
		t.Fatalf("decode %s: %v", name, err)
	}
	return img
}

func TestValidateImageAcceptsPNG(t *testing.T) {
	img := decodeFile(t, "dirt.png")
	if err := ValidateImage(img); err != nil {
		t.Errorf("expected dirt.png to be valid: %v", err)
	}
}

func TestValidateImageAcceptsJPG(t *testing.T) {
	img := decodeFile(t, "paving.jpg")
	if err := ValidateImage(img); err != nil {
		t.Errorf("expected paving.jpg to be valid: %v", err)
	}
}

func TestValidateImageRejectsUniformColor(t *testing.T) {
	img := decodeFile(t, "black.jpg")
	if err := ValidateImage(img); err == nil {
		t.Error("expected black.jpg to be rejected as uniformly colored")
	}
}

func TestValidateImageRejectsZeroDimensions(t *testing.T) {
	img := image.NewRGBA(image.Rect(0, 0, 0, 0))
	if err := ValidateImage(img); err == nil {
		t.Error("expected zero-dimension image to be rejected")
	}
}

func TestValidateImageAcceptsSyntheticMultiColor(t *testing.T) {
	img := image.NewRGBA(image.Rect(0, 0, 16, 16))
	// Paint half red, half blue
	for x := 0; x < 16; x++ {
		for y := 0; y < 16; y++ {
			if x < 8 {
				img.Set(x, y, color.RGBA{255, 0, 0, 255})
			} else {
				img.Set(x, y, color.RGBA{0, 0, 255, 255})
			}
		}
	}
	if err := ValidateImage(img); err != nil {
		t.Errorf("expected multi-color image to be valid: %v", err)
	}
}

func TestValidateImageRejectsSyntheticUniform(t *testing.T) {
	img := image.NewRGBA(image.Rect(0, 0, 16, 16))
	for x := 0; x < 16; x++ {
		for y := 0; y < 16; y++ {
			img.Set(x, y, color.RGBA{42, 42, 42, 255})
		}
	}
	if err := ValidateImage(img); err == nil {
		t.Error("expected uniform synthetic image to be rejected")
	}
}

// --- SaveVariations with mock storage ---

type mockStorage struct {
	uploads map[string][]byte
}

func (m *mockStorage) Upload(_ context.Context, path string, data []byte, _ string) error {
	m.uploads[path] = data
	return nil
}

func (m *mockStorage) Delete(_ context.Context, _ string) error { return nil }
func (m *mockStorage) PublicURL(path string) string             { return "https://cdn.test/" + path }

func TestSaveVariationsProduces8Files(t *testing.T) {
	img := decodeFile(t, "dirt.png")
	store := &mockStorage{uploads: make(map[string][]byte)}
	proc := NewThumbnailProcessor(store)

	if err := proc.SaveVariations(context.Background(), 999, img); err != nil {
		t.Fatalf("SaveVariations: %v", err)
	}

	if len(store.uploads) != 8 {
		t.Errorf("expected 8 uploads, got %d", len(store.uploads))
		for k := range store.uploads {
			t.Logf("  uploaded: %s", k)
		}
	}
}

func TestSaveVariationsRejectsUniformImage(t *testing.T) {
	img := decodeFile(t, "black.jpg")
	store := &mockStorage{uploads: make(map[string][]byte)}
	proc := NewThumbnailProcessor(store)

	if err := proc.SaveVariations(context.Background(), 999, img); err == nil {
		t.Error("expected SaveVariations to reject uniform image")
	}

	if len(store.uploads) != 0 {
		t.Errorf("expected 0 uploads after rejection, got %d", len(store.uploads))
	}
}
