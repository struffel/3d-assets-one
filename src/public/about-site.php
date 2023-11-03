<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/../include/init.php';
?>
<!DOCTYPE html>
<html lang="en">
	<?php include $_SERVER['DOCUMENT_ROOT'].'/../components/head.php'; ?>
	<body>
		<link rel="stylesheet" href="/css/page/about-site.css">
		<?php include $_SERVER['DOCUMENT_ROOT'].'/../components/header.php'; ?>
		<main>
			<h2>What is 3Dassets.one?</h2>
			<p>
				3Dassets.one is a search engine that combines the libraries of many independent CG asset websites like Poly Haven, ambientCG and others and allows you to look for the right texture, HDRI or model without having to open half a dozen browser tabs.
			</p>
			<p>
				The site automatically polls the websites of all creators for new assets and automatically lists them. You can filter the results by creator or using keywords, categories and licenses. When clicking on an asset you will be redirected to the creators website.
				3Dassets.one does not copy, archive or re-upload the assets.
			</p>
			<h2>Can I get my assets listed?</h2>
			<p>
				Yes, you can submit your assets, but there are a few requirements:
				<ul>
					<li><strong>Your collection must already be available on the internet.</strong>
					You don't need to have your own website with a custom domain, a page on Gumroad, Sketchfab or other platforms works as well.</li>
					<li><strong>The assets must be available for free.</strong>
					There are no precise restrictions regarding the exact license, but 3Dassets.one currently does not list paid models.</li>
					<li><strong>Your collection should have at least ~20 assets.</strong></li>
					<li><strong>Every asset must be adressable individually using a unique URL.</strong>
					The URL should ideally point to a dedicated page for just this asset:
						<pre>https://example.com/assets/bricks01</pre>
					But it can also use anchor tags:
						<pre>https://example.com/assets#bricks01</pre>
					This rule means that asset packs (which bundle multiple materials/models/HDRIs in one download) cannot be supported.<br>
					</li>
				</ul>
				If your asset collection fulfills these criterias you can write to <strong>info [at] 3dassets.one</strong> to discuss an integration. 
			</p>

		</main>
		<?php include $_SERVER['DOCUMENT_ROOT'].'/../components/footer.php'; ?>
	</body>
</html>

