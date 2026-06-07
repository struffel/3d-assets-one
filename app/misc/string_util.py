import re


class StringUtil:
    @staticmethod
    def filter_tag_array(input_array: list[str]) -> list[str]:
        result: list[str] = []
        for element in input_array:
            filtered = element.strip().lower()
            split_elements = re.split(r"\s+", filtered)
            for split_el in split_elements:
                cleaned = re.sub(r"[^a-z0-9]", "", split_el)
                if cleaned:
                    result.append(cleaned)
        return list(dict.fromkeys(result))  # unique, preserving order

    @staticmethod
    def only_small_letters(input_str: str) -> str:
        return re.sub(r"[^a-z]", "", input_str.lower())

    @staticmethod
    def only_numbers(input_str: str) -> str:
        return re.sub(r"[^0-9]", "", input_str)

    @staticmethod
    def remove_newline(input_str: str) -> str:
        return input_str.replace("\n\r", "").replace("\n", "").replace("\r", "")

    @staticmethod
    def explode_filter_trim(separator: str, string: str) -> list[str]:
        if not separator:
            raise ValueError("Separator string cannot be empty")
        return [p.strip() for p in string.split(separator) if p.strip()]

    @staticmethod
    def add_http_parameters(url: str, new_parameters: dict[str, str]) -> str:
        from urllib.parse import urlparse, urlencode, parse_qs, urlunparse
        parts = urlparse(url)
        existing = parse_qs(parts.query, keep_blank_values=True)
        # Flatten existing params
        flat_existing = {k: v[0] if len(v) == 1 else v for k, v in existing.items()}
        merged = {**flat_existing, **new_parameters}
        new_query = urlencode(merged, doseq=True)
        return urlunparse(parts._replace(query=new_query))
