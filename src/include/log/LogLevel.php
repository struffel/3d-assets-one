<?php

namespace log;

enum LogLevel: int
{
	case DEBUG = 1;
	case INFO = 2;
	case WARNING = 3;
	case ERROR = 4;
	case EXCEPTION = 5;
	case SYSTEM = 6;

	public static function fromName(?string $name): ?LogLevel
	{
		return match (strtoupper($name ?? '')) {
			'DEBUG' => LogLevel::DEBUG,
			'INFO' => LogLevel::INFO,
			'WARNING' => LogLevel::WARNING,
			'ERROR' => LogLevel::ERROR,
			'EXCEPTION' => LogLevel::EXCEPTION,
			'SYSTEM' => LogLevel::SYSTEM,
			default => null,
		};
	}
}
