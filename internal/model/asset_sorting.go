package model

// AssetSorting defines how query results are ordered.
type AssetSorting string

const (
	SortPopular                 AssetSorting = "popular"
	SortLatest                  AssetSorting = "latest"
	SortOldest                  AssetSorting = "oldest"
	SortRandom                  AssetSorting = "random"
	SortMostClicked             AssetSorting = "most-clicked"
	SortLeastClicked            AssetSorting = "least-clicked"
	SortMostTagged              AssetSorting = "most-tagged"
	SortLeastTagged             AssetSorting = "least-tagged"
	SortOldestValidationSuccess AssetSorting = "oldest-validation-success"
	SortLatestValidationSuccess AssetSorting = "latest-validation-success"
)

var allSortings = []AssetSorting{
	SortPopular, SortLatest, SortOldest, SortRandom,
	SortMostClicked, SortLeastClicked,
	SortMostTagged, SortLeastTagged,
	SortOldestValidationSuccess, SortLatestValidationSuccess,
}

var publicSortings = []AssetSorting{
	SortPopular, SortLatest, SortOldest, SortRandom,
}

func AllSortings() []AssetSorting    { return allSortings }
func PublicSortings() []AssetSorting { return publicSortings }

func (s AssetSorting) Slug() string {
	return string(s)
}

func AssetSortingFromSlug(slug string) AssetSorting {
	for _, s := range allSortings {
		if string(s) == slug {
			return s
		}
	}
	return SortLatest
}
