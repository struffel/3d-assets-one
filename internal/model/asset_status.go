package model

// StoredAssetStatus represents the lifecycle state of an asset in the database.
type StoredAssetStatus int

const (
	StatusManuallyBlocked          StoredAssetStatus = -1
	StatusPending                  StoredAssetStatus = 0
	StatusActive                   StoredAssetStatus = 1
	StatusValidationFailedRecently StoredAssetStatus = -2
	StatusValidationFailedPerm     StoredAssetStatus = -3
)

var allStoredStatuses = []StoredAssetStatus{
	StatusManuallyBlocked,
	StatusPending,
	StatusActive,
	StatusValidationFailedRecently,
	StatusValidationFailedPerm,
}

func AllStoredAssetStatuses() []StoredAssetStatus { return allStoredStatuses }

func (s StoredAssetStatus) Name() string {
	switch s {
	case StatusManuallyBlocked:
		return "MANUALLY_BLOCKED"
	case StatusPending:
		return "PENDING"
	case StatusActive:
		return "ACTIVE"
	case StatusValidationFailedRecently:
		return "VALIDATION_FAILED_RECENTLY"
	case StatusValidationFailedPerm:
		return "VALIDATION_FAILED_PERMANENTLY"
	default:
		return "UNKNOWN"
	}
}

func StoredAssetStatusTryFrom(v int) (StoredAssetStatus, bool) {
	for _, s := range allStoredStatuses {
		if int(s) == v {
			return s, true
		}
	}
	return 0, false
}

// ScrapedAssetStatus represents the state of a scraped asset before persistence.
type ScrapedAssetStatus int

const (
	ScrapedNewlyFound       ScrapedAssetStatus = 0
	ScrapedUpdated          ScrapedAssetStatus = 1
	ScrapedValidated        ScrapedAssetStatus = 2
	ScrapedNewlyFoundFailed ScrapedAssetStatus = 100
	ScrapedUpdatedFailed    ScrapedAssetStatus = 101
	ScrapedValidatedFailed  ScrapedAssetStatus = 102
)

func (s ScrapedAssetStatus) ToStoredAssetStatus() StoredAssetStatus {
	switch s {
	case ScrapedNewlyFound, ScrapedUpdated, ScrapedValidated:
		return StatusActive
	case ScrapedNewlyFoundFailed, ScrapedUpdatedFailed, ScrapedValidatedFailed:
		return StatusValidationFailedRecently
	default:
		return StatusValidationFailedRecently
	}
}
