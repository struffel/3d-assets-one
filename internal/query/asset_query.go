package query

import (
	"database/sql"
	"fmt"
	"log/slog"
	"net/url"
	"strconv"
	"strings"
	"time"

	"github.com/struffel/3d-assets-one/internal/database"
	"github.com/struffel/3d-assets-one/internal/model"
)

// AssetQuery defines filters and pagination for querying stored assets.
type AssetQuery struct {
	Offset            int
	Limit             *int // nil means no limit
	Sort              model.AssetSorting
	FilterAssetID     []int64
	FilterTag         []string
	FilterCreator     []model.Creator
	FilterType        []model.AssetType
	FilterStatus      *model.StoredAssetStatus // nil = any status
	FilterLicenseType model.CreatorLicenseType
}

// NewAssetQuery returns a default query (active assets, latest, 150 limit).
func NewAssetQuery() *AssetQuery {
	limit := 150
	status := model.StatusActive
	return &AssetQuery{
		Offset:            0,
		Limit:             &limit,
		Sort:              model.SortLatest,
		FilterStatus:      &status,
		FilterLicenseType: model.LicenseAnyLicense,
	}
}

// FromHTTPParams populates a query from URL query parameters.
func FromHTTPParams(params url.Values, filterStatus *model.StoredAssetStatus) *AssetQuery {
	q := &AssetQuery{
		FilterLicenseType: model.LicenseAnyLicense,
	}

	// Status
	if filterStatus != nil {
		q.FilterStatus = filterStatus
	} else if sv := params.Get("status"); sv != "" {
		if v, err := strconv.Atoi(sv); err == nil {
			if s, ok := model.StoredAssetStatusTryFrom(v); ok {
				q.FilterStatus = &s
			}
		}
	}

	// License
	if lv := params.Get("license"); lv != "" {
		if l, ok := model.CreatorLicenseTypeTryFromSlug(lv); ok {
			q.FilterLicenseType = l
		}
	}

	// Asset IDs
	for _, idStr := range params["id[]"] {
		if v, err := strconv.ParseInt(idStr, 10, 64); err == nil && v > 0 {
			q.FilterAssetID = append(q.FilterAssetID, v)
		}
	}
	// Also accept "id" for single value
	if idStr := params.Get("id"); idStr != "" {
		if v, err := strconv.ParseInt(idStr, 10, 64); err == nil && v > 0 {
			q.FilterAssetID = append(q.FilterAssetID, v)
		}
	}

	// Creator
	for _, slug := range params["creator[]"] {
		if c, ok := model.CreatorFromSlug(slug); ok {
			q.FilterCreator = append(q.FilterCreator, c)
		}
	}

	// Type
	for _, slug := range params["type[]"] {
		if t, ok := model.AssetTypeTryFromSlug(slug); ok {
			q.FilterType = append(q.FilterType, t)
		}
	}

	// Tags (comma or space separated)
	if tagStr := params.Get("q"); tagStr != "" {
		parts := strings.FieldsFunc(tagStr, func(r rune) bool {
			return r == ',' || r == ' '
		})
		for _, p := range parts {
			p = strings.TrimSpace(p)
			if p != "" {
				q.FilterTag = append(q.FilterTag, p)
			}
		}
	}

	// Offset
	if ov := params.Get("offset"); ov != "" {
		if v, err := strconv.Atoi(ov); err == nil && v >= 0 {
			q.Offset = v
		}
	}

	// Limit
	if lv := params.Get("limit"); lv != "" {
		if v, err := strconv.Atoi(lv); err == nil {
			if v > 500 {
				v = 500
			}
			if v < 1 {
				v = 1
			}
			q.Limit = &v
		}
	} else {
		limit := 150
		q.Limit = &limit
	}

	// Sort
	q.Sort = model.AssetSortingFromSlug(params.Get("sort"))

	return q
}

// ToHTTPGet serializes the query back to URL query parameters.
func (q *AssetQuery) ToHTTPGet() string {
	params := url.Values{}
	if len(q.FilterTag) > 0 {
		params.Set("q", strings.Join(q.FilterTag, ","))
	}
	params.Set("offset", strconv.Itoa(q.Offset))
	if q.Limit != nil {
		params.Set("limit", strconv.Itoa(*q.Limit))
	}
	params.Set("sort", string(q.Sort))
	for _, c := range q.FilterCreator {
		params.Add("creator[]", c.Slug())
	}
	for _, t := range q.FilterType {
		params.Add("type[]", t.Slug())
	}
	for _, id := range q.FilterAssetID {
		params.Add("id[]", strconv.FormatInt(id, 10))
	}
	params.Set("license", q.FilterLicenseType.Slug())
	return params.Encode()
}

// Execute runs the query and returns a StoredAssetCollection.
func (q *AssetQuery) Execute(db *sql.DB) (*model.StoredAssetCollection, error) {
	sqlStr, args := q.buildSQL(false)
	slog.Debug("Executing asset query", "sql_length", len(sqlStr), "args", len(args))

	rows, err := db.Query(sqlStr, args...)
	if err != nil {
		return nil, fmt.Errorf("asset query failed: %w", err)
	}
	defer rows.Close()

	collection := &model.StoredAssetCollection{}
	for rows.Next() {
		a, err := scanAsset(rows)
		if err != nil {
			return nil, err
		}
		collection.Assets = append(collection.Assets, a)
	}

	// Pagination: if we got exactly `limit` results, there may be more
	if q.Limit != nil && len(collection.Assets) == *q.Limit {
		nextQ := *q
		nextQ.Offset += *q.Limit
		qs := nextQ.ToHTTPGet()
		collection.NextQuery = &qs
	}

	return collection, nil
}

// ExecuteCountByCreator returns a map of creatorId -> asset count.
func (q *AssetQuery) ExecuteCountByCreator(db *sql.DB) (map[int]int, error) {
	sqlStr, args := q.buildSQL(true)
	rows, err := db.Query(sqlStr, args...)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	result := make(map[int]int)
	for rows.Next() {
		var creatorID, count int
		if err := rows.Scan(&creatorID, &count); err != nil {
			return nil, err
		}
		result[creatorID] = count
	}
	return result, nil
}

func (q *AssetQuery) buildSQL(countByCreator bool) (string, []any) {
	var sb strings.Builder
	var args []any

	if countByCreator {
		sb.WriteString("SELECT creatorId, COUNT(*) AS count FROM Asset")
	} else {
		sb.WriteString("SELECT id, url, title, state, date, clicks, lastSuccessfulValidation, typeId, creatorId, tags FROM Asset")
	}

	// Join tags
	sb.WriteString(" LEFT JOIN (SELECT id AS tid, GROUP_CONCAT(tag, ',') AS tags FROM Tag GROUP BY id) AllTags ON AllTags.tid = Asset.id")
	sb.WriteString(" WHERE 1=1")

	// Tag filter
	for _, tag := range q.FilterTag {
		sb.WriteString(" AND Asset.id IN (SELECT id FROM Tag WHERE tag = ?)")
		args = append(args, tag)
	}

	// Asset ID filter
	if len(q.FilterAssetID) > 0 {
		ph := database.GeneratePlaceholders(len(q.FilterAssetID))
		sb.WriteString(fmt.Sprintf(" AND Asset.id IN (%s)", ph))
		for _, id := range q.FilterAssetID {
			args = append(args, id)
		}
	}

	// Type filter
	if len(q.FilterType) > 0 {
		ph := database.GeneratePlaceholders(len(q.FilterType))
		sb.WriteString(fmt.Sprintf(" AND typeId IN (%s)", ph))
		for _, t := range q.FilterType {
			args = append(args, int(t))
		}
	}

	// Creator filter with license type consideration
	actualCreators := model.AllCreators()
	if !countByCreator && len(q.FilterCreator) > 0 {
		actualCreators = q.FilterCreator
	}
	// Filter by license type
	var filteredCreators []model.Creator
	for _, c := range actualCreators {
		if int(c.LicenseType()) <= int(q.FilterLicenseType) {
			filteredCreators = append(filteredCreators, c)
		}
	}
	if len(filteredCreators) > 0 {
		ph := database.GeneratePlaceholders(len(filteredCreators))
		sb.WriteString(fmt.Sprintf(" AND creatorId IN (%s)", ph))
		for _, c := range filteredCreators {
			args = append(args, int(c))
		}
	}

	// Status filter
	if q.FilterStatus != nil {
		sb.WriteString(" AND state = ?")
		args = append(args, int(*q.FilterStatus))
	}

	// Sort
	if !countByCreator {
		switch q.Sort {
		case model.SortLatest:
			sb.WriteString(" ORDER BY date DESC, Asset.id DESC")
		case model.SortOldest:
			sb.WriteString(" ORDER BY date ASC, Asset.id ASC")
		case model.SortRandom:
			sb.WriteString(" ORDER BY RANDOM()")
		case model.SortPopular:
			sb.WriteString(" ORDER BY popularityScore DESC, date DESC, Asset.id DESC")
		case model.SortLeastClicked:
			sb.WriteString(" ORDER BY clicks ASC")
		case model.SortMostClicked:
			sb.WriteString(" ORDER BY clicks DESC")
		case model.SortLeastTagged:
			sb.WriteString(" ORDER BY (SELECT COUNT(*) FROM Tag WHERE Tag.id = Asset.id) ASC")
		case model.SortMostTagged:
			sb.WriteString(" ORDER BY (SELECT COUNT(*) FROM Tag WHERE Tag.id = Asset.id) DESC")
		case model.SortLatestValidationSuccess:
			sb.WriteString(" ORDER BY lastSuccessfulValidation DESC, RANDOM()")
		case model.SortOldestValidationSuccess:
			sb.WriteString(" ORDER BY lastSuccessfulValidation ASC, RANDOM()")
		default:
			sb.WriteString(" ORDER BY date DESC, Asset.id DESC")
		}
	}

	// Limit & Offset
	if !countByCreator && q.Limit != nil {
		sb.WriteString(" LIMIT ? OFFSET ?")
		args = append(args, *q.Limit, q.Offset)
	}

	if countByCreator {
		sb.WriteString(" GROUP BY creatorId")
	}

	return sb.String(), args
}

func scanAsset(rows *sql.Rows) (*model.StoredAsset, error) {
	var (
		id                int64
		urlStr, title     string
		state, clicks     int
		dateStr           sql.NullString
		lastValidationStr sql.NullString
		typeID, creatorID int
		tagsStr           sql.NullString
	)

	if err := rows.Scan(&id, &urlStr, &title, &state, &dateStr, &clicks, &lastValidationStr, &typeID, &creatorID, &tagsStr); err != nil {
		return nil, fmt.Errorf("scan asset: %w", err)
	}

	date := time.Now()
	if dateStr.Valid && dateStr.String != "" {
		if t, err := time.Parse("2006-01-02 15:04:05", dateStr.String); err == nil {
			date = t
		} else if t, err := time.Parse(time.RFC3339, dateStr.String); err == nil {
			date = t
		}
	}

	var lastValidation *time.Time
	if lastValidationStr.Valid && lastValidationStr.String != "" {
		if t, err := time.Parse("2006-01-02 15:04:05", lastValidationStr.String); err == nil {
			lastValidation = &t
		}
	}

	var tags []string
	if tagsStr.Valid && tagsStr.String != "" {
		for _, t := range strings.Split(tagsStr.String, ",") {
			t = strings.TrimSpace(t)
			if t != "" {
				tags = append(tags, t)
			}
		}
	}

	creator, _ := model.CreatorFromValue(creatorID)

	return &model.StoredAsset{
		ID:                       id,
		Title:                    title,
		URL:                      urlStr,
		Date:                     date,
		Type:                     model.AssetType(typeID),
		Creator:                  creator,
		Tags:                     tags,
		Status:                   model.StoredAssetStatus(state),
		Clicks:                   clicks,
		LastSuccessfulValidation: lastValidation,
	}, nil
}

// AssetCountTotal returns the total number of assets.
func AssetCountTotal(db *sql.DB) (int, error) {
	var count int
	err := db.QueryRow("SELECT COUNT(*) FROM Asset").Scan(&count)
	return count, err
}

// AssetCountByCreator returns asset counts grouped by creator ID.
func AssetCountByCreator(db *sql.DB) (map[int]int, error) {
	rows, err := db.Query("SELECT creatorId, COUNT(*) AS count FROM Asset GROUP BY creatorId")
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	result := make(map[int]int)
	for rows.Next() {
		var cid, count int
		if err := rows.Scan(&cid, &count); err != nil {
			return nil, err
		}
		result[cid] = count
	}
	return result, nil
}

// WriteAsset inserts or updates an asset in the database.
func WriteAsset(db *sql.DB, a *model.StoredAsset) (int64, error) {
	if a.ID > 0 {
		// Update
		_, err := db.Exec(
			"UPDATE Asset SET creatorGivenId=?, title=?, state=?, url=?, date=?, typeId=?, creatorId=?, lastSuccessfulValidation=? WHERE id=?",
			a.CreatorGivenID, a.Title, int(a.Status), a.URL,
			a.Date.Format("2006-01-02 15:04:05"),
			int(a.Type), int(a.Creator), formatNullableTime(a.LastSuccessfulValidation),
			a.ID,
		)
		if err != nil {
			return a.ID, err
		}
		// Replace tags
		if _, err := db.Exec("DELETE FROM Tag WHERE id = ?", a.ID); err != nil {
			return a.ID, err
		}
		for _, tag := range a.Tags {
			if _, err := db.Exec("INSERT INTO Tag (id, tag) VALUES (?, ?)", a.ID, tag); err != nil {
				return a.ID, err
			}
		}
		return a.ID, nil
	}

	// Insert
	result, err := db.Exec(
		"INSERT INTO Asset (id, creatorGivenId, state, title, url, date, clicks, typeId, creatorId) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?)",
		a.CreatorGivenID, int(a.Status), a.Title, a.URL,
		a.Date.Format("2006-01-02 15:04:05"),
		0, int(a.Type), int(a.Creator),
	)
	if err != nil {
		return 0, err
	}
	id, err := result.LastInsertId()
	if err != nil {
		// Fallback: query by URL
		err = db.QueryRow("SELECT id FROM Asset WHERE url = ?", a.URL).Scan(&id)
		if err != nil {
			return 0, fmt.Errorf("failed to get inserted asset ID: %w", err)
		}
	}
	a.ID = id

	// Insert tags
	for _, tag := range a.Tags {
		if _, err := db.Exec("INSERT INTO Tag (id, tag) VALUES (?, ?)", id, tag); err != nil {
			return id, err
		}
	}

	return id, nil
}

func formatNullableTime(t *time.Time) any {
	if t == nil {
		return nil
	}
	return t.Format("2006-01-02 15:04:05")
}
