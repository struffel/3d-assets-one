package web

import (
	"net/http"
	"strconv"
	"strings"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/struffel/3d-assets-one/internal/model"
	"github.com/struffel/3d-assets-one/internal/query"
)

// --- Admin Availability ---

type availabilityRow struct {
	Title          string
	LastChecked    string
	LastAvailable  string
	FailedAttempts int
}

func (s *Server) handleAdminAvailability(c *gin.Context) {
	c.Header("Cache-Control", "no-store")

	rows, err := s.DB.Query("SELECT creatorId, lastChecked, lastSuccess, failedAttempts FROM CreatorAvailability ORDER BY failedAttempts DESC")
	if err != nil {
		c.String(http.StatusInternalServerError, "Error retrieving availability data.")
		return
	}
	defer rows.Close()

	var data []availabilityRow
	for rows.Next() {
		var creatorID, failedAttempts int
		var lastChecked, lastAvailable string
		if err := rows.Scan(&creatorID, &lastChecked, &lastAvailable, &failedAttempts); err != nil {
			continue
		}
		creator, ok := model.CreatorFromValue(creatorID)
		if !ok {
			continue
		}
		data = append(data, availabilityRow{
			Title:          creator.Title(),
			LastChecked:    lastChecked,
			LastAvailable:  lastAvailable,
			FailedAttempts: failedAttempts,
		})
	}

	s.render(c, http.StatusOK, "admin_availability.html", struct {
		Rows []availabilityRow
	}{Rows: data})
}

// --- Admin Editor ---

type editorCreatorItem struct {
	Slug     string
	Title    string
	Selected bool
}

type editorSortItem struct {
	Value    string
	Selected bool
}

type editorStatusItem struct {
	Value    string
	Name     string
	Selected bool
}

type editorPageData struct {
	Creators []editorCreatorItem
	AssetID  string
	Sortings []editorSortItem
	Statuses []editorStatusItem
}

func (s *Server) handleAdminEditor(c *gin.Context) {
	selectedCreators := c.QueryArray("creator[]")
	selectedSort := c.Query("sort")
	selectedStatus := c.Query("status")
	assetID := c.Query("id[]")

	var creatorItems []editorCreatorItem
	for _, cr := range model.AllCreators() {
		selected := false
		for _, sc := range selectedCreators {
			if sc == cr.Slug() {
				selected = true
				break
			}
		}
		creatorItems = append(creatorItems, editorCreatorItem{
			Slug:     cr.Slug(),
			Title:    cr.Title(),
			Selected: selected,
		})
	}

	var sortItems []editorSortItem
	for _, sr := range model.AllSortings() {
		sortItems = append(sortItems, editorSortItem{
			Value:    string(sr),
			Selected: selectedSort == string(sr),
		})
	}

	var statusItems []editorStatusItem
	for _, st := range model.AllStoredAssetStatuses() {
		statusItems = append(statusItems, editorStatusItem{
			Value:    strconv.Itoa(int(st)),
			Name:     st.Name(),
			Selected: selectedStatus == strconv.Itoa(int(st)),
		})
	}

	s.render(c, http.StatusOK, "admin_editor.html", editorPageData{
		Creators: creatorItems,
		AssetID:  assetID,
		Sortings: sortItems,
		Statuses: statusItems,
	})
}

// --- Admin Editor List (HTMX partial) ---

type editorAssetStatusItem struct {
	Value    string
	Name     string
	Selected bool
}

type editorAssetTypeItem struct {
	Value    string
	Name     string
	Selected bool
}

type editorAssetItem struct {
	ID           int64
	Title        string
	ThumbnailURL string
	TagString    string
	URL          string
	DateString   string
	Updated      bool
	Statuses     []editorAssetStatusItem
	Types        []editorAssetTypeItem
}

type editorListData struct {
	Assets      []editorAssetItem
	NextPageURL string
}

func (s *Server) handleAdminEditorList(c *gin.Context) {
	c.Header("Cache-Control", "no-store")
	c.Header("HX-Replace-Url", "?"+c.Request.URL.RawQuery)

	q := query.FromHTTPParams(c.Request.URL.Query(), nil)
	collection, err := q.Execute(s.DB)
	if err != nil {
		c.String(http.StatusInternalServerError, "query error")
		return
	}

	var items []editorAssetItem
	for _, a := range collection.Assets {
		var statuses []editorAssetStatusItem
		for _, st := range model.AllStoredAssetStatuses() {
			statuses = append(statuses, editorAssetStatusItem{
				Value:    strconv.Itoa(int(st)),
				Name:     st.Name(),
				Selected: a.Status == st,
			})
		}

		var types []editorAssetTypeItem
		for _, t := range model.AllAssetTypes() {
			types = append(types, editorAssetTypeItem{
				Value:    strconv.Itoa(int(t)),
				Name:     t.Name(),
				Selected: a.Type == t,
			})
		}

		items = append(items, editorAssetItem{
			ID:           a.ID,
			Title:        a.Title,
			ThumbnailURL: a.ThumbnailURL(model.ThumbJPG64, s.Config.CDNBaseURL),
			TagString:    strings.Join(a.Tags, " "),
			URL:          a.URL,
			DateString:   a.Date.Format("2006-01-02T15:04"),
			Statuses:     statuses,
			Types:        types,
		})
	}

	var nextPageURL string
	if collection.NextQuery != nil {
		nextPageURL = "/admin/render/editor-list?" + *collection.NextQuery
	}

	s.render(c, http.StatusOK, "admin_editor_list.html", editorListData{
		Assets:      items,
		NextPageURL: nextPageURL,
	})
}

// --- Admin Editor Update Asset ---

func (s *Server) handleAdminEditorUpdateAsset(c *gin.Context) {
	c.Header("Cache-Control", "no-store")

	idStr := c.PostForm("id")
	assetID, err := strconv.ParseInt(idStr, 10, 64)
	if err != nil || assetID <= 0 {
		c.String(http.StatusBadRequest, "invalid id")
		return
	}

	// Fetch the existing asset
	q := &query.AssetQuery{
		Offset:        0,
		Limit:         intPtr(1),
		FilterAssetID: []int64{assetID},
	}
	collection, err := q.Execute(s.DB)
	if err != nil || len(collection.Assets) == 0 {
		c.String(http.StatusNotFound, "asset not found")
		return
	}

	asset := collection.Assets[0]

	// Apply updates
	asset.Title = c.PostForm("title")
	asset.URL = c.PostForm("url")

	if tagStr := c.PostForm("tagString"); tagStr != "" {
		var tags []string
		for _, t := range strings.Fields(tagStr) {
			t = strings.TrimSpace(t)
			if t != "" {
				tags = append(tags, t)
			}
		}
		asset.Tags = tags
	} else {
		asset.Tags = nil
	}

	if dateStr := c.PostForm("date"); dateStr != "" {
		if t, err := time.Parse("2006-01-02T15:04", dateStr); err == nil {
			asset.Date = t
		}
	}

	if statusStr := c.PostForm("status"); statusStr != "" {
		if v, err := strconv.Atoi(statusStr); err == nil {
			if st, ok := model.StoredAssetStatusTryFrom(v); ok {
				asset.Status = st
			}
		}
	}

	if typeStr := c.PostForm("type"); typeStr != "" {
		if v, err := strconv.Atoi(typeStr); err == nil {
			asset.Type = model.AssetType(v)
		}
	}

	// Save
	if _, err := query.WriteAsset(s.DB, asset); err != nil {
		c.String(http.StatusInternalServerError, "update failed: "+err.Error())
		return
	}

	// Render the single editor row with updated flag
	var statuses []editorAssetStatusItem
	for _, st := range model.AllStoredAssetStatuses() {
		statuses = append(statuses, editorAssetStatusItem{
			Value:    strconv.Itoa(int(st)),
			Name:     st.Name(),
			Selected: asset.Status == st,
		})
	}
	var types []editorAssetTypeItem
	for _, t := range model.AllAssetTypes() {
		types = append(types, editorAssetTypeItem{
			Value:    strconv.Itoa(int(t)),
			Name:     t.Name(),
			Selected: asset.Type == t,
		})
	}

	item := editorAssetItem{
		ID:           asset.ID,
		Title:        asset.Title,
		ThumbnailURL: asset.ThumbnailURL(model.ThumbJPG64, s.Config.CDNBaseURL),
		TagString:    strings.Join(asset.Tags, " "),
		URL:          asset.URL,
		DateString:   asset.Date.Format("2006-01-02T15:04"),
		Updated:      true,
		Statuses:     statuses,
		Types:        types,
	}

	s.render(c, http.StatusOK, "admin_editor_list.html", editorListData{
		Assets: []editorAssetItem{item},
	})
}
