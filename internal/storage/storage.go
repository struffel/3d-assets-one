package storage

import "context"

// ObjectStorage abstracts file storage operations.
// Currently backed by FTP
// TODO: swap to S3-compatible storage when bunny supports that.
type ObjectStorage interface {
	// Upload stores data at the given path with the specified content type.
	Upload(ctx context.Context, path string, data []byte, contentType string) error

	// Delete removes the object at the given path.
	Delete(ctx context.Context, path string) error
}
