<?php

namespace log;

enum LogLevel: int
{
	case DEBUG = 1;
	case INFO = 2;
	case WARNING = 3;
	case ERROR = 4;
	case FINISH_OK = 5;
	case FINISH_FAILED = 6;
}
