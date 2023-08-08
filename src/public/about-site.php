<!DOCTYPE html>
<html lang="en">
    <?php
        include $_SERVER['DOCUMENT_ROOT']."/../components/head.php";
    ?>
    <body>
        <?php include $_SERVER['DOCUMENT_ROOT']."/../components/header.php";?>
        <main id="aboutText" class="content container">
            
            <h2>What is 3Dassets.one?</h2>
            <p>
                3Dassets.one is a search engine that combines the libraries of many independent CG asset websites like Poly Haven, ambientCG and others and allows you to look for the right texture, HDRI or model without having to open half a dozen browser tabs.
            </p>

            <h2>How does this site work?</h2>
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
                    If your asset collection fulfills these criterias you can write to <strong>info [at] 3dassets.one</strong> to discuss an integration. 
                    </li>
                </ul>
            </p>

			<h2>Can I use the 3dassets.one-API for something else?</h2>
			<p>
				<strong>Generally, yes.</strong> I'll try to keep it as stable as possible, but I can't give any guarantees.
			</p>
			<p>
				<a href="/api/v1/getCreators">/api/v1/getCreators</a> lists all creators. It does not accept any filters.
			</p>
			<p>
				<a href="/api/v1/getAssets">/api/v1/getAssets</a> lists assets. You can apply the following filters:
				<ul>
					<li><strong>?asset=id,id,...</strong> loads information about one or several specific assets.</li>
					<li><strong>?tags=tag,tag,...</strong> loads information about all assets with <i>all</i> the given tags. Spaces in the input string will also be interpreted as commas.</li>
					<li><strong>?asset=licenseSlug,licenseSlug,...</strong> loads information about all assets that have one of the licenses listed. License slugs are: <pre>cc-0
cc-by
cc-by-sa
cc-by-nc
cc-by-nd
cc-by-nc-sa
cc-by-nc-nd
apache-2-0
</pre></li>
					<li><strong>?type=typeSlug,typeSlug,...</strong> loads information about all assets that have one of the types listed. Type slugs are: <pre>
other
pbr-material
3d-model
sbsar
hdri
brush
</pre></li>
					<li><strong>?creator=creatorSlug,creatorSlug,...</strong> loads information about all assets that were made by the creators listed. Creator slugs are: <pre>
ambientcg
polyhaven
sharetextures
3dtextures
cgbookcase
texturecan
noemotionhdrs
benianus3d
chocofur
gpuopen-matlib
hdri-workshop
pbrmaterials-com
</pre></li>
					<li><strong>?sort=order</strong> defines the sorting order. Possible Options are: <pre>
latest
oldest
random
</pre></li>
					<li><strong>?limit=X&offset=Y</strong> allows for pagination. The default limit is 100 items.</li>
					<li><strong>?include=attribute,attribute,...</strong> defines which attributes are included in the JSON output. Only assetId, assetName, url and date are included by default. Possible other attributes are:<pre>
tag
creator
license
type
</pre>
				</ul>
			</p>

            <h2>Who is building this website?</h2>
            <p>
                3Dassets.one is a side project to ambientCG.com. Check the <a href="https://docs.ambientCG.com/legal">Imprint</a> for details.
            </p>

        </main>
    </body>
</html>