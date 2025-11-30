<?php

namespace log;

enum LogLevel: int
{
	case DEBUG = 1;
	case INFO = 2;
	case WARNING = 3;
	case ERROR = 4;
	case EXCEPTION = 5;

	public function displayName(): string
	{
		return match ($this) {
			LogLevel::DEBUG => "DEBUG",
			LogLevel::INFO => "INFO",
			LogLevel::WARNING => "WARNING",
			LogLevel::ERROR => "ERROR",
			LogLevel::EXCEPTION => "EXCEPTION",
		};
	}
}
