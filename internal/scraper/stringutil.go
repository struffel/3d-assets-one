package scraper

import (
	"regexp"
	"strings"
)

// FilterTagArray processes raw tag strings into a deduplicated, cleaned list.
// Port of PHP StringUtil::filterTagArray.
func FilterTagArray(input []string) []string {
	seen := make(map[string]bool)
	var result []string
	wsRe := regexp.MustCompile(`\s+`)
	nonAlpha := regexp.MustCompile(`[^a-z0-9]`)

	for _, s := range input {
		s = strings.ToLower(strings.TrimSpace(s))
		parts := wsRe.Split(s, -1)
		for _, p := range parts {
			cleaned := nonAlpha.ReplaceAllString(p, "")
			if cleaned != "" && !seen[cleaned] {
				seen[cleaned] = true
				result = append(result, cleaned)
			}
		}
	}
	return result
}

// ExplodeFilterTrim splits a string by separator, trims each part, and removes empty entries.
// Port of PHP StringUtil::explodeFilterTrim.
func ExplodeFilterTrim(sep, s string) []string {
	if sep == "" {
		return nil
	}
	parts := strings.Split(s, sep)
	var result []string
	for _, p := range parts {
		p = strings.TrimSpace(p)
		if p != "" {
			result = append(result, p)
		}
	}
	return result
}

// OnlySmallLetters keeps only lowercase a-z characters.
func OnlySmallLetters(s string) string {
	s = strings.ToLower(s)
	return regexp.MustCompile(`[^a-z]`).ReplaceAllString(s, "")
}

// OnlyNumbers keeps only 0-9 characters.
func OnlyNumbers(s string) string {
	return regexp.MustCompile(`[^0-9]`).ReplaceAllString(s, "")
}

// RemoveNewlines strips \n, \r, and \r\n from a string.
func RemoveNewlines(s string) string {
	s = strings.ReplaceAll(s, "\r\n", "")
	s = strings.ReplaceAll(s, "\n", "")
	s = strings.ReplaceAll(s, "\r", "")
	return s
}
