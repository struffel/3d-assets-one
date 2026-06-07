package web

import (
	"database/sql"
	"embed"
	"html/template"
	"io/fs"
	"log/slog"
	"net/http"
	"regexp"

	"github.com/gin-gonic/gin"
	"github.com/struffel/3d-assets-one/internal/config"
)

//go:embed templates/*.html
var templateFS embed.FS

// Server holds all dependencies for the web layer.
type Server struct {
	DB        *sql.DB
	Config    *config.Config
	Templates *template.Template
}

// NewServer creates a Server and parses templates.
func NewServer(db *sql.DB, cfg *config.Config) *Server {
	tmpl := template.Must(template.ParseFS(templateFS, "templates/*.html"))
	return &Server{
		DB:        db,
		Config:    cfg,
		Templates: tmpl,
	}
}

// SetupRouter creates and configures the Gin engine with all routes.
func (s *Server) SetupRouter(staticFS fs.FS) *gin.Engine {
	r := gin.Default()

	// Serve static files (css, js, img)
	r.StaticFS("/css", http.FS(mustSubFS(staticFS, "css")))
	r.StaticFS("/js", http.FS(mustSubFS(staticFS, "js")))
	r.StaticFS("/img", http.FS(mustSubFS(staticFS, "img")))

	// Serve thumbnails from local directory when configured (dev mode)
	if s.Config.LocalThumbnailDir != "" {
		r.Static("/thumbnail", s.Config.LocalThumbnailDir)
	}

	// Health check
	r.GET("/health", func(c *gin.Context) {
		c.String(http.StatusOK, "ok")
	})

	// Public pages
	r.GET("/", s.handleIndex)
	r.GET("/about-creators", s.handleAboutCreators)
	r.GET("/about-site", s.handleAboutSite)
	r.GET("/go", s.handleGo)

	// HTMX partial
	r.GET("/render/asset-list", s.handleAssetList)

	// API v2
	api := r.Group("/api/v2")
	api.GET("/assets", s.handleAPIAssets)
	api.GET("/assets-rss", s.handleAPIAssetsRSS)
	api.GET("/creators", s.handleAPICreators)
	api.GET("/types", s.handleAPITypes)

	// Admin (basic auth)
	admin := r.Group("/admin", s.adminAuth())
	admin.GET("/availability", s.handleAdminAvailability)
	admin.GET("/editor", s.handleAdminEditor)
	admin.GET("/render/editor-list", s.handleAdminEditorList)
	admin.POST("/render/editor-update-asset", s.handleAdminEditorUpdateAsset)

	return r
}

// adminAuth returns Gin middleware for HTTP Basic Auth.
func (s *Server) adminAuth() gin.HandlerFunc {
	return gin.BasicAuth(gin.Accounts{
		"admin": s.Config.AdminToken,
	})
}

// render executes a named template with data.
func (s *Server) render(c *gin.Context, status int, name string, data any) {
	c.Header("Content-Type", "text/html; charset=utf-8")
	c.Status(status)
	if err := s.Templates.ExecuteTemplate(c.Writer, name, data); err != nil {
		slog.Error("Template render error", "template", name, "error", err)
	}
}

// sanitizeQuery removes non-alphanumeric/comma/space characters from a query string.
var queryRe = regexp.MustCompile(`[^a-zA-Z0-9, ]`)

func sanitizeQuery(q string) string {
	return queryRe.ReplaceAllString(q, "")
}

func mustSubFS(fsys fs.FS, dir string) fs.FS {
	sub, err := fs.Sub(fsys, dir)
	if err != nil {
		panic("sub fs: " + err.Error())
	}
	return sub
}
