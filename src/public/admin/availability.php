<?php

use blocks\AdminHeaderBlock;
use creator\Creator;
use database\Database;
use misc\Auth;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../include/init.php';

Auth::requireAuth();

header('Cache-Control: no-store');

$availabilityData = [];
$sql = "SELECT creatorId,lastChecked,lastAvailable,failedAttempts FROM CreatorAvailability ORDER BY failedAttempts DESC;";
$result = Database::runQuery($sql);

if (is_bool($result)) {
	echo "<p>Error retrieving availability data.</p>";
	exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Creator availability</title>
	<link rel="stylesheet" href="/css/page/availability.css">
</head>

<body>
	<?php AdminHeaderBlock::render(); ?>

	<table>
		<tr>
			<th>Creator</th>
			<th>Last Checked</th>
			<th>Last Available</th>
			<th>Failed Attempts</th>
		</tr>
		<?php while ($row = $result->fetchArray(SQLITE3_ASSOC)) :
		?>
			<tr>
				<td><?= htmlspecialchars(Creator::from(intval($row['creatorId']))->title()) ?></td>
				<td><?= htmlspecialchars($row['lastChecked']) ?></td>
				<td><?= htmlspecialchars($row['lastAvailable']) ?></td>
				<td><?= htmlspecialchars($row['failedAttempts']) ?></td>
			</tr>
		<?php endwhile; ?>
	</table>


</body>

</html>