<?php

namespace asset;

enum Sorting: string
{
	case POPULAR = "popular";
	case LATEST = "latest";
	case OLDEST = "oldest";
	case RANDOM = "random";
	case MOST_CLICKED = "most-clicked";
	case LEAST_CLICKED = "least-clicked";
	case MOST_TAGGED = "most-tagged";
	case LEAST_TAGGED = "least-tagged";
	case OLDEST_VALIDATION_SUCCESS = "oldest-validation-success";
	case LATEST_VALIDATION_SUCCESS = "latest-validation-success";

	/**
	 * Returns the enum value for the string. 
	 * Every other/invalid string gets turned into SORTING::LATEST.
	 */
	public static function fromAnyString(string $string): SORTING
	{
		if (in_array($string, array_column(SORTING::cases(), 'value'))) {
			return SORTING::from($string);
		} else {
			return SORTING::LATEST;
		}
	}
}
