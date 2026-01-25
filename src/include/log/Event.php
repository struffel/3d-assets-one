<?php

namespace log;

use asset\Asset;
use creator\Creator;
use DateTime;

class Event
{
	public function __construct(
		public int $id,
		public EventType $type,
		public DateTime $time,
		public ?Asset $affectedAsset,
		public ?Creator $affectedCreator
	) {}
}
