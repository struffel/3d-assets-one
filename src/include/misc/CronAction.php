<?php

namespace misc;

enum CronAction: string
{
	case REFRESH = "refresh";
	case ACTIVATE = "activate";
	case VALIDATE = "validate";
}
