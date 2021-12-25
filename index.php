<!DOCTYPE html>
<html lang="en">
	<?php include $_SERVER['DOCUMENT_ROOT']."/components/head.php";?>
    <body class="has-navbar-fixed-bottom">
        <div id="assetList" >
			<?php include $_SERVER['DOCUMENT_ROOT']."/components/header.php";?>

			<nav class="navbar p-3 has-shadow has-background-white-ter is-bordered is-fixed-bottom">
				<div id="mainForm" class="is-flex is-size-7 navbar-start">
					<div class="select mx-1 is-multiple">
						<select multiple title="Crtl-click to select multiple options." size="4"  v-model="creator" >
							<option selected="selected" value="">Any creator</option>
							<option value="ambientcg">ambientCG</option>
							<option value="polyhaven">Poly Haven</option>
							<option value="cgbookcase">CG Bookcase</option>
							<option value="sharetextures">Share Textures</option>
							<option value="3dtextures">3D Textures</option>
							<option value="texturecan">TextureCan</option>
							<option value="noemotionhdrs">NoEmotion HDRs</option>
							<option value="benianus3d">Benianus 3D</option>
							<option value="chocofur">Chocofur (Freebies)</option>
							<option value="gpuopen-matlib">AMD GPUOpen MaterialX Library</option>
						</select>
					</div>
					<div class="select mx-1 is-multiple">
						<select multiple title="Ctrl-click to select multiple options." size="4" v-model="type" >
							<option selected="selected" value="">Any type</option>
							<option value="hdri">HDRI</option>
							<option value="pbr-material">PBR Material</option>
							<option value="3d-model">3D Model</option>
							<option value="sbsar">SBSAR</option>
						</select>
					</div>
					<div class="select mx-1 is-multiple">
						<select multiple title="Ctrl-click to select multiple options." size="4" v-model="license" >
							<option selected="selected" value="">Any license</option>
							<option value="cc-0">Creative Commons CC0</option>
							<option value="cc-by-nd">Creative Commons BY ND</option>
							<option value="apache-2-0">Apache 2.0 License</option>
						</select>
					</div>
					<div class="menu">
						<input class="input mx-1 mb-1" id="tagsBar" size="15" v-model.lazy="tags" @change="resetOffset();" placeholder="Query">
						<div class="is-flex">
							<div class="select mx-1 mt-1">
								<select v-model="order" >
									<option selected="selected" value="latest">Latest</option>
									<option value="oldest">Oldest</option>
									<option value="random">Random</option>
								</select>
							</div>
							<button class="button mt-1 ml-1" @click="reset();">
								<span>
									<svg class="image is-16x16" viewBox="0 0 24 24">
										<path fill="currentColor" d="M2 12C2 16.97 6.03 21 11 21C13.39 21 15.68 20.06 17.4 18.4L15.9 16.9C14.63 18.25 12.86 19 11 19C4.76 19 1.64 11.46 6.05 7.05C10.46 2.64 18 5.77 18 12H15L19 16H19.1L23 12H20C20 7.03 15.97 3 11 3C6.03 3 2 7.03 2 12Z" />
									</svg>
								</span>
							</button>
						</div>
					</div>
				</div>
				<div class="navbar-end is-flex is-hidden-touch is-justify-content-flex-end is-align-content-center" v-if="currentlyHoveringAsset != null">
					<div v-if="currentlyHoveringAsset != null">
						<div class=" is-flex is-align-items-center is-justify-content-flex-end">
							<span class="is-size-5">{{currentlyHoveringAsset.assetName}}</span>
							<div class="image is-24x24 m-2">
								<img v-bind:alt="currentlyHoveringAsset.creator.CreatorName" v-bind:title="currentlyHoveringAsset.creator.CreatorName" loading="lazy" width="32" height="32" v-bind:src="'https://cdn3.struffelproductions.com/file/3D-Assets-One/creator-icon/64-PNG/' + currentlyHoveringAsset.creator.creatorId + '.png'">
							</div>
						</div>
						<div class="has-text-right is-size-7 mx-2 mb-2">
							{{currentlyHoveringAsset.license.licenseName}}
						</div>
						<div class="has-text-right">
							<span class=" is-white mx-1 tag" v-for="tag in currentlyHoveringAsset.tags.slice(0,4)">{{tag}}</span>
							<span class=" is-white mx-1 tag" v-if="currentlyHoveringAsset.tags.length > 5">...</span>
						</div>
					</div>
				</div>
			</nav>
			
			<div class="has-text-centered m-3">
				<strong>Showing {{totalNumberOfAssets}} assets.</strong>
			</div>

            <main class="assets is-flex is-justify-content-center is-flex-wrap-wrap">
				<div class="" v-if="totalNumberOfAssets < 1">No results for this query.</div>
            	<div v-for="asset in assetData.assets" :key="asset.assetId" >
                	<a style="z-index:99;" v-bind:href="'/go?id='+asset.assetId" class="scaleHover scaleHoverStrong box is-clipped is-bordered mx-1 my-1 " v-on:mouseover="setHoveringAssetData(asset.assetId)">
						<figure class="image is-128x128">
							<img style="opacity: 0%;" onload="this.style.opacity = '100%'" v-bind:alt="asset.assetName" width="256" height="256" loading="lazy" v-bind:src="'https://cdn3.struffelproductions.com/file/3D-Assets-One/thumbnail/256-JPG-FFFFFF/' + asset.assetId + '.jpg'">
						</figure>
					</a>
              	</div>
            </main>
			<div class="has-text-centered">
				<button class="mx-5 my-5 button is-large" v-if="this.assetData.assets.length < this.assetData.totalNumberOfAssets" v-on:click="nextPage()">Load more</button>
			</div>
			
        </div>
		<div style="min-height:150px;"></div>
        <script src="./src/browse.js"></script>
    </body>
</html>