package storage

import (
	"bytes"
	"context"
	"fmt"
	"path"
	"strings"
	"time"

	"github.com/jlaffaye/ftp"
)

// FTPStorage implements ObjectStorage using FTP (Bunny Storage).
type FTPStorage struct {
	host       string
	user       string
	password   string
	cdnBaseURL string
}

// NewFTPStorage creates an FTP-backed ObjectStorage.
func NewFTPStorage(host, zone, password, cdnBaseURL string) *FTPStorage {
	return &FTPStorage{
		host:       host,
		user:       zone,
		password:   password,
		cdnBaseURL: strings.TrimRight(cdnBaseURL, "/"),
	}
}

func (s *FTPStorage) dial() (*ftp.ServerConn, error) {
	conn, err := ftp.Dial(s.host+":21", ftp.DialWithTimeout(30*time.Second))
	if err != nil {
		return nil, fmt.Errorf("ftp dial: %w", err)
	}
	if err := conn.Login(s.user, s.password); err != nil {
		conn.Quit()
		return nil, fmt.Errorf("ftp login: %w", err)
	}
	return conn, nil
}

func (s *FTPStorage) Upload(ctx context.Context, remotePath string, data []byte, contentType string) error {
	conn, err := s.dial()
	if err != nil {
		return err
	}
	defer conn.Quit()

	// Ensure parent directories exist
	dir := path.Dir(remotePath)
	if dir != "" && dir != "." && dir != "/" {
		s.mkdirAll(conn, dir)
	}

	return conn.Stor(remotePath, bytes.NewReader(data))
}

func (s *FTPStorage) Delete(ctx context.Context, remotePath string) error {
	conn, err := s.dial()
	if err != nil {
		return err
	}
	defer conn.Quit()

	return conn.Delete(remotePath)
}

func (s *FTPStorage) PublicURL(remotePath string) string {
	return s.cdnBaseURL + "/" + strings.TrimLeft(remotePath, "/")
}

// mkdirAll creates directory and parents, ignoring "already exists" errors.
func (s *FTPStorage) mkdirAll(conn *ftp.ServerConn, dir string) {
	parts := strings.Split(strings.Trim(dir, "/"), "/")
	current := ""
	for _, p := range parts {
		if current == "" {
			current = p
		} else {
			current = current + "/" + p
		}
		conn.MakeDir(current) // ignore errors (dir may already exist)
	}
}
