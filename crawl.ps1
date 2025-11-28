param(
	[Parameter(Mandatory = $true, Position = 0)]
	[string]$startUrl,

	[Parameter(Mandatory = $true, Position = 1)]
	[string]$urlMatchingRegex,

	[Parameter(Mandatory = $true, Position = 2)]
	[string]$outputFile,

	[Parameter(Mandatory = $false, Position = 3)]
	[int]$maxDepth = 3
)

# Use HashSet for efficient lookups and to store unique URLs
$visitedUrls = [System.Collections.Generic.HashSet[string]]::new([System.StringComparer]::OrdinalIgnoreCase)
$matchingUrls = [System.Collections.Generic.HashSet[string]]::new([System.StringComparer]::OrdinalIgnoreCase)
$queue = [System.Collections.Generic.Queue[object]]::new()

try {
	$startUri = [System.Uri]$startUrl
}
catch {
	Write-Error "Invalid start URL: $startUrl"
	exit 1
}

# Enqueue the starting URL with depth 0
$queue.Enqueue(@{ Url = $startUri; Depth = 0 })

Write-Host "Starting crawl at $startUrl with max depth $maxDepth"

while ($queue.Count -gt 0) {
	$currentItem = $queue.Dequeue()
	$currentUrl = $currentItem.Url
	$currentDepth = $currentItem.Depth

	if ($visitedUrls.Contains($currentUrl.AbsoluteUri)) {
		continue
	}

	if ($currentDepth -gt $maxDepth) {
		Write-Verbose "Max depth reached for $($currentUrl.AbsoluteUri)"
		continue
	}

	$visitedUrls.Add($currentUrl.AbsoluteUri)
	Write-Verbose "Crawling $($currentUrl.AbsoluteUri) at depth $currentDepth"

	try {
		$response = Invoke-WebRequest -Uri $currentUrl -UseBasicParsing -ErrorAction Stop
	}
	catch {
		Write-Warning "Failed to retrieve $($currentUrl.AbsoluteUri): $_"
		continue
	}

	# Find all links on the page
	foreach ($link in $response.Links) {
		try {
			# Resolve relative URLs to absolute URLs
			$absoluteUri = [System.Uri]::new($currentUrl, $link.href)

			# Only crawl links within the same domain
			if ($absoluteUri.Host -ne $startUri.Host) {
				continue
			}

			# Check if the URL matches the regex
			if ($absoluteUri.AbsoluteUri -match $urlMatchingRegex) {
				if ($matchingUrls.Add($absoluteUri.AbsoluteUri)) {
					Write-Host "Found matching URL: $($absoluteUri.AbsoluteUri)"
				}
			}

			# If not visited and not an image or similar file, add to the queue for crawling
			if (-not $visitedUrls.Contains($absoluteUri.AbsoluteUri) -and $absoluteUri.AbsoluteUri.EndsWith("/")) {
				$queue.Enqueue(@{ Url = $absoluteUri; Depth = $currentDepth + 1 })
			}
		}
		catch {
			Write-Warning "Could not process link '$($link.href)' on page $($currentUrl.AbsoluteUri): $_"
		}
	}

	# Save the results to the output file
	try {
		$matchingUrls | Sort-Object | Out-File -FilePath $outputFile -Encoding utf8 -ErrorAction Stop
		Write-Host "Results saved to $outputFile"
	}
	catch {
		Write-Error "Failed to write to output file $outputFile : $_"
		exit 1
	}
}

Write-Host "Crawl finished. Found $($matchingUrls.Count) matching URLs."



