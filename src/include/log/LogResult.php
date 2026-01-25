<?php

namespace log;

enum LogResult: string
{
	case OK = 'ok';
	case ERR = 'err';
	case RUN = 'run';
}
