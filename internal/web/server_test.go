package web

import (
	"net/http"
	"net/http/httptest"
	"testing"
	"testing/fstest"

	"github.com/struffel/3d-assets-one/internal/config"
)

// newTestServer creates a Server with nil DB (sufficient for routes that don't query).
func newTestServer(t *testing.T) *Server {
	t.Helper()
	cfg := &config.Config{
		AdminToken: "test-token",
		CDNBaseURL: "https://cdn.test",
	}
	return NewServer(nil, cfg)
}

var testStaticFS = fstest.MapFS{
	"css/base.css": &fstest.MapFile{Data: []byte("body{}")},
	"js/index.js":  &fstest.MapFile{Data: []byte("")},
	"img/.keep":    &fstest.MapFile{Data: []byte("")},
}

func TestHealthEndpoint(t *testing.T) {
	srv := newTestServer(t)
	router := srv.SetupRouter(testStaticFS)

	w := httptest.NewRecorder()
	req, _ := http.NewRequest("GET", "/health", nil)
	router.ServeHTTP(w, req)

	if w.Code != http.StatusOK {
		t.Errorf("GET /health status = %d, want %d", w.Code, http.StatusOK)
	}
	if w.Body.String() != "ok" {
		t.Errorf("GET /health body = %q, want %q", w.Body.String(), "ok")
	}
}

func TestAPICreatorsReturnsJSON(t *testing.T) {
	srv := newTestServer(t)
	router := srv.SetupRouter(testStaticFS)

	w := httptest.NewRecorder()
	req, _ := http.NewRequest("GET", "/api/v2/creators", nil)
	router.ServeHTTP(w, req)

	if w.Code != http.StatusOK {
		t.Errorf("GET /api/v2/creators status = %d, want %d", w.Code, http.StatusOK)
	}
	ct := w.Header().Get("Content-Type")
	if ct == "" || (ct != "application/json; charset=utf-8" && ct != "application/json") {
		t.Errorf("Content-Type = %q, want application/json", ct)
	}
}

func TestAPITypesReturnsJSON(t *testing.T) {
	srv := newTestServer(t)
	router := srv.SetupRouter(testStaticFS)

	w := httptest.NewRecorder()
	req, _ := http.NewRequest("GET", "/api/v2/types", nil)
	router.ServeHTTP(w, req)

	if w.Code != http.StatusOK {
		t.Errorf("GET /api/v2/types status = %d, want %d", w.Code, http.StatusOK)
	}
}

func TestGoRedirectReturns404ForMissingID(t *testing.T) {
	srv := newTestServer(t)
	router := srv.SetupRouter(testStaticFS)

	w := httptest.NewRecorder()
	req, _ := http.NewRequest("GET", "/go", nil)
	router.ServeHTTP(w, req)

	// Missing id param triggers the ParseInt error path (no DB needed)
	if w.Code != http.StatusNotFound {
		t.Errorf("GET /go status = %d, want %d", w.Code, http.StatusNotFound)
	}
}

func TestGoRedirectReturns404ForNegativeID(t *testing.T) {
	srv := newTestServer(t)
	router := srv.SetupRouter(testStaticFS)

	w := httptest.NewRecorder()
	req, _ := http.NewRequest("GET", "/go?id=-1", nil)
	router.ServeHTTP(w, req)

	if w.Code != http.StatusNotFound {
		t.Errorf("GET /go?id=-1 status = %d, want %d", w.Code, http.StatusNotFound)
	}
}

func TestAdminRequiresAuth(t *testing.T) {
	srv := newTestServer(t)
	router := srv.SetupRouter(testStaticFS)

	w := httptest.NewRecorder()
	req, _ := http.NewRequest("GET", "/admin/editor", nil)
	router.ServeHTTP(w, req)

	if w.Code != http.StatusUnauthorized {
		t.Errorf("GET /admin/editor without auth status = %d, want %d", w.Code, http.StatusUnauthorized)
	}
}

func TestSanitizeQuery(t *testing.T) {
	tests := []struct {
		input string
		want  string
	}{
		{"hello world", "hello world"},
		{"wood, metal", "wood, metal"},
		{"<script>alert(1)</script>", "scriptalert1script"},
		{"normal123", "normal123"},
		{"a!@#b$%^c", "abc"},
	}
	for _, tt := range tests {
		got := sanitizeQuery(tt.input)
		if got != tt.want {
			t.Errorf("sanitizeQuery(%q) = %q, want %q", tt.input, got, tt.want)
		}
	}
}
