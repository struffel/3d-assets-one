package scraper

import (
	"testing"
)

// --- FilterTagArray ---

func TestFilterTagArrayBasic(t *testing.T) {
	result := FilterTagArray([]string{"Wood", "Metal", "Stone"})
	assertContainsAll(t, result, []string{"wood", "metal", "stone"})
}

func TestFilterTagArrayTrimsAndLowercases(t *testing.T) {
	result := FilterTagArray([]string{"  BRICK  ", " Tile "})
	assertContainsAll(t, result, []string{"brick", "tile"})
}

func TestFilterTagArraySplitsBySpace(t *testing.T) {
	result := FilterTagArray([]string{"red brick", "blue stone"})
	assertContainsAll(t, result, []string{"red", "brick", "blue", "stone"})
}

func TestFilterTagArrayRemovesSpecialCharacters(t *testing.T) {
	result := FilterTagArray([]string{"wood!@#", "metal$%^"})
	assertContainsAll(t, result, []string{"wood", "metal"})
}

func TestFilterTagArrayRemovesDuplicates(t *testing.T) {
	result := FilterTagArray([]string{"wood", "Wood", "WOOD"})
	if len(result) != 1 || result[0] != "wood" {
		t.Errorf("expected [wood], got %v", result)
	}
}

func TestFilterTagArraySkipsEmptyElements(t *testing.T) {
	result := FilterTagArray([]string{"", "   ", "!!!"})
	if len(result) != 0 {
		t.Errorf("expected empty, got %v", result)
	}
}

func TestFilterTagArrayHandlesEmptyInput(t *testing.T) {
	result := FilterTagArray([]string{})
	if len(result) != 0 {
		t.Errorf("expected empty, got %v", result)
	}
}

// --- OnlySmallLetters ---

func TestOnlySmallLettersBasic(t *testing.T) {
	assertStr(t, OnlySmallLetters("Hello World!"), "helloworld")
}

func TestOnlySmallLettersRemovesNumbers(t *testing.T) {
	assertStr(t, OnlySmallLetters("a1b2c3"), "abc")
}

func TestOnlySmallLettersReturnsEmptyForNoLetters(t *testing.T) {
	assertStr(t, OnlySmallLetters("12345!@#"), "")
}

func TestOnlySmallLettersPreservesLowercase(t *testing.T) {
	assertStr(t, OnlySmallLetters("alreadylower"), "alreadylower")
}

// --- OnlyNumbers ---

func TestOnlyNumbersBasic(t *testing.T) {
	assertStr(t, OnlyNumbers("abc123def"), "123")
}

func TestOnlyNumbersReturnsEmptyForNoDigits(t *testing.T) {
	assertStr(t, OnlyNumbers("abcdef"), "")
}

func TestOnlyNumbersPreservesAllDigits(t *testing.T) {
	assertStr(t, OnlyNumbers("9876543210"), "9876543210")
}

func TestOnlyNumbersStripsSpacesAndSpecials(t *testing.T) {
	assertStr(t, OnlyNumbers(" 4 2 "), "42")
}

// --- RemoveNewlines ---

func TestRemoveNewlinesLineFeed(t *testing.T) {
	assertStr(t, RemoveNewlines("hello\n world"), "hello world")
}

func TestRemoveNewlinesCarriageReturn(t *testing.T) {
	assertStr(t, RemoveNewlines("hello\r world"), "hello world")
}

func TestRemoveNewlinesCRLF(t *testing.T) {
	// \r\n is replaced first, then individual \n and \r
	result := RemoveNewlines("hello\n\r world")
	assertStr(t, result, "hello world")
}

func TestRemoveNewlinesNoNewlines(t *testing.T) {
	assertStr(t, RemoveNewlines("hello world"), "hello world")
}

// --- ExplodeFilterTrim ---

func TestExplodeFilterTrimBasic(t *testing.T) {
	result := ExplodeFilterTrim(",", "a, b, c")
	assertStrSlice(t, result, []string{"a", "b", "c"})
}

func TestExplodeFilterTrimRemovesEmptyEntries(t *testing.T) {
	result := ExplodeFilterTrim(",", "a,,b,,,c")
	assertStrSlice(t, result, []string{"a", "b", "c"})
}

func TestExplodeFilterTrimTrimsWhitespace(t *testing.T) {
	result := ExplodeFilterTrim(",", "  alpha  ,  beta  ,  gamma  ")
	assertStrSlice(t, result, []string{"alpha", "beta", "gamma"})
}

func TestExplodeFilterTrimEmptyString(t *testing.T) {
	result := ExplodeFilterTrim(",", "")
	if len(result) != 0 {
		t.Errorf("expected empty, got %v", result)
	}
}

func TestExplodeFilterTrimEmptySeparator(t *testing.T) {
	result := ExplodeFilterTrim("", "a,b,c")
	if result != nil {
		t.Errorf("expected nil for empty separator, got %v", result)
	}
}

func TestExplodeFilterTrimCustomSeparator(t *testing.T) {
	result := ExplodeFilterTrim("|", "x | y | z")
	assertStrSlice(t, result, []string{"x", "y", "z"})
}

// --- AddHTTPParameters ---

func TestAddHTTPParametersToURL(t *testing.T) {
	result, err := AddHTTPParameters("https://example.com/path", map[string]string{"key": "value"})
	if err != nil {
		t.Fatal(err)
	}
	assertContains(t, result, "key=value")
	assertContains(t, result, "https://example.com")
}

func TestAddHTTPParametersMergesWithExisting(t *testing.T) {
	result, err := AddHTTPParameters("https://example.com/path?existing=1", map[string]string{"new": "2"})
	if err != nil {
		t.Fatal(err)
	}
	assertContains(t, result, "existing=1")
	assertContains(t, result, "new=2")
}

func TestAddHTTPParametersOverridesExisting(t *testing.T) {
	result, err := AddHTTPParameters("https://example.com/path?key=old", map[string]string{"key": "new"})
	if err != nil {
		t.Fatal(err)
	}
	assertContains(t, result, "key=new")
	assertNotContains(t, result, "key=old")
}

func TestAddHTTPParametersMultipleParams(t *testing.T) {
	result, err := AddHTTPParameters("https://example.com/", map[string]string{"a": "1", "b": "2"})
	if err != nil {
		t.Fatal(err)
	}
	assertContains(t, result, "a=1")
	assertContains(t, result, "b=2")
}

func TestAddHTTPParametersPathOnly(t *testing.T) {
	result, err := AddHTTPParameters("/path/only", map[string]string{"key": "value"})
	if err != nil {
		t.Fatal(err)
	}
	assertContains(t, result, "key=value")
	assertContains(t, result, "/path/only")
}

// --- Helpers ---

func assertStr(t *testing.T, got, want string) {
	t.Helper()
	if got != want {
		t.Errorf("got %q, want %q", got, want)
	}
}

func assertStrSlice(t *testing.T, got, want []string) {
	t.Helper()
	if len(got) != len(want) {
		t.Errorf("got %v (len %d), want %v (len %d)", got, len(got), want, len(want))
		return
	}
	for i := range want {
		if got[i] != want[i] {
			t.Errorf("index %d: got %q, want %q", i, got[i], want[i])
		}
	}
}

func assertContainsAll(t *testing.T, got []string, want []string) {
	t.Helper()
	m := make(map[string]bool)
	for _, s := range got {
		m[s] = true
	}
	for _, w := range want {
		if !m[w] {
			t.Errorf("expected %q in %v", w, got)
		}
	}
}

func assertContains(t *testing.T, s, substr string) {
	t.Helper()
	if !contains(s, substr) {
		t.Errorf("expected %q to contain %q", s, substr)
	}
}

func assertNotContains(t *testing.T, s, substr string) {
	t.Helper()
	if contains(s, substr) {
		t.Errorf("expected %q NOT to contain %q", s, substr)
	}
}

func contains(s, substr string) bool {
	return len(s) >= len(substr) && searchStr(s, substr)
}

func searchStr(s, sub string) bool {
	for i := 0; i <= len(s)-len(sub); i++ {
		if s[i:i+len(sub)] == sub {
			return true
		}
	}
	return false
}
