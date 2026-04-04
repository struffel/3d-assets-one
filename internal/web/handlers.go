package web

import (
	"fmt"
	"net/http"
	"regexp"
	"strconv"
	"strings"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/struffel/3d-assets-one/internal/database"
	"github.com/struffel/3d-assets-one/internal/model"
	"github.com/struffel/3d-assets-one/internal/query"
	"golang.org/x/text/cases"
	"golang.org/x/text/language"
)

// --- Index page ---

type indexCreatorItem struct {
	Value    int
	Slug     string
	Title    string
	Count    int
	Selected bool
}

type indexLicenseItem struct {
	Slug     string
	Title    string
	Selected bool
}

type indexTypeItem struct {
	Slug     string
	Name     string
	Selected bool
}

type indexSortItem struct {
	Value    string
	Label    string
	Selected bool
}

type indexData struct {
	Query        string
	LicenseTypes []indexLicenseItem
	Creators     []indexCreatorItem
	CreatorCount int
	Types        []indexTypeItem
	TypeCount    int
	Sortings     []indexSortItem
}

func (s *Server) handleIndex(c *gin.Context) {
	counts, _ := query.AssetCountByCreator(s.DB)

	selectedCreators := c.QueryArray("creator[]")
	selectedTypes := c.QueryArray("type[]")
	selectedLicense := c.Query("license")
	selectedSort := c.Query("sort")

	creators := model.AllCreators()
	var creatorItems []indexCreatorItem
	for _, cr := range creators {
		selected := false
		for _, sc := range selectedCreators {
			if sc == cr.Slug() {
				selected = true
				break
			}
		}
		creatorItems = append(creatorItems, indexCreatorItem{
			Value:    int(cr),
			Slug:     cr.Slug(),
			Title:    cr.Title(),
			Count:    counts[int(cr)],
			Selected: selected,
		})
	}

	var licenseItems []indexLicenseItem
	for _, l := range model.AllCreatorLicenseTypes() {
		licenseItems = append(licenseItems, indexLicenseItem{
			Slug:     l.Slug(),
			Title:    l.Title(),
			Selected: selectedLicense == l.Slug(),
		})
	}

	var typeItems []indexTypeItem
	for _, t := range model.AllAssetTypes() {
		selected := false
		for _, st := range selectedTypes {
			if st == t.Slug() {
				selected = true
				break
			}
		}
		typeItems = append(typeItems, indexTypeItem{
			Slug:     t.Slug(),
			Name:     t.Name(),
			Selected: selected,
		})
	}

	publicSortings := model.PublicSortings()
	titleCaser := cases.Title(language.English)
	var sortItems []indexSortItem
	for _, sr := range publicSortings {
		sortItems = append(sortItems, indexSortItem{
			Value:    string(sr),
			Label:    titleCaser.String(string(sr)),
			Selected: selectedSort == string(sr),
		})
	}

	s.render(c, http.StatusOK, "index.html", indexData{
		Query:        sanitizeQuery(c.Query("q")),
		LicenseTypes: licenseItems,
		Creators:     creatorItems,
		CreatorCount: len(creators),
		Types:        typeItems,
		TypeCount:    len(model.AllAssetTypes()),
		Sortings:     sortItems,
	})
}

// --- Asset list HTMX partial ---

type assetListCreatorCount struct {
	Value int
	Count int
}

type assetListItem struct {
	ID           int64
	Title        string
	ThumbnailURL string
	CreatorTitle string
	CreatorValue int
	LicenseURL   string
}

type assetListData struct {
	ShowWelcome   bool
	AssetCount    int
	CreatorCount  int
	CreatorCounts []assetListCreatorCount
	Assets        []assetListItem
	NextPageURL   string
}

func (s *Server) handleAssetList(c *gin.Context) {
	status := model.StatusActive
	q := query.FromHTTPParams(c.Request.URL.Query(), &status)
	collection, err := q.Execute(s.DB)
	if err != nil {
		c.String(http.StatusInternalServerError, "query error")
		return
	}

	countByCreator, _ := q.ExecuteCountByCreator(s.DB)

	// Determine if the welcome message should show
	showWelcome := len(q.FilterAssetID) == 0 &&
		q.FilterLicenseType == model.LicenseAnyLicense &&
		len(q.FilterCreator) == 0 &&
		len(q.FilterType) == 0 &&
		q.Offset == 0 &&
		len(q.FilterTag) == 0

	var assetCount int
	if showWelcome {
		assetCount, _ = query.AssetCountTotal(s.DB)
	}

	// Build creator count OOB updates
	var creatorCounts []assetListCreatorCount
	for _, cr := range model.AllCreators() {
		creatorCounts = append(creatorCounts, assetListCreatorCount{
			Value: int(cr),
			Count: countByCreator[int(cr)],
		})
	}

	// Build asset items
	var items []assetListItem
	for _, a := range collection.Assets {
		items = append(items, assetListItem{
			ID:           a.ID,
			Title:        a.Title,
			ThumbnailURL: a.ThumbnailURL(model.ThumbJPG256, s.Config.CDNBaseURL),
			CreatorTitle: a.Creator.Title(),
			CreatorValue: int(a.Creator),
			LicenseURL:   a.Creator.LicenseURL(),
		})
	}

	var nextPageURL string
	if collection.NextQuery != nil {
		nextPageURL = "/render/asset-list?" + *collection.NextQuery
	}

	// Set HX-Replace-Url header
	c.Header("HX-Replace-Url", "?"+c.Request.URL.RawQuery)

	s.render(c, http.StatusOK, "asset_list.html", assetListData{
		ShowWelcome:   showWelcome,
		AssetCount:    assetCount,
		CreatorCount:  len(model.AllCreators()),
		CreatorCounts: creatorCounts,
		Assets:        items,
		NextPageURL:   nextPageURL,
	})
}

// --- Go redirect ---

func (s *Server) handleGo(c *gin.Context) {
	idStr := c.Query("id")
	assetID, err := strconv.ParseInt(idStr, 10, 64)
	if err != nil || assetID <= 0 {
		c.String(http.StatusNotFound, "3Dassets.one\nURL could not be resolved.")
		return
	}

	q := &query.AssetQuery{
		Offset:        0,
		Limit:         intPtr(1),
		FilterAssetID: []int64{assetID},
	}
	collection, err := q.Execute(s.DB)
	if err != nil || len(collection.Assets) == 0 {
		c.String(http.StatusNotFound, "3Dassets.one\nURL could not be resolved.")
		return
	}

	asset := collection.Assets[0]
	_ = database.AddAssetClick(s.DB, assetID)
	c.Redirect(http.StatusFound, asset.URL)
}

// --- About Creators page ---

type aboutCreatorItem struct {
	Value       int
	Title       string
	Description string
	BaseURL     string
	LicenseURL  string
}

func (s *Server) handleAboutCreators(c *gin.Context) {
	var items []aboutCreatorItem
	for _, cr := range model.AllCreators() {
		items = append(items, aboutCreatorItem{
			Value:       int(cr),
			Title:       cr.Title(),
			Description: cr.Description(),
			BaseURL:     cr.BaseURL(),
			LicenseURL:  cr.LicenseURL(),
		})
	}
	s.render(c, http.StatusOK, "about_creators.html", struct {
		Creators []aboutCreatorItem
	}{Creators: items})
}

// --- About Site page ---

func (s *Server) handleAboutSite(c *gin.Context) {
	var sortValues []string
	for _, sr := range model.AllSortings() {
		sortValues = append(sortValues, string(sr))
	}
	s.render(c, http.StatusOK, "about_site.html", struct {
		SortValues string
	}{SortValues: strings.Join(sortValues, " ")})
}

// --- API v2/assets ---

func (s *Server) handleAPIAssets(c *gin.Context) {
	status := model.StatusActive
	q := query.FromHTTPParams(c.Request.URL.Query(), &status)
	collection, err := q.Execute(s.DB)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "query error"})
		return
	}

	thumbFormatStr := c.Query("thumbnailFormat")
	thumbFormat, ok := model.ThumbnailFormatFromValue(thumbFormatStr)
	if !ok {
		thumbFormat = model.ThumbPNG256
	}

	var output []map[string]any
	for _, a := range collection.Assets {
		output = append(output, a.APIRepresentation(thumbFormat, s.Config.CDNBaseURL))
	}

	c.JSON(http.StatusOK, output)
}

// --- API v2/assets-rss ---

func (s *Server) handleAPIAssetsRSS(c *gin.Context) {
	status := model.StatusActive
	q := query.FromHTTPParams(c.Request.URL.Query(), &status)
	q.Sort = model.SortLatest
	collection, err := q.Execute(s.DB)
	if err != nil {
		c.String(http.StatusInternalServerError, "query error")
		return
	}

	host := c.Request.Host

	var sb strings.Builder
	sb.WriteString(`<?xml version="1.0" encoding="UTF-8" ?>` + "\n")
	sb.WriteString(`<rss xmlns:media="http://search.yahoo.com/mrss/" version="2.0">` + "\n")
	sb.WriteString("<channel>\n")
	sb.WriteString("<title>3Dassets.one Auto-Generated Asset Feed</title>\n")
	sb.WriteString("<link>https://3Dassets.one</link>\n")
	sb.WriteString("<description>RSS feed containing all newly released 3D models, materials, HDRIs and other resources from creators tracked by 3Dassets.one.</description>\n")

	for _, a := range collection.Assets {
		thumbURL := a.ThumbnailURL(model.ThumbJPG256, s.Config.CDNBaseURL)
		sb.WriteString("<item>\n")
		sb.WriteString("<title>" + xmlEscape(a.Title) + "</title>\n")
		sb.WriteString(fmt.Sprintf(`<media:thumbnail url="%s" height="256" width="256" />`+"\n", thumbURL))
		sb.WriteString("<description>" + xmlEscape(a.Title) + " by " + xmlEscape(a.Creator.Title()) + " / Type: " + a.Type.Name() + " / Tags: " + strings.Join(a.Tags, ",") + "</description>\n")
		sb.WriteString(fmt.Sprintf("<link>https://%s/go?id=%d</link>\n", host, a.ID))
		sb.WriteString(fmt.Sprintf("<guid isPermaLink=\"false\">3D1-%d</guid>\n", a.ID))
		sb.WriteString("<pubDate>" + a.Date.Format(time.RFC1123Z) + "</pubDate>\n")
		sb.WriteString("</item>\n")
	}

	sb.WriteString("</channel>\n</rss>")

	c.Data(http.StatusOK, "application/rss+xml; charset=utf-8", []byte(sb.String()))
}

// --- API v2/creators ---

func (s *Server) handleAPICreators(c *gin.Context) {
	var creators []map[string]any
	for _, cr := range model.AllCreators() {
		creators = append(creators, map[string]any{
			"id":          int(cr),
			"slug":        cr.Slug(),
			"name":        cr.Title(),
			"licenseUrl":  cr.LicenseURL(),
			"description": cr.Description(),
		})
	}
	c.JSON(http.StatusOK, creators)
}

// --- API v2/types ---

func (s *Server) handleAPITypes(c *gin.Context) {
	var types []map[string]any
	for _, t := range model.AllAssetTypes() {
		types = append(types, map[string]any{
			"id":   int(t),
			"slug": t.Slug(),
			"name": t.Name(),
		})
	}
	c.JSON(http.StatusOK, types)
}

// helpers

func intPtr(v int) *int { return &v }

var xmlEscaper = regexp.MustCompile(`[<>&"']`)

func xmlEscape(s string) string {
	return xmlEscaper.ReplaceAllStringFunc(s, func(match string) string {
		switch match {
		case "<":
			return "&lt;"
		case ">":
			return "&gt;"
		case "&":
			return "&amp;"
		case `"`:
			return "&quot;"
		case "'":
			return "&apos;"
		}
		return match
	})
}
