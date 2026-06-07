from app.misc.string_util import StringUtil


def test_filter_tag_array_empty():
    assert StringUtil.filter_tag_array([]) == []


def test_filter_tag_array_cleans():
    result = StringUtil.filter_tag_array(["Hello World", "  spaced  ", "123abc", "ab"])
    assert all(t == t.lower() for t in result)
    # short/empty tags are dropped
    assert "ab" not in result


def test_only_small_letters():
    assert StringUtil.only_small_letters("Hello World 123!") == "helloworld"


def test_only_numbers():
    assert StringUtil.only_numbers("abc 123 def 456") == "123456"


def test_remove_newline():
    assert StringUtil.remove_newline("line1\nline2\r\nline3") == "line1 line2  line3"


def test_explode_filter_trim():
    result = StringUtil.explode_filter_trim(",", "a, b,, c ,")
    assert result == ["a", "b", "c"]


def test_add_http_parameters():
    url = StringUtil.add_http_parameters("https://example.com/page", {"q": "metal", "p": "1"})
    assert "q=metal" in url
    assert "p=1" in url
    assert url.startswith("https://example.com/page?")
